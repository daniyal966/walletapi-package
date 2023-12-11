<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use WalletApi\Configuration\Models\Address;
use WalletApi\Configuration\Models\User;
use WalletApi\Configuration\Models\ErrorLog;
use WalletApi\Configuration\Models\Transaction;
use App\Jobs\SendEmailJob;
use App\Http\Controllers\API;
use App\Http\Requests\CreateAddressValidation;
use App\Http\Requests\GetWalletValidation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tighten\SolanaPhpSdk\Connection;
use Tighten\SolanaPhpSdk\SolanaRpcClient;
use Tighten\SolanaPhpSdk\Keypair;
use Tighten\SolanaPhpSdk\PublicKey;
use Tighten\SolanaPhpSdk\Transaction as TightenTransactionClass;
use Tighten\SolanaPhpSdk\Programs\SystemProgram;
// use Tighten\SolanaPhpSdk\Account;

class APIModel extends Model
{
    /**
     * This function is used to create wallet addresses.
     * 1) First get the address From database.
     * 2) If address found from database then assign address to user.
     * 3) If address not found in database then call the daemon to create address and assign to user.
     * 4) In the last update the log against response.
     * 5) insert the address, wallet_id, request_id in address table in Database and return wallet address.
     * @param   $$user_id, $wallet_id, $request_id.
     * @return  wallet_address
     **/

