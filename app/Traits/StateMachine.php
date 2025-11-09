<?php

namespace App\Traits;

use App\Events\ModelTransitioned;
use App\Events\ModelTransitioning;

trait StateMachine
{

    /**
     * Get the name of the state column.
     * Override this method if your state column has a different name.
     *
     * @return string
     */
    protected function getStateColumnName(): string
    {
        return 'state';
    }

    /**
     * Get the current state of the model.
     *
     * @return string|null
     */
    public function getCurrentState(): ?string
    {
        $column = $this->getStateColumnName();
        return $this->getAttribute($column);
    }

    /**
     * Check if a transition from the current state to a new state is allowed.
     *
     * @param string $newState
     * @return bool
     */
    public function canTransitionTo(string $newState): bool
    {
        $currentState = $this->getCurrentState();

        // If no current state, check if new state is a valid initial state
        // (a state that exists as a key in the states array)
        if ($currentState === null || $currentState === '') {
            return isset(static::$states[$newState]);
        }

        // Check if the transition is allowed
        if (!isset(static::$states[$currentState])) {
            return false;
        }

        return in_array($newState, static::$states[$currentState], true);
    }

    /**
     * Check if a state is valid (exists in the states array).
     *
     * @param string $state
     * @return bool
     */
    public function isValidState(string $state): bool
    {
        // Check if state exists as a key or in any transition array
        if (isset(static::$states[$state])) {
            return true;
        }

        // Check if state exists in any transition array
        foreach (static::$states as $transitions) {
            if (in_array($state, $transitions, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all allowed transitions from the current state.
     *
     * @return array<string>
     */
    public function getAllowedTransitions(): array
    {
        $currentState = $this->getCurrentState();

        if ($currentState === null || !isset(static::$states[$currentState])) {
            return [];
        }

        return static::$states[$currentState];
    }

    /**
     * Transition to a new state.
     *
     * @param string $newState
     * @param bool $save Whether to save the model after transition
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function transitionTo(string $newState, bool $save = true): bool
    {
        $currentState = $this->getCurrentState();

        // Validate the transition
        if (!$this->canTransitionTo($newState)) {
            $allowed = $this->getAllowedTransitions();
            $allowedStr = empty($allowed) ? 'none' : implode(', ', $allowed);
            throw new \InvalidArgumentException(
                "Cannot transition from '{$currentState}' to '{$newState}'. Allowed transitions: {$allowedStr}"
            );
        }

        // Fire the transitioning event
        event(new ModelTransitioning($this, $currentState, $newState));

        // Update the state
        $column = $this->getStateColumnName();
        $this->setAttribute($column, $newState);

        // Save if requested
        if ($save) {
            $saved = $this->save();
            if (!$saved) {
                return false;
            }
        }

        // Fire the transitioned event
        event(new ModelTransitioned($this, $currentState, $newState));

        return true;
    }

    /**
     * Get all available states.
     *
     * @return array<string>
     */
    public static function getAvailableStates(): array
    {
        $states = array_keys(static::$states);
        
        // Also include states that are only in transition arrays
        foreach (static::$states as $transitions) {
            foreach ($transitions as $state) {
                if (!in_array($state, $states, true)) {
                    $states[] = $state;
                }
            }
        }

        return array_unique($states);
    }
}

