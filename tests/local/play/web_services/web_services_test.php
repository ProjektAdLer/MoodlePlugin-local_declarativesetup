<?php

namespace local_adlersetup\local\play\web_services;

use core\di;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\php;
use local_adlersetup\local\play\web_services\models\web_services_model;
use Mockery;

global $CFG;
require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');

class web_services_test extends adler_testcase {
    public function test_enable_rest_protocol(): void {
        global $CFG;

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        $CFG->config_php_settings = ['enablewebservices' => true];
        set_config('webserviceprotocols', '');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals(true, $CFG->enablewebservices);
        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
    }


    public function test_enable_rest_protocol_already_enabled(): void {
        global $CFG;

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        $CFG->config_php_settings = ['enablewebservices' => true];
        set_config('webserviceprotocols', 'rest');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertEquals(true, $CFG->enablewebservices);
        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
    }

    public function test_enable_rest_protocol_forced(): void {
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        di::set(php::class, $php_mock);

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            ['*'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('enablewebservices', $capturedData);
        $this->assertStringContainsString('webserviceprotocols', $capturedData);
        $this->assertStringContainsString('\'rest\'', $capturedData);
        // verify only one line with webserviceprotocols and config.php longer than 3 lines
        $this->assertEquals(1, substr_count($capturedData, 'webserviceprotocols'));
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_disable_rest_protocol_forced_to_soft(): void {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $php_mock
            ->shouldReceive('file_get_contents')
            ->andReturn($this->get_sample_config_php() . "\n\$CFG->enablewebservices = true;\n\$CFG->webserviceprotocols = 'rest';\n");
        $CFG->config_php_settings = ['enablewebservices' => true, 'webserviceprotocols' => 'rest'];
        di::set(php::class, $php_mock);
        set_config('webserviceprotocols', 'rest');
        set_config('enablewebservices', true);

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [''],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('enablewebservices', $capturedData);
        $this->assertStringNotContainsString('webserviceprotocols', $capturedData);
        $this->assertStringNotContainsString('\'rest\'', $capturedData);
        // verify only one line with webserviceprotocols and config.php longer than 3 lines
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_enable_rest_protocol_back_to_soft() {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $example_config_php = $this->get_sample_config_php() . "\n\$CFG->webserviceprotocols = rest;\n";
        $php_mock->shouldReceive('file_get_contents')->andReturn($example_config_php);
        di::set(php::class, $php_mock);
        $CFG->config_php_settings = ['webserviceprotocols' => 'rest'];

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('rest', $CFG->webserviceprotocols);
        $this->assertStringNotContainsString('webserviceprotocols', $capturedData);
        // verify config.php longer than 3 lines
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_enable_second_protocol_hard_to_hard() {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php() . "\n\$CFG->enablewebservices = true;\n\$CFG->webserviceprotocols = 'rest';\n");
        $CFG->config_php_settings = ['enablewebservices' => true, 'webserviceprotocols' => 'rest'];

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest', 'soap'],
            ['*'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        // get the line that contains the webserviceprotocols setting
        preg_match('/.*webserviceprotocols.*$/m', $capturedData, $matches);
        $this->assertStringContainsString('rest', $matches[0]);
        $this->assertStringContainsString('soap', $matches[0]);
        // verify only one line with webserviceprotocols and config.php longer than 3 lines
        $this->assertEquals(1, substr_count($capturedData, 'webserviceprotocols'));
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_enable_second_protocol_soft_to_hard() {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php() . "\n\$CFG->enablewebservices = true;\n");
        $CFG->config_php_settings = ['enablewebservices' => true];

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest', 'soap'],
            ['*'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        // get the line that contains the webserviceprotocols setting
        preg_match('/.*webserviceprotocols.*$/m', $capturedData, $matches);
        $this->assertStringContainsString('rest', $matches[0]);
        $this->assertStringContainsString('soap', $matches[0]);
        // verify only one line with webserviceprotocols and config.php longer than 3 lines
        $this->assertEquals(1, substr_count($capturedData, 'webserviceprotocols'));
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_disable_second_protocol_hard() {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php() . "\n\$CFG->enablewebservices = true;\n\$CFG->webserviceprotocols = 'rest,soap';\n");
        $CFG->config_php_settings = ['enablewebservices' => true, 'webserviceprotocols' => 'rest,soap'];

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            ['*'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        // get the line that contains the webserviceprotocols setting
        preg_match('/.*webserviceprotocols.*$/m', $capturedData, $matches);
        $this->assertStringContainsString('rest', $matches[0]);
        $this->assertStringNotContainsString('soap', $matches[0]);
        // verify only one line with webserviceprotocols and config.php longer than 3 lines
        $this->assertEquals(1, substr_count($capturedData, 'webserviceprotocols'));
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }


    public function test_enable_second_protocol_soft() {
        global $CFG;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldNotReceive('file_put_contents');
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $CFG->config_php_settings = ['enablewebservices' => true];
        set_config('webserviceprotocols', 'rest');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest', 'soap'],
            [''],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
        $this->assertStringContainsString('soap', $CFG->webserviceprotocols);
    }


    public function test_disable_second_protocol_soft() {
        global $CFG;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldNotReceive('file_put_contents');
        $php_mock->shouldReceive('file_get_contents')->andReturn($this->get_sample_config_php());
        $CFG->config_php_settings = ['enablewebservices' => true];
        set_config('webserviceprotocols', 'rest,soap');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            ['soap'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
        $this->assertStringNotContainsString('soap', $CFG->webserviceprotocols);
    }

    public function provide_update_setting_data(): array {
        return [
            'enable -> enable' => [
                'initally_enabled' => web_services_model::STATE_ENABLED,
                'desired_enabled' => web_services_model::STATE_ENABLED,
            ],
            'enable -> disable' => [
                'initally_enabled' => web_services_model::STATE_ENABLED,
                'desired_enabled' => web_services_model::STATE_DISABLED,
            ],
            'disable -> enable' => [
                'initally_enabled' => web_services_model::STATE_DISABLED,
                'desired_enabled' => web_services_model::STATE_ENABLED,
            ],
            'disable -> disable' => [
                'initally_enabled' => web_services_model::STATE_DISABLED,
                'desired_enabled' => web_services_model::STATE_DISABLED,
            ],
            'unset -> enable' => [
                'initally_enabled' => web_services_model::STATE_UNSET,
                'desired_enabled' => web_services_model::STATE_ENABLED,
            ],
            'unset -> disable' => [
                'initally_enabled' => web_services_model::STATE_UNSET,
                'desired_enabled' => web_services_model::STATE_DISABLED,
            ],
            'disabled -> unset' => [
                'initally_enabled' => web_services_model::STATE_DISABLED,
                'desired_enabled' => web_services_model::STATE_UNSET,
            ],
            'enabled -> unset' => [
                'initally_enabled' => web_services_model::STATE_ENABLED,
                'desired_enabled' => web_services_model::STATE_UNSET,
            ],
            'unset -> unset' => [
                'initally_enabled' => web_services_model::STATE_UNSET,
                'desired_enabled' => web_services_model::STATE_UNSET,
            ],
        ];
    }

    /**
     * @dataProvider provide_update_setting_data
     */
    public function test_switch_webservices(int $initally_enabled, int $desired_enabled) {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        //configure file write access
        if ($initally_enabled !== $desired_enabled) {
            $php_mock->shouldReceive('file_put_contents')
                ->withArgs(function ($filename, $data) use (&$capturedData) {
                    // Capture the arguments
                    $capturedData = $data;
                    return true; // Return true to indicate the arguments match
                });
        } else {
            $php_mock->shouldNotReceive('file_put_contents');
        }
        // configure file read access and moodle CFG object
        if (in_array($initally_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
            $example_config_php = $this->get_sample_config_php() . "\n\$CFG->enablewebservices = " . ($initally_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false') . ";\n";
            $CFG->config_php_settings = ['enablewebservices' => $initally_enabled === web_services_model::STATE_ENABLED];
        } else {
            $example_config_php = $this->get_sample_config_php();
            $CFG->config_php_settings = [];
        }
        $php_mock->shouldReceive('file_get_contents')->andReturn($example_config_php);

        $play = new web_services(new web_services_model(
            $desired_enabled,
            [],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        // only check file content if it is excepted to be changed. Otherwise, the mock handles the check
        if ($desired_enabled !== $initally_enabled) {
            $this->assertTrue($changed);
            // check file content to not contain more than expected number of enablewebservices. Can only test
            // "simulated config.php" in case it was modified.
            $this->assertEquals(
                $desired_enabled === web_services_model::STATE_UNSET ? 0 : 1,
                substr_count($capturedData, 'enablewebservices'),
                'enablewebservices found more often than expected'
            );
            // check config.php length longer than 3 lines. There is no scenario when it could have one line or less
            // and be valid. Can only test "simulated config.php" in case it was modified.
            $this->assertGreaterThan(3, substr_count($capturedData, "\n"), 'config.php has less than 4 lines');
            if (in_array($desired_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
                $this->assertStringContainsString('enablewebservices = ' . ($desired_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false'), $capturedData);
            } else {
                $this->assertStringNotContainsString('enablewebservices', $capturedData);
            }
        } else {
            $this->assertFalse($changed);
        }
    }

    /**
     * @dataProvider provide_update_setting_data
     */
    public function test_enable_mobile_service(int $initally_enabled, int $desired_enabled) {
        global $CFG;
        $capturedData = null;

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        //configure file write access
        if ($initally_enabled !== $desired_enabled) {
            $php_mock->shouldReceive('file_put_contents')
                ->withArgs(function ($filename, $data) use (&$capturedData) {
                    // Capture the arguments
                    $capturedData = $data;
                    return true; // Return true to indicate the arguments match
                });
        } else {
            $php_mock->shouldNotReceive('file_put_contents');
        }
        // configure file read access and moodle CFG object
        if (in_array($initally_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
            $example_config_php = $this->get_sample_config_php() . "\n\$CFG->" . MOODLE_OFFICIAL_MOBILE_SERVICE . " = " . ($initally_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false') . ";\n";
            $CFG->config_php_settings = [MOODLE_OFFICIAL_MOBILE_SERVICE => $initally_enabled === web_services_model::STATE_ENABLED];
        } else {
            $example_config_php = $this->get_sample_config_php();
            $CFG->config_php_settings = [];
        }
        $php_mock->shouldReceive('file_get_contents')->andReturn($example_config_php);

        $play = new web_services(new web_services_model(
            web_services_model::STATE_UNSET,
            [],
            [],
            $desired_enabled));

        $changed = $play->play();

        // only check file content if it is excepted to be changed. Otherwise, the mock handles the check
        if ($desired_enabled !== $initally_enabled) {
            $this->assertTrue($changed);
            // check file content to not contain more than expected number of enablewebservices. Can only test
            // "simulated config.php" in case it was modified.
            $this->assertEquals(
                $desired_enabled === web_services_model::STATE_UNSET ? 0 : 1,
                substr_count($capturedData, MOODLE_OFFICIAL_MOBILE_SERVICE),
                'enablewebservices found more often than expected'
            );
            // check config.php length longer than 1 line. There is no scenario when it could have one line or less
            // and be valid. Can only test "simulated config.php" in case it was modified.
            $this->assertGreaterThan(1, substr_count($capturedData, "\n"), 'config.php has less than 2 lines');
            if (in_array($desired_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
                $this->assertStringContainsString(MOODLE_OFFICIAL_MOBILE_SERVICE . ' = ' . ($desired_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false'), $capturedData);
            } else {
                $this->assertStringNotContainsString(MOODLE_OFFICIAL_MOBILE_SERVICE, $capturedData);
            }
        } else {
            $this->assertFalse($changed);
        }
    }

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
require_once(__DIR__ . '/lib/setup.php'); // Do not edit
EOD;
    }
}