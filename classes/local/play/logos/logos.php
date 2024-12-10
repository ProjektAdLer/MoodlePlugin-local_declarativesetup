<?php

namespace local_declarativesetup\local\play\logos;

use context_system;
use core\di;
use dml_exception;
use file_exception;
use invalid_parameter_exception;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\lib\config_manager;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\logos\models\logo_model;
use moodle_exception;
use stored_file;
use stored_file_creation_exception;

/**
 * @property logo_model $input
 */
class logos extends base_play {
    private config_manager $config_manager;

    /**
     * This play takes a {@link logo_model} and ensures that the defined logos are set.
     *
     * @param logo_model $input
     */
    public function __construct(logo_model $input) {
        parent::__construct($input);
        $this->config_manager = di::get(config_manager::class);
    }

    /**
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     * @throws invalid_parameter_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;

        if ($this->input->logo_path !== null) {
            $state_changed = $this->update_logo_if_changed('logo', $this->input->logo_path);
        }
        if ($this->input->logocompact_path !== null) {
            $state_changed = $this->update_logo_if_changed('logocompact', $this->input->logocompact_path) || $state_changed;
        }
        if ($this->input->favicon_path !== null) {
            $state_changed = $this->update_logo_if_changed('favicon', $this->input->favicon_path) || $state_changed;
        }

        return $state_changed;
    }

    /**
     * @throws setting_is_forced
     * @throws moodle_exception
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    private function update_logo(string $filearea, string $path): void {
        $context = context_system::instance();
        $itemid = 0;

        // Delete existing file if it exists.
        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'core_admin', $filearea, $itemid);

        if ($path === '') {
            $this->config_manager->set_soft_setting($filearea, '', 'core_admin');
        } else {
            $file_record = [
                'contextid' => $context->id,
                'component' => 'core_admin',
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => basename($path),
            ];


            // Store the new file.
            $fs->create_file_from_pathname($file_record, $path);

            // Update the plugin configuration.
            $this->config_manager->set_soft_setting($filearea, '/' . basename($path), 'core_admin');
        }

        theme_reset_all_caches();
    }

    /**
     * @throws dml_exception
     * @throws file_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws setting_is_forced
     * @throws stored_file_creation_exception
     */
    private function update_logo_if_changed(string $filearea, string $path): bool {
        if ($this->did_logo_change($filearea, $path)) {
            $this->update_logo($filearea, $path);
            return true;
        }
        return false;
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    private function did_logo_change(string $filearea, string $logo_path): bool {
        $hash_current_logo = $this->get_current_logo_file_hash($filearea);
        if ($logo_path === '') {
            $hash_new_logo = '';
        } else {
            $hash_new_logo = $this->get_new_logo_file_hash($logo_path);
        }
        return $hash_current_logo !== $hash_new_logo;
    }

    /**
     * @throws dml_exception
     */
    private function get_current_logo_file(string $filearea): stored_file|false {
        $config = get_config('core_admin');
        $filename = ltrim($config->{$filearea}, '/');

        $context = context_system::instance();
        $fs = get_file_storage();
        return $fs->get_file($context->id, 'core_admin', $filearea, 0, '/', $filename);
    }

    /**
     * @param string $filearea
     * @return string
     * @throws dml_exception
     */
    private function get_current_logo_file_hash(string $filearea): string {
        $file = $this->get_current_logo_file($filearea);

        if ($file === false) {
            return '';
        } else {
            return $file->get_contenthash();
        }
    }

    /**
     * @throws invalid_parameter_exception
     */
    private function get_new_logo_file_hash(string $logo_path): string {
        if (!file_exists($logo_path)) {
            throw new invalid_parameter_exception('File does not exist: ' . $logo_path);
        }

        return hash_file('sha1', $logo_path);
    }
}
