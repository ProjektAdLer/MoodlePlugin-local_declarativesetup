<?php

namespace local_declarativesetup\local\play\config\models;

class config_model {
    public string $config_name;

    /**
     * @var string|bool|int|null $config_value The value to set the config to. If null, the config will be deleted.
     */
    public string|bool|int|null $config_value;

    /**
     * @var bool $forced If true, the config will be in config.php and can't be changed in the admin interface.
     */
    public bool $forced;

    /**
     * @var string|null $plugin The plugin to set the config for. If null, it's a core config.
     */
    public string|null $plugin;

    /**
     * @param string $config_name
     * @param string|null $config_value see {@link $config_value}
     * @param bool $forced see {@link $forced}
     * @param string|null $plugin see {@link $plugin}
     */
    public function __construct(string $config_name, string|null $config_value, bool $forced = false, string|null $plugin = null) {
        $this->config_name = $config_name;
        $this->config_value = $config_value;
        $this->forced = $forced;
        $this->plugin = $plugin;
    }
}