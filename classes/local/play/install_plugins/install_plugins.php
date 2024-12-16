<?php

namespace local_declarativesetup\local\play\install_plugins;

use coding_exception;
use core\plugin_manager;
use core\session\manager;
use core\update\api;
use core_component;
use core_plugin_manager;
use ddl_exception;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\install_plugins\exceptions\downgrade_exception;
use local_declarativesetup\local\play\install_plugins\models\install_plugins_model;
use moodle_exception;
use stdClass;

global $CFG;
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');       // required for admin_apply_default_settings
require_once($CFG->libdir . '/environmentlib.php');   // required for check_moodle_environment

/**
 * @property install_plugins_model[] $input
 */
class install_plugins extends base_play {
    /**
     * This play takes a list of {@link install_plugins_model} and ensure that these plugins in the specified version
     * are installed.
     * Supported sources:
     * - package registry: A web endpoint providing a public accessible endpoint with a list of folders matching the
     *   moodle plugin name (e.g. local_test) and below these folders a list of files <version number>.zip and
     *   <version number>.zip.md5. The full url for a package looks like that:
     *   <url of repo>/<moodle plugin name>/<version>.<zip|zip.md5>
     *   (e.g. https://packages.projekt-adler.eu/packages/moodle/local_declarativesetup/0.1.0.zip)
     *
     * {@link get_output} returns a list of all installed plugins: [<plugin_name> => ['release' => <version>, 'version' => <version>]]
     *
     * @param install_plugins_model[] $input
     */
    public function __construct(array $input) {
        parent::__construct($input);
    }

    /**
     * @throws downgrade_exception
     * @throws moodle_exception
     * @throws coding_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;
        $plugins_requiring_update = [];
        foreach ($this->input as $plugin) {
            if ($this->is_plugin_update_required($plugin)) {
                $plugins_requiring_update[] = $plugin;
                $state_changed = true;
            }
        }
        $this->update_plugins($plugins_requiring_update);
        return $state_changed;
    }

    protected function get_output_implementation(): array {
        $plugins_list = [];
        foreach (plugin_manager::instance()->get_plugins() as $plugin_type => $plugins_by_type) {
            foreach ($plugins_by_type as $plugin) {
                $plugins_list[$plugin_type . '_' . $plugin->name] = [
                    'release' => $plugin->release,
                    'version' => $plugin->versiondb,
                ];
            }
        }
        return $plugins_list;
    }

    /**
     * @param install_plugins_model[] $plugins
     * @throws coding_exception
     * @throws moodle_exception
     * @throws ddl_exception
     */
    private function update_plugins(array $plugins): void {
        $plugin_infos = [];
        foreach ($plugins as $plugin) {
            cli_writeln("Plugin \"{$plugin->moodle_name}\", version \"{$plugin->version}\"");
            $download_url = "{$plugin->package_repo}/{$plugin->moodle_name}/{$plugin->version}.zip";
            $md5_file_content = file_get_contents("{$plugin->package_repo}/{$plugin->moodle_name}/{$plugin->version}.zip.md5");
            if ($md5_file_content === false) {
                throw new moodle_exception('Failed to get md5 hash');
            }
            $md5_hash = explode(' ', $md5_file_content)[0];

            cli_writeln("[DEBUG]: md5 hash: $md5_hash");
            if (!preg_match('/^[a-fA-F0-9]{32}$/', $md5_hash)) {
                throw new moodle_exception('Invalid MD5 hash');
            }

            $raw_plugin_info_version = new stdClass();
            $raw_plugin_info_version->downloadurl = $download_url;
            $raw_plugin_info_version->downloadmd5 = strtolower($md5_hash);
            $raw_plugin_info_version->id = 42;  // required, but not relevant here, very likely the id of the version in the moodle plugin repository
            $raw_plugin_info_version->version = 42;  // required, but not relevant here, long version, e.g. 2016052300

            $raw_plugin_info_root = new stdClass();
            $raw_plugin_info_root->version = $raw_plugin_info_version;
            $raw_plugin_info_root->id = 42;  // required, but not relevant here, very likely plugin id in moodle plugin repository
            $raw_plugin_info_root->name = $plugin->moodle_name;  // human readable name - i dont care about this here
            $raw_plugin_info_root->component = $plugin->moodle_name;

            // adding all the "not required" fields ... moodle things ...
            $raw_plugin_info_root->source = null;
            $raw_plugin_info_root->doc = null;
            $raw_plugin_info_root->bugs = null;
            $raw_plugin_info_root->discussion = null;
            $raw_plugin_info_version->release = null;
            $raw_plugin_info_version->maturity = null;
            $raw_plugin_info_version->vcssystem = null;
            $raw_plugin_info_version->vcssystemother = null;
            $raw_plugin_info_version->vcsrepositoryurl = null;
            $raw_plugin_info_version->vcsbranch = null;
            $raw_plugin_info_version->vcstag = null;
            $raw_plugin_info_version->supportedmoodles = null;

            $new_plugin_info = api::client()->validate_pluginfo_format($raw_plugin_info_root);
            if ($new_plugin_info === false) {
                throw new moodle_exception('failed to validate plugin info');
            }
            $plugin_infos[] = $new_plugin_info;
        }

        if (count($plugin_infos) > 0) {
            $plugin_manager = plugin_manager::instance();
            $success = $plugin_manager->install_plugins($plugin_infos, true, false);
            if (!$success) {
                throw new moodle_exception('failed to install plugin');
            }
            $this->moodle_plugin_upgrade();
        } else {
            cli_writeln('[INFO] No plugins to update');
        }
    }

