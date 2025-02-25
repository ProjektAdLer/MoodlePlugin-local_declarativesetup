<?php

global $CFG;

use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\cli\create_course_cat_and_assign_user_role;
use local_declarativesetup\local\exceptions\exit_exception;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class create_course_cat_and_assign_user_role_test extends adler_testcase {
    public static function provide_test_call_data() {
        return [
            [
                'username_input' => 'username',
                'username_expect' => 'username',
            ],
            [
                'username_input' => ' username ',
                'username_expect' => 'username',
            ]
        ];
    }

    /**
     * @dataProvider provide_test_call_data
     * @runInSeparateProcess
     */
    public function test_call_success($username_input, $username_expect) {
        global $CFG;

        $role = 'role';
        $category_path = 'category_path';

        $mock = Mockery::mock('overload:' . create_course_cat_and_assign_user_role::class);
        $mock->shouldReceive('__construct')->once()->with($username_expect, $role, $category_path);
        $mock->shouldReceive('execute')->once();

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--username=' . $username_input,
            '--role=' . $role,
            '--category_path=' . $category_path,
        ];

        require $CFG->dirroot . '/local/declarativesetup/cli/create_course_cat_and_assign_user_role.php';
    }

    public function test_invalid_parameter() {
        global $CFG;

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
            '--baum=Eiche',
        ];

        $this->expectException(exit_exception::class);

        require $CFG->dirroot . '/local/declarativesetup/cli/create_course_cat_and_assign_user_role.php';
    }

    public function test_fail_execute() {
        global $CFG;

        $role = 'role';
        $category_path = 'category_path';

        $_SERVER['argv'] = [
            'create_course_cat_and_assign_user_role.php',
//            '--username=username',  // fail because this parameter is missing
            '--role=' . $role,
            '--category_path=' . $category_path,
        ];

        $this->expectException(exit_exception::class);

        require $CFG->dirroot . '/local/declarativesetup/cli/create_course_cat_and_assign_user_role.php';
    }
}