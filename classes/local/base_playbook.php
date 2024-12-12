<?php

namespace local_declarativesetup\local;

use Exception;

abstract class base_playbook {
    /**
     * @param string[] $roles
     */
    public function __construct(protected array $roles) {}

    /**
     * Run the playbook
     */
    final public function run(): void {
        try {
            $this->playbook_implementation();
        } catch (Exception $e) {
            $this->failed($e);
            throw $e;
        }
    }

    protected function has_role(string $role): bool {
        return in_array($role, $this->roles);
    }

    /**
     * Get an environment variable. Variables for playbook should be prefixed with DECLARATIVE_SETUP_.
     *
     * @throws Exception
     */
    protected function get_environment_variable(string $name): string {
        $value = getenv('DECLARATIVE_SETUP_' . $name);
        if ($value === false) {
            throw new Exception("Environment variable $name not set");
        }
        return $value;
    }

    /**
     * Implementation of the playbook
     */
    abstract protected function playbook_implementation(): void;

    /**
     * Called when the playbook failed (exception was thrown).
     * Allows to implement error handling (e.g. activating maintenance mode), the exception will be rethrown afterward.
     */
    abstract protected function failed(Exception $e): void;
}