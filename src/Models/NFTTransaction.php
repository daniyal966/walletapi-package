<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NFTTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'affiliate_id',
        'request_id',
        'user_id',
        'trx_id',
        'customer_email',
        'merchant_email',
        'fiat_amount',
        'crypto_amount',
        'conversion_rate',
        'fiat_currency',
        'crypto_currency',
        'callback_url',
        'psp_mid',
        'customer_address',
        'merchant_address',
        'nft_id',
        'nft_name',
        'status',
        'mint_transaction_hash',
        'transfer_transaction_hash',
        'mint_transaction_fee',
        'transaction_transaction_fee',
        'fiat_gas_fee',
        'source',
        'callback_status',
        'is_notified',
        'bin',
        'card',
        'customer_ip',
        'flow_wallet',
        'token_id'
    ];
    protected $table = 'nft_transactions';
}
