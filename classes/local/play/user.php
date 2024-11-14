<?php

namespace local_adlersetup\local\play;

use coding_exception;
use context_system;
use core\di;
use dml_exception;
use invalid_parameter_exception;
use local_adlersetup\local\moodle_core;
use local_adlersetup\local\play\models\user_model;
use moodle_exception;
use stdClass;

global $CFG;
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot.'/user/lib.php');  // required for user_<...> functions



/**
 * @property user_model $input
 */
class user extends base_play {

    /**
     * This play takes a {@link user_model} and ensures that the user exists with the specified properties and roles.
     *
     * @param user_model $input
     */
    public function __construct(user_model $input) {
        parent::__construct($input);
    }

    /**
     * @throws moodle_exception
     * @throws dml_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;

        // check if user exists
        $user = get_complete_user_data('username', $this->input->username);

        if ($user === false){
            // if user does not exist, create user
            if ($this->input->present) {
                $user = $this->create_user();
                $state_changed = true;
            } else {
                return false;
            }
        } else {
            // if user exists, check if properties need to be updated or delete
            if ($this->input->present) {
                list($user_updated, $user) = $this->check_and_update_user_properties($user);
                $state_changed = $state_changed || $user_updated;
            } else {
                user_delete_user($user);
                return true;
            }
        }

        // here I know the user exists and should exist

        // check if roles need to be updated
        if ($this->check_and_update_roles($user)) {
            $state_changed = true;
        }

        return $state_changed;
    }

//    public function get_output_implementation(): array {
//
//    }

    /**
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function create_user(): stdClass {
        $user = new stdClass();
        $user->username = $this->input->username;
        $user->password = $this->input->password;
        $user->email = $this->input->email;
        $user->firstname = $this->input->firstname;
        $user->lastname = $this->input->lastname;
        $user->lang = $this->input->language;
        $user->description = $this->input->description;
        $user->auth = 'manual';
        $user->timecreated = time();
        $user->timemodified = $user->timecreated;
        $user->confirmed = 1;
        $user->mnethostid = get_config('core', 'mnet_localhost_id');

        $user->id = user_create_user($user);

        //    Setting the password this way ignore password validation rules
        //    update_internal_user_password($user, $password);

        return get_complete_user_data('username', $this->input->username, null, true);
    }

    /**
     * @param stdClass $user $USER object
     * @throws moodle_exception
     */
    private function check_and_update_user_properties(stdClass $user): array {
        // password
        if ($user->email != $this->input->email ||
            $user->firstname != $this->input->firstname ||
            $user->lastname != $this->input->lastname ||
            $user->lang != $this->input->language ||
            $user->description != $this->input->description ||
            !validate_internal_user_password($user, $this->input->password)
        ) {
            $user->email = $this->input->email;
            $user->firstname = $this->input->firstname;
            $user->lastname = $this->input->lastname;
            $user->lang = $this->input->language;
            $user->description = $this->input->description;
            $user->password = $this->input->password;
            $user->timemodified = time();

            user_update_user($user);

            return [true, get_complete_user_data('username', $this->input->username, null, true)];
        }
        return [false, $user];
    }

    /**
     * @param stdClass $user
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    private function check_and_update_roles(stdClass $user): bool {
        $state_changed = false;

        // get all user roles
        $user_roles = get_user_roles(context_system::instance(), $user->id);
        if (!$this->input->append_roles) {
            // remove roles not in $this->input->roles
            foreach ($user_roles as $user_role) {
                if (!in_array($user_role->roleid, $this->input->system_roles)) {
                    role_unassign($user_role->roleid, $user->id, context_system::instance()->id);
                    $state_changed = true;
                }
            }
        }
        // add roles in $this->input->roles
        foreach ($this->input->system_roles as $role_shortname) {
            $role_id = di::get(moodle_core::class)::get_role($role_shortname)->id;
            if (!in_array($role_id, array_column($user_roles, 'roleid'))) {
                role_assign($role_id, $user->id, context_system::instance()->id);
                $state_changed = true;
            }
        }
        return $state_changed;
    }
}