<?php

namespace local_declarativesetup\local\lib;

use core\di;
use dml_exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\db\moodle_config_repository;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_is_forced;
use local_declarativesetup\local\moodle_core;
use Mockery;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class config_manager_test extends adler_testcase {
    public function test_set_soft_setting_non_forced() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Test setting a non-forced setting
        $config_php_file_manager_mock->shouldReceive('setting_exists')->with('testsetting', null)->andReturn(false);
        $moodle_core_mock->shouldReceive('set_config')->with('testsetting', 'testvalue', null)->once();

        $config_manager->set_soft_setting('testsetting', 'testvalue');
    }

    public function test_set_soft_setting_forced() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Test setting a forced setting
        $config_php_file_manager_mock->shouldReceive('setting_exists')->with('testsetting', null)->andReturn(true);

        $this->expectException(setting_is_forced::class);
        $config_manager->set_soft_setting('testsetting', 'testvalue');
    }


    public function test_set_forced_setting() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Test setting a forced setting
        $config_php_file_manager_mock->shouldReceive('set_setting')->with('testsetting', 'testvalue', null)->once();

        $config_manager->set_forced_setting('testsetting', 'testvalue');
    }

//    private function get_sample_config_php(): string {
//        return <<<'EOD'
//<?php  // Moodle configuration file
//unset($CFG);
//global $CFG;
//$CFG = new stdClass();
//$CFG->someconfig    = 'somevalue';
//require_once(__DIR__ . '/lib/setup.php'); // Do not edit
//EOD;
//    }
//
//    public function test_delete_setting_with_forced_setting() {
//        $php_mock = Mockery::mock(php::class);
//        $capturedData = $this->get_sample_config_php(); // Initial content
//        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
//            return $capturedData;
//        });
//        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
//            $capturedData = $data; // Store the written data
//            return true; // Return true to indicate the arguments match
//        });
//        di::set(php::class, $php_mock);
//        set_config('someconfig', 'somevalue');
//
//
//        $config_manager = di::get(config_manager::class);
//        $config_manager->delete_setting('someconfig');
//
//        $this->assertStringNotContainsString('someconfig', $capturedData);
//        $this->assertFalse($config_manager->setting_exists('someconfig'));
//    }

//    public function test_delete_setting_with_soft_setting() {
//        $php_mock = Mockery::mock(php::class);
//        $capturedData = $this->get_sample_config_php(); // Initial content
//        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
//            return $capturedData;
//        });
//        $php_mock->shouldNotReceive('file_put_contents');
//        di::set(php::class, $php_mock);
//        set_config('someotherconfig', 'somevalue');
//
//        $config_manager = di::get(config_manager::class);
//        $config_manager->delete_setting('someotherconfig');
//
//        $this->assertFalse($config_manager->setting_exists('someotherconfig'));
//    }


    public function test_delete_setting_exists_and_forced() {
        // Mock internal method calls
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting exists and is forced
        $config_manager->shouldReceive('setting_exists')->with('testsetting', null)->twice()->andReturn(true);
        $config_manager->shouldReceive('is_setting_forced')->with('testsetting', null)->andReturn(true);
        $config_manager->shouldReceive('delete_forced_setting')->with('testsetting', null)->once();
        $config_manager->shouldReceive('delete_soft_setting')->with('testsetting', null)->once();

        // Call the method under test
        $config_manager->delete_setting('testsetting', null);
    }

    public function test_delete_setting_exists_and_not_forced() {
        // Mock internal method calls
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting exists and is not forced
        $config_manager->shouldReceive('setting_exists')->with('testsetting', null)->twice()->andReturn(true);
        $config_manager->shouldReceive('is_setting_forced')->with('testsetting', null)->andReturn(false);
        $config_manager->shouldReceive('delete_forced_setting')->with('testsetting', null)->never();
        $config_manager->shouldReceive('delete_soft_setting')->with('testsetting', null)->once();

        // Call the method under test
        $config_manager->delete_setting('testsetting', null);
    }

    public function test_delete_setting_does_not_exist() {
        // Mock internal method calls
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting does not exist
        $config_manager->shouldReceive('setting_exists')->with('testsetting', null)->twice()->andReturn(false);
        $config_manager->shouldReceive('is_setting_forced')->with('testsetting', null)->never();
        $config_manager->shouldReceive('delete_forced_setting')->with('testsetting', null)->never();
        $config_manager->shouldReceive('delete_soft_setting')->with('testsetting', null)->never();

        // Call the method under test
        $config_manager->delete_setting('testsetting', null);
    }

    public function test_delete_soft_setting_is_forced() {
        // Mock internal method calls
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting is forced
        $config_manager->shouldReceive('is_setting_forced')->with('testsetting', null)->andReturn(true);
        $this->expectException(setting_is_forced::class);
        $config_manager->delete_soft_setting('testsetting', null);
    }

    public function test_delete_soft_setting_not_forced() {
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        di::set(moodle_core::class, $moodle_core_mock);

        // Mock internal method calls
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting is not forced
        $moodle_core_mock->shouldReceive('unset_config')->with('testsetting', null)->once();
        $config_manager->shouldReceive('is_setting_forced')->with('testsetting', null)->andReturn(false);

        $config_manager->delete_soft_setting('testsetting', null);
    }

    public function test_get_value_forced_setting() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Case: Setting is forced
        $config_php_file_manager_mock->shouldReceive('setting_exists')->with('testsetting', null)->andReturn(true);
        $config_php_file_manager_mock->shouldReceive('get_setting_value')->with('testsetting', null)->andReturn('forcedvalue');

        $this->assertEquals('forcedvalue', $config_manager->get_value('testsetting', null));
    }

    public function test_get_value_non_forced_setting() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        $moodle_config_repository_mock = Mockery::mock(moodle_config_repository::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);
        di::set(moodle_config_repository::class, $moodle_config_repository_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Case: Setting is not forced
        $config_php_file_manager_mock->shouldReceive('setting_exists')->with('testsetting', null)->andReturn(false);
        $moodle_config_repository_mock->shouldReceive('get_config')->with('testsetting', null)->andReturn((object)['value' => 'dbvalue']);

        $this->assertEquals('dbvalue', $config_manager->get_value('testsetting', null));
    }

    public function test_get_value_setting_does_not_exist() {
        $config_php_file_manager_mock = Mockery::mock(config_php_file_manager::class);
        $moodle_config_repository_mock = Mockery::mock(moodle_config_repository::class);
        di::set(config_php_file_manager::class, $config_php_file_manager_mock);
        di::set(moodle_config_repository::class, $moodle_config_repository_mock);

        $config_manager = new config_manager($config_php_file_manager_mock);

        // Case: Setting does not exist
        $config_php_file_manager_mock->shouldReceive('setting_exists')->with('testsetting', null)->andReturn(false);
        $moodle_config_repository_mock->shouldReceive('get_config')->with('testsetting', null)->andThrow(new dml_exception(''));

        $this->expectException(setting_does_not_exist::class);
        $config_manager->get_value('testsetting', null);
    }

    public function test_setting_exists_true() {
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting exists
        $config_manager->shouldReceive('get_value')->with('testsetting', null)->andReturn('somevalue');

        $this->assertTrue($config_manager->setting_exists('testsetting', null));
    }

    public function test_setting_exists_false() {
        $config_manager = Mockery::mock(config_manager::class)->makePartial();

        // Case: Setting does not exist
        $config_manager->shouldReceive('get_value')->with('testsetting', null)->andThrow(setting_does_not_exist::class);

        $this->assertFalse($config_manager->setting_exists('testsetting', null));
    }
}