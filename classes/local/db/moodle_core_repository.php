<?php

namespace local_adlersetup\local\db;


use dml_exception;

class moodle_core_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function get_capabilities_of_role(int $role_id): array {
        return $this->db->get_records('role_capabilities', ['roleid' => $role_id]);
    }
}