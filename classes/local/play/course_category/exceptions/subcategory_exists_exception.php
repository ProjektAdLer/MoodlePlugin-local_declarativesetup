<?php

namespace local_declarativesetup\local\play\course_category\exceptions;

use moodle_exception;

class subcategory_exists_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('subcategory_exists', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}