<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $table = "transactions";

    public $timestamps = FALSE;

    public static function getTransactionOnAddress($address) {
        try {
            $data = Transaction::where('address', $address)->select(DB::raw("CASE WHEN category = 'send' THEN 'sent' else 'received' END as category"), 'address', 'amount', 'fee', 'confirmations', 'tx_hash', 'status', DB::raw("to_timestamp(cast(tx_unix_timestamp as bigint))::timestamp as created_at"))->get();
            return $data;
        } catch (\Throwable $e) {
            $data = "error: In ".env('APP_ENV')." ENV ".str_replace('"' , ' ' ,$e->getMessage())."on  Line Number ".$e->getLine()." in file ".$e->getFile()." method: Get Transaction By Address";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage()." Line Number ". $e->getLine(),'method'=>'Get Transaction By Address']);
            return false;
        }
    }

}
