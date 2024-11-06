<?php

namespace local_adlersetup\local;

use coding_exception;
use core\context;

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
}