    public static function createAddress($userId, $walletId, $request, $request_id)
    {
        try {
            $logId = Log::insertGetId(['request_id' => $request_id, 'user_id' => $userId, 'raw_request' => json_encode($request->all()), 'log_type' => 'address_creation_logs', 'raw_response' => 'Address Creation Logs', 'process_state' => 'APIModel Create Address Called']);
            $currencyNick = $request->altcoin;
            if ($currencyNick == 'ETH')
                $walletAddress = self::generateETHAddress($currencyNick, $request_id, $userId, $walletId, $request);
            else if ($currencyNick == 'SOL')
                $walletAddress = self::generateSOLAddress($currencyNick, $request_id, $userId, $walletId, $request);
            else
                $walletAddress = self::generateNewAddress($currencyNick, $request_id, $userId, $walletId, $request);
            return $walletAddress;
        } catch (\Throwable $e) {
            DB::rollback();
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' method: Create Address ' . config('constants.NICKNAME');
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Address ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    public static function generateSOLAddress($currencyNick, $request_id, $userId, $walletId, $request)
    {
        try {
            $address = DB::table('addresses')->where('request_id', NULL)->where('user_wallet_id', NULL)->where('address_type', $currencyNick)->limit(10)->inRandomOrder()->where('daemon_used', 'SOL')->select('address')->sharedLock()->first();
            if (!isset($address)) {
                $token = ApiToken::where('api_name', 'nft_pay_solana')->pluck('token')->first();
                $logId = Log::insertGetId(['raw_request' => json_encode($token), 'log_type' => 'GenerateSOLAddressesEndPoint']);
                $headers = ['x-access-token: ' . $token, 'Content-Type: application/json'];
                $response = curlRequest(config('constants.NFT_PAY_SOLANA_URL') . 'get_key_payer', null, FALSE, $headers);
                Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
                $response = json_decode($response, true);
                $walletAddress = '';
                $statusCode = 400;
                if(isset($response['error']) && $response['error'] == false) {
                    $walletAddress = $response['data']['address'];
                    $statusCode = 201;
                    $addressId = Address::insertGetId([
                        'address' => $walletAddress, 
                        'user_wallet_id' => $walletId, 
                        'request_id' => $request_id, 
                        'address_type' => $currencyNick, 
                        'password' => Crypt::encryptString($response['data']['private_key']),
                        'encrypted_json' => json_encode($response['data']),
                        'daemon_used' => 'SOL', 
                        'is_locked' => $request->is_locked, 
                        'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null,  
                        'contract' => isset($request->contract) ? $request->contract : null, 
                        'psp' => isset($request->psp) ? $request->psp : null, 
                        'traffic_originator' => isset($request->traffic_originator) ? $request->traffic_originator : null, 
                        'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null
                    ]);
                } 
            } else {
                $statusCode = 201;
                $walletAddress = $address->address;
                DB::table('addresses')->where(['address' => $walletAddress])->update(['user_wallet_id' => $walletId, 'request_id' => $request_id, 'is_locked' => $request->is_locked, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null,  'contract' => isset($request->contract) ? $request->contract : null, 'psp' => isset($request->psp) ? $request->psp : null, 'traffic_originator' => isset($request->traffic_originator) ? $request->traffic_originator : null, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null]);
            }

            $walletAddress = ['address' => $walletAddress, 'status_code' => $statusCode];
            UserWallet::where('id', $walletId)->update(['current_address' => $walletAddress['address']]);
            Log::insert(['request_id' => $request_id, 'raw_request' => json_encode($request->all()), 'user_id' => $userId, 'log_type' => 'address_creation_logs', 'raw_response' => json_encode($walletAddress), 'process_state' => 'Response From APIModel Create Address']);
            return $walletAddress;
        } catch (\Throwable $e) {
            DB::rollback();
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Generate ' . $currencyNick . ' Address.';
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Address ' . config('constants.NICKNAME') . ' Method: generateSOLAddress']);
            return false;
        }
    }

    public static function generateETHAddress($currencyNick, $request_id, $userId, $walletId, $request)
    {
        try {
            $logId = Log::insertGetId(['request_id' => $request_id, 'user_id' => $userId, 'raw_request' => json_encode($request->all()), 'log_type' => 'address_creation_logs_' . strtolower($currencyNick), 'user_id' => $userId, 'process_state' => 'Call To Ether JS for new address']);
            $walletDetails = UserWallet::where('id', $walletId)->first();
            $token = Affiliate::where(['email' => 'admin@etherjs.com'])->first();
            $headers = ['x-access-token: ' . $token->token, 'Content-Type: application/x-www-form-urlencoded'];
            if (isset($request->password) && ($request->source == 'NM' || $request->source == 'NMT')) {
                $password = $request->password;
            } else {
                $password = substr($request->source, 0, 1) . strtolower(substr($request->source, 1, 2)) . '#' . date('dmhis');
            }
            Log::where(['id' => $logId])->update(['raw_response' => $password]);
            $addressReceived = FALSE;
            $x = 1;
            $walletAddress = '';
            do {
                $x = $x + 1;
                if (empty($walletDetails['mnemonic_phrase'])) {
                    $data = 'password=' . $password;
                    // $decryptedMnemonicePhrase = '';
                } else {
                    $decryptedMnemonicePhrase = Crypt::decryptString($walletDetails['mnemonic_phrase']);
                    $addressIndex = Address::where('user_wallet_id', $walletId)->count();
                    if($request['email'] == 'admin@celoxo.com') {
                        $addressIndex = $addressIndex + 100;    
                    }
                    $data = 'address_count=' . $addressIndex + $x . '&mnemonic=' . $decryptedMnemonicePhrase . '&password=' . $password;
                }
                $etherJSlogId = Log::insertGetId(['request_id' => $request_id,  'user_id' => $userId,  'raw_request' => json_encode($data), 'log_type' => 'address_creation_logs_' . strtolower($currencyNick), 'user_id' => $userId, 'process_state' => 'EtherJS']);
                $response = json_decode(curlRequest(config('constants.ETHER_JS_URL') . 'create_account', $data, true, $headers), 1);
                Log::where(['id' => $etherJSlogId])->update(['raw_response' => json_encode($response)]);
                isset($response['data']['mnemonic_phrase']) ? $mnemonic_phrase = $response['data']['mnemonic_phrase'] : $mnemonic_phrase = $decryptedMnemonicePhrase;
                if ($response['status_code'] == 201) {
                    $walletAddress = $response['data']['address'];
                    UserWallet::where('id', $walletId)->update(['current_address' => $walletAddress, 'mnemonic_phrase' => Crypt::encryptString($mnemonic_phrase)]);
                    Address::insert(['user_wallet_id' => $walletId, 'address' => $walletAddress, 'address_type' => 'ETH', 'daemon_used' => 'ETH', 'request_id' => $request_id, 'is_locked' => $request->is_locked, 'contract' => isset($request->contract) ? $request->contract : null, 'psp' => isset($request->psp) ? $request->psp : null, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null, 'password' => Crypt::encryptString($password), 'encrypted_json' => $response['data']['encryptedJson']]);
                    DB::table('address_balance')->insert(['address' => $walletAddress, 'amount' => 0, 'user_wallet_id' => $walletId]);
                    $walletAddress = ['address' => $walletAddress, 'status_code' => 201];
                    $addressReceived = TRUE;
                } else {
                    $addressReceived = FALSE;
                }
            } while (!$addressReceived);
            
            return $walletAddress;
        } catch (\Throwable $e) {
            DB::rollback();
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Generate ETH Address.';
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Address ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    public static function generateNewAddress($currencyNick, $request_id, $userId, $walletId, $request)
    {
        try {
            $address = DB::table('addresses')->where('request_id', NULL)->where('user_wallet_id', NULL)->where('address_type', $currencyNick)->limit(10)->inRandomOrder()->where('daemon_used', 'v6')->select('address')->sharedLock()->first();
            if (!isset($address)) {
                $cmd['method'] = "getnewaddress";
                $logId = Log::insertGetId(['request_id' => $request_id, 'user_id' => $userId, 'raw_request' => json_encode($cmd), 'log_type' => 'address_creation_logs_' . $currencyNick, 'user_id' => $userId, 'process_state' => 'Call To Deamon request for' . $currencyNick]);
                $response = json_decode(self::encodeUrlSendRequest(json_encode($cmd)), 1);
                $walletAddress = trim($response['result']);
                Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
                $addressId = Address::insertGetId(['address' => $walletAddress, 'user_wallet_id' => $walletId, 'request_id' => $request_id, 'address_type' => $currencyNick, 'daemon_used' => 'v6', 'is_locked' => $request->is_locked, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null,  'contract' => isset($request->contract) ? $request->contract : null, 'psp' => isset($request->psp) ? $request->psp : null, 'traffic_originator' => isset($request->traffic_originator) ? $request->traffic_originator : null, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null]);
            } else {
                $walletAddress = $address->address;
                DB::table('addresses')->where(['address' => $walletAddress])->update(['user_wallet_id' => $walletId, 'request_id' => $request_id, 'is_locked' => $request->is_locked, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null,  'contract' => isset($request->contract) ? $request->contract : null, 'psp' => isset($request->psp) ? $request->psp : null, 'traffic_originator' => isset($request->traffic_originator) ? $request->traffic_originator : null, 'psp_mid' => isset($request->psp_mid) ? $request->psp_mid : null]);
            }
            $walletAddress = ['address' => $walletAddress, 'status_code' => 201];
            UserWallet::where('id', $walletId)->update(['current_address' => $walletAddress['address']]);
            Log::insert(['request_id' => $request_id, 'raw_request' => json_encode($request->all()), 'user_id' => $userId, 'log_type' => 'address_creation_logs', 'raw_response' => json_encode($walletAddress), 'process_state' => 'Response From APIModel Create Address']);
            return $walletAddress;
        } catch (\Throwable $e) {
            DB::rollback();
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Generate ' . $currencyNick . ' Address.';
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . ' Line Number ' . $e->getLine(), 'method' => 'Create Address ' . config('constants.NICKNAME') . ' Method: generateNewAddress']);
            return false;
        }
    }

    /**
     * This function is used to get the wallet detail.
     * 1) First log the request.
     * 2) Then get customer_id against email from database.
     * 3) Then call the method that get the details of wallet against customer_id and return response.
     * 4) In the last update the log against customer_id in database.
     * @param  $request
     * @return JSON response
     **/

    public static function getWallet($request)
    {
        try {
            $logId = Log::insertGetId(['request_id' => $request->source, 'raw_request' => json_encode($request->email), 'log_type' => 'get_wallet']);
            $customerId = User::where(["email" => $request->email, "affiliate_id" => $request->id])->select("id")->first();
            $response = self::getWalletDetail($request, $request->source, $customerId->id, $request->is_locked, null);
            $response != false && $response != NULL ? $response = ['data' => $response, 'status_code' => 200] : $response =  ['data' => "", 'status_code' => 404];
            Log::where('id', $logId)->update(['user_id' => $customerId->id, 'raw_response' => json_encode($response)]);
            return isset($response) ? $response : $response =  ['data' => '', 'status_code' => 404];
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' method: Get Wallet ' . config('constants.NICKNAME');
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Wallet' . config('constants.NICKNAME')]);
            return ['data' => "", 'status_code' => 404];
        }
    }

    /**
     * This function is used to get the wallet data.
     * 1) First get customer_id against customer id from database if not find then  create wallet of that user.
     * 2) If the user is purser user then we show both direct and non direct transactions.
     * 3) Then call the get_transaction method to get transaction history of that user.
     * @param  $source, $customer_id, $is_locked,$request_id
     * @return response
     **/

    public static function getWalletDetail($request, $source, $customerId, $is_locked, $null)
    {
        try {
            if ($customerId != null) {
                $customerWallet = UserWallet::where(['user_id' => $customerId, 'source' => $source, 'is_locked' => $is_locked, 'crypto_currency' => config('constants.CURRENCY'), 'host' => config('constants.HOST')])->first();
                if (isset($customerWallet)) {
                    isset($customerWallet->spendable_balance) ? $customerWallet->spendable_balance : $customerWallet->spendable_balance = '0.00000000';
                    isset($customerWallet->total_balance) ? $customerWallet->total_balance : $customerWallet->total_balance = '0.00000000';
                    $data['spendable_balance']  = "$customerWallet->spendable_balance";
                    $data['balance']            = "$customerWallet->total_balance";
                    $data['current_address']    = "$customerWallet->current_address";
                    if ($source == 'NM' || $source == 'NMT') {
                        $encryptedJson = Address::where(['address' => $customerWallet->current_address])->select('encrypted_json')->first()->encrypted_json;
                        $data['encrypted_json']    = json_decode($encryptedJson, 1);
                    }
                }
                return isset($data) ? $data : NULL;
            } else {
                return $data = NULL;
            }
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' Method: Get Wallet Data ' . config('constants.NICKNAME');
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Wallet Data ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    /**
     * This function is used to get the reserve Transactions
     * 1) We will check the affiliate_id, then check users table where it matches with the affiliate, it will fetch all users.
     * 2) From users it will get user wallets.
     * 3) From that wallet_id we will get all transaction against them. And make a sum of that.
     * @param  $coin, $affiliate_id
     * @return response
     **/

    public static function getReserveTransactions($coin, $affiliate_id)
    {
        try {
            // $userId = User::select('id')->where(['affiliate_id' => $affiliate_id])->get();
            $userId = User::select('id')->where(['affiliate_id' => $affiliate_id])->get()->toArray();
            $userIds = array_column($userId, 'id');
            $walletId = UserWallet::where(['crypto_currency' => $coin])->whereIn('user_id', $userIds)->get()->toArray();
            $walletIds = array_column($walletId, 'id');
            $transactionSum = Transaction::whereIn('user_wallet_id', $walletIds)->where('status', '!=', 'Declined')->select('amount')->sum('amount');
            (!isset($transactionSum) && $transactionSum == NULL) ?  $response = ['status_code' => 404, 'totalAmount' => ''] : $response = ['status_code' => 200, 'totalAmount' => $transactionSum];
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Reserves Transactions";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Reserves Transactions']);
        }
    }

    /**
     * This function is used to set label
     * 1) We will first check if the address exists in address.
     * 2) If we get address we will set a label against it.
     * @param  $address, $label
     * @return response
     **/

    public static function setLabel($request)
    {
        try {
            $cmd = [
                "method" => "setlabel",
                "params" => ["" . $request['address'] . "", "" . $request['label'] . ""]
            ];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'setlabel_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            $response = json_decode($response, true);
            if (checkErrorsExistsNew($response) == false) {
                $response = ['data' => 'Label is set as ' . $request['label'], 'status_code' => 200];
            } else {
                $response = ['data' => $response['error']['message'], 'status_code' => 404];
            }
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: set Label";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'set Label']);
        }
    }

    public static function isValidETHAddress($request)
    {
        try {
            $logId = Log::insertGetId([
                'raw_request' => json_encode($request),
                'log_type' => 'isValidETHAddress_logs_' . config('constants.NICKNAME')
            ]);

            $token = Affiliate::where(['email' => 'admin@etherjs.com'])->first();
            $headers = ['x-access-token: ' . $token->token, 'Content-Type: application/x-www-form-urlencoded'];
            $data = 'address=' . $request['address'];

            $response = json_decode(curlRequest(config('constants.ETHER_JS_URL') . 'validate_address', $data, true, $headers), 1);
            if ($response['status_code'] != 200) {
                Log::insertGetId([
                    'raw_response' => json_encode($response),
                    'log_type' => 'isValidETHAddress_logs_' . config('constants.NICKNAME')
                ]);
                return [
                    'data' => 'Invalid Bitcoin address',
                    'status_code' => $response['status_code']
                ];
            }
            Log::where('id', $logId)->update([
                'raw_response' => json_encode($response),
                'log_type' => 'isValidETHAddress_logs_' . config('constants.NICKNAME')
            ]);
            return [
                'data' => $response['message'],
                'status_code' => $response['status_code']
            ];
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: is valid ETH Label";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'is valid ETH Label']);
        }
    }

    public static function isValidSOLAddress($request) {
        try {
            $logId = Log::insertGetId(['raw_request' => json_encode($request), 'log_type' => 'isValidSOLAddress_logs_' . config('constants.NICKNAME')]);
            $publicKey = new PublicKey($request['address']);
            $response = $publicKey->isOnCurve($publicKey);
            if (!$response) {
                Log::insertGetId(['raw_response' => json_encode($response), 'log_type' => 'isValidSOLAddress_logs_' . config('constants.NICKNAME')]);
                return [
                    'data' => 'Invalid Solana address',
                    'status_code' => 400
                ];
            }
            Log::where('id', $logId)->update(['raw_response' => json_encode($response), 'log_type' => 'isValidETHAddress_logs_' . config('constants.NICKNAME')]);
            return [
                'data' => 'Valid Solana Address',
                'status_code' => '200'
            ];

        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: is valid SOL Label";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'is valid SOL Label']);
        }
        
   }

    /**
     * This function is used to validate address provided.
     * 1) We will first provide the address.
     * 2) It will check if the provided address is valid or no
     * @param  $address
     * @return response
     **/

    public static function validateAddress($request)
    {
        try {
            $cmd = [
                "method" => "validateaddress",
                "params" => ["" . $request['address'] . ""]
            ];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'validateaddress_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            $response = json_decode($response, true);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            $response = [
                'isvalid' => $response['result']['isvalid'],
                'address' => $request['address'],
                'scriptPubKey' => isset($response['result']['scriptPubKey']) ? $response['result']['scriptPubKey'] : '',
                'isscript' => $response['result']['isvalid']
            ];
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Verify Address";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Verify Address']);
        }
    }
    public static function validateSolAddress($request) {
        try {
            $logId = Log::insertGetId(['raw_request' => json_encode($request), 'log_type' => 'validateaddress_logs_' . config('constants.NICKNAME')]);
            $res = self::isValidSOLAddress($request);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($res)]);
            $response = [
                'isvalid'   => ($res['status_code'] == 200) ? true : false , 
                'address'   => $request['address'], 
                'isscript'  => ($res['status_code'] == 200) ? true : false
            ];
            return $response;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' method: Verify SOL Address';
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Verify Address']);
        }
    }

    public static function validateEthAddress($request)
    {
        try {
            $logId = Log::insertGetId(['raw_request' => json_encode($request), 'log_type' => 'AalidateAddressLogs' . config('constants.NICKNAME')]);
            $token = Affiliate::where(['email' => 'admin@etherjs.com'])->first();
            $headers = ['x-access-token: ' . $token->token, 'Content-Type: application/x-www-form-urlencoded'];
            $data = 'address=' . $request['address'];
            $response = json_decode(curlRequest(config('constants.ETHER_JS_URL') . 'validate_address', $data, true, $headers), 1);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            $response = ['isvalid' => $response['data']['isValidAddress'], 'address' => $request['address'], 'isscript' => $response['data']['isValidAddress']];
            return $response;
        } catch (\Throwable $e) {
            $data = 'Error: In ' . env('APP_ENV') . ' ENV ' . str_replace('"', ' ', $e->getMessage()) . ' on Line Number ' . $e->getLine() . ' in file ' . $e->getFile() . ' method: Verify ETH Address';
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Verify Address']);
        }
    }

    /**
     * This function is used to get Address against in the label
     * 1) We will get all the address agains the label
     * @param  $address
     * @return response
     **/

    public static function getAddress($request)
    {
        try {
            $cmd = [
                "method" => "getaddressesbylabel",
                "params" => [
                    "" . $request['label'] . ""
                ]
            ];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'getaddresslabel_logs_ltc']);
            $response = self::encodeUrlSendRequest($cmd);
            $response = json_decode($response);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: getaddresslabel ";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'getaddresslabel ']);
        }
    }

    /**
     * This function is used to get Information against the Address
     * @param  $address
     * @return response
     **/

    public static function getAddressInfo($request)
    {
        try {
            $cmd = ["method" => "getaddressinfo", "params" => ["" . $request['address'] . ""]];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'getaddressInfo_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            $response = json_decode($response, true);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            $response = array('address' => $response['result']['address'], 'script_pub_key' => $response['result']['scriptPubKey'], 'is_mine' => $response['result']['ismine'], 'timestamp' => $response['result']['timestamp']);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: getadddressInfo";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'getadddressInfo']);
        }
    }

    /**
     * This function is used to get Transaction against in the address
     * 1) We will get all the address agains the label
     * @param  $address
     * @return response
     **/

    public static function getReceivedByAddress($request)
    {
        try {
            $cmd = ["method" => "getreceivedbyaddress", "params" => ["" . $request['address'] . ""]];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'getreceivedaddress_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            $response = json_decode($response, true);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: getreceivedaddress";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'getreceivedaddress']);
        }
    }

    /**
     * This function is used to get the transaction history of PURSER user.
     * 1) First get customer id against request data.
     * 2) Then get the customer_wallet if the customer wallet is not created it first created and update in database.
     * 3) Then get the transaction history against data request.
     * @param  $customer_id, $source,
     * @return response
     **/

    public static function getTransactionList($request)
    {
        try {
            $userSql = User::select('id')->where('email', $request->email);
            if ($request->source != 'PURSER') {
                $userSql->where('affiliate_id', $request->id);
            }
            $userData = $userSql->get()->toArray();
            $userIds = array_column($userData, "id");

            $userWalletSql = UserWallet::select("id")->where(['crypto_currency' => config('constants.CURRENCY'), 'host' => config('constants.HOST')])->whereIn('user_id', $userIds);
            if ($request->source != 'PURSER') {
                $userWalletSql->where(['source' => $request->source, 'is_locked' => $request->is_locked]);
            }
            $userWalletData = $userWalletSql->get()->toArray();
            $userWalletIds = array_column($userWalletData, "id");
            $tResponse = [];
            if (isset($userWalletData)) {
                $transactionsList = self::getTransactionsDetail($userWalletIds, $request);
                $tResponse['total'] = $transactionsList['total'];
                $searchArray =  json_decode($request['fields'], true);
                $itemsPerPage = $searchArray['items_per_page'];
                if ($transactionsList['total'] > 0) {
                    if ($itemsPerPage > 0) {
                        $currentPage = LengthAwarePaginator::resolveCurrentPage();
                        $col = collect($transactionsList['transactions']);
                        $perPage = $itemsPerPage;
                        $currentPageItems = $col->slice(($currentPage - 1) * $perPage, $perPage)->all();
                        $items = new LengthAwarePaginator($currentPageItems, count($col), $perPage);
                        $check = json_decode(json_encode($items), true);
                        foreach ($check['data'] as $data) {
                            $tResponse['transaction_list'][] = $data;
                        }
                    } else {
                        $tResponse['transaction_list'] = $transactionsList['transactions'];
                    }
                } else {
                    $tResponse['transaction_list'] = array();
                }
            }
            $response = ['data' => $tResponse, 'status_code' => 200];
            return isset($response) ? $response : NULL;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Transaction List ";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Transaction List LTC']);
            return ['data' => "", 'status_code' => 400];
        }
    }
    /**
     * This function is used to get the transaction history of PURSER user.
     * 1) First get customer id against request data.
     * 2) Then get the transaction against both direct and non direct case and marge them.
     * @param  $customer_id, $source,
     * @return response
     **/
    private static function mergeTransactionsList($customerId, $request)
    {

        try {
            $mergeArrays = [];
            $users = User::where("email", $request->email)->get();
            $total = 0;
            foreach ($users as $user) {
                $userData = UserWallet::where('user_id', $user->id)->whereIn('is_locked', [0, 1])->where('crypto_currency', config('constants.CURRENCY'))->where('host', config('constants.HOST'))->select("id")->get();
                foreach ($userData as $data) {
                    $transactionList = self::getTransactionsDetail($data->id, $request);
                    $mergeArrays = array_merge($mergeArrays, $transactionList['transactions']);
                    $total += $transactionList['total'];
                }
            }
            $response = [
                'total' => $total,
                'transactions' => $mergeArrays
            ];

            return !empty($mergeArrays) ? $response : array('total' => '0', 'transactions' => array());
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Merge Transactions " . config('constants.NICKNAME');
            slackCurlRequest($data);

            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Merge Transactions ' . config('constants.NICKNAME')]);
            return false;
        }
    }
    /**
     * This function is used to get the transaction from database.
     * 1) First get all the transaction against customer wallet id.
     * 2) Then traverse each transaction and formatting value.
     * @param  $customer_wallet_id,
     * @return response
     **/
    public static function getTransactionsDetail($customerWalletIds, $request)
    {
        try {
            $trxsData = self::getTransactionArray($customerWalletIds, $request);
            if ($trxsData == true) {
                $total = count($trxsData);
                if ($total > 0) {

                    $response = [
                        'total' => $total,
                        'transactions' => $trxsData
                    ];
                }
            }
            return isset($response) ? $response : array('total' => '0', 'transactions' => array());
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Transactions " . config('constants.NICKNAME');
            slackCurlRequest($data);

            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Transactions ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    /**
     * This function is used to get the transaction from database which is searched from frontend.
     * 1) First get all the searched fields from request.
     * 2) If request from frontend is all then we get all the result and set in response.
     * 3) If searched for specific result then make query and get result and return in response.
     * @param  $customer_wallet_id,
     * @return response
     **/

    public static function getTransactionArray($customerWalletIds, $request)
    {
        try {
            if (isset($request['fields'])) {
                $searchArray =  json_decode($request['fields'], true);
                $start_date = $searchArray['search_filter']['start_date'];
                $end_date = $searchArray['search_filter']['end_date'];
                // $sql = Transaction::where('user_wallet_id', $customerWalletIds);
                $sql = Transaction::whereIn('transactions.user_wallet_id', $customerWalletIds);
                if ($request->source == 'PURSER') {
                    $sql->select(DB::raw("cast(transactions.confirmations as int8), 
                    transactions.address as address, 
                    transactions.tx_hash as txid, 
                    transactions.network_fee as network_fee, 
                    cast( abs(transactions.amount) as varchar ) as amount, 
                    (case when transactions.category = 'receive' then 'Received' else 'Sent' end) as category, 
                    to_timestamp(cast(transactions.tx_unix_timestamp as bigint))::timestamp as date, 
                    transactions.status, affiliates.name as affiliate"))
                        ->join('user_wallets', 'user_wallets.id', '=', 'transactions.user_wallet_id')
                        ->join('users', 'users.id', '=', 'user_wallets.user_id')
                        ->join('affiliates', 'affiliates.id', '=', 'users.affiliate_id');
                } else {
                    $sql->select(DB::raw("cast(transactions.confirmations as int8), 
                    transactions.address as address, 
                    transactions.tx_hash as txid, 
                    transactions.network_fee as network_fee, 
                    cast(transactions.amount as varchar) as amount, 
                    (case when transactions.category = 'receive' then 'Received' else 'Sent' end) as category, 
                    to_timestamp(cast(transactions.tx_unix_timestamp as bigint))::timestamp as date,
                    transactions.status"));
                }
                $itemsPerPage = $searchArray['items_per_page'];
                if ($searchArray['items_per_page'] == -1) {
                    $trxsData = $sql->get()->toArray();
                    $itemsPerPage = count($trxsData);
                } else {
                    foreach ($searchArray['search_filter'] as $key => $value) {
                        if ($key != 'start_date' && $key != 'end_date') {
                            // ($key == 'status') ? $sql->whereRaw("confirmations " . ($value == true ? ">3" : (isset($value) ? "<4" : ">=0"))) : 
                            ((!empty($value)) ? $sql->whereRaw("transactions.$key::text LIKE '%$value%'") : '');
                        }
                    }
                }
                if ($start_date == $end_date && $start_date != '') {
                    $trxsData = $sql->whereDate('transactions.created_at', $start_date);
                }
                if ($start_date != null && $end_date != null && $start_date != $end_date) {
                    $trxsData = $sql->whereBetween('transactions.created_at', [$start_date, $end_date]);
                }
                $trxsData = $sql->orderBy('tx_unix_timestamp', 'DESC')->orderBy('transactions.id', 'DESC')->get()->toArray();

                $response = !empty($trxsData) ? $trxsData : array();
            }
            return (isset($response) && $response != null) ? $response : array();
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Transactions " . config('constants.NICKNAME');
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Transactions ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    /**
     * This function is used to send LTC.
     * 1) First log the request in database.
     * First, check if request is from direct or non direct.
     * If NON DIRECT , get the total spendable balance of customer & match against request's amount.
     * IF amount is greater in any of the cases, then send request to daemon.
     * GET response , fill data in necessary tables & send response back.
     * @param  $request
     **/
    public static function SendCrypto($request)
    {
        try {
            $request_id = $request['source'] . $request['request_id'];
            $logId = Log::insertGetId(['request_id' => $request_id, 'raw_request' => json_encode($request->all()), 'log_type' => 'send_' . config('constants.NICKNAME') . '_logs', 'raw_response' => 'Saving Raw Request', 'process_state' => 'APIModel SendCrypto Method Called']);
            $userData = User::checkUserExistence($request, config('constants.CURRENCY'), config('constants.HOST'));
            Log::where(['id' => $logId])->update(['user_id' => $userData['user_id']]);
            if ($request['altcoin'] == 'ETH' || $request['altcoin'] == 'SOL') {
                $balance = AddressBalance::where(['address' => $request['from_address'], 'user_wallet_id' => $userData['user_wallet_id']])->first();
                if ($balance == null)
                    return $response = ['data' => ['message' => 'Seems like the address (' . $request['from_address'] . ') does not belongs to this user.', 'status_code' => '452']];
                else
                    $balance = $balance->amount;
            } else {
                $balance = @data_get(DB::table("user_wallets")->select('spendable_balance')->where('id', $userData['user_wallet_id'])->first(), 'spendable_balance', 0);
            }
            $affiliate_fee = Affiliate::where('id', $request->id)->pluck($request['altcoin'] . '_transaction_fee');
            $amount = bcsub($balance, $affiliate_fee[0], 8);
            if ($balance != false && $amount >= $request->amount && $request->amount > config('constants.' . $request['altcoin'] . '_MIN_TRANSACTION_AMOUNT')) {
                $response = self::sendCryptoAndSave($request->all(), $userData, $affiliate_fee[0]);
                if ($response['status_code'] == 200) {
                    $responsePrint = [
                        'data' => [
                            'status_code' => 200,
                            'status' => 'accepted',
                            'amount' => ($request['altcoin'] == 'SOL') ? $response['result']['amount'] : $request['amount'],
                            'hash' => null
                        ]
                    ];
                    if ($request['altcoin'] == 'LTC' || $request['altcoin'] == 'BTC' || $request['altcoin'] == 'BCH') {
                        $responsePrint['data']['amount'] = "" . $request['amount'] - $response['result']['network_fee'] . "";
                        $responsePrint['data']['network_fee']   = $response['result']['network_fee'];
                    }
                    if($request['altcoin'] == 'SOL') {
                        $responsePrint['data']['network_fee']   = rtrim($response['result']['network_fee'],'0');
                    }
                    $responsePrint['data']['hash'] = $response['result']['result'];
                    $process_state = 'Pending';
                    $response = $responsePrint;
                } else {
                    $response =  ['data' => ['message' => $response['message'], 'status_code' => $response['status_code']]];
                    DB::table('transactions')->insert(['tx_unix_timestamp' => Carbon::now()->timestamp, 'user_wallet_id' => $userData['user_wallet_id'], 'amount' => "-" . $request['amount'], 'fee' => '0', 'category' => 'send', 'confirmations' => '0', 'address' => $request['to_address'], 'status' => 'Declined', 'reason' => $response['data']['message']]);
                    Log::insertGetId(['log_type' => 'send_' . config('constants.NICKNAME') . '_logs', 'user_id' => $userData['user_id'], 'raw_request' => 'Sending Final Response', 'raw_response' => json_encode($response), 'process_state' => 'Transaction Failed - Send Crypto - User or Address Not Found', 'request_id' => $request_id]);
                    $process_state = 'Failed';
                }
            } else {
                $response =  ['data' => ['message' => 'Not enough balance to make a transaction or amount too small.', 'status_code' => 452]];
                $process_state = 'Decline';
                DB::table('transactions')->insert(['tx_unix_timestamp' => Carbon::now()->timestamp, 'user_wallet_id' => $userData['user_wallet_id'], 'amount' => "-" . $request['amount'], 'fee' => '0', 'category' => 'send', 'confirmations' => '0', 'address' => $request['to_address'], 'status' => 'Declined', 'reason' => 'Not Enough Balance in Account']);
                Log::insert(['log_type' => 'send_' . strtolower(config('constants.NICKNAME')) . '_logs', 'user_id' => $userData['user_id'], 'raw_request' => 'Sending Final Response', 'raw_response' => json_encode($response), 'process_state' => 'Transaction Declined - Send Crypto - Not Enough Balance or Amount', 'request_id' => $request_id]);
                $response = [
                    'data' => [
                        'status_code' => 200,
                        'status' => 'declined',
                        'amount' => $request['amount'],
                        'hash' => null
                    ]
                ];
            }
            Log::insertGetId(['log_type' => 'send_' . strtolower(config('constants.NICKNAME')) . '_logs', 'user_id' => $userData['user_id'], 'raw_request' => 'Final Response', 'raw_response' => json_encode($response), 'process_state' => $process_state, 'request_id' => $request_id]);
            return $response;
        } catch (\Throwable $e) {
            // $data = "error: In ".env('APP_ENV')." ENV ".str_replace('"' , ' ' ,$e->getMessage())."on  Line Number ".$e->getLine()." in file ".$e->getFile()." method: Send " . config('constants.NICKNAME');
            // slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    /**
     * This function is used to send LTC.
     * 1) First detect the free amount from actual amount.
     * 2) Then make url which is send to daemon.
     * 3) In last log the request and response.
     * @param $request,$user_data
     * @return response
     */
    public static function sendCryptoAndSave($request, $userData, $affiliate_fee)
    {
        try {
            $logId = Log::insertGetId(['raw_request' => json_encode($request), 'log_type' => 'send_crypto_logs', 'raw_response' => 'Send Crypto Logs', 'process_state' => 'Send Crypto and Save Called']);
            set_time_limit(0);
            // $request['altcoin'] == 'ETH' ? $response = self::sendEthAndSave($request, $userData, $affiliate_fee) : $response = self::sendCoinsAndSave($request, $userData, $affiliate_fee);
            if($request['altcoin'] == 'ETH') {
                $response = self::sendEthAndSave($request, $userData, $affiliate_fee);
            } else if ($request['altcoin'] == 'SOL') {
                $response = self::sendSolAndSave($request, $userData, $affiliate_fee);
            } else {
                $response = self::sendCoinsAndSave($request, $userData, $affiliate_fee);
            }
            if ($response['status_code'] == 200) {
                $transactionId = Transaction::insertGetId([
                    'user_wallet_id' => $userData['user_wallet_id'],
                    'amount' => "-" . $request['amount'],
                    'fee' => $affiliate_fee,
                    'tx_hash' => $response['response']['result'],
                    'category' => 'send',
                    'confirmations' => '0',
                    'address' => ($request['altcoin'] == 'ETH' || $request['altcoin'] == 'SOL') ? $request['from_address'] : $request['to_address'],
                    'tx_unix_timestamp' => $response['transactionDetails']['result']['time'],
                    'tx_ts' => date("Y-m-d H:i:s", $response['transactionDetails']['result']['time']),
                    'network_fee' => $response['response']['network_fee'],
                    'status' => 'Pending'
                ]);
                Log::insert(['log_type' => 'send_' . config('constants.NICKNAME') . '_logs', 'user_id' => $userData['user_id'], 'raw_request' => 'Sending Final Response', 'raw_response' => json_encode($response), 'process_state' => 'Transaction Pending - Send Crypto']);
                $response = ['status_code' => 200, 'result' => $response['response']];
            } else {
                $response = ['status_code' => 400, 'message' => $response['message'] ? $response['message'] : '', 'result' => ''];
            }
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send " . config('constants.NICKNAME') . " And Save";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config('constants.NICKNAME') . ' And Save']);
            return false;
        }
    }
    public static function sendSolAndSave($request, $userData, $affiliate_fee) {
        try {
            $user = User::select('id')->where(['affiliate_id' => $request['id'], 'email' => $request['email']])->first()->toArray();
            
            $response['status_code'] = 400;
            if($user) {
                $addressDetails = Address::where('address', $request['from_address'])->first();
                // $accountDetails = UserWallet::where(['user_id' => $user['id'], 'crypto_currency' => config('constants.CURRENCY')])->first()->toArray();

                $secretKey = json_decode(Crypt::decryptString($addressDetails['password']));
                $fromPublicKey = KeyPair::fromSecretKey($secretKey);
                $toPublicKey = new PublicKey($request['to_address']);
                $solanaAmount = $request['amount'] * config('constants.LAMPORTS');
                // the amount given here is in lamports, 1 SOL = 1000000000 laports
                $instruction = SystemProgram::transfer(
                    $fromPublicKey->getPublicKey(),
                    $toPublicKey,
                    $solanaAmount
                );
                $transaction = new TightenTransactionClass(null, null, $fromPublicKey->getPublicKey());
                $transaction->add($instruction);
                $sdk = new Connection(new SolanaRpcClient(config('constants.DAEMON_URL')));
                $txHash = $sdk->sendTransaction($transaction, [$fromPublicKey]);
                $transactionDetails = self::getSoLanaTransactionDetails($sdk,$txHash);
                $response['status_code'] = 200;
                $transactionAmountWithFees = ($transactionDetails['amount'] + $transactionDetails['fees']) / config('constants.LAMPORTS');
                $transactionAmount = ($transactionDetails['amount']) / config('constants.LAMPORTS');
                $response['response']['network_fee'] = number_format($transactionDetails['fees']/ config('constants.LAMPORTS'),10);
                $response['response']['result'] = $txHash;
                $response['response']['amount'] = $transactionAmount;
                $response['transactionDetails']['result']['time'] = $transactionDetails['created_at']; //now()->timestamp

                UserWallet::where([
                    'user_id' => $userData['user_id'], 
                    'crypto_currency' => config('constants.CURRENCY'), 
                    'host' => config('constants.HOST')
                ])->decrement('spendable_balance', $transactionAmountWithFees);
                AddressBalance::where(['address' => $request['from_address']])->decrement('amount', $transactionAmountWithFees);
            }
            return $response;            
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send " . config('constants.NICKNAME') . " And Save";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config('constants.NICKNAME') . ' And Save']);

            return false;
        }
    }

    public static function getSoLanaTransactionDetails($sdk, $txHash) {
        $getConfirmationResponse = false;
        $confirmationsData = $sdk->getSignatureStatuses(array([$txHash], ['searchTransactionHistory' => true]));
        if(isset($confirmationsData['value'][0]['confirmationStatus']) && $confirmationsData['value'][0]['confirmationStatus'] == 'confirmed') {
            $getConfirmationResponse = true;
        }
        $count = 0;
        while(!$getConfirmationResponse) {
            $confirmationsData = $sdk->getSignatureStatuses(array([$txHash], ['searchTransactionHistory' => true]));
            if(isset($confirmationsData['value'][0]['confirmationStatus']) && $confirmationsData['value'][0]['confirmationStatus'] == 'confirmed') {
                $getConfirmationResponse = true;
            }
        }
        $transactionInfo = self::getSolanaTransactionNodeDetails($txHash);
        $transactionInfo = json_decode($transactionInfo, true);
        $response['fees'] = $transactionInfo['result']['meta']['fee'];
        $response['amount'] = $transactionInfo['result']['transaction']['message']['instructions'][0]['parsed']['info']['lamports'];
        $response['created_at'] = $transactionInfo['result']['blockTime'];
        return $response;
    }

    public static function getSolanaTransactionNodeDetails($hash) {
        $data['jsonrpc'] = '2.0';
        $data['id'] = '1';
        $data['method'] = 'getTransaction';
        $additionalParams['commitment'] = 'confirmed';
        $additionalParams['encoding'] = 'jsonParsed';
        // $additionalParams['maxSupportedTransactionVersion'] = 0;
        $data['params'] = [$hash,  json_decode(json_encode($additionalParams))];
        $headers = ['Content-Type: application/json'];
        return curlRequest(config('constants.DAEMON_URL'), json_encode($data), true,$headers);
    }

    public static function sendEthAndSave($request, $userData, $affiliate_fee)
    {
        try {
            $token = Affiliate::where(['email' => config('constants.ETHER_JS_EMAIL')])->first();
            $headers = ['x-access-token: ' . $token->token, 'Content-Type: application/x-www-form-urlencoded'];
            $addressDetails = Address::where('address', $request['from_address'])->first();
            $data = 'encryptedJson=' . $addressDetails['encrypted_json'] . '&password=' . Crypt::decryptString($addressDetails['password']) . '&fee_inclusive=true&currency=' . $request['crypto_currency'] . '&from_address=' . $request['from_address'] . '&to_address=' . $request['to_address'] . '&amount=' . $request['amount'];
            $response = json_decode(curlRequest(config('constants.ETHER_JS_URL') . 'send_crypto', $data, true, $headers), 1);

            if ($response['status_code'] == 200) {
                UserWallet::where(['user_id' => $userData['user_id'], 'crypto_currency' => config('constants.CURRENCY'), 'host' => config('constants.HOST')])->decrement('spendable_balance', $request['amount']);
                AddressBalance::where(['address' => $request['from_address']])->decrement('amount', $request['amount']);
                $response = [
                    'status_code' => 200,
                    'response' => ['result' => $response['data']['tx_hash'], 'network_fee' => $response['data']['tx_fee']],
                    'transactionDetails' => ['result' => ['time' => $response['data']['timestamp']]]
                ];
            } else {
                $response = ['status_code' => 400, 'message' => $response['message']];
            }
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send " . config('constants.NICKNAME') . " And Save";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config('constants.NICKNAME') . ' And Save']);

            return false;
        }
    }

    public static function sendCoinsAndSave($request, $userData, $affiliate_fee)
    {
        try {
            $cmd = ["method" => "sendtoaddress", "params" => [$request['to_address'], $request['amount'], 'Comment', 'Comment', true]];
            $logId = Log::insertGetId(['user_id' => $userData['user_id'], 'raw_request' => json_encode($cmd), 'log_type' => 'send_' . strtolower(config('constants.NICKNAME')) . '_logs']);
            $response = self::encodeUrlSendRequest(json_encode($cmd), config('constants.' . $request['altcoin'] . '_URL'));
            $response = json_decode($response, 1);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            if (checkErrorsExistsNew($response) == false) {
                $transactionDetails  = self::getTransaction(trim($response['result']), config('constants.' . $request['altcoin'] . '_URL'));
                $transactionDetails != false ? $transactionDetails = json_decode($transactionDetails, true) : '';
                $network_fee = substr(sprintf('%.8f', floatval($transactionDetails['result']['fee'])), 1);
                UserWallet::where(['user_id' => $userData['user_id'], 'crypto_currency' => config('constants.CURRENCY'), 'host' => config('constants.HOST')])->decrement('spendable_balance', $request['amount'] + $affiliate_fee);
                $response = [
                    'response' => [
                        'result' => $response['result'],
                        'network_fee' => $network_fee,
                    ],
                    'transactionDetails' => $transactionDetails,
                    'status_code' => 200
                ];
            } else {
                $response = ['status_code' => 400, 'message' => $response['error']['message']];
            }
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send " . config('constants.NICKNAME') . " And Save";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config('constants.NICKNAME') . ' And Save']);

            return false;
        }
    }


    /**
     * This function is used to get the detail of transaction against hash.
     * 1) Make url of get transaction
     * 2) send this request to daemon and get transaction detail.
     * 3) In last log the response.
     * @param $txid
     * @return response
     */
    public static function getTransaction($txid, $demon_url)
    {
        try {
            $cmd = ["method" => "gettransaction", "params" => ["" . $txid . ""]];
            $cmd = json_encode($cmd);
            $logId  = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'send_' . config('constants.NICKNAME') . '_logs', 'process_state' => 'Get Transaction Called']);
            $response = self::encodeUrlSendRequest($cmd, $demon_url);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Transaction of Send " . config('constants.NICKNAME');
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Transaction of Send ' . config('constants.NICKNAME')]);
            return false;
        }
    }

    /**
     * This function is used to send crypto in bulk.
     * 3) First it deduct fee amount of of each transaction in bulk and make the sending address their he/she want to send coin.
     * 4) Then log the request against url  and call the the method that return the response
     * 4) In the last update necessary table in DB.
     * @param  $transaction_array
     * @return hash in response
     */
    public static function sendManyCrypto($request)
    {
        try {
            $perLog = Log::insertGetId(['raw_request' => json_encode($request), 'log_type' => 'pre_send_many_ltc_logs']);
            $demon_url = config('constants.' . $request['altcoin'] . '_URL');
            $response = self::sendCryptoBulk($request['transactions'], $demon_url, $request['altcoin']);
            Log::where(['id' => $perLog])->update(['raw_response' => json_encode($response)]);
            DB::table('send_many_logs')->insert(['raw_request' => json_encode($request['transactions']), 'batch_id' => $request['batch_id'], 'raw_response' => json_encode($response)]);
            if (checkErrorsExistsNew($response) == false) {
                self::saveBulkTransactionDetails($request, $response);
                $response =  ['data' => ['status' => 'accepted', 'hash' => $response['result']], 'status_code' => 200];
            } else {
                $response =  ['data' => ['message' => isset($response['error']['message']) ? $response['error']['message'] : $response['error']], 'status_code' => 452];
            }
            return $response;
        } catch (\Throwable $e) {
            $data = "Error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send Many Crypto";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send Many Crypto']);
            return false;
        }
    }

    /**
     * This function is user to perform bulk transactions on daemon
     * @param array $transaction_array
     * @param string $deamon_url
     * @return array $response
     */
    public static function sendCryptoBulk($transactionArray, $demon_url, $altcoin)
    {
        try {
            set_time_limit(0);
            $transactionArray = json_decode($transactionArray, true);
            $cmd = [
                "method" => "sendmany",
                "params" => ["", [], 1, "", []]
            ];
            foreach ($transactionArray as $key => $transaction) {
                DB::table('celoxo_addresses_qbd')->insertGetId(['sending_address' => $transaction['to_address'], 'request_id' => $transaction['request_id'], 'is_coin_sent' => 0,  'address_type' => $altcoin]);
                $amount = (float) $transaction['amount'];
                $cmd['params'][1] = array_merge($cmd['params'][1], [$transaction['to_address'] => $amount]);
                $cmd['params'][4] = array_merge($cmd['params'][4], [$transaction['to_address']]);
            }
            $cmd = json_encode($cmd);
            $cmd = stripslashes($cmd);
            $logId    = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'sendCryptoBulk']);
            $response  = self::encodeUrlSendRequest($cmd, $demon_url);
            $response = json_decode($response, true);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send " . config("constants.NICKNAME") . " BULK";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send ' . config("constants.NICKNAME") . ' BULK']);
            return false;
        }
    }

    /**
     * This will save the transaction Details
     * @param  $request, $response
     **/
    public  static function saveBulkTransactionDetails($request, $response)
    {
        try {
            $demon_url = config('constants.' . $request['altcoin'] . '_URL');
            $transactionList     = json_decode($request['transactions'], true);
            $transactionDetails  = self::getTransaction($response['result'], $demon_url);
            $transactionDetails != false ? $transactionDetails = json_decode($transactionDetails, true) : '';
            foreach ($transactionList as $transaction) {
                DB::table('celoxo_addresses_qbd')->where('request_id', $transaction['request_id'])->update(['is_coin_sent' => 1]);
                $celoxo_id = DB::table('celoxo_addresses_qbd')->where('request_id', $transaction['request_id'])->first();
                $source = substr($transaction['request_id'], 0, 2);
                $affiliateId = DB::table('affiliates')->where('source', $source)->select('id')->first();
                $user = User::where(["email" => $transaction['email'], 'affiliate_id' => $affiliateId->id])->first();
                $wallet = UserWallet::where(['user_id' => $user->id, 'source' => $source,  'crypto_currency' => config('constants.' . $request['altcoin'] . '_CURRENCY'), 'host' => config('constants.' . $request['altcoin'] . '_HOST')])->first();
                DB::table('qbd_transactions')->where('request_id', $transaction['request_id'])->update(['user_wallet_id' => $wallet->id, 'currency' => 'LTC', 'sending_address' => $transaction['to_address'], 'send_transaction_hash' => $response['result']]);
                Transaction::insert(['user_wallet_id' => $wallet['id'], 'celoxo_transaction_id' => $celoxo_id->id, 'amount' => "-" . $transaction['amount'], 'fee' => "0.00000000", 'tx_hash' => $response['result'], 'category' => 'send', 'confirmations' => '0', 'address' => $transaction['to_address'], 'tx_unix_timestamp' => $transactionDetails['result']['time'], 'tx_ts' => date("Y-m-d H:i:s", $transactionDetails['result']['time'])]);
            }
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Send Many " . config('constants.NICKNAME') . " and save";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Send Many ' . config('constants.NICKNAME') . ' and save']);
        }
    }

    /**
     * This function is used to create wallet address
     * 1) Make url and request to daemon.
     * 2) if response is true save the address in database.
     */
    public static function createWalletAddressRandom($url, $address_type)
    {
        try {
            $walletAddress = null;
            $cmd["method"] = "getnewaddress";
            $cmd = json_encode($cmd);
            $response = curlRequest($url, $cmd, true);
            $response = json_decode($response, true);
            $walletAddress = trim($response['result']);
            Address::insert(['address' => $walletAddress, 'is_locked' => 0, 'address_type' => $address_type, 'daemon_used' => 'v6']);
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Create Many " . $address_type . " Addresses";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Create Many ' . $address_type . ' Addresses']);
        }
    }

    /**
     * This function is used to get balance on daemons node.
     * 1) Make url and request to daemon.
     * 2) if response is true save return the balance.
     */

    public static function getDaemonBalance()
    {
        try {
            $cmd['method'] = "getbalance";
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'getDaemonsBalance_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            $response = trim($response);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Get Daemon Balance";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Get Daemon Balance ']);
        }
    }

    /**
     * This function is used to verify the chain
     * @param  $request
     * @return response
     **/

    public static function verifyChain($request)
    {
        try {
            $cmd = ["method" => "verifychain", "params" => []];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'verifychain_logs_' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: verify Chain";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'verify Chain ']);
        }
    }

    /**
     * This function is used to get transaction received by label
     * 1) We will check in the address if the provided address exists in it or not. 
     * @param  $label, $confirmations
     * @return response
     **/

    public static function getReceivedByLabel($request)
    {
        try {
            $label = $request['label'];
            $confirmation = $request['confirmations'];
            $cmd = [
                "method" => "getreceivedbylabel",
                "params" => ["" . $request['label'] . ""]
            ];
            $cmd = json_encode($cmd);
            $logId = Log::insertGetId(['raw_request' => json_encode($cmd), 'log_type' => 'receivedbylabel_logs_ltc' . config('constants.NICKNAME')]);
            $response = self::encodeUrlSendRequest($cmd);
            Log::where(['id' => $logId])->update(['raw_response' => json_encode($response)]);
            $response = trim($response);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: receivedbylabel";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'receivedbylabel']);
        }
    }

    /**
     * Encoding the command and doing a curl request
     * @param  $cmd
     * @return object
     */

    private static function encodeUrlSendRequest($cmd, $url = null)
    {
        try {
            if (!isset($url)) {
                $url = config('ltc_constants.DAEMON_URL');
            }
            $response = curlRequest($url, $cmd, true);
            return $response;
        } catch (\Throwable $e) {
            $data = "error: In " . env('APP_ENV') . " ENV " . str_replace('"', ' ', $e->getMessage()) . "on  Line Number " . $e->getLine() . " in file " . $e->getFile() . " method: Curl " . config('constants.NICKNAME') . " Request";
            slackCurlRequest($data);
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'Curl ' . config('constants.NICKNAME') . ' Request']);
            return NULL;
        }
    }
}
