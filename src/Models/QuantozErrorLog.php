<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantozErrorLog extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "quantoz_error_logs";
}
