<?php

namespace local_declarativesetup\local\play\course_category\models;

use local_declarativesetup\local\play\course_category\util\course_category_path;

class course_category_model {
    /**
     * @param string $course_category_path
     * @param bool $present true: course category should be present (create/update), false: user should be absent (delete)
     * @param bool $append_users if true, users will be added to existing roles, if false, existing roles will be replaced
     * @param role_user_model[] $users
     * @param string $description
     */
    public function __construct(string $course_category_path,
                                public bool   $present = true,
                                public bool   $append_users = true,
                                public array  $users = [],
                                public string $description = "created by declarativesetup") {

        $this->course_category_path = new course_category_path($course_category_path);
    }

    public course_category_path $course_category_path;
}