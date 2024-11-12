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
    /** alias for get_all_roles() */
    public static function get_all_roles(...$args): array {
        return get_all_roles(...$args);
    }

    /** alias for create_role()
     * @throws coding_exception
     */
    public static function create_role(...$args): int {
        return create_role(...$args);
    }

    /** alias for role_exists()
     * @throws coding_exception
     */
    public static function assign_capability(...$args): bool {
        return assign_capability(...$args);
    }

    /**
     * @throws coding_exception
     */
    public static function unassign_capability(string $capability, int $role_id, int|context $contextid = null): bool {
        return unassign_capability($capability, $role_id);
    }

    /** alias for get_role_contextlevels() */
    public static function get_role_contextlevels(...$args): array {
        return get_role_contextlevels(...$args);
    }

    /** alias for context_coursecat::instance() */
    public static function context_coursecat_instance(...$args): object {
        return context_coursecat::instance(...$args);
    }

    public static function get_role(string $role_shortname): stdClass|false {
        foreach (self::get_all_roles() as $role) {
            if ($role->shortname == $role_shortname) {
                return $role;
            }
        }
        return false;
    }
}
