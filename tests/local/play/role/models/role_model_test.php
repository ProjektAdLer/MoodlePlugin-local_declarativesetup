<?php

namespace local_adlersetup\local\play\role\models;

use invalid_parameter_exception;
use local_adlersetup\lib\adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class role_model_test extends adler_testcase {
    public function test_type_validation_contexts() {
        $this->expectException(invalid_parameter_exception::class);
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            ['invalid_context'],
        );
        $this->assertEquals('test_role', $role->shortname);
        $this->assertEquals('test_role', $role->role_name);
    }

    public function provide_test_type_validation_capabilities_data() {
        return [
            'invalid_capabilities' => [['invalid_capabilities']],
            'invalid_capability_name' => [[7 => CAP_ALLOW]],
            'invalid_capability_permission' => [['moodle/question:add' => 'CAP_ALLOW']],
        ];
    }

    /**
     * @dataProvider provide_test_type_validation_capabilities_data
     */
    public function test_type_validation_capabilities($capabilities) {
        $this->expectException(invalid_parameter_exception::class);
        $role = new role_model(
            'test_role',
            $capabilities,
            [CONTEXT_COURSECAT],
        );
    }
}