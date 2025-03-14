<?php

namespace local_declarativesetup\local\play\config\models;

abstract class config_model {
    /**
     * @param string $config_name
     * @param bool $forced If true, the config will be in config.php and can't be changed in the admin interface.
     * @param string|null $plugin The plugin to set the config for. If null, it's a core config.
     */
    public function __construct(public string  $config_name,
                                public bool    $forced = false,
                                public ?string $plugin = null) {}
}