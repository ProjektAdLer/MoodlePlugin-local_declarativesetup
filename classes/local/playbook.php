<?php

namespace local_adlersetup\local;

use local_adlersetup\local\play\exceptions\not_implemented_exception;
use local_adlersetup\local\play\exceptions\play_was_already_played_exception;
use local_adlersetup\local\play\exceptions\play_was_not_played_exception;
use local_adlersetup\local\play\install_plugins;
use local_adlersetup\local\play\models\install_plugins_model;
use local_adlersetup\local\play\models\role_model;
use local_adlersetup\local\play\role;
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


        $play = new role(new role_model(
            'adler_manager',
            [
                'moodle/course:delete' => CAP_ALLOW,
                'moodle/course:enrolconfig' => CAP_ALLOW,
                'moodle/question:add' => CAP_ALLOW,
                'moodle/question:managecategory' => CAP_ALLOW,
                'moodle/restore:configure' => CAP_ALLOW,
                'moodle/restore:restoreactivity' => CAP_ALLOW,
                'moodle/restore:restorecourse' => CAP_ALLOW,
                'moodle/restore:restoresection' => CAP_ALLOW,
                'moodle/restore:restoretargetimport' => CAP_ALLOW,
                'moodle/restore:rolldates' => CAP_ALLOW,
                'moodle/restore:uploadfile' => CAP_ALLOW,
                'moodle/restore:userinfo' => CAP_ALLOW,
                'moodle/restore:viewautomatedfilearea' => CAP_ALLOW
            ],
            [
                CONTEXT_COURSECAT
            ],
            'adler_manager',
            'Manager for adler courses. Has all permissions required to work with the authoring tool.',
            'user'
        ));
        $play->play();

    }
}