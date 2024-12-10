<?php

namespace local_declarativesetup\local\play\course_category\models;

class role_user_model {
    /**
     * @param string $username see {@link $username}
     * @param array $roles see {@link $roles}
     * @param bool $append_roles see {@link $append_roles}
     */
    public function __construct(string $username, array $roles, bool $append_roles = true) {
        $this->username = $username;
        $this->roles = $roles;
        $this->append_roles = $append_roles;
    }

    /** @var string shortname */
    public string $username;
    /** @var string[] role shortname */
    public array $roles;
    /** @var bool if true, roles will be added to existing roles, if false, existing roles will be replaced */
    public bool $append_roles;
    /** @var bool true: user should be present (create/update), false: user should be absent (delete) */
    public bool $present;
}