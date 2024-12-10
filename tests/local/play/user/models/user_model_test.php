<?php

namespace local_declarativesetup\local\play\user\models;

use invalid_parameter_exception;
use local_declarativesetup\lib\adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class user_model_test extends adler_testcase {
    public function test_invalid_username_caps() {
        $this->expectException(invalid_parameter_exception::class);
        new user_model(
            'ONLY_LOWERCASE_ALLOWED',
            'password',
        );
    }

    public function test_invalid_username_spaces() {
        $this->expectException(invalid_parameter_exception::class);
        new user_model(
            'no spaces',
            'password',
        );
    }

    public function test_invalid_role() {
        $this->expectException(invalid_parameter_exception::class);
        new user_model(
            'testuser',
            'password',
            true,
            ['invalid_role']
        );
    }

    public function test_valid_role() {
        $user = new user_model(
            'testuser',
            'password',
            true,
            ['coursecreator']
        );
        $this->assertContains('coursecreator', $user->system_roles);
    }
}