<?php

namespace local_adlersetup\local\play;

use coding_exception;
use core\plugin_manager;
use core\session\manager;
use core\update\api;
use core_component;
use core_plugin_manager;
use ddl_exception;
use local_adlersetup\local\play\exceptions\downgrade_exception;
use local_adlersetup\local\play\models\install_plugins_model;
use moodle_exception;
use stdClass;

global $CFG;
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/adminlib.php');       // required for admin_apply_default_settings


/**
 * @property install_plugins_model[] $input
 */
class install_plugins extends base_play {
    /** For testing purposes, allows to redirect the api calls to a different url.
     * @var string The base url for the GitHub api
     */
    public string $github_api_url = "https://api.github.com";

    /**
     * This play takes a list of {@link install_plugins_model} and ensure that these plugins in the specified version are installed.
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
            $github_release_info = $this->get_github_release_info($plugin);

            $raw_plugin_info_version = new stdClass();
            $raw_plugin_info_version->downloadurl = $this->get_plugin_zip_download_url($github_release_info, $plugin);
            $raw_plugin_info_version->downloadmd5 = $this->get_plugin_zip_md5_hash($github_release_info, $plugin);
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

            $plugin_infos[] = api::client()->validate_pluginfo_format($raw_plugin_info_root);
        }

        $plugin_manager = plugin_manager::instance();
        $plugin_manager->install_plugins($plugin_infos, true, false);

        $this->moodle_plugin_upgrade();
    }

    /**
     * @throws ddl_exception
     */
    private function get_github_release_info(install_plugins_model $plugin): stdClass {
        $request_url = "{$this->github_api_url}/repos/{$plugin->github_project}/releases/tags/{$plugin->version}";
        $response = file_get_contents($request_url);

        $response = json_decode($response);
        if ($response === null || !property_exists($response, 'assets')) {
            throw new ddl_exception('failed to get release info');
        }

        return $response;
    }

    /**
     * Downloads the md5 hash file for the zip and returns the file content
     *
     * @throws moodle_exception
     */
    private function get_plugin_zip_md5_hash(stdClass $github_release_info, install_plugins_model $plugin): string {
        $asset_name = "moodle-{$plugin->moodle_name}-{$plugin->version}.zip.md5";
        foreach ($github_release_info->assets as $asset) {
            if ($asset->name === $asset_name) {
                $md5sum_file = file_get_contents($asset->url);
                return explode(' ', $md5sum_file)[0];
            }
        }
        throw new moodle_exception('md5 hash file not found');
    }

    /**
     * @throws moodle_exception
     */
    private function get_plugin_zip_download_url(stdClass $github_release_info, install_plugins_model $plugin): string {
        $asset_name = "moodle-{$plugin->moodle_name}-{$plugin->version}.zip";
        foreach ($github_release_info->assets as $asset) {
            if ($asset->name === $asset_name) {
                return $asset->url;
            }
        }
        throw new moodle_exception('zip file not found');
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
     * @throws coding_exception
     */
    private function moodle_plugin_upgrade(): void {
        global $CFG;

        // have to reset cached plugin list, otherwise the new plugin is not recognized
        core_component::reset();

        // this checks for moodle dependencies and so on. I don't think it is necessary for the plugin installation,
        // but I am not sure, and it does not hurt to call it.
        list($envstatus, $environment_results) = check_moodle_environment(normalize_version($CFG->version), ENV_SELECT_RELEASE);
        if (!$envstatus) {
            $errors = environment_get_errors($environment_results);
            foreach ($errors as $error) {
                list($info, $report) = $error;
                cli_writeln("!! $info !!\n$report\n\n");
            }
            throw new coding_exception('environment check failed');
        }

        $failed = array();
        if (!core_plugin_manager::instance()->all_plugins_ok($CFG->version, $failed, $CFG->branch)) {
            throw new coding_exception('plugin check failed');
        }

        upgrade_noncore(true);
        manager::set_user(get_admin());
        admin_apply_default_settings(null, false);
    }
}