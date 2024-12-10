<?php

namespace local_declarativesetup\local\exceptions;

use moodle_exception;

class setting_exists_multiple_times extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('setting_exists_multiple_times', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}