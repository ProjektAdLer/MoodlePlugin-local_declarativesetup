<?php

namespace local_declarativesetup\local\play\user\models;

use core\di;
use invalid_parameter_exception;
use local_declarativesetup\local\moodle_core;

class user_model {
    /**
     * @param string $username login name, lowercase, no spaces
     * @param string $password
     * @param bool $present true: user should be present (create/update), false: user should be absent (delete)
     * @param array $system_roles
     * @param bool $append_roles if true, roles will be added to existing roles, if false, existing roles will be replaced
     * @param string $language
     * @param string|null $firstname
     * @param string|null $lastname
     * @param string|null $email
     * @param string $description
     * @throws invalid_parameter_exception
     */
    public function __construct(public string $username,
                                public string $password,
                                public bool   $present = true,
                                public array  $system_roles = [],
                                public bool   $append_roles = true,
                                public string $language = 'en',
                                string|null   $firstname = null,
                                string|null   $lastname = null,
                                string|null   $email = null,
                                public string $description = "created by declarative setup") {
        if ($username !== strtolower($username) || str_contains($username, ' ')) {
            throw new invalid_parameter_exception('Username must be lowercase');
        }
        // assert roles are valid
        foreach ($system_roles as $role) {
            if (!in_array($role, array_column(di::get(moodle_core::class)::get_all_roles(), 'shortname'))) {
                throw new invalid_parameter_exception('Invalid role');
            }
        }

        $this->username = trim($this->username);
        $this->firstname = $firstname ?? $username;
        $this->lastname = $lastname ?? $username;
        $this->email = $email ?? $username . '@example.local';
    }

    public string $firstname;
    public string $lastname;
    public string $email;
}