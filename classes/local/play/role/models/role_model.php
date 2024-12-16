<?php

namespace local_declarativesetup\local\play\role\models;

use invalid_parameter_exception;

class role_model {
    /**
     * @param string $shortname
     * @param array[] $list_of_capabilities List of capabilities (name as string) and their permissions (CAP_ constant):
     *  ['mod/adleradaptivity:edit' => CAP_ALLOW]
     * @param int[]|null $list_of_contexts List of CONTEXT_ constants. Contexts where this role can be assigned. If null, contexts will not be changed.
     * @param bool $replace_capabilities If true, all capabilities not in the list will be removed from the role.
     * @param string|null $role_name defaults to {@link $shortname}
     * @param string $description
     * @param string $archetype Role archetype (the role this role inherits capabilities from)
     * @throws invalid_parameter_exception
     */
    public function __construct(public string     $shortname,
                                public array      $list_of_capabilities,
                                public array|null $list_of_contexts = null,
                                public bool       $replace_capabilities = true,
                                string|null       $role_name = null,
                                public string     $description = '',
                                public string     $archetype = '') {
        if ($list_of_contexts !== null) {
            foreach ($list_of_contexts as $context) {
                if (!is_int($context)) {
                    throw new invalid_parameter_exception('Contexts must be integers');
                }
            }
        }

        foreach ($list_of_capabilities as $capability => $permission) {
            if (!is_string($capability)) {
                throw new invalid_parameter_exception('Capability key must be a string');
            }
            if (!is_int($permission)) {
                throw new invalid_parameter_exception('Permission value must be an integer');
            }
        }

        $this->role_name = $role_name ?? $shortname;
    }

    public string $role_name;
}
