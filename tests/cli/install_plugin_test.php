<?php

global $CFG;

use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\exceptions\exit_exception;
use local_declarativesetup\local\play\install_plugins\install_plugins;
use local_declarativesetup\local\play\install_plugins\models\install_plugins_model;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class install_plugin_test extends adler_testcase {
    public function test_missing_parameters() {
        // Capture the output of the script.
        $this->expectException(exit_exception::class);

        // Run the script without required parameters.
        $_SERVER['argv'] = ['install_plugin.php'];

        require __DIR__ . '/../../cli/install_plugin.php';
    }

    /**
     * @runInSeparateProcess
     */
    public function test_plugin_installation() {
        // Mock the install_plugins_model class.
        $installPluginsModelMock = Mockery::mock('overload:' . install_plugins_model::class);
        $installPluginsModelMock->shouldReceive('__construct')->once();

        // Mock the install_plugins class.
        $installPluginsMock = Mockery::mock('overload:' . install_plugins::class);
        $installPluginsMock->shouldReceive('__construct')->once();
        $installPluginsMock->shouldReceive('play')->once()->andReturn(true);

//        $this->expectOutputString("Plugin installed or updated.\n");  // does not work with beStrictAboutOutputDuringTests set to false

        // Run the script with valid parameters.
        $_SERVER['argv'] = [
            'install_plugin.php',
            '--github-project=ProjektAdler/MoodlePluginModAdleradaptivity',
            '--version=1.0.0',
            '--moodle-name=mod_adleradaptivity'
        ];

        require __DIR__ . '/../../cli/install_plugin.php';
    }
}