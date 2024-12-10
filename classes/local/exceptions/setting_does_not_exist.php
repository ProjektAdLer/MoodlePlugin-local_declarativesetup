<?php

namespace local_declarativesetup\local\exceptions;

use moodle_exception;

class setting_does_not_exist extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('setting_does_not_exist', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}