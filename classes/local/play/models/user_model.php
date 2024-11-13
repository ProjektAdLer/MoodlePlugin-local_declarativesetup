<?php

namespace local_adlersetup\local\play\models;

use core\di;
use invalid_parameter_exception;
use local_adlersetup\local\moodle_core;

class user_model {
    /**
     * Requires local_adler plugin to be installed, requires role "adler_manager" to be present
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
                                string $description = "created by adler_setup") {
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