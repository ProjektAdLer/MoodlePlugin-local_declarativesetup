<?php

namespace local\play;

use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\play\models\role_model;
use local_adlersetup\local\play\role;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class roles_test extends adler_testcase {
    public function test_play() {
        $this->setAdminUser();

        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
        );

        $play = new role($role);
        $play->play();

        // check if role was created
        $role_exists = false;
        foreach (get_all_roles() as $role) {
            if ($role->shortname === 'test_role') {
                $role_exists = true;
                break;
            }
        }
        $this->assertTrue($role_exists);
        $this->assertArrayHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/question:add'], CAP_ALLOW);
    }

    // TODO: further tests like updating
}
