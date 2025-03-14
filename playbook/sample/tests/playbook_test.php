<?php

namespace playbook_sample;

global $CFG;

use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\config\config;
use local_declarativesetup\local\play\config\models\simple_config_model;
use local_declarativesetup\local\play\role\models\role_model;
use local_declarativesetup\local\play\role\role;
use local_declarativesetup\local\play\user\models\user_model;
use local_declarativesetup\local\play\user\user;
use Mockery;

require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');


class playbook_test extends adler_testcase {
    public function test_playbook() {
        // Static counters to track the number of calls for each model's constructor and methods
        $call_count_config_model_construct = 0;
        $call_count_role_model_construct = 0;
        $call_count_user_model_construct = 0;
        $call_count_config_construct = 0;
        $call_count_role_construct = 0;
        $call_count_user_construct = 0;

        // Static counters to track the number of calls for `play` methods
        $call_count_config_play = 0;
        $call_count_role_play = 0;
        $call_count_user_play = 0;

        // Mock the simple_config_model class
        $configModelMock = Mockery::mock('overload:' . simple_config_model::class);
        $configModelMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_config_model_construct) {
            $call_count_config_model_construct++;
            return (object)[]; // Simulate returning an object
        });

        // Mock the role_model class
        $roleModelMock = Mockery::mock('overload:' . role_model::class);
        $roleModelMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_role_model_construct) {
            $call_count_role_model_construct++;
            return (object)[];
        });

        // Mock the user_model class
        $userModelMock = Mockery::mock('overload:' . user_model::class);
        $userModelMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_user_model_construct) {
            $call_count_user_model_construct++;
            return (object)[];
        });

        // Mock the config class
        $configMock = Mockery::mock('overload:' . config::class);
        $configMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_config_construct) {
            $call_count_config_construct++;
            return (object)[];
        });
        $configMock->shouldReceive('play')->andReturnUsing(function () use (&$call_count_config_play) {
            $call_count_config_play++;
        });

        // Mock the role class
        $roleMock = Mockery::mock('overload:' . role::class);
        $roleMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_role_construct) {
            $call_count_role_construct++;
            return (object)[];
        });
        $roleMock->shouldReceive('play')->andReturnUsing(function () use (&$call_count_role_play) {
            $call_count_role_play++;
        });

        // Mock the user class
        $userMock = Mockery::mock('overload:' . user::class);
        $userMock->shouldReceive('__construct')->andReturnUsing(function () use (&$call_count_user_construct) {
            $call_count_user_construct++;
            return (object)[];
        });
        $userMock->shouldReceive('play')->andReturnUsing(function () use (&$call_count_user_play) {
            $call_count_user_play++;
        });

        // Instantiate the playbook
        new playbook();

        // Assert that the constructors were called the expected number of times
        $this->assertEquals(3, $call_count_config_model_construct, "config_model constructor failed");
        $this->assertEquals(1, $call_count_role_model_construct, "role_model constructor failed");
        $this->assertEquals(2, $call_count_user_model_construct, "user_model constructor failed");
        $this->assertEquals(2, $call_count_config_construct, "config constructor failed");
        $this->assertEquals(1, $call_count_role_construct, "role constructor failed");
        $this->assertEquals(2, $call_count_user_construct, "user constructor failed");

        // Assert that the play methods were called the expected number of times
        $this->assertEquals(2, $call_count_config_play, "config play method failed");
        $this->assertEquals(1, $call_count_role_play, "role play method failed");
        $this->assertEquals(2, $call_count_user_play, "user play method failed");
    }
}