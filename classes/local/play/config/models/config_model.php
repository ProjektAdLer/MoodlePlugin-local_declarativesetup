<?php

namespace local_declarativesetup\local\play\config\models;

class config_model {
    /**
     * @param string $config_name
     * @param string|bool|int|null $config_value The value to set the config to. If null, the config will be deleted.
     * @param bool $forced If true, the config will be in config.php and can't be changed in the admin interface.
     * @param string|null $plugin The plugin to set the config for. If null, it's a core config.
     */
    public function __construct(public string               $config_name,
                                public string|bool|int|null $config_value,
                                public bool                 $forced = false,
                                public string|null          $plugin = null) {}
}