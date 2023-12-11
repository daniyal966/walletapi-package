<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'affiliate_id',
        'request_id',
        'customer_email',
        'merchant_email',
        'fiat_amount',
        'fiat_currency',
        'callback_url',
        'transaction_id',
        'eth_amount',
        'nft_unique_id',
        'transaction_status',
        'transaction_hash',
        'received_crypto_amount',
        'cp_transaction_id'
    ];

    public static function getOrderDetails($transaction_code)
    {
        return self::where('transaction_code', $transaction_code)->first();
    }
}