    /**
     * @throws downgrade_exception If a plugin downgrade is attempted
     * @throws moodle_exception
     */
    private function is_plugin_update_required(install_plugins_model $desired_plugin): bool {
        $plugin_manager = plugin_manager::instance();
        $installed_plugin_info = $plugin_manager->get_plugin_info($desired_plugin->moodle_name);
        if ($installed_plugin_info === null) {
            // plugin is not installed
            return true;
        }
        if (version_compare($desired_plugin->version, $installed_plugin_info->release, '<')) {
            throw new downgrade_exception('plugin downgrade is not allowed');
        }
        return version_compare($desired_plugin->version, $installed_plugin_info->release, '>');
    }


    /**
     * Update plugins in the moodle installation
     *
     * @throws moodle_exception
     */
    private function moodle_plugin_upgrade(): void {
        global $CFG;

        // it was trial and error that showed it is required to delete these two caches. Otherwise, plugins will be
        // stuck in "To be installed" state. This issues seems to only occur on production moodle installations.
        // I could not reproduce it in a test environment.
        purge_other_caches();
        // have to reset cached plugin list, otherwise the new plugin is not recognized
        core_component::reset();
//        plugin_manager::instance()->get_plugin_info('local_testplugin')->get_status();

        // this checks for moodle dependencies and so on. I don't think it is necessary for the plugin installation,
        // but I am not sure, and it does not hurt to call it.
        list($envstatus, $environment_results) = check_moodle_environment(normalize_version($CFG->release), ENV_SELECT_RELEASE);
        if (!$envstatus) {
            $errors = environment_get_errors($environment_results);
            foreach ($errors as $error) {
                list($info, $report) = $error;
                cli_writeln("!! $info !!\n$report\n\n");
            }
            throw new moodle_exception('environment check failed', debuginfo: "$info\n$report");
        }
        $failed = array();
        if (!core_plugin_manager::instance()->all_plugins_ok($CFG->version, $failed, $CFG->branch)) {
            throw new moodle_exception('plugin check failed');
        }

        upgrade_noncore(true);
        // unsure if this is required, some code snippets set admin user before
        // calling admin_apply_default_settings, but this could also happen for other reasons.
        // There is currently no test covering this
        manager::set_user(get_admin());
        admin_apply_default_settings(null, false);
    }
}