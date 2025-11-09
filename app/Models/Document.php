<?php

namespace App\Models;

use App\Traits\StateMachine;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use StateMachine;

    /**
     * Define the states and their allowed transitions.
     *
     * @var array<string, array<string>>
     */
    public static array $states = [
        'draft' => ['submitted'],
        'submitted' => ['approved', 'rejected'],
        'approved' => [],
        'rejected' => [],
    ];

    protected $fillable = [
        'title',
        'content',
        'state',
    ];
}
