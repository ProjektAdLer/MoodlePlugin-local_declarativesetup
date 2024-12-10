<?php

namespace local_declarativesetup\local\play\course_category\models;

use local_declarativesetup\local\play\course_category\util\course_category_path;

class course_category_model {
    /**
     * @param string $course_category_path see {@link $course_category_path}
     * @param bool $present see {@link $present}
     * @param bool $append_users see {@link $append_users}
     * @param role_user_model[] $users see {@link $users}
     * @param string $description see {@link $description}, default: "created by declarativesetup"
     */
    public function __construct(string $course_category_path,
                                bool   $present = true,
                                bool   $append_users = true,
                                array  $users = [],
                                string $description = "created by declarativesetup") {

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