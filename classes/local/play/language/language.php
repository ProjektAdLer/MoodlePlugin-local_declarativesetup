<?php

namespace local_declarativesetup\local\play\language;

use core\di;
use local_declarativesetup\local\moodle_core;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\language\models\language_model;
use moodle_exception;
use tool_langimport\controller;

/**
 * @property language_model[] $languages
 */
class language extends base_play {
    /**
     * This play takes a list of {@link language_model} and ensures that the specified languages are in the desired state.
     * Note: Most languages require a system locale to be installed. This play does not install system locales and will
     * fail if the required system locale is not installed.
     *
     * {@link get_output} returns a list of all active language keys as string[].
     *
     * @param language_model[] $languages
     * @throws moodle_exception
     */
    public function __construct(array $languages) {
        // check entries are unique
        $language_codes = [];
        foreach ($languages as $language) {
            if (in_array($language->language_code, $language_codes)) {
                throw new moodle_exception("Language code \"" . $language->language_code . "\" is not unique.", 'local_declarativesetup');
            }
            $language_codes[] = $language->language_code;
        }
        parent::__construct($languages);
    }


    /**
     * @throws moodle_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;

        foreach ($this->input as $language) {
            if ($language->enabled !== $this->is_language_pack_installed($language->language_code)) {
                $state_changed = true;
                if ($language->enabled) {
                    $this->install_language_pack($language->language_code);
                } else {
                    $this->uninstall_language_pack($language->language_code);
                }
            }
        }

        if ($state_changed) {
            di::get(moodle_core::class)::get_string_manager()->reset_caches();
        }

        return $state_changed;
    }

    /**
     * @param $language_code string language code. List of valid codes can be found at Site administration -> Language -> Language packs
     * @return bool
     */
    private function is_language_pack_installed(string $language_code): bool {
        return array_key_exists(
            $language_code,
            di::get(moodle_core::class)::get_string_manager()->get_list_of_translations()
        );
    }

    /**
     * Installs a language pack. If no exception is thrown, the language pack was installed successfully.
     *
     * @throws moodle_exception if language pack could not be installed
     */
    private function install_language_pack(string $language_code): void {
        $result = di::get(controller::class)->install_languagepacks($language_code);  // returns count of installed languages
        if ($result !== 1) {
            throw new moodle_exception("Language \"" . $language_code . "\" was not installed. The reason is unknown due to moodle limitations. Check installed system locales (locales -a).", 'local_declarativesetup');
        }
    }

    /**
     * @throws moodle_exception
     */
    private function uninstall_language_pack(string $language_code): void {
        if (!di::get(controller::class)->uninstall_language($language_code)) {
            throw new moodle_exception("Failed to uninstall language pack \"" . $language_code . "\".", 'local_declarativesetup');
        }
    }

    /**
     * @return string[]
     */
    protected function get_output_implementation(): array {
        return array_keys(di::get(moodle_core::class)::get_string_manager()->get_list_of_translations());
    }
}