<?php

namespace local_adlersetup\local\play\language;

use core\di;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\moodle_core;
use local_adlersetup\local\play\language\models\language_model;
use Mockery;
use moodle_exception;
use tool_langimport\controller;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class language_test extends adler_testcase {
    public function test_integration() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->assertCount(1, get_string_manager()->get_list_of_translations());

        $play = new language([
            new language_model('en_us'),
            new language_model('en')
        ]);
        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertContains('en', $play->get_output());
        $this->assertContains('en_us', $play->get_output());
        $this->assertCount(2, $play->get_output());
    }
    public function test_no_change() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English']);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldNotReceive('install_languagepacks');
        $lang_controller_mock->shouldNotReceive('uninstall_language');
        di::set(controller::class, $lang_controller_mock);

        $play = new language([new language_model('en')]);
        $changed = $play->play();
        $this->assertFalse($changed);
    }

    public function test_output() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English'])->twice();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English', 'en_us' => 'English (US)'])->once();
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldNotReceive('install_languagepacks');
        $lang_controller_mock->shouldNotReceive('uninstall_language');
        di::set(controller::class, $lang_controller_mock);

        // has to be played once, otherwise it is not allowed to get output
        $play = new language([new language_model('en')]);
        $play->play();

        $this->assertEqualsCanonicalizing(['en'], $play->get_output());

        // with two languages: 2nd mock now active (3rd call)
        $this->assertEqualsCanonicalizing(['en', 'en_us'], $play->get_output());
    }

    public function test_output_with_change() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English'])->twice();
        $string_manager_mock->shouldReceive('reset_caches')->once();
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldReceive('install_languagepacks')->with('en_us')->once()->andReturn(1);
        $lang_controller_mock->shouldNotReceive('uninstall_language');
        di::set(controller::class, $lang_controller_mock);


        $play = new language([
            new language_model('en_us'),
            new language_model('en')
        ]);
        $changed = $play->play();

        $this->assertTrue($changed);
    }

    public function test_duplicate_language_code() {
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldNotReceive('get_string_manager');
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldNotReceive('install_languagepacks');
        $lang_controller_mock->shouldNotReceive('uninstall_language');
        di::set(controller::class, $lang_controller_mock);


        $this->expectException(moodle_exception::class);
        $play = new language([
            new language_model('en'),
            new language_model('en')
        ]);
    }

    public function test_uninstall_language() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English', 'en_us' => 'English (US)'])->twice();
        $string_manager_mock->shouldReceive('reset_caches')->once();
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldReceive('uninstall_language')->with('en_us')->andReturn(true)->once();
        di::set(controller::class, $lang_controller_mock);

        $play = new language([
            new language_model('en'),
            new language_model('en_us', false)
            ]);
        $changed = $play->play();

        $this->assertTrue($changed);
    }

    public function test_uninstall_fail() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English']);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldReceive('uninstall_language')->with('en')->andReturn(false)->once();
        di::set(controller::class, $lang_controller_mock);

        $this->expectException(moodle_exception::class);

        $play = new language([
            new language_model('en', false),  // uninstalling 'en' is not allowed
            ]);
        $play->play();
    }

    public function test_install_fail() {
        $string_manager_mock = Mockery::mock();
        $string_manager_mock->shouldReceive('get_list_of_translations')->andReturn(['en' => 'English']);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('get_string_manager')->andReturn($string_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        $lang_controller_mock = Mockery::mock(controller::class);
        $lang_controller_mock->shouldReceive('install_languagepacks')->with('lorem_impsum')->andReturn(0)->once();
        di::set(controller::class, $lang_controller_mock);

        $this->expectException(moodle_exception::class);

        $play = new language([
            new language_model('lorem_impsum'),
            ]);
        $play->play();
    }
}