<?php

namespace local_declarativesetup\local\lib;

use core\di;
use dml_exception;
use local_declarativesetup\local\db\moodle_config_repository;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\exceptions\setting_unable_to_extract_value;
use local_declarativesetup\local\moodle_core;
use moodle_exception;

class config_manager {
    public function __construct(
        protected readonly config_php_file_manager $config_php_file_manager,
    ) {}

    /**
     * @throws moodle_exception
     * @throws setting_is_forced
     */
    public function set_soft_setting(string $config_name, string $config_value, string|null $plugin = null): void {
        if ($this->is_setting_forced($config_name, $plugin)) {
            throw new setting_is_forced("Setting $config_name of plugin $plugin is forced");
        }
        di::get(moodle_core::class)->set_config($config_name, $config_value, $plugin);
    }

    /**
     * Sets a config value in the config.php file. If the setting is already set in the config.php file, it will be overwritten.
     * Does not change the setting in the database.
     *
     * @throws moodle_exception
     */
    public function set_forced_setting(string $config_name, string|int|bool $config_value, string|null $plugin = null): void {
        di::get(config_php_file_manager::class)->set_setting($config_name, $config_value, $plugin);
    }


    /**
     * @throws moodle_exception
     */
    public function delete_setting(string $config_name, string|null $plugin = null): void {
        if ($this->setting_exists($config_name, $plugin) && $this->is_setting_forced($config_name, $plugin)) {
            $this->delete_forced_setting($config_name, $plugin);
        }
        if ($this->setting_exists($config_name, $plugin)) {
            // if it still exists, even after deleting the forced setting, it's also in the database
            $this->delete_soft_setting($config_name, $plugin);
        }
    }

    /**
     * @throws moodle_exception
     * @throws setting_is_forced
     */
    public function delete_soft_setting(string $config_name, string|null $plugin = null): void {
        if ($this->is_setting_forced($config_name, $plugin)) {
            throw new setting_is_forced();
        }
        di::get(moodle_core::class)::unset_config($config_name, $plugin);
    }

    /**
     * Removes a setting from the config.php file. A potential setting in the database is not removed.
     *
     * @param string $config_name
     * @param string|null $plugin
     * @throws setting_does_not_exist in case the setting does not exist in the config.php file, meaning it is not forced
     * @throws moodle_exception
     */
    public function delete_forced_setting(string $config_name, string|null $plugin = null): void {
        di::get(config_php_file_manager::class)->remove_setting($config_name, $plugin);
    }

    /**
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws moodle_exception
     */
    public function is_setting_forced(string $config_name, string|null $plugin = null): bool {
        return di::get(config_php_file_manager::class)->setting_exists($config_name, $plugin);
    }

    /**
     * Returns the value of a config setting. It's mostly equivalent to {@link get_config()} but operates directly
     * on the config.php file and database. This way I can ensure that the value is not cached.
     * For forced settings, not all datatypes are supported.
     *
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws setting_does_not_exist
     * @throws moodle_exception
     */
    public function get_value(string $config_name, string|null $plugin = null): string|int|bool {
        if ($this->is_setting_forced($config_name, $plugin)) {
            return di::get(config_php_file_manager::class)->get_setting_value($config_name, $plugin);
        }
        try {
            return di::get(moodle_config_repository::class)->get_config($config_name, $plugin)->value;
        } catch (dml_exception) {
            throw new setting_does_not_exist();
        }
    }

    /**
     * Returns the value of a config setting as an array. Does not verify if a setting is of type array.
     * See {@link get_value()} for more information.
     *
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws setting_does_not_exist
     * @throws moodle_exception
     * @return string[]
     */
    public function get_array_values(string $config_name, string|null $plugin = null): array {
        return explode(',', $this->get_value($config_name, $plugin));
    }

    /**
     * See {@link get_value()} on how this works.
     *
     * @throws moodle_exception
     */
    public function setting_exists(string $key, string|null $plugin = null): bool {
        try {
            $this->get_value($key, $plugin);
            return true;
        } catch (setting_does_not_exist) {
            return false;
        }
    }
}