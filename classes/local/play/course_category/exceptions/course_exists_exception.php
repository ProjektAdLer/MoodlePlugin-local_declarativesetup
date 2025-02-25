<?php

namespace local_declarativesetup\local\play\course_category\exceptions;

use moodle_exception;

class course_exists_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('course_exists', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}