<?php

namespace local_declarativesetup\local\play\config;

global $CFG;

use core\di;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\php;
use local_declarativesetup\local\play\config\models\array_config_model;
use local_declarativesetup\local\play\config\models\simple_config_model;
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
            new simple_config_model('new_config', $value, forced: !$soft),
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
            $play = new config([new simple_config_model('soft_config', 'somevalue')]);
        } else {
            $play = new config(new simple_config_model('soft_config', 'somevalue'));
        }

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('somevalue', get_config('', 'soft_config'));
    }

    public function test_add_two_new_soft_settings() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php();
        // We only read from config.php, so file_put_contents shouldn't be called.
        $php_mock->shouldReceive('file_get_contents')
            ->atLeast()->once()
            ->andReturnUsing(function () use (&$capturedData) {
                return $capturedData;
            });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        // Ensure both settings do not exist initially
        $this->assertFalse(get_config('', 'soft_config_a'));
        $this->assertFalse(get_config('', 'soft_config_b'));

        // Create a config object with two new soft settings
        $play = new config([
            new simple_config_model('soft_config_a', 'valueA'),
            new simple_config_model('soft_config_b', 'valueB')
        ]);

        $changed = $play->play();

        // Check that the new settings were added
        $this->assertTrue($changed);
        $this->assertEquals('valueA', get_config('', 'soft_config_a'));
        $this->assertEquals('valueB', get_config('', 'soft_config_b'));
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
            new simple_config_model('new_forced_config', 'somevalue', true),
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
            new simple_config_model('soft_config', 'someothervalue'),
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
            new simple_config_model('forced_config', 'someothervalue', true),
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
            new simple_config_model(
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
            new simple_config_model(
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
            new simple_config_model('soft_config', null),
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
            new simple_config_model('forced_config', null, true),
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
            $config_models[] = new simple_config_model('soft_config', 'somevalue');
        } else if ($current === 'forced' && $new_is === 'equal') {
            $config_models[] = new simple_config_model('forced_config', 'somevalue', true);
        }
        $play = new config($config_models);

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertEquals('somevalue', get_config('', 'soft_config'));
    }

    public function test_array_config_add_forced() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        // For forced config, file_put_contents should be called
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true;
        });
        di::set(php::class, $php_mock);

        // Make sure the setting doesn't exist initially
        $this->assertFalse(get_config('', 'new_array_config'));

        $play = new config([
            new array_config_model(
                'new_array_config',
                values_present: ['value1', 'value4'],
                values_absent: [],
                forced: true
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        // Check that it was added to config.php
        $this->assertStringContainsString('$CFG->new_array_config = \'value1,value4\';', $capturedData);
    }

    public function test_array_config_add_soft() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        // No file_put_contents should be called for soft config
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        $play = new config([
            new array_config_model(
                'new_array_config',
                values_present: ['value1', 'value4'],
                values_absent: [],
                forced: false
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        // Check that it was added as a soft config (to the database)
        $this->assertEquals('value1,value4', get_config('', 'new_array_config'));
        // Verify it was not added to config.php
        $this->assertStringNotContainsString('$CFG->new_array_config', $capturedData);
    }

    public function test_array_config_forced_with_wildcard() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

        // Should modify the config.php file with the new configuration
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true;
        });

        di::set(php::class, $php_mock);

        // Set initial array value
        set_config('array_config', 'value1,value2,value3');

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value4'],
                values_absent: ['*'],
                forced: true
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('$CFG->array_config = \'value1,value4\';', $capturedData);
    }

    public function test_array_config_soft_with_wildcard() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        // Set initial array value
        set_config('array_config', 'value1,value2,value3');

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value4'],
                values_absent: ['*'],
                forced: false
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertEquals('value1,value4', get_config('', 'array_config'));
    }

    public function test_array_config_soft_no_change() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldNotReceive('file_put_contents');
        di::set(php::class, $php_mock);

        // Set initial array value
        set_config('array_config', 'value1,value2');

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value2'],
                values_absent: [],
                forced: false
            ),
        ]);

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertEquals('value1,value2', get_config('', 'array_config'));
    }

    public function test_array_config_forced_no_change() {
        $php_mock = Mockery::mock(php::class);

        // Create config.php with the forced array config already present
        $initialConfig = $this->get_sample_config_php() . "\n\$CFG->array_config = 'value1,value2';";
        $capturedData = $initialConfig;

        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

        // Should not modify the config.php file since no changes are needed
        $php_mock->shouldNotReceive('file_put_contents');

        di::set(php::class, $php_mock);

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value2'],
                values_absent: [],
                forced: true
            ),
        ]);

        $changed = $play->play();

        $this->assertFalse($changed);
        $this->assertStringContainsString('$CFG->array_config = \'value1,value2\';', $capturedData);
    }

    public function test_array_config_change_to_forced_state() {
        $php_mock = Mockery::mock(php::class);
        $capturedData = $this->get_sample_config_php(); // Initial content
        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });
        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true;
        });
        di::set(php::class, $php_mock);

        // Set initial array value as soft config
        set_config('array_config', 'value1,value2');

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value2'],
                values_absent: [],
                forced: true
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringContainsString('$CFG->array_config = \'value1,value2\';', $capturedData);
    }

    public function test_array_config_change_to_soft_state() {
        $php_mock = Mockery::mock(php::class);

        // Create config.php with the forced array config already present
        $initialConfig = $this->get_sample_config_php() . "\n\$CFG->array_config = 'value1,value2';";
        $capturedData = $initialConfig;

        $php_mock->shouldReceive('file_get_contents')->atLeast()->once()->andReturnUsing(function () use (&$capturedData) {
            return $capturedData;
        });

        $php_mock->shouldReceive('file_put_contents')->once()->withArgs(function ($filename, $data) use (&$capturedData) {
            $capturedData = $data; // Store the written data
            return true;
        });

        di::set(php::class, $php_mock);

        $play = new config([
            new array_config_model(
                'array_config',
                values_present: ['value1', 'value2'],
                values_absent: [],
                forced: false
            ),
        ]);

        $changed = $play->play();

        $this->assertTrue($changed);
        $this->assertStringNotContainsString('$CFG->array_config', $capturedData);
        $this->assertEquals('value1,value2', get_config('', 'array_config'));
    }
}