<?php /** @noinspection PhpExpressionResultUnusedInspection */

namespace local_declarativesetup\local\play;

use Exception;
use local_declarativesetup\lib\adler_testcase;
use local_declarativesetup\local\play\exceptions\play_was_already_played_exception;
use local_declarativesetup\local\play\exceptions\play_was_not_played_exception;
use local_logging\logger;
use Mockery;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');
class base_play_test extends adler_testcase {

    public function test_play_successfully_changes_state() {
        $input = ['test' => 'data'];
        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mock_play->shouldReceive('play_implementation')
            ->once()
            ->andReturn(true);

        $result = $mock_play->play();

        $this->assertTrue($result);
        $this->assertTrue($mock_play->get_was_played());
        $this->assertTrue($mock_play->get_state_changed());
    }

    public function test_play_throws_exception_when_played_twice() {
        $this->expectException(play_was_already_played_exception::class);

        $input = ['test' => 'data'];
        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mock_play->shouldReceive('play_implementation')
            ->once()
            ->andReturn(true);

        $mock_play->play(); // First play should work.
        $mock_play->play(); // Second play should throw an exception.
    }

    public function test_play_logs_messages() {
        $input = ['test' => 'data'];
        $mock_logger = Mockery::mock(logger::class);
        $mock_logger->shouldReceive('info')->atLeast()->once();
        $mock_logger->shouldReceive('error')->never();

        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mock_play->logger = $mock_logger; // Inject mock logger.

        $mock_play->shouldReceive('play_implementation')
            ->once()
            ->andReturn(true);

        $mock_play->play();
    }

    public function test_play_handles_exception_and_logs_error() {
        $input = ['test' => 'data'];
        $mock_logger = Mockery::mock(logger::class);
        $mock_logger->shouldReceive('info')->atLeast()->once();
        $mock_logger->shouldReceive('error')
            ->once()
            ->with(Mockery::on(function ($message) {
                return str_contains($message, 'Play failed, exception occurred: Test Exception');
            }));

        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mock_play->logger = $mock_logger; // Inject mock logger.

        $mock_play->shouldReceive('play_implementation')
            ->once()
            ->andThrow(new Exception('Test Exception'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test Exception');

        $mock_play->play();
    }

    public function test_get_state_changed_throws_exception_if_not_played() {
        $input = ['test' => 'data'];
        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->expectException(play_was_not_played_exception::class);

        $mock_play->get_state_changed(); // Attempting to get state changed without playing.
    }

    public function test_get_output_throws_exception_if_not_played() {
        $input = ['test' => 'data'];
        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->expectException(play_was_not_played_exception::class);

        $mock_play->get_output(); // Attempting to get output without playing.
    }

    public function test_get_output_returns_implementation_output_after_playing() {
        $input = ['test' => 'data'];
        $expected_output = ['key' => 'value'];

        $mock_play = Mockery::mock(base_play::class, [$input])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $mock_play->shouldReceive('play_implementation')
            ->once()
            ->andReturn(true);

        $mock_play->shouldReceive('get_output_implementation')
            ->once()
            ->andReturn($expected_output);

        $mock_play->play(); // Set played to true.
        $output = $mock_play->get_output();

        $this->assertSame($expected_output, $output);
    }
}