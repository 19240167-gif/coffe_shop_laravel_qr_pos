<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'table_seat_id',
        'handled_by',
        'customer_name',
        'customer_note',
        'status',
        'payment_status',
        'subtotal',
        'tax',
        'total',
        'ordered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function tableSeat(): BelongsTo
    {
        return $this->belongsTo(TableSeat::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
