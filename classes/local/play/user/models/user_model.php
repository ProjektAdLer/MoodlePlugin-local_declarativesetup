<?php

namespace local_declarativesetup\local\play\user\models;

use core\di;
use invalid_parameter_exception;
use local_declarativesetup\local\moodle_core;

class user_model {
    /**
     * @param string $username see {@link $username}
     * @param string $password see {@link $password}
     * @param bool $present see {@link $present}
     * @param array $system_roles see {@link $system_roles}
     * @param bool $append_roles see {@link $append_roles}
     * @param string $langauge see {@link $langauge}
     * @param string|null $firstname see {@link $firstname}
     * @param string|null $lastname see {@link $lastname}
     * @param string|null $email see {@link $email}
     * @param string $description see {@link $description}
     * @throws invalid_parameter_exception
     */
    public function __construct(string      $username,
                                string      $password,
                                bool        $present = true,
                                array       $system_roles = [],
                                bool        $append_roles = true,
                                string      $langauge = 'en',
                                string|null $firstname = null,
                                string|null $lastname = null,
                                string|null $email = null,
                                string $description = "created by declarative setup") {
        if ($username !== strtolower($username) || str_contains($username, ' ')) {
            throw new invalid_parameter_exception('Username must be lowercase');
        }
        // assert roles are valid
        foreach ($system_roles as $role) {
            if (!in_array($role, array_column(di::get(moodle_core::class)::get_all_roles(), 'shortname'))) {
                throw new invalid_parameter_exception('Invalid role');
            }
        }

        $this->present = $present;
        $this->username = trim($username);
        $this->password = $password;
        $this->firstname = $firstname ?? $username;
        $this->lastname = $lastname ?? $username;
        $this->email = $email ?? $username . '@example.local';
        $this->system_roles = $system_roles;
        $this->append_roles = $append_roles;
        $this->language = $langauge;
        $this->description = $description;
    }

    /** @var bool true: user should be present (create/update), false: user should be absent (delete) */
    public bool $present;
    /** @var string login name, lowercase, no spaces */
    public string $username;
    public string $password;
    public string $firstname;
    public string $lastname;
    public string $email;
    /** @var bool if true, roles will be added to existing roles, if false, existing roles will be replaced */
    public bool $append_roles;
    public array $system_roles;
    public string $language;
    public string $description;
}