<?php

use core\plugin_manager;
use local_declarativesetup\lib\adler_testcase;
use Symfony\Component\Process\Process;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class install_plugin_integration_test extends adler_testcase {
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
        usleep(100 * 1000);  // wait for the server to start.
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

    public function test_install_plugin() {
        // Run the script with valid parameters.
        $_SERVER['argv'] = [
            'install_plugin.php',
            '--package-repo=http://localhost:48531/packages/moodle',
            '--version=0.1.0',
            '--moodle-name=local_testplugin'
        ];

        require __DIR__ . '/../../cli/install_plugin.php';

        $installed_mods = plugin_manager::instance()->get_installed_plugins('local');
        $this->assertArrayHasKey('testplugin', $installed_mods, 'Plugin was not installed');
    }
}