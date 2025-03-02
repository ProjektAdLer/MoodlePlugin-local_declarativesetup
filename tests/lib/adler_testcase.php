<?php

namespace local_declarativesetup\lib;

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/declarativesetup/vendor/autoload.php');

use advanced_testcase;
use Mockery;

trait general_testcase_adjustments {
    public function setUp(): void {
        parent::setUp();

        // set default value: reset DB after each test case
        $this->resetAfterTest();

        // if creating multiple mocks of the same class (in my example context_module) in different tests or
        // same test with different parameters Mockery always reused the first mock created for that class.
        // This is not desired, because test cases should be independent of each other. Therefore, the
        // Mockery container is reset before each test case.
        Mockery::resetContainer();

        // workaround for beStrictAboutOutputDuringTests = true in default moodle phpunit configuration
        $this->expectOutputRegex('/.*/');
    }

    public function tearDown(): void {
        parent::tearDown();

        // output everything that was captured by expectOutput
        fwrite(STDOUT, $this->getActualOutputForAssertion());

        Mockery::close();
    }
}

abstract class adler_testcase extends advanced_testcase {
    use general_testcase_adjustments;
}
