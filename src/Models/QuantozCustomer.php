<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use WalletApi\Configuration\Models\QuantozLog;
use Illuminate\Support\Facades\DB;
use WalletApi\Configuration\Models\QuantozApiToken;
use Carbon\Carbon;
use App\Http\Controllers\API;

class QuantozCustomer extends Model
{
    use HasFactory;
    public $timestamps = true;
    protected $table = "customers";
    
    /**
     * This function is used to create quantoz token for add demand account
     * @return string access_token
     */
    public static function createQuantozTokenAddDemand()
    {
        try {
            $headers = ["Cache-Control: no-cache", "Content-Type: application/x-www-form-urlencoded"];
            $postFields = config('constants.QUANTOZ_IDENTITY_POST_FIELDS_AD');
            $qlogId = QuantozLog::insertGetId(['raw_request' => json_encode(['headers' => $headers, 'post_fields' => $postFields]),  'method' => 'createQuantozTokenAddDemand']);
            $response = curlRequest(config('constants.QUANTOZ_TOKEN_URL'), $postFields, true, $headers, false);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($response)]);
            $response = json_decode($response, true);
            if (isset($response['access_token'])) {
                QuantozApiToken::where('account', 'quantoz_ad')->update(['token' => $response['access_token']]);
                return $response['access_token'];
            } else {
                $data = "Quantoz Token not generated for AddDemand ,  method:createQuantozTokenAddDemand";
                slackCurlRequest($data);
            }
            return null;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Quantoz AddDemand Token';
            slackCurlRequest($data);
            return serverError($e, 'Create Quantoz Token');
        }
    }


    /**
     * This function is used to create quantoz token for quickbit account
     * @return string access_token
     */
    public static function createQuantozToken($token)
    {
        try {
            $headers = ["Cache-Control: no-cache", "Content-Type: application/x-www-form-urlencoded"];
            $token == 'quantoz_ad' ? $postFields = config('constants.QUANTOZ_IDENTITY_POST_FIELDS_AD') : $postFields = config('constants.QUANTOZ_IDENTITY_POST_FIELDS');

            $qlogId = QuantozLog::insertGetId(['raw_request' => json_encode(['headers' => $headers, 'post_fields' => $postFields]),  'method' => 'createQuantozToken']);
            $response = curlRequest(config('constants.QUANTOZ_TOKEN_URL'), $postFields, true, $headers, false);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($response)]);
            $response = json_decode($response, true);

            if (isset($response['access_token'])) {
                QuantozApiToken::where('account', $token)->update(['token' => $response['access_token']]);
                return $response['access_token'];
            } else {
                $data = "Quantoz Token Not Generated || Method: Create Quantoz Token | Quantoz Customer.php";
                slackCurlRequest($data);
            }
            return null;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Quantoz Token';
            slackCurlRequest($data);
            return serverError($e, 'Create Quantoz Token');
        }
    }

    /**
     * This function is used to get quantoz token
     * @return string token
     */
    public static function getQuantozToken($request)
    {
        try {
            $token = DB::table('quantoz_api_token')->where('account', 'quantoz_qb')->first()->token;
            return $token;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Quantoz Token';
            slackCurlRequest($data);
            return serverError($e, 'Get Quantoz Token');
        }
    }

    /**
     * This function is used to check if customer is available on quantoz or not.
     * 1) Hit /Customer/{customerCode} if customer exists it will return customer data else return message "Customer Not Available".
     * @param  Request $request
     * @return JSON response
     */
    public static function checkQuantozCustomer($customerCode, $request)
    {
        try {
            $qlogId = QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => $customerCode, 'raw_request' => json_encode($request), 'method' => 'checkQuantozCustomer']);
            $headers = ["Authorization: Bearer " . self::getQuantozToken($request), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $customerExistResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "Customer/{$customerCode}", false, false, $headers);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($customerExistResponse)]);
            return $customerExistResponse;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Check Quantoz Customer \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Check Quantoz Customer');
        }
    }

    /**
     * This function is used to create customer on quantoz with provided customer_code.
     * @param  Request $request
     * @return array response
     */

    public static function createQuantozCustomer($customerCode, $request)
    {
        try {
            $qlogId = QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => $customerCode, 'raw_request' => json_encode($request), 'method' => 'createQuantozCustomer']);
            $headers = ["Authorization: Bearer " . self::getQuantozToken($request), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $data = array(
                "customerCode" => $customerCode,
                "email" => $request['email'],
                "currencyCode" => 'EUR',
                "trustLevel" => 'Trusted',
                'status' => 'ACTIVE',
                "accountType" => "BrokerBuyOnly",
            );
            $customerResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "Customer", json_encode($data), true, $headers, false);
            QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => 'Checking', 'raw_request' => $customerResponse['header_code'], 'raw_response' => json_encode($customerResponse), 'method' => 'createQuantozCustomer - Customer']);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($customerResponse)]);
            if ($customerResponse['header_code'] != 201) {
                $data = "Quantoz Customer not generated for " . $request['source'] . $request['request_id'] . ", || Response:\n" . $customerResponse . "  method: checkQuantozCustomer";
                slackCurlRequest($data);
            }
            return $customerResponse;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Quantoz Customer \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Create Quantoz Customer');
        }
    }

    /**
     * This function is used to create customer account on quantoz with provided customerCode and cryptoAddress.
     * @param  string $customerCode
     * @param  array $request
     * @param  string $ltc_address
     * @return array response
     */

    public static function createQuantozCustomerAccount($customerCode, $request, $ltc_address)
    {
        try {
            $qlogId = QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => $customerCode, 'raw_request' => json_encode($request), 'method' => 'createQuantozCustomerAccount']);
            $headers = ['Authorization: Bearer ' . self::getQuantozToken($request), 'Cache-Control: no-cache', 'Content-Type: application/json', 'api_version: 1.2'];
            $data = [
                "customerCryptoAddress" => $ltc_address,
                "dcCode" => $request['altcoin'],
                "ip" => $request['customer_ip'],
                "accountType" => "BrokerBuyOnly",
            ];
            $customerAccountResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "customer/{$customerCode}/accounts", json_encode($data), true, $headers, false);
            if ($customerAccountResponse['header_code'] == 400) {
                $addressRequest = new Request((array)$request);
                $createAddressData = API::createAddress($addressRequest, false)->content();
                $createAddressData = json_decode($createAddressData, true);
                $ltc_address = $createAddressData["data"]["address"];
                $data['customerCryptoAddress'] = $ltc_address;
                $customerAccountResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "customer/{$customerCode}/accounts", json_encode($data), true, $headers, false);
            }
            if ($customerAccountResponse['header_code'] == 400) {
                $data = "Quantoz Customer Account not created for " . $request['source'] . $request['request_id'] . ",  method: checkQuantozCustomer";
                slackCurlRequest($data);
            }
            $customerAccountCode = json_decode($customerAccountResponse['body'], true)['values']['accountCode'];
            $customerAccountActivation = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "customer/{$customerCode}/accounts/activate/{$customerAccountCode}", null, false, $headers, false);
            QuantozLog::insert(['customer_code' => $customerCode, 'raw_request' => 'Activate Account', 'raw_response' => json_encode($customerAccountActivation), 'method' => 'createQuantozCustomerAccount - active']);

            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($customerAccountResponse)]);
            return $customerAccountResponse;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Quantoz Customer Account \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Create Quantoz Customer Account');
        }
    }

    /**
     * This function is used to check whether the customer account is available on quantoz or not
     * @param string $customerCode
     * @param string $accountCode
     * @param array $request
     */
    public static function checkQuantozCustomerAccount($customerCode, $accountCode, $request)
    {
        try {
            $request['customerCode'] = $customerCode;
            $request['accountCode'] = $accountCode;

            $qlogId = QuantozLog::insertGetId(['customer_code' => $customerCode, 'raw_request' => json_encode($request), 'method' => 'checkQuantozCustomerAccount']);
            $headers = ["Authorization: Bearer " . self::getQuantozToken($request), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $customerAccountResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "customer/{$customerCode}/accounts/{$accountCode}", null, true, $headers, false);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($customerAccountResponse)]);

            return $customerAccountResponse;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Check Quantoz Customer Account';
            slackCurlRequest($data);
            return serverError($e, 'Check Quantoz Customer Account');
        }
    }

    /**
     * This function is used to Buy crypto on a provided customer acount and crypto address.
     * 1) Call getBuyPriceSummary to get the transaction summary.
     * 2) Call quantozBuy to create the transaction on Quantoz.
     * 3) Add transaction record in quantoz_order table
     * 4) Call finalCallToSendCoins to further process transaction on quantoz.
     * @param  Object $quantozCustomerData
     * @param  string $account_code
     * @param  array $data
     * @param  string $ltc_address
     * @return array response
     */

    public static function quantozBuyCrypto($quantozCustomerData, $account_code, $data, $ltc_address)
    {
        try {
            // quantoz buy request
            $qlogId = QuantozLog::insertGetId(['request_id' => $data['source'] . $data['request_id'], 'customer_code' => $quantozCustomerData->customer_code, 'raw_request' => json_encode($data), 'method' => 'quantozBuyCrypto']);
            $buyPriceSummary = self::getBuyPriceSummary($data['fiat_amount'], $data['fiat_currency'], $account_code, $quantozCustomerData->customer_code, $data);
            if ($buyPriceSummary['header_code'] == 400) {
                $data = "Buy Price Summaryn Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: quantozBuyCrypto";
                slackCurlRequest($data);
                return $buyPriceSummary;
            }
            $buyPriceSummary = json_decode($buyPriceSummary['body'], true)['values'];
            $quantozOrderResponse = self::quantozBuy($quantozCustomerData, $data, $ltc_address);

            if ($quantozOrderResponse['header_code'] == 400) {
                $data = "Quantoz Buy Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: quantozBuyCrypto";
                slackCurlRequest($data);
                return $quantozOrderResponse;
            }
            $quantozOrderResponse = json_decode($quantozOrderResponse['body'], true)['values'];
            $order_id = QuantozOrder::insertGetId([
                'account_code' => $quantozOrderResponse['accountCode'],
                'customer_code' => $quantozOrderResponse['customerCode'],
                'transaction_code' => $quantozOrderResponse['transactionCode'],
                'crypto_currency_code' => $data['altcoin'],
                'crypto_amount' => $buyPriceSummary['cryptoAmount'],
                'fiat_currency_code' => $data['fiat_currency'],
                'cp_trx_id' => isset($data['trx_id']) ? $data['trx_id'] : '',
                'fiat_amount' => $data['fiat_amount'],
                'transaction_address' => $ltc_address,
                'network_fee' => $quantozOrderResponse['networkFee'],
                'request_id' => $data['source'] . $data['request_id'],
                'merchant_email' => isset($data['merchant_email']) ? $data['merchant_email'] : $data['affiliate_email'],
                'callback_url' => $data['callback_url']
            ]);
            // quantoz final call back here
            $quantozFinalCallCheck = self::finalCallToSendCoins($quantozOrderResponse, $order_id, $data);
            if (!$quantozFinalCallCheck) {
                $cleanPayResponse = [
                    'status_code' => 200,
                    'error' => false,
                    'message' => "Transaction Initiated Successfully. Wait for the callback",
                    'data' => [
                        'transaction_address' => $ltc_address,
                        'transaction_status' => $quantozOrderResponse['status'],
                        'request_id' => $data['request_id']
                    ]
                ];
            } else {
                $cleanPayResponse = [
                    'status_code' => 400,
                    'error' => true,
                    'message' => "Transaction Failed",
                    'data' => [
                        'transaction_address' => $ltc_address,
                        'transaction_status' => $quantozOrderResponse['status'],
                        'request_id' => $data['request_id']
                    ]
                ];
                $data = "Transaction Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: quantozBuyCrypto";
                slackCurlRequest($data);
            }
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($cleanPayResponse)]);

            return json_encode($cleanPayResponse);
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Cleanpay Direct Flow \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Quantoz Buy Crypto');
        }
    }

    /**
     * This function is used to create transaction on quantoz.
     * 1) Hit /Buy end point of quantoz.
     * @param  Object $quantozCustomerData
     * @param  array $data
     * @param  string $ltc_address
     * @return array response
     */

    public static function quantozBuy($quantozCustomerData, $data, $ltc_address)
    {
        try {
            $qlogId = QuantozLog::insertGetId(['request_id' => $data['source'] . $data['request_id'], 'customer_code' => $quantozCustomerData->customer_code, 'raw_request' => json_encode($data), 'method' => 'quantozBuy']);

            $headers = ["Authorization: Bearer " . self::getQuantozToken($data), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $finalResponse['error'] = true;
            $requestData = array(
                'paymentMethodCode' => config('constants.AD_PAYMENT_METHOD_' . $data['altcoin']),
                'accountCode' => "",
                'amount'      => $data['fiat_amount'],
                'currency'    => 'EUR',
                'buyPrice'    => "",
                "callbackUrl" => config('constants.QUANTOZ_CALL_BACK_URL'),
                "customer" => [
                    "customerCode" => $quantozCustomerData->customer_code,
                    "cryptoCode" => $data['altcoin'],
                    "customerCryptoAddress" =>  $ltc_address,
                    "status" => "ACTIVE",
                    "trustLevel" => "Trusted",
                    "portfolioCode" => "",
                    "currencyCode" => 'EUR',
                    "accountType" => "BrokerBuyOnly"
                ],
                'ip' => $data['customer_ip']
            );
            $quantozBuyResponse = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "Buy", json_encode($requestData), true, $headers, false);
            QuantozLog::where(['id' => $qlogId])->update(['raw_response' => json_encode($quantozBuyResponse)]);

            return $quantozBuyResponse;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Quantoz Buy \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Quantoz Buy');
        }
    }

    /**
     * This function is used to further process transaction on quantoz after hitting /Buy on quantoz.
     * 1) Hit /Transaction/psp/generic on quantoz to process transaction on quantoz.
     * @param  Request $request
     * @return JSON response
     */

    public static function finalCallToSendCoins($transactionDetails, $order_id, $data)
    {
        try {
            $result = true;
            $requestData = array(
                'transactionCode' => $transactionDetails['transactionCode'],
                'statusCode' => 200
            );
            $headers = ["Authorization: Bearer " . self::getQuantozToken($data), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.1"];
            $logId = QuantozLog::insertGetId(['request_id' => $data['source'] . $data['request_id'], 'order_id' => $order_id, 'raw_request' => json_encode(["url" => "/Transaction/psp/generic", $requestData]), 'method' => 'finalCallToSendCoins']);
            $response = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . '/Transaction/psp/generic', json_encode($requestData), true, $headers);
            QuantozLog::where('id', $logId)->update(['raw_response' => json_encode($response)]);
            if ($response['header_code'] == 200) {
                $result = false;
            }

            return $result;
        } catch (\Throwable $e) {
            $data = "Error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Pinal Call To Send Coins \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Final Call to Send Coins');
        }
    }

    /**
     * This function is used get the exchange rate summary from fiat currancy to crypto currency.
     * 1) Hit /Prices/{fiatCurrancy}/{cryptoCurrency} to get the exchange rate summary.
     * @param  Request $request
     * @return JSON response
     */

    public static function getPriceSummarySpecificCurrency($currency, $crypto, $customer_id, $order_id, $data)
    {
        try {
            $data = [
                'currency' => $currency,
                'crypto'   => $crypto
            ];
            $logId = QuantozLog::insertGetId(['customer_id' => $customer_id, 'order_id' => $order_id, 'request_data' => json_encode(["url" => "/Prices/$currency/$crypto", $data]), 'method' => 'getPriceSummarySpecificCurrency']);
            $headers = ["Authorization: Bearer " . self::getQuantozToken($data), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $rates = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "/Prices/$currency/$crypto", false, false, $headers);
            QuantozLog::where('id', $logId)->update(['response_data' => json_encode($rates)]);
            if ($rates['header_code'] == 500) {
                QuantozLog::insert(['api_name' => 'quantoz', 'status_code' => $rates['header_code'], 'raw_response' => json_encode($rates), 'order_id' => $order_id, 'customer_id' => $customer_id, 'method' => 'getPriceSummarySpecificCurrency - 2']);
            }
            if ($rates['header_code'] == 200) {
                $rawResult = json_decode($rates['body'])->values->prices;
                if ('LTC' == $crypto)
                    return $rawResult->LTC->estimatedNetworkSlowFee;
            }

            return null;
        } catch (\Throwable $e) {
            $data = "Error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Price Summary Specific Currency';
            slackCurlRequest($data);
            return serverError($e, 'Price Summary Specific Currency');
        }
    }

    /**
     * This function is used to get the summary of transaction befor hitting /Buy.
     * 1) Hit /Buy/broker/simulate to get the summary of the transaction.
     * @param  Request $request
     * @return JSON response
     */
    public static function getBuyPriceSummary($amount, $currency, $account_code, $customer_id, $request, $order_id = null)
    {
        try {
            $data = [
                'AccountCode' => $account_code,
                'Amount' => $amount,
                'PaymentMethodCode' => config('constants.AD_PAYMENT_METHOD_' . $request['altcoin']),
                'Currency' => $currency,
            ];
            $headers = ["Authorization: Bearer " . self::getQuantozToken($request), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $logId = QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => $customer_id, 'order_id' => $order_id, 'raw_request' => json_encode(["headers" => $headers, "url" => 'Buy/broker/simulate', $data]), 'method' => 'getBuyPriceSummary']);
            $response = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . 'Buy/broker/simulate', json_encode($data), true, $headers);
            QuantozLog::where('id', $logId)->update(['raw_response' => json_encode($response)]);

            return $response;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Buy Price Summary \n Account Code: ' . $account_code;
            slackCurlRequest($data);
            return serverError($e, 'Buy Price Summary');
        }
    }

    /**
     * This function is used to Buy crypto based on contract in request on a provided customer account and crypto address.
     * 1) Call getBuyPriceSummary to get the transaction summary.
     * 2) Call quantozBuy to create the transaction on Quantoz.
     * 3) Add transaction record in quantoz_order table
     * 4) Call finalCallToSendCoins to further process transaction on quantoz.
     * @param  Object $quantozCustomerData
     * @param  string $account_code
     * @param  array $data
     * @param  string $ltc_address
     * @return array response
     */
    public static function quantozBuyCryptoContract($quantoz_customer_data, $account_code, $data, $ltc_address)
    {
        try {
            // quantoz buy request
            $qlog_id = QuantozLog::insertGetId(['request_id' => $data['source'] . $data['request_id'], 'customer_code' => $quantoz_customer_data->customer_code, 'raw_request' => json_encode($data), 'method' => 'quantozBuyCryptoContract']);
            $buyPriceSummary = self::getBuyPriceSummaryContract($data['fiat_amount'], $data['fiat_currency'], $account_code, $quantoz_customer_data->customer_code, $data);
            if ($buyPriceSummary['header_code'] == 400) {
                $data = "Buy Price Summary Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto Contract,  method: getBuyPriceSummaryContract";
                slackCurlRequest($data);
                return $buyPriceSummary;
            }
            $buyPriceSummary = json_decode($buyPriceSummary['body'], true)['values'];
            $quantoz_order_response = self::quantozBuyContract($quantoz_customer_data, $data, $ltc_address);

            if ($quantoz_order_response['header_code'] == 400) {
                $data = "Quantoz Buy Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto Contract,  method: quantozBuyCryptoContract";
                slackCurlRequest($data);
                return $quantoz_order_response;
            }
            $quantoz_order_response = json_decode($quantoz_order_response['body'], true)['values'];
            $order_id = QuantozOrder::insertGetId([
                'account_code' => $quantoz_order_response['accountCode'],
                'customer_code' => $quantoz_order_response['customerCode'],
                'transaction_code' => $quantoz_order_response['transactionCode'],
                'crypto_currency_code' => $data['altcoin'],
                'crypto_amount' => $buyPriceSummary['cryptoAmount'],
                'fiat_currency_code' => $data['fiat_currency'],
                'fiat_amount' => $data['fiat_amount'],
                'transaction_address' => $ltc_address,
                'network_fee' => $quantoz_order_response['networkFee'],
                'request_id' => $data['source'] . $data['request_id'],
                'merchant_email' => isset($data['merchant_email']) ? $data['merchant_email'] : $data['affiliate_email'],
                'callback_url' => $data['callback_url']
            ]);
            // quantoz final call back here
            // $priceSummary = self::getPriceSummarySpecificCurrency($data->fiat_currency, $data->altcoin, $quantoz_customer_data->customer_code, $order_id);
            $quantoz_final_call_check = self::finalCallToSendCoins($quantoz_order_response, $order_id, $data);
            if (!$quantoz_final_call_check) {
                $cleanPay_response = [
                    'status_code' => 200,
                    'error' => false,
                    'message' => "Transaction Initiated Successfully. Wait for the callback",
                    'data' => [
                        'transaction_address' => $ltc_address,
                        'transaction_status' => $quantoz_order_response['status'],
                        'request_id' => $data['request_id']
                    ]
                ];
            } else {
                $cleanPay_response = [
                    'status_code' => 400,
                    'error' => true,
                    'message' => "Transaction Failed",
                    'data' => [
                        'transaction_address' => $ltc_address,
                        'transaction_status' => $quantoz_order_response['status'],
                        'request_id' => $data['request_id']
                    ]
                ];
                $data = "Transaction Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto Contract,  method: quantozBuyCryptoContract";
                slackCurlRequest($data);
            }
            QuantozLog::where(['id' => $qlog_id])->update(['raw_response' => json_encode($cleanPay_response)]);

            return json_encode($cleanPay_response);
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Quantoz Buy Crypto \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Quantoz Buy Crypto');
        }
    }

    /**
     * This function is used to create transaction on quantoz based on contract in request.
     * 1) Hit /Buy end point of quantoz.
     * @param  Object $quantoz_customer_data
     * @param  array $data
     * @param  string $ltc_address
     * @return array response
     */
    public static function quantozBuyContract($quantoz_customer_data, $data, $ltc_address)
    {
        try {
            $qlog_id = QuantozLog::insertGetId(['request_id' => $data['source'] . $data['request_id'], 'customer_code' => $quantoz_customer_data->customer_code, 'raw_request' => json_encode($data), 'method' => 'quantozBuyContract']);
            $headers = ["Authorization: Bearer " . self::getQuantozToken($data), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $quantoz_payment_method = '';

            $quantoz_payment_method = config('constants.QUANTOZ_PAYMENT_METHOD');

            $final_response['error'] = true;
            $request_data = array(
                'paymentMethodCode' => $quantoz_payment_method,
                'accountCode' => "",
                'amount'      => $data['fiat_amount'],
                'currency'    => 'EUR',
                'buyPrice'    => "",
                "callbackUrl" => config('constants.QUANTOZ_CALL_BACK_URL'),
                "customer" => [
                    "customerCode" => $quantoz_customer_data->customer_code,
                    "cryptoCode" => "LTC",
                    "customerCryptoAddress" =>  $ltc_address,
                    "status" => "ACTIVE",
                    "trustLevel" => "Trusted",
                    "portfolioCode" => "",
                    "currencyCode" => 'EUR',
                    "accountType" => "BrokerBuyOnly"
                ],
                'ip' => $data['customer_ip']
            );
            $quantoz_buy_response = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . "Buy", json_encode($request_data), true, $headers, false);
            QuantozLog::where(['id' => $qlog_id])->update(['raw_response' => json_encode($quantoz_buy_response)]);

            return $quantoz_buy_response;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Quantoz Buy Contract \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return serverError($e, 'Quantoz Buy');
        }
    }

    /**
     * This function is used to get the summary of transaction befor hitting /Buy based on contract in request.
     * 1) Hit /Buy/broker/simulate to get the summary of the transaction.
     * @param  double $amount
     * @param  string $currency
     * @param  string $account_code
     * @param  int $customer_id
     * @param  int $request
     * @param  array $request
     * @param  int $order_id
     * @return array response
     */
    public static function getBuyPriceSummaryContract($amount, $currency, $account_code, $customer_id, $request, $order_id = null)
    {
        try {
            $quantoz_payment_method = config('constants.QUANTOZ_PAYMENT_METHOD');
            $data = [
                'AccountCode' => $account_code,
                'Amount' => $amount,
                'PaymentMethodCode' => $quantoz_payment_method,
                'Currency' => $currency,
            ];
            $headers = ["Authorization: Bearer " . self::getQuantozToken($request), "Cache-Control: no-cache", "Content-Type: application/json", "api_version: 1.2"];
            $log_id = QuantozLog::insertGetId(['request_id' => $request['source'] . $request['request_id'], 'customer_code' => $customer_id, 'order_id' => $order_id, 'raw_request' => json_encode(["headers" => $headers, "url" => 'Buy/broker/simulate', $data]), 'method' => 'getBuyPriceSummaryContract']);
            $response = curlRequestQuantoz(config('constants.QUANTOZ_API_URL') . 'Buy/broker/simulate', json_encode($data), true, $headers);
            QuantozLog::where('id', $log_id)->update(['raw_response' => json_encode($response)]);

            return $response;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Buy Price Summary';
            slackCurlRequest($data);
            return serverError($e, 'Buy Price Summary');
        }
    }
}
