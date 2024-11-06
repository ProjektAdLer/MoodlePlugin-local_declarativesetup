<?php

namespace local_adlersetup\local\play\exceptions;

use moodle_exception;

class play_was_already_played_exception extends moodle_exception {
    public function __construct(string $debuginfo = null) {
        parent::__construct('play_was_already_played', 'local_adlersetup', '', NULL, $debuginfo);
    }
}