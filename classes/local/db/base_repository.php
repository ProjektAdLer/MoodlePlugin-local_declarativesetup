<?php

namespace local_declarativesetup\local\db;

use moodle_database;

abstract class base_repository {
    public function __construct(
        protected readonly moodle_database $db
    ) {}
}