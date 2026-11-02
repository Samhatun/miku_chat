<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    protected $fillable = ['sender_id', 'receiver_id', 'status'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /** Friendship row between two users, in either direction. */
    public function scopeBetween(Builder $query, int $a, int $b): Builder
    {
        return $query->where(function ($q) use ($a, $b) {
            $q->where(fn ($q) => $q->where('sender_id', $a)->where('receiver_id', $b))
              ->orWhere(fn ($q) => $q->where('sender_id', $b)->where('receiver_id', $a));
        });
    }
}