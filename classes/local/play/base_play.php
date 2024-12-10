<?php


namespace local_declarativesetup\local\play;

use local_declarativesetup\local\play\exceptions\not_implemented_exception;
use local_declarativesetup\local\play\exceptions\play_was_already_played_exception;
use local_declarativesetup\local\play\exceptions\play_was_not_played_exception;
use stdClass;

abstract class base_play {
    // TODO: implement logging. like: what is the desired state, did something change?
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
     */
    public final function play(): bool {
        if ($this->played) {
            throw new play_was_already_played_exception();
        }
        $this->played = true;
        $this->state_changed = $this->play_implementation();
        return $this->state_changed;
    }

    /**
     * Implementation of the play action
     *
     * @return bool True if state changed, false otherwise
     */
    abstract protected function play_implementation(): bool;

    /**
     * @return bool True if the action was played, false otherwise
     */
    public function get_was_played(): bool {
        return $this->played;
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
     * @throws play_was_not_played_exception
     */
    public function get_state_changed(): bool {
        if (!$this->played) {
            throw new play_was_not_played_exception();
        }
        return $this->state_changed;
    }

    /**
     * Implementation of the get_output method
     *
     * @return array The output of the action
     * @throws not_implemented_exception
     */
    protected function get_output_implementation(): array {
        throw new not_implemented_exception();
    }
}
