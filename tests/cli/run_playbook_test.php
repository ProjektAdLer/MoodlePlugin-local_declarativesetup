<?php

global $CFG;

use core\di;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\exceptions\exit_exception;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class run_playbook_test extends adler_testcase {
    public function test_playbook_not_found() {
        // Mock the core_component::get_plugin_list_with_class method to return an empty array.
        $coreComponentMock = Mockery::mock(core_component::class);
        $coreComponentMock->shouldReceive('get_plugin_list_with_class')
            ->andReturn([]);
        di::set(core_component::class, $coreComponentMock);

        // Capture the output of the script.
        $this->expectException(exit_exception::class);

        // Run the script with the --playbook option set to a nonexistent playbook.
        $_SERVER['argv'] = ['run_playbook.php', '--playbook=nonexistent'];

        require __DIR__ . '/../../cli/run_playbook.php';
    }

    public function test_playbook_found() {
        // Mock the playbook class.
        $playbook_mock = Mockery::mock('overload:');
        $playbook_mock->shouldReceive('__construct')->once();
        $playbook_mock_2 = Mockery::mock();
        $playbook_mock_2->shouldReceive('__construct')->never();


        // Mock the core_component::get_plugin_list_with_class method to return the mock playbook.
        $coreComponentMock = Mockery::mock('core_component');
        $coreComponentMock->shouldReceive('get_plugin_list_with_class')
            ->andReturn([
                'playbook_sample' => get_class($playbook_mock),
                'playbook_sample2' => get_class($playbook_mock_2),
            ]);
        di::set('core_component', $coreComponentMock);

        // Capture the output of the script.
        $this->expectOutputString("done\n");

        // Run the script with the --playbook option set to the mock playbook.
        $_SERVER['argv'] = ['run_playbook.php', '--playbook=sample'];

        require __DIR__ . '/../../cli/run_playbook.php';
    }
}