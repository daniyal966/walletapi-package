<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantozLog extends Model
{
    use HasFactory;
    public $timestamps = true;
    // protected $table = "quantoz_logs";
    protected $table = "order_logs";
}
