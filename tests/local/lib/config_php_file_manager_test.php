<?php

namespace local_declarativesetup\local\lib;

use core\di;
use Exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\exceptions\setting_does_not_exist;
use local_declarativesetup\local\exceptions\setting_exists_multiple_times;
use local_declarativesetup\local\php;
use Mockery;
use moodle_exception;
use TypeError;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class config_php_file_manager_test extends adler_testcase {
    private function get_sample_config_php(): string {
        return <<<'EOD'
<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'bitnami_moodle';
$CFG->dbuser    = 'bitnami_moodle';
$CFG->dbpass    = 'c';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3312,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);
$CFG->examplewithcomment = 'test'; // This is a comment
$CFG->examplebool = true;
$CFG->exampleint = 1;
$CFG->forced_plugin_settings['local_example']['test'] = 'abc';
$CFG->exampleconstant = MOODLE_OFFICIAL_MOBILE_SERVICE;
require_once(__DIR__ . '/lib/setup.php'); // Do not edit
EOD;
    }


    public function test_get_setting_value(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->assertEquals('mariadb', $config_manager->get_setting_value('dbtype'));
        $this->assertTrue($config_manager->get_setting_value('examplebool'));
        $this->assertEquals(1, $config_manager->get_setting_value('exampleint'));
        $this->assertEquals('test', $config_manager->get_setting_value('examplewithcomment'));
        $this->assertEquals('abc', $config_manager->get_setting_value('test', 'local_example'));
    }

    public function test_get_settings_value_not_allowed_dboptions(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(Exception::class);
        $config_manager->get_setting_value('dboptions');
    }

    public function test_get_settings_value_not_allowed_exampleconstant(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(TypeError::class);
        $config_manager->get_setting_value('exampleconstant');
    }

    public function test_get_settings_value_not_allowed_notexisting(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(setting_does_not_exist::class);
        $config_manager->get_setting_value('notexisting');
    }

    public function test_get_settings_value_duplicate(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn(
            $this->get_sample_config_php() . "\n" . '$CFG->dbtype = "mysql";' . "\n"
        );
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(setting_exists_multiple_times::class);
        $config_manager->get_setting_value('dbtype');
    }

    public function test_get_settings_failed_to_read_file(): void {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn(false);
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(moodle_exception::class);
        $config_manager->get_setting_value('dbtype');
    }

    public function provide_test_setting_exists(): array {
        return [
            ['dbtype', null, true],
            ['dbtype', 'local_example', false],
            ['examplebool', null, true],
            ['examplebool', 'local_example', false],
            ['exampleint', null, true],
            ['exampleint', 'local_example', false],
            ['examplewithcomment', null, true],
            ['examplewithcomment', 'local_example', false],
            ['test', 'local_example', true],
            ['test', null, false],
            ['notexisting', null, false],
        ];
    }

    /**
     * @dataProvider provide_test_setting_exists
     */
    public function test_setting_exists($key, $plugin, $exists) {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->assertEquals($exists, $config_manager->setting_exists($key, $plugin));
    }

    public function test_remove_setting() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            // Capture the arguments
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $config_manager->remove_setting('dbtype');
        $this->assertStringNotContainsString('dbtype', $capturedData);
    }

    public function test_remove_setting_not_existing() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(setting_does_not_exist::class);
        $config_manager->remove_setting('notexisting');
    }

    public function test_remove_setting_unsupported() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(Exception::class);
        $config_manager->remove_setting('dboptions');
    }


    public function provide_test_add_setting(): array {
        return [
            'string setting' => ['newsetting', 'newvalue', '$CFG->newsetting = \'newvalue\';'],
            'boolean setting' => ['boolsetting', true, '$CFG->boolsetting = true;'],
            'integer setting' => ['intsetting', 123, '$CFG->intsetting = 123;'],
        ];
    }

    /**
     * @dataProvider provide_test_add_setting
     */
    public function test_add_setting($key, $value, $expected) {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            // Capture the arguments
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $config_manager->set_setting($key, $value);
        $this->assertStringContainsString($expected, $capturedData);

        // Verify the new setting is added before the line "require_once(__DIR__ . '/lib/setup.php');"
        $setup_pos = strpos($capturedData, "require_once(__DIR__ . '/lib/setup.php');");
        $setting_pos = strpos($capturedData, $expected);
        $this->assertLessThan($setup_pos, $setting_pos, 'The new setting should be added before the line "require_once(__DIR__ . \'/lib/setup.php\');"');
    }

    public function test_add_setting_with_array() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(TypeError::class);
        $config_manager->set_setting('arraysetting', ['value1', 'value2']);
    }

    public function test_add_setting_plugin() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            // Capture the arguments
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $config_manager->set_setting('newsetting', 'newvalue', 'local_example');
        $this->assertStringContainsString('$CFG->forced_plugin_settings[\'local_example\'][\'newsetting\'] = \'newvalue\';', $capturedData);
    }

    public function test_update_value() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            // Capture the arguments
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $config_manager->set_setting('dbtype', 'mysql');
        $expected = '$CFG->dbtype = \'mysql\';';
        $this->assertStringContainsString($expected, $capturedData);

        // Verify the new setting is before the line "require_once(__DIR__ . '/lib/setup.php');"
        $setup_pos = strpos($capturedData, "require_once(__DIR__ . '/lib/setup.php');");
        $setting_pos = strpos($capturedData, $expected);
        $this->assertLessThan($setup_pos, $setting_pos, 'The new setting should be added before the line "require_once(__DIR__ . \'/lib/setup.php\');"');
    }

    public function test_update_existing_array_value() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldNotReceive('file_get_contents');
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $this->expectException(TypeError::class);
        $config_manager->set_setting('dboptions', ['dbpersist' => 1, 'dbport' => 3312]);
    }

    public function test_update_plugin_setting() {
        $php_mock = Mockery::mock(php::class);
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            // Capture the arguments
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $config_manager = new config_php_file_manager();
        $config_manager->set_setting('test', 'def', 'local_example');
        $this->assertStringContainsString('$CFG->forced_plugin_settings[\'local_example\'][\'test\'] = \'def\';', $capturedData);
    }
}