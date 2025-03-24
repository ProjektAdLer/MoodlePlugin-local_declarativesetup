<?php

namespace local_declarativesetup\local\play\config;

use core\di;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\exceptions\setting_unable_to_extract_value;
use local_declarativesetup\local\lib\config_manager;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\config\models\array_config_model;
use local_declarativesetup\local\play\config\models\config_model;
use local_declarativesetup\local\play\config\models\simple_config_model;
use moodle_exception;

/**
 * @property config_model[]|config_model $config
 */
class config extends base_play {
    private config_manager $config_manager;

    /**
     * This play takes a list of {@link config_model} and ensures that the specified languages are in the desired state.
     *
     * Some changes are applied instantly, others only in the next moodle execution.
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
            if ($config instanceof simple_config_model) {
                $state_changed =  $this->update_simple_setting($config) || $state_changed;
            }
            if ($config instanceof array_config_model) {
                $state_changed = $this->update_array_setting($config) || $state_changed;
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
    private function update_simple_setting(simple_config_model $config): bool {
        if ($config->config_value === null && $this->config_manager->setting_exists($config->config_name, $config->plugin)) {
            // desired state: unset, now: set
            $this->config_manager->delete_setting($config->config_name);
            return true;
        }
        if ($config->forced !== $this->config_manager->is_setting_forced($config->config_name, $config->plugin)) {
            // desired forced state does not match current forced state
            $this->update_setting_wrong_forced_state($config->config_name, $config->config_value, $config->forced, $config->plugin);
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
     * @throws moodle_exception
     */
    private function update_array_setting(array_config_model $config): bool {
        $current_values = $this->config_manager->setting_exists($config->config_name, $config->plugin)
            ? $this->config_manager->get_array_values($config->config_name, $config->plugin)
            : [];
        $desired_state = $this->generate_desired_values($current_values, $config->values_present, $config->values_absent);
        $desired_values = $desired_state['values'];
        $values_changed = $desired_state['changed'];
        $formatted_value = implode(',', $desired_values);

        if ($config->forced !== $this->config_manager->is_setting_forced($config->config_name, $config->plugin)) {
            // wrong forced state: update forced state and value
            $this->update_setting_wrong_forced_state($config->config_name, $formatted_value, $config->forced, $config->plugin);
            return true;
        }
        if ($values_changed ||
            !$this->config_manager->setting_exists($config->config_name, $config->plugin)
        ) {
            // values changed or setting does not yet exist (empty)
            if ($config->forced) {
                $this->config_manager->set_forced_setting($config->config_name, $formatted_value, $config->plugin);
            } else {
                $this->config_manager->set_soft_setting($config->config_name, $formatted_value, $config->plugin);
            }
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
    private function update_setting_wrong_forced_state(string $config_name, string|int|bool $config_value, bool $forced, string|null $plugin = null): void {
        if ($forced && !$this->config_manager->is_setting_forced($config_name, $plugin)) {
            // desired: forced, now: soft
            $this->config_manager->delete_soft_setting($config_name, $plugin);
            $this->config_manager->set_forced_setting($config_name, $config_value, $plugin);
        } else if (!$forced && $this->config_manager->is_setting_forced($config_name, $plugin)) {
            // desired: soft, now: forced
            $this->config_manager->delete_forced_setting($config_name, $plugin);
            $this->config_manager->set_soft_setting($config_name, $config_value, $plugin);
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

    /**
     * @param array $current_values
     * @param array $values_present
     * @param array $values_absent
     * @return array{values: string[], changed: bool}
     */
    private function generate_desired_values(array $current_values, array $values_present, array $values_absent): array {
        $desired_values = $current_values;
        $changed = false;

        foreach ($values_present as $value) {
            if (!in_array($value, $desired_values)) {
                $desired_values[] = $value;
                $changed = true;
            }
        }

        foreach ($values_absent as $value) {
            if ($value === '*') {
                if (!empty($desired_values)) {
                    $desired_values = array_intersect($desired_values, $values_present);
                    $changed = true;
                }
            } else {
                $count_before = count($desired_values);
                $desired_values = array_diff($desired_values, [$value]);
                if ($count_before !== count($desired_values)) {
                    $changed = true;
                }
            }
        }

        return [
            'values' => $desired_values,
            'changed' => $changed
        ];
    }
}