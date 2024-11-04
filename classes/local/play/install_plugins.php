<?php

namespace local_adlersetup\classes\local\play;

use coding_exception;
use core\di;
use core\plugin_manager;
use core\session\manager;
use core\update\api;
use core\update\remote_info;
use core_component;
use core_plugin_manager;
use local_adlersetup\classes\local\play\exceptions\downgrade_exception;
use local_adlersetup\classes\local\play\models\InstallPluginsModel;
use moodle_exception;
use stdClass;

class InstallPlugins extends BasePlay {
    /**
     * InstallPlugins constructor.
     * @param InstallPluginsModel[] $input
     */
    public function __construct(array $input) {
        parent::__construct($input);
    }

    /**
     * @throws moodle_exception
     * @throws downgrade_exception
     */
    public function play(): bool {
        $state_changed = false;
        $plugins_requiring_update = [];
        foreach ($this->input as $plugin) {
            if ($this->is_update_required($plugin)) {
                $plugins_requiring_update[] = $plugin;
                $state_changed = true;
            }
        }
        $this->update_plugins($plugins_requiring_update);
        return $state_changed;
    }

    /**
     * @param InstallPluginsModel[] $plugins
     * @throws coding_exception
     */
    private function update_plugins(array $plugins) {
        $plugin_infos = [];
        /** @var InstallPluginsModel $plugin */
        foreach ($this->input as $plugin) {
            $raw_plugin_info_version = new stdClass();
            $raw_plugin_info_version->downloadurl = $this->get_plugin_download_url($plugin);
            $raw_plugin_info_version->downloadmd5 = $this->get_plugin_md5_hash($plugin);
            $raw_plugin_info_version->id = 42;  // required, but not relevant here, very likely the id of the version in the moodle plugin repository
            $raw_plugin_info_version->version = 42;  // required, but not relevant here, long version, e.g. 2016052300

            $raw_plugin_info_root = new stdClass();
            $raw_plugin_info_root->version = $raw_plugin_info_version;
            $raw_plugin_info_root->id = 42;  // required, but not relevant here, very likely plugin id in moodle plugin repository
            $raw_plugin_info_root->name = $plugin->moodle_name;  // human readable name - i dont care about this here
            $raw_plugin_info_root->component = $plugin->moodle_name;

            $plugin_infos[] = di::get(api::class)->validate_pluginfo_format($raw_plugin_info_root);
        }

        $plugin_manager = di::get(plugin_manager::class);
        $plugin_manager->install_plugins($plugin_infos, true, false);

        $this->moodle_plugin_upgrade();
    }

    /**
     * @throws downgrade_exception If a plugin downgrade is attempted
     * @throws moodle_exception
     */
    private function is_update_required(InstallPluginsModel $plugin): bool {
        $plugin_manager = di::get(plugin_manager::class);
        $plugin_info = $plugin_manager->get_plugin_info($plugin->moodle_name);
        if ($plugin_info === null) {
            // plugin is not installed
            return true;
        }
        if ($this->is_release_version($plugin)) {
            return $this->is_update_required_release_version($plugin, $plugin_info);
        } else {
            return $this->is_update_required_branch_version($plugin, $plugin_info);
        }
    }

    /**
     * @throws downgrade_exception
     */
    private function is_update_required_release_version(InstallPluginsModel $desired_plugin, array $installed_plugin): bool {
        if (version_compare($desired_plugin->version, $installed_plugin['version'], '<')) {
            throw new downgrade_exception('plugin downgrade is not allowed');
        }
        return version_compare($desired_plugin->version, $installed_plugin['version'], '>');
    }

    /**
     * @throws downgrade_exception
     * @throws moodle_exception
     */
    private function is_update_required_branch_version(InstallPluginsModel $desired_plugin, array $installed_plugin): bool {
        $desired_version_long = $this->get_versions_from_github($desired_plugin)['version'];
        if ((int)$desired_version_long < (int)$installed_plugin['version']) {
            throw new downgrade_exception('plugin downgrade is not allowed');
        }
        return true;
    }

    private function is_release_version(InstallPluginsModel $plugin): bool {
        return preg_match('/^[0-9]+(\.[0-9]+){0,2}(-rc(\.[0-9]+)?)?$/', $plugin->version);
    }


    /**
     * Get the url to the version.php file of the plugin on GitHub
     *
     * @param InstallPluginsModel $plugin
     * @return object
     * @throws moodle_exception
     */
    private function get_versions_from_github(InstallPluginsModel $plugin): object {
        if ($this->is_release_version($plugin)) {
            // plugin is a release
            $version_php_url = "https://raw.githubusercontent.com/" . $plugin->github_project . "/refs/tags/" . $plugin['version'] . "/version.php";
        } else {
            // plugin is a branch
            $version_php_url = "https://raw.githubusercontent.com/" . $plugin->github_project . "/refs/heads/" . $plugin['version'] . "/version.php";
        }

        $version_php_file_content = file_get_contents($version_php_url);

        // parse plugin->release and $plugin-version from version.php
        if (preg_match('/\$plugin->release\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $version_php_file_content, $matches)) {
            $release = $matches[1];
        } else {
            throw new moodle_exception('release not found in version.php', 'local_adlersetup');
        }

        if (preg_match('/\$plugin->version\s*=\s*([0-9]+)\s*;/', $version_php_file_content, $matches)) {
            $version = $matches[1];
        } else {
            throw new moodle_exception('version not found in version.php', 'local_adlersetup');
        }

        return (object)[
            'release' => $release,
            'version' => $version
        ];
    }

    protected function play_implementation(): bool {
        // TODO: Implement play_implementation() method.
    }

    /**
     * Downloads the md5 hash file and returns the file content
     *
     * @param InstallPluginsModel $plugin
     * @return string
     */
    private function get_plugin_md5_hash(InstallPluginsModel $plugin): string {
        download_file_content($this->get_plugin_md5_hash_url($plugin));
    }

    /**
     * Generates the download url for the plugin
     *
     * @param InstallPluginsModel $plugin
     * @return string
     */
    private function get_plugin_download_url(mixed $plugin): string {}

    /**
     * Update plugins in the moodle installation
     *
     * @return bool true if the moodle installation was updated, false otherwise
     * @throws coding_exception
     */
    private function moodle_plugin_upgrade(): bool {
        global $CFG;

        if (!moodle_needs_upgrading(false)) {
            cli_writeln("Moodle does not need upgrading");
            return false;
        }

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

        return true;
    }
}