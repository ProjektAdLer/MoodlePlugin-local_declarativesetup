<?php

namespace local_adlersetup\local\db;

use core\di;
use local_adlersetup\lib\adler_testcase;
use moodle_database;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class moodle_role_repository_test extends adler_testcase {
    public function test_get_capabilities_of_role(): void {
        $repository = di::get(moodle_role_repository::class);
        $capabilities = $repository->get_capabilities_of_role(1);
        $so_dumm = reset($capabilities);
        $this->assertTrue(property_exists($so_dumm, 'capability'));
    }

    public function test_update_role(): void {
        $repository = di::get(moodle_role_repository::class);
        $this->assertTrue($repository->update_role(1, 'test', 'test', 'test'));

        $this->assertEquals('test', di::get(moodle_database::class)->get_record('role', ['id' => 1])->name);
    }
}