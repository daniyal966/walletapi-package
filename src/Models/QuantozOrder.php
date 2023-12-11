<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantozOrder extends Model
{
    use HasFactory;
    public $timestamps = true;
    // protected $table = "quantoz_orders";
    protected $table = "orders";

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

    /**
     * This function is used to fetch all quantoz transaction of a customer
     * @param string $customerCode
     * @param array $data
     * @return array $response
     */
    public static function getQuantozTransactions($customerCode, $data)
    {
        $qlog_id = QuantozLog::insertGetId(['customer_code' => $customerCode, 'raw_request' => json_encode($data)]);
        $sql =  self::where('customer_code', $customerCode);
        $fields = json_decode($data['fields'], true);
        foreach ($fields['search_filter'] as $key => $value) {
            if ($value != null) {
                if ($key == 'start_date')
                    $sql->where('created_at', '>=', $value);
                else if ($key == 'end_date')
                    $sql->where('created_at', '<=', $value);
                else if ($key == 'transaction_code')
                    $sql->where('transaction_code', '=', $value);
                else if ($key == 'status')
                    $sql->where('transaction_status', '=', $value);
                else if ($key == 'request_id')
                    $sql->where('request_id', '=', $data['source'] . $value);
                else
                    $sql->where($key, '=', $value);
            }
        }
        if ($fields['items_per_page'] >= 1)
            $sql->limit($fields['items_per_page']);
        $quantoz_transactions = $sql->get()->toArray();

        $response = array();
        foreach ($quantoz_transactions as $key => $transaction) {
            $response[$key]['request_id'] = preg_replace("/[^0-9.]+/", '', $transaction['request_id']);
            $response[$key]['transaction_code'] = $transaction['transaction_code'];
            $response[$key]['merchant_email'] = $transaction['merchant_email'];
            $response[$key]['transaction_address'] = $transaction['transaction_address'];
            $response[$key]['transaction_hash'] = $transaction['transaction_hash'];
            $response[$key]['transaction_status'] = $transaction['transaction_status'];
            $response[$key]['fiat_currency'] = $transaction['fiat_currency_code'];
            $response[$key]['fiat_amount'] = $transaction['fiat_amount'];
            $response[$key]['crypto_currency'] = $transaction['crypto_currency_code'];
            // $response[$key]['crypto_amount'] = $transaction['crypto_amount'];
            $response[$key]['crypto_amount'] = $transaction['received_crypto_amount'];
            $response[$key]['network_fee'] = $transaction['network_fee'];
        }
        QuantozLog::where(['id' => $qlog_id])->update(['raw_response' => json_encode($response)]);

        return $response;
    }

    /**
     * This function is used to fetch single quantoz transaction of a customer
     * @param array $data
     * @return array $response
     */
    public static function getQuantozTransaction($data)
    {
        try {
            $qlog_id = QuantozLog::insertGetId(['raw_request' => json_encode($data)]);
            if ($data['altcoin'] !== 'ETH') {
                $transaction_data = Order::select('request_id', 'transaction_code', 'merchant_email', 'transaction_address', 'transaction_hash', 'transaction_status', 'fiat_currency_code', 'fiat_amount', 'crypto_currency_code', 'received_crypto_amount', 'network_fee')->where('transaction_code', $data['transaction_code'])->first();
            } else {
                $transaction_data = NftRequests::select('request_id', 'cp_trx_id as reference_id', 'mint_transaction_hash', 'transfer_transaction_hash', 'transfer_transaction_fees', 'status', 'crypto_amount',  'mint_transaction_fees')->where('cp_trx_id', $data['transaction_code'])->first();
            } 
            $response = [];
            $transaction_data_array = $transaction_data->toArray();
            $response['request_id'] = preg_replace("/[^0-9.]+/", '', $transaction_data['request_id']);
            $merge_array = array_merge($transaction_data_array, $response);
            QuantozLog::where(['id' => $qlog_id])->update(['raw_response' => json_encode($merge_array)]);
            return $merge_array;
        } catch (\Throwable $e) {
            $data = "error: In ".env('APP_ENV')." ENV ".str_replace('"' , ' ' ,$e->getMessage())."on  Line Number ".$e->getLine()." in file ".$e->getFile()." method: Get Quantoz Transactions";
            slackCurlRequest($data);
            return serverError($e, 'Get Quantoz Transactions');
        }
    }
}
