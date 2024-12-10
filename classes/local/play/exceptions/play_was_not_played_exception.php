<?php

namespace local_declarativesetup\local\play\exceptions;

use moodle_exception;

class play_was_not_played_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('play_was_not_played', 'local_declarativesetup', '', NULL, $debuginfo);
    }
}