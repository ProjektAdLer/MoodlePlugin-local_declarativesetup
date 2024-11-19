<?php

namespace local_adlersetup\local\exceptions;

use Exception;
use Throwable;

class exit_exception extends Exception {
    public function __construct($code = 0, Throwable $previous = null) {
        parent::__construct(
            "Exiting script",
            $code,
            $previous
        );
    }
}