<?php

namespace local_declarativesetup\local\play\web_services\models;

use invalid_parameter_exception;
use local_declarativesetup\lib\adler_testcase;

global $CFG;
require_once($CFG->dirroot . '/local/declarativesetup/tests/lib/adler_testcase.php');

class web_services_model_test extends adler_testcase {
    public function test_type_validation_enable_webservices(): void {
        new web_services_model(web_services_model::STATE_UNSET, [], [], web_services_model::STATE_UNSET);
        $this->expectException(invalid_parameter_exception::class);
        new web_services_model(2, [], [], web_services_model::STATE_UNSET);
    }

    public function test_type_validation_enable_moodle_mobile_service(): void {
        new web_services_model(web_services_model::STATE_UNSET, [], [], web_services_model::STATE_UNSET);
        $this->expectException(invalid_parameter_exception::class);
        new web_services_model(web_services_model::STATE_UNSET, [], [], 2);
    }

    public static function provide_procotol_validation_data() {
        return [
            'valid' => [
                'enable_list' => ['rest'],
                'disable_list' => [],
                'exception_expected' => false
            ],
            'valid with *' => [
                'enable_list' => ['rest'],
                'disable_list' => ['*'],
                'exception_expected' => false
            ],
            'invalid * in enable list' => [
                'enable_list' => ['*'],
                'disable_list' => [],
                'exception_expected' => true
            ],
            'invalid * in disable list' => [
                'enable_list' => [],
                'disable_list' => ['*', 'rest'],
                'exception_expected' => true
            ],
            'invalid duplicate' => [
                'enable_list' => ['rest'],
                'disable_list' => ['rest'],
                'exception_expected' => true
            ],
        ];
    }

    /**
     * @dataProvider provide_procotol_validation_data
     */
    public function test_protocol_validation(array $enable_list, array $disable_list, bool $exception_expected): void {
        if ($exception_expected) {
            $this->expectException(invalid_parameter_exception::class);
        }
        new web_services_model(web_services_model::STATE_UNSET, $enable_list, $disable_list, web_services_model::STATE_UNSET);
    }
}