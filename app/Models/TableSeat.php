<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TableSeat extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'qr_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $tableSeat): void {
            if (empty($tableSeat->qr_token)) {
                $tableSeat->qr_token = (string) Str::uuid();
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
