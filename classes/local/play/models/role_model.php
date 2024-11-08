<?php

namespace local_adlersetup\local\play\models;

use InvalidArgumentException;

class role_model {
    /**
     * @param string $role_name
     * @param array[] $list_of_capabilities {@link $list_of_capabilities}
     * @param int[] $list_of_contexts {@link $list_of_contexts}
     * @param string|null $shortname defaults to {@link $role_name}
     * @param string $description
     * @param string $archetype
     */
    public function __construct(string $shortname, array $list_of_capabilities, array $list_of_contexts, string|null $role_name = null, string $description = '', string $archetype = '') {
        // assert all list_of_contexts keys are integers
        foreach ($list_of_contexts as $context) {
            if (!is_int($context)) {
                throw new InvalidArgumentException('Contexts must be integers');
            }
        }

        // assert all list_of_contexts keys are integers
        foreach ($list_of_capabilities as $capability => $permission) {
            if (!is_string($capability)) {
                throw new InvalidArgumentException('Capability key must be a string');
            }
            if (!is_int($permission)) {
                throw new InvalidArgumentException('Permission value must be an integer');
            }
        }

        $this->role_name = $role_name ?? $shortname;
        $this->list_of_capabilities = $list_of_capabilities;
        $this->list_of_contexts = $list_of_contexts;
        $this->shortname = $shortname;
        $this->description = $description;
        $this->archetype = $archetype;
    }

    /**
     * @var string $role_name
     */
    public string $role_name;
    /**
     * @var string[] $list_of_capabilities List of capabilities (name as string) and their permissions (CAP_ constant):
     * ['mod/adleradaptivity:edit' => CAP_ALLOW]
     */
    public array $list_of_capabilities;

    /**
     * @var int[] $list_of_contexts List of CONTEXT_ constants. Contexts where this role can be assigned.
     */
    public array $list_of_contexts;

    /**
     * @var string $shortname Role shortname
     */
    public string $shortname;

    /**
     * @var string $description Role description
     */
    public string $description;

    /**
     * @var string $archetype Role archetype (the role this role inherits capabilities from)
     */
    public string $archetype;
}
