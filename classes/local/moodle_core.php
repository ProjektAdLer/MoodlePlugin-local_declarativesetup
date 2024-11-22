<?php

namespace local_adlersetup\local;

use coding_exception;
use context_coursecat;
use core\context;
use stdClass;

/**
 * This class contains aliases for moodle core functions to allow mocking them.
 */
class moodle_core {
    /** alias for {@link get_all_roles()} */
    public static function get_all_roles(...$args): array {
        return get_all_roles(...$args);
    }

    /** alias for {@link create_role()}
     * @throws coding_exception
     */
    public static function create_role(...$args): int {
        return create_role(...$args);
    }

    /** alias for {@link assign_capability()}
     *
     * context id: https://docs.moodle.org/405/en/Override_permissions
     *
     * @throws coding_exception
     */
    public static function assign_capability(...$args): bool {
        return assign_capability(...$args);
    }

    /** alias for {@link unassign_capability()}
     * @throws coding_exception
     */
    public static function unassign_capability(string $capability, int $role_id, int|context $contextid = null): bool {
        return unassign_capability($capability, $role_id);
    }

    /** alias for {@link get_role_contextlevels()} */
    public static function get_role_contextlevels(...$args): array {
        return get_role_contextlevels(...$args);
    }

    /** alias for {@link context_coursecat::instance()} */
    public static function context_coursecat_instance(...$args): object {
        return context_coursecat::instance(...$args);
    }

    /** function that filters {@link get_all_roles()} for a specific role
     *
     * @param string $role_shortname
     * @return stdClass|false
     */
    public static function get_role(string $role_shortname): stdClass|false {
        foreach (self::get_all_roles() as $role) {
            if ($role->shortname == $role_shortname) {
                return $role;
            }
        }
        return false;
    }

    /** alias for {@link get_string_manager()} */
    public static function get_string_manager(...$args): object {
        return get_string_manager(...$args);
    }
}