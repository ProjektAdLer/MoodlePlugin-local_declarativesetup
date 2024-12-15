<?php

namespace local_declarativesetup\local\db;


use dml_exception;

class moodle_external_services_repository extends base_repository {
    /**
     * @throws dml_exception
     */
    public function get_external_service_by_name(string $name): object {
        return $this->db->get_record('external_services', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * @throws dml_exception
     */
    public function update_external_service(int $id, object $external_service): void {
        $external_service->id = $id;
        $this->db->update_record('external_services', $external_service);
    }
}