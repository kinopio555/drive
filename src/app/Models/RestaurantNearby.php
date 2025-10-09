<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantNearby extends Model
{
    use HasFactory;

    protected $table = 'restaurants_nearby';

    protected $fillable = [
        'user_id',
        'origin',
        'destination',
        'restaurants_names',
    ];

    protected $casts = [
        'restaurants_names' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
