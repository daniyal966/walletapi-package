<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantozApiToken extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = 'quantoz_api_token';
    protected $fillable = ['token', 'account'];
}
