<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_adlersetup\local\play\course_category\models;

global $CFG;

use local_adlersetup\lib\adler_testcase;

require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class role_user_model_test extends adler_testcase {
    public function test_create_instance() {
        $model = new role_user_model('username', ['role']);
        $this->assertEquals('username', $model->username);
        $this->assertEquals(['role'], $model->roles);
        $this->assertTrue($model->append_roles);
    }
}