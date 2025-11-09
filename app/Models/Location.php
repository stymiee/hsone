<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'city',
        'state',
        'address',
    ];

    /**
     * Get the appointments for the location.
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
