<?php

namespace local_adlersetup\local\play\exceptions;

use moodle_exception;

class not_implemented_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('not_implemented_exception', 'local_adlersetup', '', NULL, $debuginfo);
    }
}