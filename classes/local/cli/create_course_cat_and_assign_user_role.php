<?php

namespace local_adlersetup\local\cli;

use core\di;
use dml_exception;
use invalid_parameter_exception;
use local_adlersetup\local\moodle_core;
use local_adlersetup\local\play\course_category\util\course_category_path;
use moodle_exception;

global $CFG;
require_once($CFG->libdir . '/clilib.php');

class create_course_cat_and_assign_user_role {
    private string $username;
    private string $role_shortname;
    private course_category_path $category_path;
    private mixed $user_id;
    private mixed $role_id;

    /**
     * @param string $username
     * @param string $role_shortname
     * @param string|null $category_path
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(string $username, string $role_shortname, string|null $category_path) {
        $this->validate_required_parameters($username, $role_shortname);

        $this->username = $username;
        $this->role_shortname = $role_shortname;
        $this->category_path = new course_category_path(empty($category_path) ? $this->get_default_category_path() : $category_path);

        list($this->user_id, $this->role_id) = $this->validate_parameters_validity();
    }

    private function get_default_category_path(): string {
        return "adler/{$this->username}";
    }

    /**
     * @param string $username
     * @param string $role
     * @return void
     * @throws invalid_parameter_exception
     */
    private function validate_required_parameters(string $username, string $role): void {
        if (empty(trim($username))) {
            cli_writeln('--username is required');
            throw new invalid_parameter_exception('username is required');
        }

        if (empty(trim($role))) {
            cli_writeln('--role is required');
            throw new invalid_parameter_exception('role is required');
        }
    }

    /**
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @returns array [user_id, role_id]
     */
    private function validate_parameters_validity(): array {
        // users exists
        $user = get_complete_user_data('username', $this->username);
        if (!$user) {
            throw new invalid_parameter_exception('user_not_found');
        }

        // role exists
        $role = di::get(moodle_core::class)::get_role($this->role_shortname);
        if (!$role) {
            throw new invalid_parameter_exception('role_not_found');
        }

        // role is assignable to course category
        if (!in_array(CONTEXT_COURSECAT, di::get(moodle_core::class)::get_role_contextlevels($role->id))) {
            throw new invalid_parameter_exception('role_not_assignable_to_course_category');
        }

        return [$user->id, $role->id];
    }

    /**
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    public function execute(): int {
        if (!$this->category_path->exists()) {
            cli_writeln("Creating category with path {$this->category_path}");
            $this->category_path->create();
        }

        cli_writeln("Assigning user with ID {$this->user_id} to role with ID {$this->role_id} in category with ID {$this->category_path->get_category_id()}");
        role_assign(
            $this->role_id,
            $this->user_id,
            di::get(moodle_core::class)->context_coursecat_instance($this->category_path->get_category_id())
        );

        return $this->category_path->get_category_id();
    }
}