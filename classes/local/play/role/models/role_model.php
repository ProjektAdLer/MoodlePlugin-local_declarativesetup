<?php

namespace local_declarativesetup\local\play\role\models;

use invalid_parameter_exception;

class role_model {
    /**
     * @param string $shortname see {@link $shortname}
     * @param array[] $list_of_capabilities see {@link $list_of_capabilities}
     * @param int[]|null $list_of_contexts see {@link $list_of_contexts}
     * @param bool $replace_capabilities see {@link $replace_capabilities}
     * @param string|null $role_name defaults to {@link $shortname}
     * @param string $description see {@link $description}
     * @param string $archetype see {@link $archetype}
     * @throws invalid_parameter_exception
     */
    public function __construct(string $shortname, array $list_of_capabilities, array|null $list_of_contexts = null, bool $replace_capabilities = true, string|null $role_name = null, string $description = '', string $archetype = '') {
        // assert all list_of_contexts keys are integers
        if ($list_of_contexts !== null) {
            foreach ($list_of_contexts as $context) {
                if (!is_int($context)) {
                    throw new invalid_parameter_exception('Contexts must be integers');
                }
            }
        }

        // assert all list_of_contexts keys are integers
        foreach ($list_of_capabilities as $capability => $permission) {
            if (!is_string($capability)) {
                throw new invalid_parameter_exception('Capability key must be a string');
            }
            if (!is_int($permission)) {
                throw new invalid_parameter_exception('Permission value must be an integer');
            }
        }

        $this->role_name = $role_name ?? $shortname;
        $this->list_of_capabilities = $list_of_capabilities;
        $this->list_of_contexts = $list_of_contexts;
        $this->replace_capabilities = $replace_capabilities;
        $this->shortname = $shortname;
        $this->description = $description;
        $this->archetype = $archetype;
    }

    public string $role_name;
    /**
     * @var string[] $list_of_capabilities List of capabilities (name as string) and their permissions (CAP_ constant):
     * ['mod/adleradaptivity:edit' => CAP_ALLOW]
     */
    public array $list_of_capabilities;

    /**
     * @var bool $replace_capabilities If true, all capabilities not in the list will be removed from the role.
     */
    public bool $replace_capabilities;

    /**
     * @var int[]|null $list_of_contexts List of CONTEXT_ constants. Contexts where this role can be assigned. If null, contexts will not be changed.
     */
    public array|null $list_of_contexts;

    public string $shortname;

    public string $description;

    /**
     * @var string $archetype Role archetype (the role this role inherits capabilities from)
     */
    public string $archetype;
}
