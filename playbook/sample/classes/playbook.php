<?php

namespace playbook_sample;

use Exception;
use invalid_parameter_exception;
use local_declarativesetup\local\base_playbook;
use local_declarativesetup\local\play\config\config;
use local_declarativesetup\local\play\config\models\config_model;
use local_declarativesetup\local\play\exceptions\play_was_already_played_exception;
use local_declarativesetup\local\play\role\models\role_model;
use local_declarativesetup\local\play\role\role;
use local_declarativesetup\local\play\user\models\user_model;
use local_declarativesetup\local\play\user\user;

class playbook extends base_playbook {
    /**
     * @throws play_was_already_played_exception
     * @throws invalid_parameter_exception
     */
    protected function playbook_implementation(): void {
        // first ensure maintenance mode is active. A playbook can take some time and users should not use
        // the system while it is being configured.
        $play = new config([
            new config_model('maintenance_enabled', 1),
            new config_model('maintenance_message', 'This site is currently under maintenance. Please try again later.'),
        ]);
        $play->play();

        // Now ensure the role 'sample_role' exists and has the correct set of capabilities.
        // The role might have already existed before and might have had a different set of capabilities.
        $play = new role(new role_model(
            'sample_role',
            [
                'moodle/course:delete' => CAP_ALLOW,
                'moodle/course:enrolconfig' => CAP_ALLOW,
            ],
            [CONTEXT_SYSTEM, CONTEXT_COURSE],
        ));
        $play->play();
        // $output = $play->get_output();  // plays can optionally return the new state after playing

        // Now ensure the user 'sample_user' exists and has the role 'sample_role'.
        $play = new user(new user_model(
            'sample_user',
            'Secret_password123',
            true,
            ['sample_role']
        ));
        $play->play();

        // A employee has left the company and therefore his user account should be deleted.
        $play = new user(new user_model(
            'previously_existing_user',
            '',
            false
        ));
        $play->play();

        // Now disable maintenance mode again.
        $play = new config([
            new config_model('maintenance_enabled', 0),
        ]);
        $play->play();
    }

    protected function failed(Exception $e): void {
        // do nothing
    }
}
