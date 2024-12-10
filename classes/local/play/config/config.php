<?php

namespace local_declarativesetup\local\play\config;

use core\di;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\exceptions\setting_unable_to_extract_value;
use local_declarativesetup\local\lib\config_manager;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\config\models\config_model;
use moodle_exception;

/**
 * @property config_model[]|config_model $config
 */
class config extends base_play {
    private config_manager $config_manager;

    /**
     * This play takes a list of {@link config_model} and ensures that the specified languages are in the desired state.
     *
     * {@link get_output} returns a list of all active language keys as string[].
     *
     * @param config_model[]|config_model $config
     */
    public function __construct(config_model|array $config) {
        if (!is_array($config)) {
            $config = [$config];
        }
        parent::__construct($config);
        $this->config_manager = di::get(config_manager::class);
    }


    /**
     * @throws setting_exists_multiple_times
     * @throws setting_is_forced
     * @throws setting_unable_to_extract_value
     * @throws moodle_exception
     * @throws setting_does_not_exist
     */
    protected function play_implementation(): bool {
        $state_changed = false;
        foreach ($this->input as $config) {
            if ($this->update_setting($config)) {
                $state_changed = true;
            }
        }
        return $state_changed;
    }

    /**
     * @throws setting_unable_to_extract_value
     * @throws setting_exists_multiple_times
     * @throws setting_is_forced
     * @throws moodle_exception
     * @throws setting_does_not_exist
     */
    private function update_setting(config_model $config): bool {
        if ($config->config_value === null && $this->config_manager->setting_exists($config->config_name, $config->plugin)) {
            // desired state: unset, now: set
            $this->config_manager->delete_setting($config->config_name);
            return true;
        }
        if ($config->forced !== $this->config_manager->is_setting_forced($config->config_name, $config->plugin)) {
            // desired forced state does not match current forced state
            $this->update_setting_wrong_forced_state($config);
            return true;
        }
        if (!$this->config_manager->setting_exists($config->config_name, $config->plugin)
            || $config->config_value !== $this->config_manager->get_value($config->config_name, $config->plugin)) {
            // desired value does not match current value or setting does not exist
            $this->update_setting_wrong_value($config);
            return true;
        }

        return false;
    }

    /**
     * @throws setting_unable_to_extract_value
     * @throws setting_exists_multiple_times
     * @throws setting_is_forced
     * @throws moodle_exception
     * @throws setting_does_not_exist
     */
    private function update_setting_wrong_forced_state(config_model $config): void {
        if ($config->forced && !$this->config_manager->is_setting_forced($config->config_name, $config->plugin)) {
            // desired: forced, now: soft
            $this->config_manager->set_forced_setting($config->config_name, $config->config_value, $config->plugin);
        } else if (!$config->forced && $this->config_manager->is_setting_forced($config->config_name, $config->plugin)) {
            // desired: soft, now: forced
            $this->config_manager->delete_forced_setting($config->config_name, $config->plugin);
            $this->config_manager->set_soft_setting($config->config_name, $config->config_value, $config->plugin);
        }
    }

    /**
     * @throws setting_is_forced
     * @throws moodle_exception
     */
    private function update_setting_wrong_value(config_model $config): void {
        if ($config->forced) {
            $this->config_manager->set_forced_setting($config->config_name, $config->config_value, $config->plugin);
        } else {
            $this->config_manager->set_soft_setting($config->config_name, $config->config_value, $config->plugin);
        }
    }
}