<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_declarativesetup\local\play\course_category;

global $CFG;

use context_coursecat;
use core_course_category;
use invalid_parameter_exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\course_category\exceptions\course_exists_exception;
use local_declarativesetup\local\play\course_category\exceptions\subcategory_exists_exception;
use local_declarativesetup\local\play\course_category\models\course_category_model;
use local_declarativesetup\local\play\course_category\models\role_user_model;
use local_declarativesetup\local\play\course_category\util\course_category_path;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class course_category_test extends adler_testcase {
    public function test_course_category_basic() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $play = new course_category(new course_category_model(
            'testcategory',
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertTrue($category_path->exists());
        $this->assertNotEmpty($category_path->get_moodle_category_object()->description);
    }

    public function test_course_category_same_base_category() {
        $base_category_path = new course_category_path('testcategory');
        $this->assertFalse($base_category_path->exists());
        $sub_category_1_path = new course_category_path('testcategory/subcategory1');
        $this->assertFalse($sub_category_1_path->exists());
        $sub_category_2_path = new course_category_path('testcategory/subcategory2');
        $this->assertFalse($sub_category_2_path->exists());

        // create first subcategory
        $play = new course_category(new course_category_model(
            'testcategory/subcategory1',
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertTrue($sub_category_1_path->exists());
        $this->assertTrue($base_category_path->exists());

        // create second subcategory
        $play = new course_category(new course_category_model(
            'testcategory/subcategory2',
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertTrue($sub_category_2_path->exists());
        $this->assertTrue($base_category_path->exists());

        // check only one instance of base category exists
        $all_categories = core_course_category::make_categories_list();
        $this->assertEquals(1, count(array_filter($all_categories, function($category) {
            return $category == 'testcategory';
        })));
    }

    public function test_course_category_basic_empty_description() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $play = new course_category(new course_category_model(
            'testcategory',
            true,
            true,
            [],
            ''
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertTrue($category_path->exists());
        $this->assertEquals("", $category_path->get_moodle_category_object()->description);
    }

    public function test_course_update_no_change() {
        $category_path = new course_category_path('testcategory');
        $this->assertFalse($category_path->exists());

        $play = new course_category(new course_category_model(
            'testcategory',
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertTrue($category_path->exists());

        $play = new course_category(new course_category_model(
            'testcategory',
        ));
        $changed = $play->play();
        $this->assertFalse($changed);
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

        $changed = $play->play();

        $this->assertTrue($changed);
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

    private function create_test_category($description="description") {
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
            $description
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
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertFalse($course_category_path->exists());
    }

    public function test_delete_subcategory_force() {
        $ccp = new course_category_path('testcategory/subcategory');
        $ccp->create();

        $play = new course_category(new course_category_model(
            'testcategory',
            false,
            force_delete: true
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertFalse($ccp->exists());
    }

    public function test_delete_subcategory_no_force() {
        $ccp = new course_category_path('testcategory/subcategory');
        $ccp->create();

        $play = new course_category(new course_category_model(
            'testcategory',
            false,
            force_delete: false
        ));
        $this->expectException(subcategory_exists_exception::class);
        $play->play();
    }

    public function test_delete_with_course_force() {
        $ccp = new course_category_path('testcategory');
        $ccp->create();
        $this->getDataGenerator()->create_course([
            'category' => $ccp->get_moodle_category_object()->id,
        ]);

        $play = new course_category(new course_category_model(
            'testcategory',
            false,
            force_delete: true
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertFalse($ccp->exists());
    }

    public function test_delete_with_course_no_force() {
        $ccp = new course_category_path('testcategory');
        $ccp->create();
        $this->getDataGenerator()->create_course([
            'category' => $ccp->get_moodle_category_object()->id,
        ]);

        $play = new course_category(new course_category_model(
            'testcategory',
            false,
            force_delete: false
        ));
        $this->expectException(course_exists_exception::class);
        $play->play();
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
        $changed = $play->play();

        $this->assertTrue($changed);
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
        $changed = $play->play();

        $this->assertTrue($changed);
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
        $changed = $play->play();

        $this->assertTrue($changed);
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
        $changed = $play->play();

        $this->assertTrue($changed);
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
    public function test_update_append_and_remove_role() {
        $description = "description";
        list($user, $role_shortname, $course_category_path) = $this->create_test_category($description);

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
            $description
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user has both roles
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(2, count($user_roles));
        $this->assertEquals($role_shortname, array_values($user_roles)[0]->shortname);
        $this->assertEquals($new_role_shortname, array_values($user_roles)[1]->shortname);

        // remove a role
        $play = new course_category(new course_category_model(
            $course_category_path,
            true,
            true,
            [
                new role_user_model($user->username, [$role_shortname], false),
            ],
            $description
        ));
        $changed = $play->play();

        $this->assertTrue($changed);
        $moodle_category = $course_category_path->get_moodle_category_object();
        // check user has only new role
        $user_id = get_complete_user_data('username', $user->username)->id;
        $user_roles = get_user_roles(
            context_coursecat::instance($moodle_category->id),
            $user_id);
        $this->assertEquals(1, count($user_roles));
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
        $changed = $play->play();

        $this->assertTrue($changed);
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