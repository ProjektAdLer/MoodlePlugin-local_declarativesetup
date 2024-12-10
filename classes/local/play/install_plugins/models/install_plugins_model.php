<?php

namespace local_declarativesetup\local\play\install_plugins\models;

// TODO: do this refactoring for all models. also update docs
use invalid_parameter_exception;

class install_plugins_model {
    /**
     * @param string $version The release version or branch of the plugin to install (e.g. "1.0.0" or "main")
     * @param string $moodle_name The name of the plugin in Moodle (e.g. "mod_adleradaptivity")
     * @param string|null $github_project The GitHub project path in the form of "<user or group>/<repo>" (e.g. "ProjektAdler/MoodlePluginModAdleradaptivity")
     * @param string|null $package_repo The path to the package repository (see <a href="https://github.com/ProjektAdLer/PackageRegistry">ProjektAdLer/PackageRegistry</a>). <package_repo>/<moodle_name>/<version>.zip
     * @throws invalid_parameter_exception
     */
    public function __construct(public string $version, public string $moodle_name, public string|null $github_project = null, public string|null $package_repo = null) {
        if ($this->github_project === null && $this->package_repo === null) {
            throw new invalid_parameter_exception("Either github_project or package_repo is required");
        }
    }
}
