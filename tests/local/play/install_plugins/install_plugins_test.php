<?php

namespace local_declarativesetup\local\play\install_plugins;

use core\plugin_manager;
use ddl_exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\install_plugins\models\install_plugins_model;
use Symfony\Component\Process\Process;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class install_plugins_test extends adler_testcase {
    private static Process $webserver_process;

    public static function setUpBeforeClass(): void {
        global $CFG;
        parent::setUpBeforeClass();

        self::$webserver_process = new Process(["php", "-S", "localhost:48531", $CFG->dirroot . "/local/declarativesetup/tests/resources/install_plugins_mock_server/router.php"]);
        self::$webserver_process->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });
        usleep(500);  // wait for the server to start.
    }

    public static function tearDownAfterClass(): void {
        self::$webserver_process->stop();
    }

    private function rrmdir(string $dir) {
        if (is_dir($dir)) {
            $objects = array_diff(scandir($dir), array('.', '..'));
            foreach ($objects as $object) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object))
                    $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                else
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
            }
            rmdir($dir);
        }
    }

    public function tearDown(): void {
        global $CFG;
        parent::tearDown();
        // delete local_testplugin on filesystem
        if (is_dir($CFG->dirroot . '/local/testplugin')) {
            $this->rrmdir($CFG->dirroot . '/local/testplugin');
        }
    }

    public function setUp(): void {
        parent::setUp();
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayNotHasKey('testplugin', $installed_mods, 'Plugin was already installed before test. This can happen if a previous testrun was aborted.');
    }

    public function test_install_and_update_plugin() {
        $play = new install_plugins([new install_plugins_model(
            '0.1.0',
            'local_testplugin',
            'ProjektAdler/moodle-local_testplugin'
        )]);
        $play->github_api_url = 'http://localhost:48531';
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertTrue($changed, 'Plugin was not installed');
        // assert plugin mod_adleradaptivity is installed
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin was not installed');
        $this->assertEquals('0.1.0', $play_output['local_testplugin']['release'], 'Installed version is not correct');
        $plugin_version = $installed_mods['testplugin'];
        $plugin_ugly_version_number = $play_output['local_testplugin']['version'];


        // test update
        $play = new install_plugins([new install_plugins_model(
            '0.1.1',
            'local_testplugin',
            'ProjektAdler/moodle-local_testplugin'
        )]);
        $play->github_api_url = 'http://localhost:48531';
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertTrue($changed, 'Plugin was not updated');
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin disappeared after update');
        $this->assertGreaterThan($plugin_version, $installed_mods['testplugin'], 'Version after update is not higher than before');
        $this->assertEquals('0.1.1', $play_output['local_testplugin']['release'], 'Installed version is not correct');
        $this->assertGreaterThan($plugin_ugly_version_number, $play_output['local_testplugin']['version'], 'Version after update is not higher than before');

        // test no change
        $play = new install_plugins([new install_plugins_model(
            '0.1.1',
            'local_testplugin',
            'ProjektAdler/moodle-local_testplugin'
        )]);
        $play->github_api_url = 'http://localhost:48531';
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertFalse($changed, 'Unexpected change occurred');
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin disappeared after update');
        $this->assertGreaterThan($plugin_version, $installed_mods['testplugin'], 'Version after update is not higher than before');
        $this->assertEquals('0.1.1', $play_output['local_testplugin']['release'], 'Installed version is not correct');


        // test downgrade
        $play = new install_plugins([new install_plugins_model(
            '0.1.0',
            'local_testplugin',
            'ProjektAdler/moodle-local_testplugin'
        )]);
        $play->github_api_url = 'http://localhost:48531';
        $this->expectExceptionMessage('plugin downgrade is not allowed');
        $play->play();
    }

    public function test_install_and_update_plugin_with_package_repo() {
        $play = new install_plugins([new install_plugins_model(
            '0.1.0',
            'local_testplugin',
             package_repo: 'http://localhost:48531/packages/moodle'
        )]);
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertTrue($changed, 'Plugin was not installed');
        // assert plugin mod_adleradaptivity is installed
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin was not installed');
        $this->assertEquals('0.1.0', $play_output['local_testplugin']['release'], 'Installed version is not correct');
        $plugin_version = $installed_mods['testplugin'];
        $plugin_ugly_version_number = $play_output['local_testplugin']['version'];


        // test update
        $play = new install_plugins([new install_plugins_model(
            '0.1.1',
            'local_testplugin',
            package_repo: 'http://localhost:48531/packages/moodle'
        )]);
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertTrue($changed, 'Plugin was not updated');
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin disappeared after update');
        $this->assertGreaterThan($plugin_version, $installed_mods['testplugin'], 'Version after update is not higher than before');
        $this->assertEquals('0.1.1', $play_output['local_testplugin']['release'], 'Installed version is not correct');
        $this->assertGreaterThan($plugin_ugly_version_number, $play_output['local_testplugin']['version'], 'Version after update is not higher than before');

        // test no change
        $play = new install_plugins([new install_plugins_model(
            '0.1.1',
            'local_testplugin',
            package_repo: 'http://localhost:48531/packages/moodle'
        )]);
        $changed = $play->play();
        $play_output = $play->get_output();

        $this->assertFalse($changed, 'Unexpected change occurred');
        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin disappeared after update');
        $this->assertGreaterThan($plugin_version, $installed_mods['testplugin'], 'Version after update is not higher than before');
        $this->assertEquals('0.1.1', $play_output['local_testplugin']['release'], 'Installed version is not correct');


        // test downgrade
        $play = new install_plugins([new install_plugins_model(
            '0.1.0',
            'local_testplugin',
            'http://localhost:48531/packages/moodle'
        )]);
        $this->expectExceptionMessage('plugin downgrade is not allowed');
        $play->play();
    }

    public function test_install_plugin_with_branch() {
        $play = new install_plugins([new install_plugins_model(
            'main',
            'local_testplugin',
            'ProjektAdler/moodle-local_testplugin'
        )]);
        $play->github_api_url = 'http://localhost:48531';

        $this->expectException(ddl_exception::class);
        $play->play();
    }
}
