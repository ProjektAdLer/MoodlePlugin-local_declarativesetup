<?php

namespace local_declarativesetup\local\exceptions;

use moodle_exception;

class setting_unable_to_extract_value extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('setting_unable_to_extract_value', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}