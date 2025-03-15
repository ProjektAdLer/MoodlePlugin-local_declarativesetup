<?php

namespace local_declarativesetup\local\play;


global $CFG;
require_once($CFG->libdir . '/clilib.php');

use Exception;
use local_declarativesetup\local\play\exceptions\not_implemented_exception;
use local_declarativesetup\local\play\exceptions\play_was_already_played_exception;
use local_declarativesetup\local\play\exceptions\play_was_not_played_exception;

abstract class base_play {
    protected object|array $input;
    protected bool $played = false;
    protected bool $state_changed = false;
    public function __construct(object|array $input) {
        $this->input = $input;
    }

    /**
     * Play the action. To implement this method, override the {@link play_implementation} method
     *
     * @return bool True if state changed, false otherwise
     * @throws play_was_already_played_exception
     * @throws Exception
     */
    public final function play(): bool {
        cli_writeln('----------------------------------------------');
        cli_writeln('[INFO] Playing action: "' . $this->get_play_name() . '"');
        cli_writeln('----------------------------------------------');
        cli_writeln('[INFO] Desired state: ' . json_encode($this->input));
        if ($this->played) {
            cli_writeln('[ERROR] Action was already played');
            throw new play_was_already_played_exception();
        }
        $this->played = true;
        try {
            $this->state_changed = $this->play_implementation();
        } catch (Exception $e) {
            cli_writeln('[ERROR] Play failed, exception occurred: ' . $e->getMessage());
            throw $e;
        }
        cli_writeln("[INFO] Play \"{$this->get_play_name()}\" finished, changed state: " . ($this->state_changed ? 'yes' : 'no'));
        return $this->state_changed;
    }

    /**
     * Get the output of the action. To implement this method, override the {@link get_output_implementation} method
     *
     * @return array The output of the action
     * @throws play_was_not_played_exception
     * @throws not_implemented_exception
     */
    public final function get_output(): array {
        if (!$this->played) {
            throw new play_was_not_played_exception();
        }
        return $this->get_output_implementation();
    }

    /**
     * @return bool True if the action was played, false otherwise
     */
    public final function get_was_played(): bool {
        return $this->played;
    }


    /**
     * @throws play_was_not_played_exception
     */
    public final function get_state_changed(): bool {
        if (!$this->played) {
            throw new play_was_not_played_exception();
        }
        return $this->state_changed;
    }

    /**
     * Implementation of the play action
     *
     * @return bool True if state changed, false otherwise
     */
    abstract protected function play_implementation(): bool;

    /**
     * Implementation of the get_output method
     *
     * @return array The output of the action
     * @throws not_implemented_exception
     */
    protected function get_output_implementation(): array {
        throw new not_implemented_exception();
    }

    /**
     * @return string The name of the actual current implementation class
     */
    private function get_play_name(): string {
        return basename(str_replace('\\', '/', get_class($this)));
    }
}
