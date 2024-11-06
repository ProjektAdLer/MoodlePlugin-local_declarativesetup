<?php

namespace local_adlersetup\local\play;

use coding_exception;
use core\di;
use local_adlersetup\local\db\moodle_core_repository;
use local_adlersetup\local\moodle_core;
use local_adlersetup\local\play\models\install_plugins_model;
use local_adlersetup\local\play\models\role_model;

global $CFG;
require_once($CFG->libdir . '/clilib.php');


/**
 * @property role_model $input
 */
class role extends base_play {
    /**
     * // TODO
     *
     * This play takes a {@link role_model} and ensures that the role exists with the specified capabilities and contexts.
     *
     * {@link get_output} returns a list of all roles as an array of {@link role_model} objects.
     *
     * @param role_model $input
     */
    public function __construct(role_model $input) {
        parent::__construct($input);
    }


    /**
     * @throws coding_exception
     */
    protected function play_implementation(): bool {
        $state_changed = false;

        // get role by name
        $role_id = $this->get_role_id($this->input->role_name);
        if ($role_id !== false) {
            // compare role capabilities
            $capabilities_changed = $this->update_role_capabilities($role_id);
            if ($capabilities_changed) {
                $state_changed = true;
            }
            // compare role contexts
            $contexts_changed = $this->update_role_contexts($role_id);
            if ($contexts_changed) {
                $state_changed = true;
            }
        } else {
            // create role
            $this->create_role();
            $state_changed = true;
        }

        return $state_changed;
    }


    /**
     * @return bool True if state changed, false otherwise
     */
    private function update_role_contexts(int $role_id): bool {
        $existing_context_levels = get_role_contextlevels($role_id);
        $contexts_diff = array_diff($existing_context_levels, $this->input->list_of_contexts) || array_diff($this->input->list_of_contexts, $existing_context_levels);
        if ($contexts_diff) {
            set_role_contextlevels($role_id, $this->input->list_of_contexts);
            return true;
        }

        return false;
    }

    /**
     * @return bool True if state changed, false otherwise
     * @throws coding_exception
     */
    private function update_role_capabilities(int $role_id): bool {
        $state_changed = false;
        // compare existing role against desired role and remove capabilities that are not in the desired capabilities
        foreach (di::get(moodle_core_repository::class)->get_capabilities_of_role($role_id) as $capability) {
            if (!array_key_exists($capability->capability, $this->input->list_of_capabilities)) {
                di::get(moodle_core::class)::unassign_capability($capability->capability, $role_id);
                $state_changed = true;
                cli_writeln("Unassigned capability {$capability->capability} from role {$this->input->role_name}");
            }
        }

        // assign new capabilities and update existing capabilities
        foreach ($this->input->list_of_capabilities as $capability => $permission) {
            $capability_exists = false;
            foreach (di::get(moodle_core_repository::class)->get_capabilities_of_role($role_id) as $existing_capability) {
                if ($existing_capability->capability == $capability) {
                    $capability_exists = true;
                    if ($existing_capability->permission != $permission) {
                        di::get(moodle_core::class)::assign_capability($capability, $permission, $role_id, 1, true);
                        $state_changed = true;
                        cli_writeln("Updated capability {$capability} to permission {$permission} for role {$this->input->role_name}");
                    }
                }
            }
            if (!$capability_exists) {
                di::get(moodle_core::class)::assign_capability($capability, $permission, $role_id, 1, true);
                $state_changed = true;
                cli_writeln("Assigned capability {$capability} to permission {$permission} for role {$this->input->role_name}");
            }
        }

        return $state_changed;
    }

    private function create_role(): void {
        $role_id = di::get(moodle_core::class)::create_role($this->input->role_name, $this->input->shortname, $this->input->description, $this->input->archetype);
        foreach ($this->input->list_of_capabilities as $capability => $permission) {
            di::get(moodle_core::class)::assign_capability(
                $capability,
                $permission,
                $role_id,
                1,  // no idea what exactly this is for, seems like moodle allows assigning a capability only for a specific context. Could not find documentation about it, in UI it seems there is no way to use this feature. The default roles have always "1" as context id.
                true  // overwrites existing capabilities
            );
        }

        // set context levels where the role can be assigned
        set_role_contextlevels($role_id, $this->input->list_of_contexts);
    }

    private function get_role_id(string $role_name): int|false {
        foreach (di::get(moodle_core::class)::get_all_roles() as $role) {
            if ($role->name == $role_name) {
                return $role->id;
            }
        }
        return false;
    }

    public function get_output_implementation(): array {
        foreach (di::get(moodle_core::class)::get_all_roles() as $role) {
            $capabilities = [];
            foreach (di::get(moodle_core_repository::class)->get_capabilities_of_role($role->id) as $capability) {
                $capabilities[$capability->capability] = intval($capability->permission);
            }

            $roles[$role->name] = new role_model(
                $role->name,
                $capabilities,
                array_map('intval', get_role_contextlevels($role->id)),
                $role->shortname,
                $role->description,
                $role->archetype
            );
        }
        return $roles;
    }
}