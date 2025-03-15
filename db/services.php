<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'create_course_cat_and_assign_user_role' => [
        'classname' => 'local_declarativesetup\external\create_course_cat_and_assign_user_role',
        'description' => 'Assign a user a role in a course category (existing or new)',
        'type' => 'write',
        'ajax' => false,
        'capabilities' => 'moodle/role:assign, moodle/category:viewcourselist, moodle/category:manage',
        'loginrequired' => true
    ],
);