<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use WalletApi\Configuration\Models\Orders;
use WalletApi\Configuration\Models\OrderLogs;
use WalletApi\Configuration\Models\ApiToken;
use WalletApi\Configuration\Models\ErrorLog;
use Illuminate\Http\Request;
use App\Http\Controllers\API;

class Customers extends Model
{
    use HasFactory;

    public static function checkBrokerCustomer($customerCode, $request)
    {

        try {
            $blogId = OrderLogs::insertGetId([
                'request_id' => $request['source'] . $request['request_id'],
                'customer_code' => $customerCode,
                'method' => "checkBrokerCustomer - customer/{$customerCode}",
                'raw_request' => json_encode($request)
            ]);
            $headers = self::getBrokerHeader($request);
            $customerExistResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "customer/{$customerCode}", false, false, $headers);
            OrderLogs::where(['id' => $blogId])->update(['raw_response' => json_encode($customerExistResponse)]);
            return $customerExistResponse;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Check Broker Customer']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Check Broker Customer \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return brokerserverError($e, 'Check Broker Customer');
        }
    }

    public static function getBrokerToken($request)
    {
        try {
            $token = ApiToken::where(['api_name' => 'broker_ad_account'])->first()->token;
            return $token;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Get Broker Token']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Broker Token';
            slackCurlRequest($data);
            return brokerserverError($e, 'Get Broker Token');
        }
    }

    public static function getBrokerHeader($request)
    {
        return [
            'Authorization: Bearer ' . self::getBrokerToken($request),
            'Cache-Control: no-cache',
            'Content-Type: application/json',
            'Accept: application/json'
        ];
    }

    public static function createBrokerCustomer($customerCode, $request)
    {
        try {
            $blogId = OrderLogs::insertGetId([
                'request_id' => $request['source'] . $request['request_id'],
                'method'        => "createBrokerCustomer - customer",
                'customer_code' => $customerCode,
                'raw_request' => json_encode($request)
            ]);
            $headers = self::getBrokerHeader($request);
            $data = array(
                "customerCode" => $customerCode,
                "email" => $request['email'],
                "currencyCode" => 'EUR',
                'status' => 'ACTIVE',
                'countryCode' => 'PK'
            );

            $customerResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "customer", json_encode($data), true, $headers, false);
            OrderLogs::where(['id' => $blogId])->update([
                'raw_response' => json_encode($customerResponse)
            ]);

            if ($customerResponse['header_code'] != 201) {
                $data = "Broker Customer not generated for " . $request['source'] . $request['request_id'] . ", || Response:\n" . $customerResponse . "  method: createBrokerCustomer";
                slackCurlRequest($data);
            }

            return $customerResponse;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Broker Customer']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Broker Customer \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return brokerserverError($e, 'Create Broker Customer');
        }
    }

    public static function createBrokerCustomerAccount($customerCode, $request, $ltc_address)
    {
        try {
            $blogId = OrderLogs::insertGetId(
                [
                    'request_id' => $request['source'] . $request['request_id'],
                    'customer_code' => $customerCode,
                    'method'        => "createBrokerCustomerAccount - customer/account",
                    'raw_request' => json_encode($request)
                ]
            );
            $headers = self::getBrokerHeader($request);
            $data = [
                "customerCode" => $customerCode,
                "customerCryptoAddress" => $ltc_address,
                "cryptoCurrencyCode" => $request['altcoin'],
                "customerIP" => $request['customer_ip'],
                "accountStatus" => "ACTIVE",
            ];
            $customerAccountResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "customer/account", json_encode($data), true, $headers, false);

            OrderLogs::where(['id' => $blogId])->update([
                'raw_response' => json_encode($customerAccountResponse)
            ]);
            if ($customerAccountResponse['header_code'] == 400) {
                $addressRequest = new Request((array)$request);
                $createAddressData = API::createAddress($addressRequest, false)->content();
                $createAddressData = json_decode($createAddressData, true);
                $ltc_address = $createAddressData["data"]["address"];
                $data['customerCryptoAddress'] = $ltc_address;
                $customerAccountResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "customer/account", json_encode($data), true, $headers, false);
            }

            if ($customerAccountResponse['header_code'] == 400) {
                $data = "Broker Customer Account not created for " . $request['source'] . $request['request_id'] . ",  method: createBrokerCustomerAccount";
                slackCurlRequest($data);
            }
            return $customerAccountResponse;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Broker Customer Account']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Create Broker Customer Account \n Request ID: ' . $request['source'] . $request['request_id'];
            slackCurlRequest($data);
            return brokerserverError($e, 'Create Broker Customer Account');
        }
    }

    public static function getBrokerCustomerAccount($customerCode, $request, $ltc_address)
    {
        $blogId = OrderLogs::insertGetId(
            [
                'request_id' => $request['source'] . $request['request_id'],
                'customer_code' => $customerCode,
                'method'        => "getBrokerCustomerAccount - customer/account/$customerCode",
                'raw_request' => json_encode($request)
            ]
        );
        $headers = self::getBrokerHeader($request);
        $customerAccountResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "customer/account/$customerCode", null, false, $headers, false);
        OrderLogs::where(['id' => $blogId])->update([
            'raw_response' => json_encode($customerAccountResponse)
        ]);
        return $customerAccountResponse;
    }

    public static function brokerBuyCrypto($brokerCustomerData, $account_code, $data, $ltc_address)
    {
        $blogId = OrderLogs::insertGetId([
            'request_id' => $data['source'] . $data['request_id'],
            'customer_code' => $brokerCustomerData->customer_code,
            'method'        => "brokerBuyCrypto",
            'raw_request' => json_encode($data)
        ]);

        $buyPriceSummary = self::getBuyPriceSummary($data['fiat_amount'], $data['fiat_currency'], $account_code, $brokerCustomerData->customer_code, $data);

        if ($buyPriceSummary['header_code'] != 200) {
            $data = "Buy Price Summary Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: brokerBuyCrypto";
            slackCurlRequest($data);
            return $buyPriceSummary;
        }
        $buyPriceSummary = json_decode($buyPriceSummary['body'], true)['data'];

        $brokerOrderResponse = self::brokerBuy($brokerCustomerData, $data, $ltc_address, $buyPriceSummary['cryptoAmount']);

        if ($brokerOrderResponse['header_code'] != 200) {
            $data = "Broker Buy Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: brokerBuyCrypto";
            slackCurlRequest($data);
            return $brokerOrderResponse;
        }

        $brokerOrderResponse = json_decode($brokerOrderResponse['body'], true)['data'];

        $order_id = Orders::insertGetId([
            'account_code' => $brokerOrderResponse['accountCode'],
            'transaction_code' => $brokerOrderResponse['transactionCode'],
            'crypto_currency_code' => $data['altcoin'],
            'crypto_amount' => $buyPriceSummary['cryptoAmount'],
            'fiat_currency_code' => $data['fiat_currency'],
            'fiat_amount' => $data['fiat_amount'],
            'cp_trx_id' => isset($data['trx_id']) ? $data['trx_id'] : '' ,
            'transaction_address' => $ltc_address,
            'request_id' => $data['source'] . $data['request_id'],
            'merchant_email' => isset($data['merchant_email']) ? $data['merchant_email'] : $data['affiliate_email'],
            'type'      => 'Broker',
            'callback_url' => $data['callback_url']
        ]);


        if ($order_id) {
            $response = [
                'status_code' => 200,
                'error' => false,
                'message' => "Transaction Initiated Successfully. Wait for the callback",
                'data' => [
                    'transaction_address' => $ltc_address,
                    'transaction_status' => $brokerOrderResponse['status'],
                    'request_id' => $data['request_id']
                ]
            ];
        } else {
            $response = [
                'status_code' => 400,
                'error' => true,
                'message' => "Transaction Failed",
                'data' => [
                    'transaction_address' => $ltc_address,
                    'transaction_status' => $brokerOrderResponse['status'],
                    'request_id' => $data['request_id']
                ]
            ];
            $data = "Transaction Failed for " . $data['source'] . $data['request_id'] . " During Buy Crypto,  method: BrokerBuyCrypto";
            slackCurlRequest($data);
        }

        OrderLogs::where(['id' => $blogId])->update([
            'raw_response' => json_encode($response)
        ]);
        return json_encode($response);
    }

    public static function getBuyPriceSummary($amount, $currency, $account_code, $customer_id, $request, $order_id = null)
    {
        try {
            $blogId = OrderLogs::insertGetId([ 
                'request_id'    => $request['source'].$request['request_id'] , 
                'customer_code' => $customer_id, 
                'order_id'      => $order_id, 
                'method'        => "getBuyPriceSummary Broker - buy/simulate"
            ]);
            
            $broker_payment_method = config('constants.AD_PAYMENT_METHOD_' . $request['altcoin']);

            $data = [
                'AccountCode' => $account_code,
                'FiatAmount' => $amount,
                'PaymentMethodCode' => $broker_payment_method,
                'FiatCurrency' => $currency,
            ];
            $headers = self::getBrokerHeader($request);

            OrderLogs::where('id', $blogId)->update(['raw_request' => json_encode(["headers" => $headers, "url" => 'buy/simulate', $data])]);
            
            $response = curlRequestBroker(config('constants.BROKER_API_URL') . 'buy/simulate', json_encode($data), true, $headers);
            OrderLogs::where('id', $blogId)->update([
                'raw_response' => json_encode($response)
            ]);

            return $response;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Get Broker Buy Price Summary']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Buy Price Summary \n Account Code: ' . $account_code;
            slackCurlRequest($data);
            return brokerserverError($e, 'Buy Price Summary');
        }
    }

    public static function brokerBuy($brokerCustomerData, $data, $ltc_address, $cryptoAmount)
    {
        try {
            $blogId = OrderLogs::insertGetId([
                'request_id' => $data['source'] . $data['request_id'],
                'customer_code' => $brokerCustomerData->customer_code,
                'method'        => "brokerBuy - buy",
                'raw_request' => json_encode($data)
            ]);

            $headers = self::getBrokerHeader($data);
            $finalResponse['error'] = true;

            $requestData = array(
                'paymentMethodCode' => config('constants.AD_PAYMENT_METHOD_'.$data['altcoin']),
                'accountCode' => $brokerCustomerData['account_code'],
                'cryptoAmount' => $cryptoAmount,
                'fiatCurrency'    => 'EUR',
                "callbackUrl" => config('constants.BROKER_CALL_BACK_URL'),
                'customerCode'    => $brokerCustomerData->customer_code,
                'ip' => $data['customer_ip']
            );


            $brokerBuyResponse = curlRequestBroker(config('constants.BROKER_API_URL') . "buy", json_encode($requestData), true, $headers, false);

            OrderLogs::where(['id' => $blogId])->update([
                'raw_response' => json_encode($brokerBuyResponse)
            ]);

            return $brokerBuyResponse;
        } catch (\Throwable $e) {
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Broker Buy']);
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Broker Buy \n Request ID: ' . $data['source'] . $data['request_id'];
            slackCurlRequest($data);
            return brokerserverError($e, 'Broker Buy');
        }
    }
}
