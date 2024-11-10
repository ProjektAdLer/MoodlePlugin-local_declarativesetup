<?php

namespace local_adlersetup\local\play;

use core\di;
use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\php;
use local_adlersetup\local\play\models\web_services_model;
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

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $play->play();

        $this->assertEquals(true, $CFG->enablewebservices);
        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
    }

    public function test_enable_rest_protocol_forced(): void {
        global $CFG;
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

        $play->play();

        $this->assertEquals(true, $CFG->enablewebservices);  // TODO: invalid, config is not written
        $this->assertStringContainsString('webserviceprotocols', $capturedData);
        $this->assertStringContainsString('\'rest\'', $capturedData);
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

        $play->play();

        $this->assertEquals('rest', $CFG->webserviceprotocols);
        $this->assertStringNotContainsString('webserviceprotocols', $capturedData);
    }

    public function test_enable_second_protocol_hard() {
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

        $play->play();

        // get the line that contains the webserviceprotocols setting
        preg_match('/.*webserviceprotocols.*$/m', $capturedData, $matches);
        $this->assertStringContainsString('rest', $matches[0]);
        $this->assertStringContainsString('soap', $matches[0]);
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

        $play->play();

        // get the line that contains the webserviceprotocols setting
        preg_match('/.*webserviceprotocols.*$/m', $capturedData, $matches);
        $this->assertStringContainsString('rest', $matches[0]);
        $this->assertStringNotContainsString('soap', $matches[0]);
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

        $play->play();

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

        $play->play();

        $this->assertStringContainsString('rest', $CFG->webserviceprotocols);
        $this->assertStringNotContainsString('soap', $CFG->webserviceprotocols);
    }

    public function provide_update_setting_data() {
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
    public function test_enable_webservices(int $initally_enabled, int $desired_enabled) {
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

        $play->play();

        // only check file content if it is excepted to be changed. Otherwise, the mock handles the check
        if ($desired_enabled !== $initally_enabled) {
            if (in_array($desired_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
                $this->assertStringContainsString('enablewebservices = ' . ($desired_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false'), $capturedData);
            } else {
                $this->assertStringNotContainsString('enablewebservices', $capturedData);
            }
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

        $play->play();

        // only check file content if it is excepted to be changed. Otherwise, the mock handles the check
        if ($desired_enabled !== $initally_enabled) {
            if (in_array($desired_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
                $this->assertStringContainsString(MOODLE_OFFICIAL_MOBILE_SERVICE . ' = ' . ($desired_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false'), $capturedData);
            } else {
                $this->assertStringNotContainsString(MOODLE_OFFICIAL_MOBILE_SERVICE, $capturedData);
            }
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
