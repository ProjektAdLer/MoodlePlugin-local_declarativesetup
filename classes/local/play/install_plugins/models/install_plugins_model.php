<?php

namespace local_declarativesetup\local\play\install_plugins\models;

class install_plugins_model {
    /**
     * @param string $version The release version or branch of the plugin to install (e.g. "1.0.0" or "main")
     * @param string $moodle_name The name of the plugin in Moodle (e.g. "mod_adleradaptivity")
     * @param string|null $package_repo The path to the package repository (see <a href="https://github.com/ProjektAdLer/PackageRegistry">ProjektAdLer/PackageRegistry</a>). <package_repo>/<moodle_name>/<version>.zip
     */
    public function __construct(public string      $version,
                                public string      $moodle_name,
                                public string|null $package_repo = null) {}
}
