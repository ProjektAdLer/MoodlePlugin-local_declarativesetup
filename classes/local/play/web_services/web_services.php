<?php

namespace local_adlersetup\local\play\web_services;

use core\di;
use local_adlersetup\local\php;
use local_adlersetup\local\play\base_play;
use local_adlersetup\local\play\web_services\models\web_services_model;

global $CFG;
require_once($CFG->libdir . '/clilib.php');


/**
 * @property web_services_model $input
 */
class web_services extends base_play {
    private string $config_file_path;
    private string $file_content;

    /**
     * This play takes a {@link web_services_model} and ensures that the role exists with the specified capabilities and contexts.
     *
     * {@link get_output} returns a list of all roles as an array of {@link web_services_model} objects.
     *
     * @param web_services_model $input
     */
    public function __construct(web_services_model $input) {
        global $CFG;
        parent::__construct($input);
        $this->config_file_path = $CFG->dirroot . '/config.php';
    }

    private function update_setting(string $param, int $new_value): bool {
        // UNSET:
        //    - is set true or false -> remove
        // ENABLE:
        //    - is set false -> change to true
        //    - is not set -> add
        // DISABLE:
        //    - is set true -> change to false
        //    - is not set -> remove
        global $CFG;
        $state_changed = false;

        if ($new_value === web_services_model::STATE_UNSET) {
            if (in_array($param, array_keys($CFG->config_php_settings))) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                $this->file_content = $this->remove_setting_from_config_php($param, $this->file_content);
                $state_changed = true;
            }
        } else {
            // set to enable if entry does not exist or is not the same
            if (!in_array($param, array_keys($CFG->config_php_settings)) ||
                $CFG->config_php_settings[$param] !== (bool)$new_value
            ) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                // enable
                if ($new_value === web_services_model::STATE_ENABLED) {
                    if (in_array($param, array_keys($CFG->config_php_settings))) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                        $this->file_content = $this->remove_setting_from_config_php($param, $this->file_content);
                    }

                    $this->file_content .= "\n\$CFG->$param = true;  // Configured through local_adlersetup\n";
                    $state_changed = true;
                }
                // disable
                if ($new_value === web_services_model::STATE_DISABLED) {
                    if (in_array($param, array_keys($CFG->config_php_settings))) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                        $this->file_content = $this->remove_setting_from_config_php($param, $this->file_content);
                    }

                    $this->file_content .= "\n\$CFG->$param = false;  // Configured through local_adlersetup\n";
                    $state_changed = true;
                }
            }
        }
        return $state_changed;
    }

    /**
     * @return bool[]
     */
    private function update_protocols(): array {
        global $CFG;
        $state_changed = false;
        $config_php_changed = false;

        // compare and update if necessary
        if ($this->input->protocols_disable_list === ['*']) {
            // enforcing enable list: config.php mode

            // not in config.php -> add and state_changed
            // in config.php -> compare
            //  - if different -> change and state_changed

            if (in_array('webserviceprotocols', array_keys($CFG->config_php_settings))) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                $current_protocols = explode(',', $CFG->config_php_settings['webserviceprotocols']);
                $protocol_diffs = array_diff($current_protocols, $this->input->protocols_enable_list) ||
                    array_diff($this->input->protocols_enable_list, $current_protocols);
                if ($protocol_diffs) {
                    $this->file_content = $this->remove_setting_from_config_php('webserviceprotocols', $this->file_content);
                    $this->file_content .= "\n\$CFG->webserviceprotocols = '" . implode(',', $this->input->protocols_enable_list) . "';  // Configured through local_adlersetup\n";
                    $state_changed = true;
                    $config_php_changed = true;
                }
            } else {
                $this->file_content .= "\n\$CFG->webserviceprotocols = '" . implode(',', $this->input->protocols_enable_list) . "';  // Configured through local_adlersetup\n";
                $state_changed = true;
                $config_php_changed = true;
            }
        } else {
            // "soft mode". It is not possible to enforce enabling certain protocols without locking the other protocols
            // -> doing it in the database instead of config.php
            if (in_array('webserviceprotocols', array_keys($CFG->config_php_settings))) {  // doing `in_array('webserviceprotocols', $CFG->config_php_settings)` does not work for some unknown reason. It always returns true. With strict = true it always returns false
                // if there is a webserviceprotocols setting in config.php, remove it as switching to database mode
                $this->file_content = $this->remove_setting_from_config_php('webserviceprotocols', $this->file_content);
                $state_changed = true;
                $config_php_changed = true;
            }

            $current_protocols = explode(',', $CFG->webserviceprotocols);
            // enable disabled protocols
            foreach ($this->input->protocols_enable_list as $protocol) {
                if (!in_array($protocol, $current_protocols)) {
                    $current_protocols[] = $protocol;
                    $state_changed = true;
                }
            }
            // disable enabled protocols
            foreach ($this->input->protocols_disable_list as $protocol) {
                if (($key = array_search($protocol, $current_protocols)) !== false) {
                    unset($current_protocols[$key]);
                    $state_changed = true;
                }
            }

            if ($state_changed) {
                set_config('webserviceprotocols', implode(',', $current_protocols));
            }

        }

        return [$state_changed, $config_php_changed];
    }

    protected function play_implementation(): bool {
        $state_changed = false;
        $config_php_changed = false;
        $this->file_content = di::get(php::class)::file_get_contents($this->config_file_path);

        if ($this->update_setting('enablewebservices', $this->input->enable_webservices)) {
            $state_changed = true;
            $config_php_changed = true;
        }
        if ($this->update_setting(MOODLE_OFFICIAL_MOBILE_SERVICE, $this->input->enable_moodle_mobile_service)) {
            $state_changed = true;
            $config_php_changed = true;
        }
        list($protocols_state_changed, $protocols_config_php_changed) = $this->update_protocols();
        $state_changed = $protocols_state_changed || $state_changed;
        $config_php_changed = $protocols_config_php_changed || $config_php_changed;


        if ($config_php_changed) {
            di::get(php::class)::file_put_contents($this->config_file_path, $this->file_content);
        }

        return $state_changed;
    }

//    public function get_output_implementation(): array {
//
//    }


    private function remove_setting_from_config_php(string $key, string $file_content): string {
        // Regular expression to match the key-value pair
        $pattern = "/\\\$CFG->{$key}.*?;/ms";
        return preg_replace($pattern, '', $file_content);
    }
}