<?php

namespace local_declarativesetup\local\play\role;

use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\role\models\role_model;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class role_test extends adler_testcase {
    private function create_test_role_and_verify(): bool {
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

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

        return $changed;
    }

    public function test_play_create_role() {
        $changed = $this->create_test_role_and_verify();
        $this->assertTrue($changed);
    }

    public function test_play_update_role_no_change() {
        $this->create_test_role_and_verify();
        $changed = $this->create_test_role_and_verify();
        $this->assertFalse($changed);
    }

    public function test_play_remove_role_capability() {
        $this->create_test_role_and_verify();

        // remove capability
        $role = new role_model(
            'test_role',
            [],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertArrayNotHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
    }

    public static function provide_replace_capabilities_true_false_data() {
        return [
            'add' => [
                'replace_capabilities' => false,
            ],
            'replace' => [
                'replace_capabilities' => true,
            ],
        ];
    }

    /**
     * @dataProvider provide_replace_capabilities_true_false_data
     */
    public function test_update_role_capabilities_list($replace_capabilities) {
        $this->create_test_role_and_verify();

        // update capabilities
        $role = new role_model(
            'test_role',
            ['moodle/webservice:createtoken' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            $replace_capabilities,
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        if ($replace_capabilities) {
            $this->assertArrayNotHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
        } else {
            $this->assertArrayHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
            $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/question:add'], CAP_ALLOW);
            $this->assertArrayHasKey('moodle/restore:restoresection', $play->get_output()['test_role']->list_of_capabilities);
            $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/restore:restoresection'], CAP_ALLOW);
        }
    }

    public function test_add_role_capabiltiy(){
        $this->create_test_role_and_verify();

        // add capability
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW, 'moodle/question:managecategory' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertArrayHasKey('moodle/question:managecategory', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/question:managecategory'], CAP_ALLOW);
    }

    public function test_play_update_role_capability_permission() {
        $this->create_test_role_and_verify();

        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_PREVENT, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertArrayHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/question:add'], CAP_PREVENT);
        $this->assertArrayHasKey('moodle/restore:restoresection', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/restore:restoresection'], CAP_ALLOW);

        // update back (to test update with different permissions per capability)
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertArrayHasKey('moodle/question:add', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/question:add'], CAP_ALLOW);
        $this->assertArrayHasKey('moodle/restore:restoresection', $play->get_output()['test_role']->list_of_capabilities);
        $this->assertEquals($play->get_output()['test_role']->list_of_capabilities['moodle/restore:restoresection'], CAP_ALLOW);
    }

    public function test_play_update_role_contexts() {
        $this->create_test_role_and_verify();

        // update contexts
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_SYSTEM],
            true,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertContains(CONTEXT_SYSTEM, $play->get_output()['test_role']->list_of_contexts);
        $this->assertNotContains(CONTEXT_COURSECAT, $play->get_output()['test_role']->list_of_contexts);
    }

    /**
     * @dataProvider provide_replace_capabilities_true_false_data
     */
    public function test_play_update_role_contexts_no_change($replace_capabilities) {
        $this->create_test_role_and_verify();

        // update contexts
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            $replace_capabilities,
            'Test Role',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertContains(CONTEXT_COURSECAT, $play->get_output()['test_role']->list_of_contexts);
        $this->assertCount(1, $play->get_output()['test_role']->list_of_contexts);
    }

    public function test_play_update_role_properties() {
        $this->create_test_role_and_verify();

        // update properties
        $role = new role_model(
            'test_role',
            ['moodle/question:add' => CAP_ALLOW, 'moodle/restore:restoresection' => CAP_ALLOW],
            [CONTEXT_COURSECAT],
            true,
            'Test Role',
            'This is a test role',
            'manager',
        );

        $play = new role($role);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('test_role', $play->get_output()['test_role']->shortname);
        $this->assertEquals('This is a test role', $play->get_output()['test_role']->description);
        $this->assertEquals('manager', $play->get_output()['test_role']->archetype);
        $this->assertEquals('Test Role', $play->get_output()['test_role']->role_name);
    }
}
