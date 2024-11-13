<?php

namespace local_adlersetup\local\play;

use context_system;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\play\models\user_model;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class user_test extends adler_testcase {
    public function setUp(): void {
        parent::setUp();
        $existing_user = get_complete_user_data('username', 'testuser');
        $this->assertFalse($existing_user);

        $user = new user_model(
            'testuser',
            'password',
            true,
            ['coursecreator']);

        $play = new user($user);
        $changed = $play->play();

        $this->assertTrue($changed);
    }

    public function test_update_no_change() {
        $user = new user_model(
            'testuser',
            'password',
            true,
            ['coursecreator']);
        $play = new user($user);
        $changed = $play->play();

        $this->assertFalse($changed);
    }

    public function test_create_user() {
        $existing_user = get_complete_user_data('username', 'testuser');
        $this->assertEquals('testuser', $existing_user->username);
        $system_context = context_system::instance();
        $roles = get_user_roles($system_context, $existing_user->id);
        $this->assertContains('coursecreator', array_column($roles, 'shortname'));
    }

    public function test_update_user() {
        // update test user
        $user = new user_model(
            'testuser',
            'password',
            true,
            [],
            true,
            'en',
            'Hans',
            'Müller',
            'some@e.mail',
            'updated by test');
        $play = new user($user);
        $changed = $play->play();

        $this->assertTrue($changed);
        $existing_user = get_complete_user_data('username', 'testuser');
        $this->assertEquals('en', $existing_user->lang);
        $this->assertEquals('Hans', $existing_user->firstname);
        $this->assertEquals('Müller', $existing_user->lastname);
        $this->assertEquals('some@e.mail', $existing_user->email);
        $this->assertEquals('updated by test', $existing_user->description);
    }

    public function test_update_password() {
        // update test user
        $user = new user_model(
            'testuser',
            'newpassword');
        $play = new user($user);
        $changed = $play->play();

        $this->assertTrue($changed);
        $existing_user = get_complete_user_data('username', 'testuser');
        $this->assertTrue(validate_internal_user_password($existing_user, 'newpassword'));
    }

    public function provide_test_update_roles_data(): array {
        return [
            'append' => ['append_roles' => true],
            'replace' => ['append_roles' => false]
        ];
    }

    /**
     * @dataProvider provide_test_update_roles_data
     */
    public function test_update_roles(bool $append_roles) {
        // update test user
        $user = new user_model(
            'testuser',
            'password',
            true,
            ['manager'],
            $append_roles);
        $play = new user($user);
        $changed = $play->play();

        $this->assertTrue($changed);
        $existing_user = get_complete_user_data('username', 'testuser');
        $roles = get_user_roles(context_system::instance(), $existing_user->id);
        $this->assertContains('manager', array_column($roles, 'shortname'));
        if ($append_roles) {
            $this->assertContains('coursecreator', array_column($roles, 'shortname'));
        } else {
            $this->assertNotContains('coursecreator', array_column($roles, 'shortname'));
        }
    }

    public function provide_test_delete_user_data(): array {
        return [
            'existing user' => ['testuser'],
            'non existing user' => ['nonexistinguser']
        ];
    }

    /**
     * @dataProvider provide_test_delete_user_data
     */
    public function test_delete_user(string $username) {
        // delete test user
        $user = new user_model(
            $username,
            'password',
            false);
        $play = new user($user);
        $changed = $play->play();

        if ($username === 'testuser') {
            $this->assertTrue($changed);
        } else {
            $this->assertFalse($changed);
        }
        $existing_user = get_complete_user_data('username', $username);
        $this->assertFalse($existing_user);
    }
}