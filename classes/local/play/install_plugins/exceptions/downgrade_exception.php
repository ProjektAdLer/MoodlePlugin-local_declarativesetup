<?php

namespace local_declarativesetup\local\play\install_plugins\exceptions;

use moodle_exception;

class downgrade_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('downgrade_exception', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}