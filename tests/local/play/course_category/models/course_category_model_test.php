<?php /** @noinspection PhpIllegalPsrClassPathInspection */

namespace local_declarativesetup\local\play\course_category\models;

global $CFG;

use local_declarativesetup\lib\adler_testcase;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class course_category_model_test extends adler_testcase {
    public function test_create_instance() {
        $model = new course_category_model('category_path');
        $this->assertContains('category_path', $model->course_category_path->get_path());
        $this->assertEquals(true, $model->present);
        $this->assertEquals(true, $model->append_users);
        $this->assertEmpty($model->users);
        $this->assertNotEmpty($model->description);
        $this->assertIsString($model->description);
    }
}