<?php

namespace local_adlersetup\local\play\course_category\models;

use local_adlersetup\local\play\course_category\util\course_category_path;

class course_category_model {
    /**
     * @param string $course_category_path
     * @param bool $present
     * @param bool $append_users
     * @param role_user_model[] $users
     * @param string $description
     */
    public function __construct(string $course_category_path,
                                bool   $present = true,
                                bool   $append_users = true,
                                array  $users = [],
                                string $description = "created by adler_setup") {

        $this->course_category_path = new course_category_path($course_category_path);
        $this->present = $present;
        $this->append_users = $append_users;
        $this->users = $users;
        $this->description = $description;
    }

    public course_category_path $course_category_path;
    /** @var bool true: course category should be present (create/update), false: user should be absent (delete) */
    public bool $present;
    /** @var bool if true, users will be added to existing roles, if false, existing roles will be replaced */
    public bool $append_users;
    /** @var role_user_model[] */
    public array $users;
    public string $description;
}