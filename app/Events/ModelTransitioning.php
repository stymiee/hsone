<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelTransitioning
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model being transitioned
     * @param string|null $oldState The current state
     * @param string $newState The state being transitioned to
     */
    public function __construct(
        public Model $model,
        public ?string $oldState,
        public string $newState
    ) {
        //
    }
}
