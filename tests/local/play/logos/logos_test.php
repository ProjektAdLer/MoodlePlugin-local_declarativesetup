<?php

namespace local_declarativesetup\local\play\logos;

use core\di;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\php;
use local_declarativesetup\local\play\logos\models\logo_model;
use Mockery;
use moodle_exception;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class logos_test extends adler_testcase {
    private $initial_values;

    public function setUp(): void {
        parent::setUp();
        $this->initial_values = [
            'logo' => get_config('core_admin')->logo,
            'logocompact' => get_config('core_admin')->logocompact,
            'favicon' => get_config('core_admin')->favicon,
        ];

        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn('');
        di::set(php::class, $php_mock);
    }

    public function test_no_change() {
        $logo = new logos(new logo_model());
        $changed = $logo->play();

        $this->assertFalse($changed);
        $this->assertEquals($this->initial_values['logo'], get_config('core_admin')->logo);
        $this->assertEquals($this->initial_values['logocompact'], get_config('core_admin')->logocompact);
        $this->assertEquals($this->initial_values['favicon'], get_config('core_admin')->favicon);
    }

    public function test_change_logo() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $logo = new logos(new logo_model($CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg'));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals($this->initial_values['logocompact'], get_config('core_admin')->logocompact);
        $this->assertEquals($this->initial_values['favicon'], get_config('core_admin')->favicon);
    }

    public function test_change_logocompact() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $logo = new logos(new logo_model(null, $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg'));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals($this->initial_values['logo'], get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals($this->initial_values['favicon'], get_config('core_admin')->favicon);
    }

    public function test_change_favicon() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $logo = new logos(new logo_model(null, null, $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg'));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals($this->initial_values['logo'], get_config('core_admin')->logo);
        $this->assertEquals($this->initial_values['logocompact'], get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);
    }

    private function set_all_to_circle() {
        global $CFG;

        $logo = new logos(new logo_model(
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg'
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);
    }

    public function test_change_all() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        $this->set_all_to_circle();  // this function already does all the checks
    }

    public function test_update_change_nothing() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $this->set_all_to_circle();

        $logo = new logos(new logo_model());
        $changed = $logo->play();

        $this->assertFalse($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);

        $logo = new logos(new logo_model(
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/circle.jpg'
        ));
        $changed = $logo->play();

        $this->assertFalse($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);
    }

    public function test_update_and_delete_existing_logo() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $this->set_all_to_circle();

        $logo = new logos(new logo_model(
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg'
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/square.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);

        $logo = new logos(new logo_model(
            ''
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);
    }

    public function test_update_and_delete_existing_logocompact() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $this->set_all_to_circle();

        $logo = new logos(new logo_model(
            null,
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg'
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/square.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);

        $logo = new logos(new logo_model(
            null,
            ''
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('', get_config('core_admin')->logocompact);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->favicon);
    }

    public function test_update_and_delete_existing_favicon() {
        if (!PHPUNIT_LONGTEST) {
            $this->markTestSkipped('PHPUNIT_LONGTEST is not defined');
        }

        global $CFG;

        $this->set_all_to_circle();

        $logo = new logos(new logo_model(
            null,
            null,
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg'
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/square.jpg', get_config('core_admin')->favicon);

        $logo = new logos(new logo_model(
            null,
            null,
            ''
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/circle.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('', get_config('core_admin')->favicon);
    }

    public function test_update_and_delete_existing_all() {
        global $CFG, $DB;
        $this->set_all_to_circle();

        $logo = new logos(new logo_model(
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg',
            $CFG->dirroot . '/local/declarativesetup/tests/resources/square.jpg'
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('/square.jpg', get_config('core_admin')->logo);
        $this->assertEquals('/square.jpg', get_config('core_admin')->logocompact);
        $this->assertEquals('/square.jpg', get_config('core_admin')->favicon);
        $this->assertEmpty($DB->get_fieldset_select('files', 'filename', 'filename = ?', ['circle.jpg']));


        $logo = new logos(new logo_model(
            '',
            '',
            ''
        ));
        $changed = $logo->play();

        $this->assertTrue($changed);
        $this->assertEquals('', get_config('core_admin')->logo);
        $this->assertEquals('', get_config('core_admin')->logocompact);
        $this->assertEquals('', get_config('core_admin')->favicon);

        // assert circle.jpg and square.jpg are deleted
        $this->assertEmpty($DB->get_fieldset_select('files', 'filename', 'filename = ? OR filename = ?', ['circle.jpg', 'square.jpg']));
    }

    public function test_update_invalid_path() {global $CFG;
        $logo = new logos(new logo_model('invalid_path'));
        $this->expectException(moodle_exception::class);
        $logo->play();
    }
}