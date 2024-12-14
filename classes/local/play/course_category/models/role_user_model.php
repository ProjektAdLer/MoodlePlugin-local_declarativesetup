<?php

namespace local_declarativesetup\local\play\course_category\models;

class role_user_model {
    /**
     * @param string $username shortname
     * @param string[] $roles shortname
     * @param bool $append_roles if true, roles will be added to existing roles, if false, existing roles will be replaced
     */
    public function __construct(string $username, array $roles, bool $append_roles = true) {
        $this->username = $username;
        $this->roles = $roles;
        $this->append_roles = $append_roles;
    }
}