<?php

namespace local_adlersetup\local\db;


use dml_exception;

class moodle_role_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function get_capabilities_of_role(int $role_id): array {
        return $this->db->get_records('role_capabilities', ['roleid' => $role_id]);
    }

    /**
     * @throws dml_exception
     */
    public function update_role(int $role_id, string $role_name, string $description, string $archetype): bool {
        // verify role archetype actually exists
        $archetypes = get_role_archetypes();
        if (empty($archetypes[$archetype])) {
            $archetype = '';
        }

        return $this->db->update_record('role', (object) [
            'id' => $role_id,
            'name' => $role_name,
            'description' => $description,
            'archetype' => $archetype
        ]);
    }
}