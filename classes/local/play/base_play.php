<?php


namespace local_adlersetup\local\play;

use local_adlersetup\local\play\exceptions\not_implemented_exception;
use local_adlersetup\local\play\exceptions\play_was_already_played_exception;
use local_adlersetup\local\play\exceptions\play_was_not_played_exception;

abstract class base_play {
    protected object|array $input;
    protected bool $played = false;
    protected bool $state_changed = false;
    public function __construct(object|array $input) {
        $this->input = $input;
    }

    /**
     * Play the action. To implement this method, override the @link play_implementation method
     *
     * @return bool True if state changed, false otherwise
     * @throws play_was_already_played_exception
     */
    public final function play(): bool {
        if ($this->played) {
            throw new play_was_already_played_exception();
        }
        $this->played = true;
        $result = $this->play_implementation();
        $this->state_changed = $result;
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
     * Get the output of the action. To implement this method, override the @link get_output_implementation method
     *
     * @return object The output of the action
     * @throws play_was_not_played_exception
     * @throws not_implemented_exception
     */
    public final function get_output(): object {
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
     * @return object The output of the action
     * @throws not_implemented_exception
     */
    protected function get_output_implementation(): object {
        throw new not_implemented_exception();
    }
}
