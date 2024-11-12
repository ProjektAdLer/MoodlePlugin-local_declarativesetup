<?php

namespace local_adlersetup\local\cli;

global $CFG;

use context_coursecat;
use invalid_parameter_exception;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\cli\create_course_cat_and_assign_user_role;
use moodle_exception;
use TypeError;

require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class create_course_cat_and_assign_user_role_test extends adler_testcase {
    public function test_create_instance() {
        $this->getDataGenerator()->create_user(['username' => 'username']);
        $this->getDataGenerator()->create_role(['shortname' => 'role']);
        new create_course_cat_and_assign_user_role('username', 'role', 'category_path');
    }

    public function provide_create_instance_invalid_data() {
        return [
            'empty username' => [
                'username' => '',
                'role' => 'role',
                'category_path' => 'category_path',
                'expect_exception' => moodle_exception::class
            ],
            'empty role' => [
                'username' => 'username',
                'role' => '',
                'category_path' => 'category_path',
                'expect_exception' => moodle_exception::class
            ],
            'null username' => [
                'username' => null,
                'role' => 'role',
                'category_path' => 'category_path',
                'expect_exception' => TypeError::class
            ],
            'null role' => [
                'username' => 'username',
                'role' => null,
                'category_path' => 'category_path',
                'expect_exception' => TypeError::class
            ],
            'caps username' => [
                'username' => 'USERNAME',
                'role' => 'role',
                'category_path' => 'category_path',
                'expect_exception' => moodle_exception::class
            ],
            'space username' => [
                'username' => 'user name',
                'role' => 'role',
                'category_path' => 'category_path',
                'expect_exception' => moodle_exception::class
            ],
            'empty category path' => [
                'username' => 'username',
                'role' => 'role',
                'category_path' => '',
                'expect_exception' => false
            ],
            'null category path' => [
                'username' => 'username',
                'role' => 'role',
                'category_path' => null,
                'expect_exception' => false
            ],
        ];
    }

    /**
     * @dataProvider provide_create_instance_invalid_data
     */
    public function test_create_instance_invalid_data($username, $role, $category_path, $expect_exception) {
        if ($expect_exception !== false) {
            $this->expectException($expect_exception);
        }

        if (!empty($username)) {
            $this->getDataGenerator()->create_user(['username' => $username]);
        }
        if (!empty($role)) {
            $this->getDataGenerator()->create_role(['shortname' => $role]);
        }

        new create_course_cat_and_assign_user_role($username, $role, $category_path);
    }

    public function test_create_instance_user_not_found() {
        $this->expectException(invalid_parameter_exception::class);
        $this->getDataGenerator()->create_role(['shortname' => 'role']);
        new create_course_cat_and_assign_user_role('username', 'role', 'category_path');
    }

    public function test_create_instance_role_not_found() {
        $this->expectException(invalid_parameter_exception::class);
        $this->getDataGenerator()->create_user(['username' => 'username']);
        new create_course_cat_and_assign_user_role('username', 'role', 'category_path');
    }

    public function test_create_instance_role_not_assignable() {
        $this->expectException(invalid_parameter_exception::class);
        $this->getDataGenerator()->create_user(['username' => 'username']);
        $role_id = $this->getDataGenerator()->create_role(['shortname' => 'role']);
        set_role_contextlevels($role_id, [CONTEXT_SYSTEM]);
        new create_course_cat_and_assign_user_role('username', 'role', 'category_path');
    }

    public function test_execute() {
        $user = $this->getDataGenerator()->create_user(['username' => 'username']);
        $role_id = $this->getDataGenerator()->create_role(['shortname' => 'role']);
        $category_path = 'category_path';

        $instance = new create_course_cat_and_assign_user_role('username', 'role', $category_path);
        $cc_id = $instance->execute();


        // assert category was created
        global $DB;
        $DB->get_record('course_categories', ['name' => $category_path], '*', MUST_EXIST);
        // assert user was assigned to role
        $this->assertNotEmpty(get_user_roles(context_coursecat::instance($cc_id), $user->id));
    }

    public function test_execute_no_course_category() {
        $user = $this->getDataGenerator()->create_user(['username' => 'username']);
        $role_id = $this->getDataGenerator()->create_role(['shortname' => 'role']);

        $instance = new create_course_cat_and_assign_user_role('username', 'role', null);
        $cc_id = $instance->execute();

        // assert user was assigned to role
        $this->assertNotEmpty(get_user_roles(context_coursecat::instance($cc_id), $user->id));

        // assert category was created
        global $DB;
        $DB->get_record('course_categories', ['name' => 'username'], '*', MUST_EXIST);
        // assert user was assigned to role
        $this->assertNotEmpty(get_user_roles(context_coursecat::instance($cc_id), $user->id));
    }
}