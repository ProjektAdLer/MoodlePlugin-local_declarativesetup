<?php

namespace local_declarativesetup\local\exceptions;

use moodle_exception;

class setting_is_forced extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('setting_is_forced', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}