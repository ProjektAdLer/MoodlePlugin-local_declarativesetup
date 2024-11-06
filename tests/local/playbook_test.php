<?php

namespace local_adlersetup\local;

global $CFG;

use local_adlersetup\lib\adler_testcase;
use local_adlersetup\local\play\install_plugins;
use Mockery;
use moodle_exception;

require_once($CFG->dirroot . '/local/adlersetup/tests/lib/adler_testcase.php');


class playbook_test extends adler_testcase {
    public function provide_test_playbook_data() {
        return [
            'install_plugins' => ['install_plugins' => true],
            'no_plugins' => ['install_plugins' => false]
        ];
    }

    /**
     * @dataProvider provide_test_playbook_data
     * @ runInSeparateProcess  //todo
     */
    public function test_playbook(bool $install_plugins) {
        if (!$install_plugins) {
            $this->expectException(moodle_exception::class);
            $this->expectExceptionMessageMatches('/Plugins are not installed/');
        }

        $play_install_plugins_mock = Mockery::mock('overload:' . install_plugins::class);
        $play_install_plugins_mock->shouldReceive('play')->once();
        if ($install_plugins) {
            $play_install_plugins_mock
                ->shouldReceive('__construct')
                ->once()
                ->withArgs(function ($args) {
                    return is_array($args) && count($args) >= 1;
                });
            $play_install_plugins_mock->shouldReceive('get_output')->once()->andReturn(['local_adler' => ['release' => '2.2.0', 'version' => 2024101000]]);
        } else {
            $play_install_plugins_mock->shouldReceive('__construct')->once()->with([]);
            $play_install_plugins_mock->shouldReceive('get_output')->once()->andReturn([]);
        }

        new playbook($install_plugins);
    }
}