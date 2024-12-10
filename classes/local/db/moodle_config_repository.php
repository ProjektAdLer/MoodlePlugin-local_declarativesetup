<?php

namespace local_declarativesetup\local\db;


use dml_exception;
use stdClass;

class moodle_config_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function get_config(string $key, string|null $plugin=null): stdClass {
        if ($plugin !== null) {
            return $this->db->get_record('config_plugins', ['plugin' => $plugin, 'name' => $key], '*', MUST_EXIST);
        } else {
            return $this->db->get_record('config', ['name' => $key], '*', MUST_EXIST);
        }
    }
}