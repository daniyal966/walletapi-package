<?php

namespace WalletApi\Configuration\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use WalletApi\Configuration\WalletApiConfigurationFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use WalletApi\Configuration\Models\Affiliate;
use WalletApi\Configuration\Http\Requests\TokenRequestValidation;

class WalletApiController extends Controller
{
    
    

    public function token(Request $request)
    {               
        $requiredParameters = ['email','password'];
        $validationResult = self::validateRequiredParameters($request, $requiredParameters);
        if ($validationResult !== null) {
            return $validationResult;
        }

        $credentials = $request->only('email', 'password');
        
        try {
            $url=config("package_constants.test_url");
            $token = curlRequest($url . 'token',json_encode($credentials),TRUE,$headers = ['Content-Type: application/json'], false);
            $decode_token = json_decode($token['body'], true);
            $data = [
                'token' =>  $decode_token['token'],
            ];
            return response()->json( $data,200);

        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
    }



    /**
     * This function is used to creation of user as well as address.
     * 1) First we check the altcoin type if not set then default value set to ltc.
     * 2) Then log the request and call the model according to altcoin.
     * 3) In each we first check user existence and their customer wallet if not available then create user and his/her wallet.
     * 4) In the last log the response and return response to user.
     * @param  CreateAddressValidation $request
     * @return JSON response
     */
    
    public static function createAddress(Request $request)
    {

        try {
            $requiredParameters = ['altcoin','request_id','email'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'create_address', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    public static function transactionsAgainstAddress(Request $request)
    {

        try {
            $requiredParameters = ['address'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'address_transactions', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
 
    public static function getAddresses(Request $request)
    {

        try {
            $requiredParameters = ['email','altcoin','fields'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'user_address', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    public static function validateAddress(Request $request)
    {

        try {
            $requiredParameters = ['address','altcoin'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'validate_address', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    public static function getReceivedByAddress(Request $request)
    {

        try {
            $requiredParameters = ['address','altcoin'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'received_by_address', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public static function storeLabel(Request $request)
    {

        try {
            $requiredParameters = ['label','altcoin','address'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'set_label', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    public static function getAddressByLabel(Request $request)
    {
        try {
            $requiredParameters = ['label','altcoin','email'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'address_by_label', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    // transactions

    public static function getTransactionData(Request $request)
    {
        try {
            $requiredParameters = ['email','fields','altcoin'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'transactions', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    
    public static function sendCrypto(Request $request)
    {
        try {
            $requiredParameters = ['altcoin','to_address','from_address','crypto_currency','amount','email'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'send_crypto', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    
    public static function sendManyCryptos(Request $request)
    {
        try {
            $requiredParameters = ['altcoin','batch_id','callback_url','transactions'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'send_many_crypto', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    
    public static function getReserve(Request $request)
    {
        try {
            $requiredParameters = ['altcoin'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'get_reserves', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
    
    public static function cleanPayDirectBuyContract(Request $request)
    {
        try {
            $requiredParameters = ['email','merchant_email','request_id','fiat_amount','callback_url','fiat_currency','altcoin','customer_ip','trx_id','psp_mid','contract','flow_wallet'];
            $validationResult = self::validateRequiredParameters($request, $requiredParameters);
            if ($validationResult !== null) {
                return $validationResult;
            }
            $headers=$request->header()['authorization'][0];
            $url=config("package_constants.test_url");
            $payload=$request->all();
            $address = curlRequest($url . 'buy_sell_contract', json_encode($payload), TRUE,$headers = ["Authorization: Bearer $headers",'Content-Type: application/json','Accept:application/json'],false);
            $decode_data = json_decode($address['body'], true);
            return response()->json( $decode_data,200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }


    

    


    

      /**
     * Validate the presence of required parameters in the request.
     *
     * @param Request $request
     * @param array $requiredParameters
     * @return \Illuminate\Http\JsonResponse|null
     */
    public static function validateRequiredParameters(Request $request, array $requiredParameters)
    {
         // Create an array to store missing parameters
        $missingParameters = [];

        // Check if all required parameters are present in the request
        foreach ($requiredParameters as $param) {
            if (!$request->has($param)) {
                // If a required parameter is missing, add it to the list of missing parameters
                $missingParameters[] = $param;
            }
        }

        // Check if two or more parameters are missing
        if (count($missingParameters) >= 1) {
            // Return an error response specifying the missing parameters
            return ['error' => 'The following parameters are missing: ' . implode(', ', $missingParameters)];
        }

        // Validation passed
        return null;
    }

    
    
    
    
    }

    