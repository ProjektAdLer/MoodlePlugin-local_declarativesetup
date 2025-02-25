<?php

namespace local_declarativesetup\local\play\web_services;

use core\di;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\php;
use local_declarativesetup\local\play\web_services\models\web_services_model;
use Mockery;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class web_services_test extends adler_testcase {
    private function get_sample_config_php(string $additional_line = ''): string {
        $config_php = <<<'EOD'
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
EOD;
        return $config_php . "\n" . $additional_line . "\n" . "require_once(__DIR__ . '/lib/setup.php'); // Do not edit";
    }

    public function test_enable_rest_protocol(): void {
        $php_mock = Mockery::mock(php::class)->makePartial();
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;');
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        set_config('webserviceprotocols', '');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('rest', get_config('', 'webserviceprotocols'));
    }


    public function test_enable_rest_protocol_already_enabled(): void {
        $php_mock = Mockery::mock(php::class)->makePartial();
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;');
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        set_config('webserviceprotocols', 'rest');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertStringContainsString('rest', get_config('', 'webserviceprotocols'));
    }

    public function test_enable_rest_protocol_forced(): void {
        $capturedData = $this->get_sample_config_php();

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
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
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;' . "\n" . '$CFG->webserviceprotocols = \'rest\';');

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $php_mock
            ->shouldReceive('file_get_contents')
            ->andReturnUsing(function () use (&$capturedData) {
                return $capturedData;
            });
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
        $capturedData = $this->get_sample_config_php('$CFG->webserviceprotocols = \'rest\';');

        $php_mock = Mockery::mock(php::class)->makePartial();
        $php_mock->shouldReceive('file_put_contents')
            ->withArgs(function ($filename, $data) use (&$capturedData) {
                // Capture the arguments
                $capturedData = $data;
                return true; // Return true to indicate the arguments match
            });
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        di::set(php::class, $php_mock);

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            [],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('rest', get_config('', 'webserviceprotocols'));
        $this->assertStringNotContainsString('webserviceprotocols', $capturedData);
        // verify config.php longer than 3 lines
        $this->assertGreaterThan(3, substr_count($capturedData, "\n"));
    }

    public function test_enable_second_protocol_hard_to_hard() {
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;' . "\n" . '$CFG->webserviceprotocols = \'rest\';');

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

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
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;');

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

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
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;' . "\n" . '$CFG->webserviceprotocols = \'rest,soap\';');

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        $php_mock->shouldReceive('file_put_contents')->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data;
            return true; // Return true to indicate the arguments match
        });
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

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
        $php_mock = Mockery::mock(php::class)->makePartial();
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;');
        $php_mock->shouldNotReceive('file_put_contents');
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        di::set(php::class, $php_mock);
        set_config('webserviceprotocols', 'rest');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest', 'soap'],
            [''],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('rest', get_config('', 'webserviceprotocols'));
        $this->assertStringContainsString('soap', get_config('', 'webserviceprotocols'));
    }


    public function test_disable_second_protocol_soft() {
        $php_mock = Mockery::mock(php::class)->makePartial();
        $capturedData = $this->get_sample_config_php('$CFG->enablewebservices = true;');
        $php_mock->shouldNotReceive('file_put_contents');
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        di::set(php::class, $php_mock);
        set_config('webserviceprotocols', 'rest,soap');

        $play = new web_services(new web_services_model(
            web_services_model::STATE_ENABLED,
            ['rest'],
            ['soap'],
            web_services_model::STATE_UNSET));

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('rest', get_config('', 'webserviceprotocols'));
        $this->assertStringNotContainsString('soap', get_config('', 'webserviceprotocols'));
    }

    public static function provide_update_setting_data(): array {
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
        if (in_array($initally_enabled, [web_services_model::STATE_ENABLED, web_services_model::STATE_DISABLED])) {
            $capturedData = $this->get_sample_config_php("\$CFG->enablewebservices = " . ($initally_enabled === web_services_model::STATE_ENABLED ? 'true' : 'false') . ";");
        } else {
            $capturedData = $this->get_sample_config_php();
        }

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
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

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
        global $DB;
        $mobile_service_id = $DB->get_record('external_services', ['shortname' => MOODLE_OFFICIAL_MOBILE_SERVICE], '*', MUST_EXIST)->id;
        if ($initally_enabled === web_services_model::STATE_ENABLED) {
            $DB->update_record('external_services', (object)['id' => $mobile_service_id, 'enabled' => 1]);
        }
        if ($initally_enabled === web_services_model::STATE_DISABLED || $initally_enabled === web_services_model::STATE_UNSET) {
            $DB->update_record('external_services', (object)['id' => $mobile_service_id, 'enabled' => 0]);
        }

        $capturedData = $this->get_sample_config_php();

        $php_mock = Mockery::mock(php::class)->makePartial();
        di::set(php::class, $php_mock);
        //configure file write access
        $php_mock->shouldNotReceive('file_put_contents');
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

        $play = new web_services(new web_services_model(
            web_services_model::STATE_UNSET,
            [],
            [],
            $desired_enabled));

        $changed = $play->play();

        if ($desired_enabled === web_services_model::STATE_UNSET ||  // if desired is unset means no change
            $desired_enabled === $initally_enabled ||  // if desired is same as initial state means no change
            ($initally_enabled === web_services_model::STATE_UNSET && $desired_enabled === web_services_model::STATE_DISABLED)  // in this test initial unset is same as initial disabled
        ) {
            $this->assertFalse($changed);
        } else {
            $this->assertTrue($changed);
        }

        if ($desired_enabled === web_services_model::STATE_ENABLED) {
            $this->assertEquals('1', $DB->get_record('external_services', ['id' => $mobile_service_id])->enabled);
        }
        if ($desired_enabled === web_services_model::STATE_DISABLED) {
            $this->assertEquals('0', $DB->get_record('external_services', ['id' => $mobile_service_id])->enabled);
        }
    }
}
