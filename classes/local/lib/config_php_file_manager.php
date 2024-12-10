<?php

namespace local_declarativesetup\local\lib;

use core\di;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\exceptions\setting_unable_to_extract_value;
use local_declarativesetup\local\php;
use moodle_exception;
use TypeError;

class config_php_file_manager {
    /**
     * @throws setting_does_not_exist
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws moodle_exception
     */
    public function remove_setting(string $config_name, string|null $plugin = null): void {
        // This method does all the necessary checks to ensure the setting to be removed is supported
        $this->get_setting_value($config_name, $plugin);

        $pattern = $this->get_setting_regex_pattern($config_name, $plugin);
        $file_content = $this->read_config_php();
        $file_content = preg_replace($pattern, '', $file_content);
        $this->save($file_content);
    }

    /**
     * Adds a setting to the config.php file or updates the value of an existing setting.
     *
     * @throws moodle_exception
     */
    public function set_setting(string $config_name, string|int|bool $config_value, string|null $plugin = null): void {
        $file_content = $this->read_config_php();
        if ($this->setting_exists($config_name, $plugin)) {
            // update in place
            // This method does all the necessary checks to ensure the setting to be removed is supported
            $this->get_setting_value($config_name, $plugin);

            $pattern = $this->get_setting_regex_pattern($config_name, $plugin);
            $new_line = $this->generate_setting_line($config_name, $config_value, $plugin);
            $file_content = preg_replace($pattern, $new_line, $file_content);
        } else {
            // add new setting
            // setting has to be added before the `require_once(__DIR__ . '/lib/setup.php');` line
            $pattern = '/^.*require.*setup\.php.*$/m';
            $new_line = $this->generate_setting_line($config_name, $config_value, $plugin);
            $file_content = preg_replace(
                $pattern,
                $new_line . "\n" . '$0',  // $0 is the matched string -> adds the matched string after the new line
                $file_content);
        }
        $this->save($file_content);
    }

    /**
     * Checks the config.php for the existence of a setting.
     * It's not done against the current $CFG object as this might not match the current file content.
     *
     * @param string $key
     * @param string|null $plugin plugin name or null for moodle settings
     * @return bool
     * @throws moodle_exception
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     */
    public function setting_exists(string $key, string|null $plugin = null): bool {
        try {
            $this->get_setting_value($key, $plugin);
            return true;
        } catch (setting_does_not_exist $e) {
            return false;
        }
    }

    /**
     * Reads the config.php file and extracts the value of a setting.
     * Only supports primitive data types (int, string, bool).
     * Does not use the $CFG object as I am unsure if I can trust it to be up to date.
     *
     * @throws setting_does_not_exist
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws moodle_exception
     */
    public function get_setting_value(string $key, string|null $plugin = null): string|int|bool {
        $file_content = $this->read_config_php();
        $pattern = $this->get_setting_regex_pattern($key, $plugin);

        preg_match_all($pattern, $file_content, $matches);
        $matches = $matches[0];
        if (count($matches) === 0) {
            throw new setting_does_not_exist();
        }
        if (count($matches) > 1) {
            throw new setting_exists_multiple_times();
        }

        // Extract the value from the matched string
        $value_pattern = '/=\s*(.*?);/';
        preg_match($value_pattern, $matches[0], $value_matches);
        if (count($value_matches) === 0) {
            throw new setting_unable_to_extract_value();
        }

        $value = trim($value_matches[1]);

        // Determine the type of the value
        if (is_numeric($value)) {
            return (int)$value;
        } elseif (strtolower($value) === 'true' || strtolower($value) === 'false') {
            return strtolower($value) === 'true';
        } elseif (preg_match('/^["\'].*["\']$/', $value)) {
            return trim($value, '"\'');
        } else {
            throw new TypeError('Unsupported value type in config.php', 'local_declarativesetup');
        }
    }

    /**
     * @throws moodle_exception
     */
    private function read_config_php(): string {
        global $CFG;
        $file_content = di::get(php::class)::file_get_contents($CFG->dirroot . '/config.php');
        if ($file_content === false) {
            throw new moodle_exception('Failed to read config.php', 'local_declarativesetup');
        }
        return $file_content;
    }

    private function save(string $file_content): void {
        global $CFG;
        di::get(php::class)::file_put_contents($CFG->dirroot . '/config.php', $file_content);
    }

    private function get_setting_regex_pattern(string $key, string|null $plugin): string {
        if ($plugin === null) {
            return '/^.*\$CFG->' . $key . '.*$/m';
        } else {
            return '/^.*\$CFG->forced_plugin_settings\[[\'"]' . $plugin . '[\'"]]\[[\'"]' . $key . '[\'"]].*$/m';
        }
    }


    private function generate_setting_line(string $config_name, string|int|bool $config_value, string|null $plugin): string {
        if (is_string($config_value)) {
            $config_value = "'$config_value'";
        }
        elseif (is_bool($config_value)) {
            $config_value = $config_value ? 'true' : 'false';
        }

        if ($plugin === null) {
            return "\$CFG->$config_name = $config_value;  // Configured through local_declarativesetup";
        } else {
            return "\$CFG->forced_plugin_settings['$plugin']['$config_name'] = $config_value;  // Configured through local_declarativesetup";
        }
    }


}