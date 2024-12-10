<?php

namespace local_declarativesetup\local\play\course_category;

use coding_exception;
use context_coursecat;
use core\di;
use dml_exception;
use invalid_parameter_exception;
use local_declarativesetup\local\moodle_core;
use local_declarativesetup\local\play\base_play;
use local_declarativesetup\local\play\course_category\models\course_category_model;
use moodle_exception;

global $CFG;
require_once($CFG->libdir . '/clilib.php');


/**
 * @property course_category_model $input
 */
class course_category extends base_play {

    /**
     * @param course_category_model $input
     */
    public function __construct(course_category_model $input) {
        parent::__construct($input);
    }

    /**
     * @throws moodle_exception
     * @throws invalid_parameter_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;

        if ($this->input->present && !$this->input->course_category_path->exists()) {
            // create if not exists but should
            $this->input->course_category_path->create();
            $state_changed = true;
        } else if (!$this->input->present && $this->input->course_category_path->exists()) {
            // delete if exists but should not
            $this->input->course_category_path->delete();
            return true;
        }

        $state_changed = $this->check_and_update_course_category_properties() || $state_changed;

        return $this->check_and_update_user_roles() || $state_changed;
    }

    /**
     * @returns true if properties were updated
     * @throws moodle_exception
     */
    private function check_and_update_course_category_properties(): bool {
        $course_cat = $this->input->course_category_path->get_moodle_category_object();
        if ($course_cat->description !== $this->input->description) {
            $course_cat->update(['description' => $this->input->description]);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    private function check_and_update_user_roles(): bool {
        $state_changed = false;

        // remove users not in input
        if (!$this->input->append_users) {
            // get all users with roles in this course category
            $role_users = get_role_users(
                [],
                context_coursecat::instance($this->input->course_category_path->get_category_id()),
                false,
                // very very stupid implementation. Basically I have to add ra.id to the default set because ?
                // (it throws moodle's "exception lite" aka "debugging" otherwise), but this is not possible.
                // Instead it overrides the default, so I have to add the default fields I need and the function
                // needs back.
                'ra.id,u.username,r.shortname AS roleshortname, u.lastname, u.firstname, u.id'
            );
            foreach ($role_users as $role_user) {
                if (!in_array(
                    $role_user->username,
                    array_column($this->input->users, 'username')
                )) {
                    $user_id = get_complete_user_data('username', $role_user->username)->id;
                    role_unassign(
                        $role_user->roleid,
                        $user_id,
                        context_coursecat::instance($this->input->course_category_path->get_category_id())->id);
                    $state_changed = true;
                }
            }
        }

        // update existing users and add new users
        foreach ($this->input->users as $desired_user_roles) {
            $user_id = get_complete_user_data('username', $desired_user_roles->username)->id;
            $user_roles = get_user_roles(
                context_coursecat::instance($this->input->course_category_path->get_category_id()),
                $user_id);

            if (!$desired_user_roles->append_roles) {
                // remove roles a user should not have
                foreach ($user_roles as $user_role) {
                    if (!in_array($user_role->shortname, $desired_user_roles->roles)) {
                        role_unassign(
                            $user_role->roleid,
                            $user_id,
                            context_coursecat::instance($this->input->course_category_path->get_category_id())->id);
                        $state_changed = true;
                    }
                }
            }

            // add roles a user should have
            foreach ($desired_user_roles->roles as $role_shortname) {
                $role_id = di::get(moodle_core::class)::get_role($role_shortname)->id;
                if (!in_array($role_id, array_column($user_roles, 'roleid'))) {
                    if (!$this->is_role_assignable_to_course_category($role_id)) {
                        throw new invalid_parameter_exception('role_not_assignable_to_course_category');
                    }
                    role_assign(
                        $role_id,
                        $user_id,
                        context_coursecat::instance($this->input->course_category_path->get_category_id()));
                    $state_changed = true;
                }
            }
        }

        return $state_changed;
    }

    private function is_role_assignable_to_course_category(int $role_id): bool {
        return in_array(CONTEXT_COURSECAT, di::get(moodle_core::class)::get_role_contextlevels($role_id));
    }

//    public function get_output_implementation(): array {
//
//    }
}