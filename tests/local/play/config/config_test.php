<?php

namespace local_declarativesetup\local\play\config;

global $CFG;

use core\di;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\php;
use local_declarativesetup\local\play\config\models\config_model;
use Mockery;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class config_test extends adler_testcase {
    private function get_sample_config_php(): string {
        return <<<'EOD'
<?php  // Moodle configuration file
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->forced_config    = 'somevalue';
require_once(__DIR__ . '/lib/setup.php'); // Do not edit
EOD;
    }

    public static function provide_test_add_soft_setting() {
        return [
            'array' => [
                'array' => true,
            ],
            'single' => [
                'array' => false,
            ],
        ];
    }

    public static function provide_data_of_different_types() {
        return [
            'string soft' => [
                'value' => 'somevalue',
                'soft' => true,
            ],
            'string forced' => [
                'value' => 'somevalue',
                'soft' => false,
            ],
            'int soft' => [
                'value' => 42,
                'soft' => true,
            ],
            'int forced' => [
                'value' => 42,
                'soft' => false,
            ],
            'bool soft' => [
                'value' => true,
                'soft' => true,
            ],
            'bool forced' => [
                'value' => true,
                'soft' => false,
            ],
        ];
    }

    /**
     * @dataProvider provide_data_of_different_types
     */
    public function test_add_and_get_setting($value, bool $soft) {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        if ($soft) {
            $php_mock->shouldNotReceive('file_put_contents');
        } else {
            $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
                $capturedData = $data; // Store the written data
                return true; // Return true to indicate the arguments match
            });
        }
        di::set(php::class, $php_mock);

        $play = new config([
            new config_model('new_config', $value, forced: !$soft),
        ]);
        $changed = $play->play();

        $this->assertTrue($changed);
        if ($soft) {
            $this->assertEquals($value, get_config('', 'new_config'));
        } else {
            $this->assertStringContainsString('$CFG->new_config = ' . var_export($value, true) . ';', $capturedData);
        }
    }

    /**
     * @dataProvider provide_test_add_soft_setting
     */
    public function test_add_soft_setting(bool $array) {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);


        $this->assertFalse(get_config('', 'soft_config'));

        if ($array) {
            $play = new config([new config_model('soft_config', 'somevalue')]);
        } else {
            $play = new config(new config_model('soft_config', 'somevalue'));
        }

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('somevalue', get_config('', 'soft_config'));
    }

    public function test_add_forced_setting() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $play = new config([
            new config_model('new_forced_config', 'somevalue', true),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('$CFG->new_forced_config = \'somevalue\';', $capturedData);
    }

    public function test_update_soft_setting() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        set_config('soft_config', 'somevalue');

        $play = new config([
            new config_model('soft_config', 'someothervalue'),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('someothervalue', get_config('', 'soft_config'));
    }

    public function test_update_forced_setting() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $play = new config([
            new config_model('forced_config', 'someothervalue', true),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('$CFG->forced_config = \'someothervalue\';', $capturedData);
    }

    public static function provide_state_change_data() {
        return [
            'same value' => [
                'same_value' => true,
            ],
            'different value' => [
                'same_value' => false,
            ],
        ];
    }

    /**
     * @dataProvider provide_state_change_data
     */
    public function test_update_forced_to_soft_setting(bool $same_value) {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);

        $play = new config([
            new config_model(
                'forced_config'
                , $same_value ? 'somevalue' : 'someothervalue'
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringNotContainsString('$CFG->forced_config', $capturedData);
        $this->assertEquals(
            $same_value ? 'somevalue' : 'someothervalue',
            get_config('', 'forced_config')
        );
    }

    /**
     * @dataProvider provide_state_change_data
     */
    public function test_update_soft_to_forced_setting(bool $same_value) {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);
        set_config('soft_config', 'somevalue');

        $play = new config([
            new config_model(
                'soft_config'
                , $same_value ? 'somevalue' : 'someothervalue'
                , true
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('$CFG->soft_config = \'' . ($same_value ? 'somevalue' : 'someothervalue') . '\';', $capturedData);
    }

    public function test_delete_soft_setting() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);
        set_config('soft_config', 'somevalue');

        $play = new config([
            new config_model('soft_config', null),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertFalse(get_config('', 'soft_config'));
    }

    public function test_delete_forced_setting() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true; // Return true to indicate the arguments match
        });
        di::set(php::class, $php_mock);
        set_config('forced_config', 'somevalue');

        $play = new config([
            new config_model('forced_config', null, true),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringNotContainsString('$CFG->forced_config', $capturedData);
    }

    public static function provide_test_no_change_data() {
        return [
            'current: soft, new: equal' => [
                'current' => 'soft',
                'new_is' => 'equal'
            ],
            'current: soft, new: undefined' => [
                'current' => 'soft',
                'new_is' => 'undefined'
            ],
            'current: forced, new: equal' => [
                'current' => 'forced',
                'new_is' => 'equal'
            ],
            'current: forced, new: undefined' => [
                'current' => 'forced',
                'new_is' => 'undefined'
            ],
            'current: undefined, new: undefined' => [
                'current' => 'undefined',
                'new_is' => 'undefined'
            ],
        ];
    }

    /**
     * @dataProvider provide_test_no_change_data
     */
    public function test_no_change(string $current, string $new_is) {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php();
        $php_mock->shouldReceive('file_get_contents')->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        set_config('soft_config', 'somevalue');

        $config_models = [];
        if ($current === 'soft' && $new_is === 'equal') {
            $config_models[] = new config_model('soft_config', 'somevalue');
        } else if ($current === 'forced' && $new_is === 'equal') {
            $config_models[] = new config_model('forced_config', 'somevalue', true);
        }
        $play = new config($config_models);

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertEquals('somevalue', get_config('', 'soft_config'));
    }
}