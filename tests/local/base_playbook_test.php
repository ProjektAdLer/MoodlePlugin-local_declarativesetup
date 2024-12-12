<?php /** @noinspection PhpExpressionResultUnusedInspection */

namespace local_declarativesetup\local;

use Exception;
use local_declarativesetup\lib\adler_testcase;
use ReflectionClass;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');
class base_playbook_test extends adler_testcase {
    public function test_run_calls_playbook_implementation() {
        $playbook = $this->getMockBuilder(base_playbook::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['playbook_implementation', 'failed'])
            ->getMockForAbstractClass();

        $playbook->expects($this->once())
            ->method('playbook_implementation');

        $playbook->run();
    }

    public function test_run_calls_failed_on_exception() {
        $playbook = $this->getMockBuilder(base_playbook::class)
            ->setConstructorArgs([[]])
            ->onlyMethods(['playbook_implementation', 'failed'])
            ->getMockForAbstractClass();

        $playbook->expects($this->once())
            ->method('playbook_implementation')
            ->will($this->throwException(new Exception('Test exception')));

        $playbook->expects($this->once())
            ->method('failed');

        $this->expectException(Exception::class);

        $playbook->run();
    }

    public function test_has_role() {
        $roles = ['admin', 'editor', 'viewer'];
        $playbook = $this->getMockForAbstractClass(base_playbook::class, [$roles]);

        $reflection = new ReflectionClass($playbook);
        $method = $reflection->getMethod('has_role');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($playbook, 'admin'));
        $this->assertFalse($method->invoke($playbook, 'guest'));
    }

    public function test_get_environment_variable_positive() {
        $playbook = $this->getMockForAbstractClass(base_playbook::class, [[]]);

        putenv('DECLARATIVE_SETUP_TEST_VAR=some_value');

        $reflection = new ReflectionClass($playbook);
        $method = $reflection->getMethod('get_environment_variable');
        $method->setAccessible(true);

        $this->assertEquals('some_value', $method->invoke($playbook, 'TEST_VAR'));

        putenv('DECLARATIVE_SETUP_TEST_VAR'); // Clean up
    }

    public function test_get_environment_variable_not_prefixed_correctly() {
        $playbook = $this->getMockForAbstractClass(base_playbook::class, [[]]);

        putenv('TEST_VAR=some_value');

        $reflection = new ReflectionClass($playbook);
        $method = $reflection->getMethod('get_environment_variable');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Environment variable TEST_VAR not set');

        $method->invoke($playbook, 'TEST_VAR');

        putenv('TEST_VAR'); // Clean up
    }

    public function test_get_environment_variable_does_not_exist() {
        $playbook = $this->getMockForAbstractClass(base_playbook::class, [[]]);

        $reflection = new ReflectionClass($playbook);
        $method = $reflection->getMethod('get_environment_variable');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Environment variable NON_EXISTENT_VAR not set');

        $method->invoke($playbook, 'NON_EXISTENT_VAR');
    }
}