<?php

namespace local_adlersetup\local;

use local_adlersetup\local\play\exceptions\not_implemented_exception;
use local_adlersetup\local\play\exceptions\play_was_already_played_exception;
use local_adlersetup\local\play\exceptions\play_was_not_played_exception;
use local_adlersetup\local\play\install_plugins;
use local_adlersetup\local\play\models\install_plugins_model;
use moodle_exception;

class playbook {
    /**
     * @throws not_implemented_exception
     * @throws play_was_not_played_exception
     * @throws play_was_already_played_exception
     * @throws moodle_exception
     */
    public function __construct(bool $install_plugins = false) {
        if ($install_plugins) {
            $plugins_to_install = [
                new install_plugins_model(
                    'ProjektAdler/MoodlePluginModAdleradaptivity',
                    '2.2.0',
                    'mod_adleradaptivity'
                )
            ];
        } else {
            $plugins_to_install = [];
        }

        $play = new install_plugins($plugins_to_install);
        $play->play();
        $installed_plugins = $play->get_output();
        // assert $installed_plugins contains local_adler
        if (!array_key_exists('local_adler', $installed_plugins)) {
            throw new moodle_exception('Plugins are not installed (checked for local_adler)', 'local_adlersetup');
        }

    }
}