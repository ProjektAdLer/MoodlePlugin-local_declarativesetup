<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adlersetup\local\play\course_category;

global $CFG;

use context_coursecat;
use invalid_parameter_exception;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\play\course_category\models\course_category_model;
use local_adlersetup\local\play\course_category\models\role_user_model;
use local_adlersetup\local\play\course_category\util\course_category_path;

require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class course_category_test extends adler_testcase {
    public function test_course_category_basic() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $play = new course_category(new course_category_model(
            'testcategory',
        ));
        $play->play();

        $this->assertTrue($category_path->exists());
    }

    public function test_course_category_all_properties() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $user = $this->getDataGenerator()->create_user([
            'username' => 'username',
        ]);
        $role_shortname = 'test_role';
        $role_id = $this->getDataGenerator()->create_role([
            'shortname' => $role_shortname,
        ]);

        $play = new course_category(new course_category_model(
            'testcategory',
            true,
            true,
            [
                new role_user_model($user->username, [$role_shortname], true),
            ],
            'description'
        ));

        $play->play();

        $moodle_category = $category_path->get_moodle_category_object();
        $this->assertTrue($category_path->exists());
        $this->assertEquals('description', $moodle_category->description);
        // check user roles
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
        $this->assertEquals($role_shortname, reset($user_roles)->shortname);
    }

    public function test_invalid_role() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $user = $this->getDataGenerator()->create_user([
            'username' => 'username',
        ]);
        $role_shortname = 'test_role';
        $role_id = $this->getDataGenerator()->create_role([
            'shortname' => $role_shortname,
        ]);
        // allow role only on CONTEXT_SYSTEM
        set_role_contextlevels($role_id, [CONTEXT_SYSTEM]);


        $play = new course_category(new course_category_model(
            'testcategory',
            true,
            true,
            [
                new role_user_model($user->username, [$role_shortname], true),
            ],
            'description'
        ));

        $this->expectException(invalid_parameter_exception::class);

        $play->play();
    }

    private function create_test_category() {
        $user = $this->getDataGenerator()->create_user([
            'username' => 'username',
        ]);
        $role_shortname = 'test_role';
        $role_id = $this->getDataGenerator()->create_role([
            'shortname' => $role_shortname,
        ]);
        $course_category_path = 'testcategory';

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [
                new role_user_model($user->username, [$role_shortname], true),
            ],
            'description'
        ));

        $play->play();

        return [$user, $role_shortname, new course_category_path($course_category_path)];
    }

    public function test_delete() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $play = new course_category(new course_category_model(
            $course_category_path,
            false
        ));
        $play->play();

        $this->assertFalse($course_category_path->exists());
    }


    public function test_update_description() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [],
            'new description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        $this->assertEquals('new description', $moodle_category->description);
    }

    public function test_update_replace_users() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $new_user = $this->getDataGenerator()->create_user([
            'username' => 'new_username',
        ]);

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            false,
            [
                new role_user_model($new_user->username, [$role_shortname], true),
            ],
            'description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user removed from cc
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(0, count($user_roles));
        // check new user added to cc
        $user_id = get_complete_user_data('username', $new_user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
        $this->assertEquals($role_shortname, reset($user_roles)->shortname);
    }
    public function test_update_remove_user() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            false,
            [],
            'description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user removed from cc
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(0, count($user_roles));
    }
    public function test_update_append_users() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $new_user = $this->getDataGenerator()->create_user([
            'username' => 'new_username',
        ]);

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [
                new role_user_model($new_user->username, [$role_shortname], true),
            ],
            'description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user still in cc
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
        $this->assertEquals($role_shortname, reset($user_roles)->shortname);
        // check new user added to cc
        $user_id = get_complete_user_data('username', $new_user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
        $this->assertEquals($role_shortname, reset($user_roles)->shortname);
    }
    public function test_update_append_roles() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $new_role_shortname = 'new_role';
        $new_role_id = $this->getDataGenerator()->create_role([
            'shortname' => $new_role_shortname,
        ]);

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [
                new role_user_model($user->username, [$new_role_shortname], true),
            ],
            'description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user has both roles
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(2, count($user_roles));
        $this->assertEquals($role_shortname, array_values($user_roles)[0]->shortname);
        $this->assertEquals($new_role_shortname, array_values($user_roles)[1]->shortname);

    }
    public function test_update_replace_roles() {
        list($user, $role_shortname, $course_category_path) = $this->create_test_category();

        $new_role_shortname = 'new_role';
        $new_role_id = $this->getDataGenerator()->create_role([
            'shortname' => $new_role_shortname,
        ]);

        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [
                new role_user_model($user->username, [$new_role_shortname], false),
            ],
            'description'
        ));
        $play->play();

        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user has only new role
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
        $this->assertEquals($new_role_shortname, reset($user_roles)->shortname);
    }
}