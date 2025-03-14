<?php

namespace local_declarativesetup\local\play\config\models;

use invalid_parameter_exception;

class array_config_model extends config_model {
    /**
     * To remove a setting use {@link simple_config_model} with null as value.
     *
     * @param string $config_name
     * @param string[] $values_present The values that should be present in the config.
     * @param string[] $values_absent The values that should be absent in the config. Set to ['*'] to remove all values not in {@link $values_present}.
     * @param bool $forced If true, the config will be in config.php and can't be changed in the admin interface.
     * @param string|null $plugin The plugin to set the config for. If null, it's a core config.
     * @throws invalid_parameter_exception
     */
    public function __construct(
        string $config_name,
        public array $values_present = [],
        public array $values_absent = [],
        bool $forced = false,
        ?string $plugin = null
    ) {
        parent::__construct($config_name, $forced, $plugin);

        foreach ($values_absent as $value) {
            if (in_array($value, $values_present)) {
                throw new invalid_parameter_exception('Value ' . $value . ' is in both present and absent list');
            }
        }

        if (in_array('*', $values_absent) && count($values_absent) !== 1) {
            throw new invalid_parameter_exception('If * is in the absent list, it must be the only element');
        }
    }
}