<?php

namespace local_adlersetup\local\play\install_plugins\models;


class install_plugins_model {
    public function __construct(string $github_project, string $version, string $moodle_name) {
        $this->github_project = $github_project;
        $this->version = $version;
        $this->moodle_name = $moodle_name;
    }

    /**
     * @var string $github_project The GitHub project path in the form of "<user or group>/<repo>" (e.g. "ProjektAdler/MoodlePluginModAdleradaptivity")
     */
    public string $github_project;
    /**
     * @var string $version The release version or branch of the plugin to install (e.g. "1.0.0" or "main")
     */
    public string $version;

    /**
     * @var string $moodle_name The name of the plugin in Moodle (e.g. "mod_adleradaptivity")
     */
    public string $moodle_name;
}
