<?php

global $CFG;

use core\plugin_manager;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\moodle_core;
use core\di;
use core\plugininfo\base;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class version_cli_test extends adler_testcase {
    public function test_version_output() {
        $plugin_info_mock = MOckery::mock(base::class);
        $plugin_info_mock->shouldReceive('load_disk_version')->once();
        $plugin_info_mock->versiondisk = '2024121200';
        $plugin_manager_mock = Mockery::mock(plugin_manager::class);
        $plugin_manager_mock->shouldReceive('get_plugin_info')->once()->andReturn($plugin_info_mock);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('core_plugin_manager_instance')->once()->andReturn($plugin_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        // Capture the output of the script.
        $this->expectOutputString("2024121200\n");

        // Run the script without the --release option.
        $_SERVER['argv'] = ['version.php'];

        require __DIR__ . '/../../cli/version.php';
    }

    public function test_release_output() {
        $plugin_info_mock = Mockery::mock(base::class);
        $plugin_info_mock->shouldReceive('load_disk_version')->once();
        $plugin_info_mock->release = '1.0.0';
        $plugin_manager_mock = Mockery::mock(plugin_manager::class);
        $plugin_manager_mock->shouldReceive('get_plugin_info')->once()->andReturn($plugin_info_mock);
        $moodle_core_mock = Mockery::mock(moodle_core::class);
        $moodle_core_mock->shouldReceive('core_plugin_manager_instance')->once()->andReturn($plugin_manager_mock);
        di::set(moodle_core::class, $moodle_core_mock);

        // Capture the output of the script.
        $this->expectOutputString("1.0.0\n");

        // Run the script with the --release option.
        $_SERVER['argv'] = ['version.php', '--release'];

        require __DIR__ . '/../../cli/version.php';
    }
}