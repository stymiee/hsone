<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ModelTransitioned
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Model $model The model that was transitioned
     * @param string|null $oldState The previous state
     * @param string $newState The new state
     */
    public function __construct(
        public Model $model,
        public ?string $oldState,
        public string $newState
    ) {
        //
    }
}
