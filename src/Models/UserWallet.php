<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    use HasFactory;
    protected $table = "user_wallets";
    public $timestamps = FALSE;
}
