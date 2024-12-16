<?php

namespace local_declarativesetup\local\play\course_category\models;

class role_user_model {
    /**
     * @param string $username shortname
     * @param string[] $roles shortname
     * @param bool $append_roles if true, roles will be added to existing roles, if false, existing roles will be replaced
     */
    public function __construct(public string $username,
                                public array  $roles,
                                public bool   $append_roles = true) {}
}