<?php

namespace local_declarativesetup\local\play\web_services;

use core\di;
use dml_exception;
use local_declarativesetup\local\db\moodle_external_services_repository;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\exceptions\setting_unable_to_extract_value;
use local_declarativesetup\local\lib\config_manager;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\web_services\models\web_services_model;
use moodle_exception;


/**
 * @property web_services_model $input
 */
class web_services extends base_play {
    private config_manager $config_manager;

    /**
     * This play takes a {@link web_services_model} and ensures that the role exists with the specified capabilities and contexts.
     *
     * {@link get_output} returns a list of all roles as an array of {@link web_services_model} objects.
     *
     * @param web_services_model $input
     */
    public function __construct(web_services_model $input) {
        parent::__construct($input);
        $this->config_manager = di::get(config_manager::class);
    }

    /**
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     * @throws setting_does_not_exist
     * @throws moodle_exception
     */
    private function update_setting(int $new_value): bool {
        $state_changed = false;

        if ($new_value === web_services_model::STATE_UNSET) {
            if ($this->config_manager->is_setting_forced('enablewebservices')) {
                $this->config_manager->delete_forced_setting('enablewebservices');
                $state_changed = true;
            }
        } else {
            // update if entry does not exist or is not the same
            if (!$this->config_manager->is_setting_forced('enablewebservices') ||
                $this->config_manager->get_value('enablewebservices') !== (bool)$new_value
            ) {
                $this->config_manager->set_forced_setting('enablewebservices', $new_value === web_services_model::STATE_ENABLED);
                $state_changed = true;
            }
        }
        return $state_changed;
    }

    /**
     * @throws setting_is_forced
     * @throws setting_exists_multiple_times
     * @throws dml_exception
     * @throws setting_unable_to_extract_value
     * @throws setting_does_not_exist
     * @throws moodle_exception
     */
    private function update_protocols(): bool {
        $state_changed = false;

        if ($this->input->protocols_disable_list === ['*']) {
            // Enforcing enable list: config.php mode
            $current_forced_protocols = $this->config_manager->is_setting_forced('webserviceprotocols')
                ? explode(',', $this->config_manager->get_value('webserviceprotocols'))
                : [];

            if (array_diff($current_forced_protocols, $this->input->protocols_enable_list) || array_diff($this->input->protocols_enable_list, $current_forced_protocols)) {
                $this->config_manager->set_forced_setting('webserviceprotocols', implode(',', $this->input->protocols_enable_list));
                $state_changed = true;
            }
        } else {
            // "soft mode"
            if ($this->config_manager->is_setting_forced('webserviceprotocols')) {
                $this->config_manager->delete_forced_setting('webserviceprotocols');
                $state_changed = true;
            }

            $current_protocols = explode(',', get_config('', 'webserviceprotocols'));
            // enable disabled protocols
            $new_protocols = array_unique(array_merge(
                array_diff($current_protocols, $this->input->protocols_disable_list),
                $this->input->protocols_enable_list
            ));
            // disable enabled protocols
            if ($new_protocols !== $current_protocols) {
                $this->config_manager->set_soft_setting('webserviceprotocols', implode(',', $new_protocols));
                $state_changed = true;
            }
        }

        return $state_changed;
    }

    /**
     * @throws dml_exception
     */
    private function update_mobile_service(): bool {
        if ($this->input->enable_moodle_mobile_service !== web_services_model::STATE_UNSET) {
            $external_service_record = di::get(moodle_external_services_repository::class)->get_external_service_by_shortname(MOODLE_OFFICIAL_MOBILE_SERVICE);
            if ((int)$external_service_record->enabled === 1 && $this->input->enable_moodle_mobile_service === web_services_model::STATE_DISABLED) {
                $external_service_record->enabled = 0;
                di::get(moodle_external_services_repository::class)->update_external_service($external_service_record->id, $external_service_record);
                cli_writeln("MOODLE_OFFICIAL_MOBILE_SERVICE were enabled and are now disabled");
                return true;
            } elseif ((int)$external_service_record->enabled === 0 && $this->input->enable_moodle_mobile_service === web_services_model::STATE_ENABLED) {
                $external_service_record->enabled = 1;
                di::get(moodle_external_services_repository::class)->update_external_service($external_service_record->id, $external_service_record);
                cli_writeln("MOODLE_OFFICIAL_MOBILE_SERVICE were disabled and are now enabled");
                return true;
            }
        }
        return false;
    }

    /**
     * @throws dml_exception
     * @throws moodle_exception
     * @throws setting_does_not_exist
     * @throws setting_exists_multiple_times
     * @throws setting_unable_to_extract_value
     */
    protected function play_implementation(): bool {
        return
            $this->update_setting($this->input->enable_webservices) |
            $this->update_mobile_service() |  // TODO: test this change
            $this->update_protocols();
    }

//    public function get_output_implementation(): array {
//
//    }
}