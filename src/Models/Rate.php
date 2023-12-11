<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    protected $table    = "rates"; 
    protected $fillable = ['crypto_currency', 'fiat_currency', 'rate','created_at','updated_at'];
}
