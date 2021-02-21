<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subscription_id',
        'type',
        'start_date',
        'end_date',
        'order_id',
        'checkout_id',
        'payment_method',
        'payment_date',
        'status',
        'action',
        'paddle_subscription_id',
        'receipt_url'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function subscription() {
        return $this->belongsTo(Subscription::class);
    }
}
