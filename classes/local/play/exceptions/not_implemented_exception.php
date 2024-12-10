<?php

namespace local_declarativesetup\local\play\exceptions;

use moodle_exception;

class not_implemented_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('not_implemented_exception', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}