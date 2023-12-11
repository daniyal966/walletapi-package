<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use WalletApi\Configuration\Models\UserWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = FALSE;
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'created_at',
        'updated_at'
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


       /*
     * This function is used to check user existence and their customer wallet if not available then create user and his/her wallet
     * 1)First we get the user through email from DB if not available then log the request and call the wallet existence method  
     * 4) In the last return response to user.
     * @param  $request($eamil,$source,$crypto_currency, $host)
     * @return response
     */
    public static function checkUserExistence($request, $cryptoCurrency, $host)
    {   
        Log::insertGetId(['request_id' => strtoupper($request->source).$request['request_id'] , 'raw_request'=>json_encode($request->all()), 'log_type'=>'check_user_existence_logs', 'raw_response'=> 'Check User Existence Logs', 'process_state' => 'Check User Existance']);
        $source = strtoupper($request->source);
        // dump($request->all());
        $user = User::where(["email" => $request->email, 'affiliate_id' => $request->id])->first();
        !isset($user) ? $userId = User::insertGetId(['email' => $request->email, 'affiliate_id' => $request->id, 'tag' => isset($request->tag) ? strtolower(trim($request->tag)) : NULL]) : $userId = $user->id;
        $walletId = self::checkUserWalletExistence($userId, $source, $cryptoCurrency, $host,$request);
        Log::insertGetId(['request_id' => $source.$request['request_id'] , 'user_id' => $userId, 'raw_request'=>json_encode($request->all()), 'log_type'=>'check_user_existence_logs', 'raw_response'=> 'Check User Existence Logs', 'process_state' => 'Return Response Check User Existance']);
        return ['user_id' => $userId, 'user_wallet_id' => $walletId];
    }
    
  

    private static function checkUserWalletExistence($userId, $source, $cryptoCurrency, $host,$request)
    {   
        Log::insertGetId(['request_id' => $source.$request['request_id'] , 'user_id' => $userId, 'raw_request'=>json_encode($request->all()), 'log_type'=>'check_user_wallet_existence_logs', 'raw_response'=> 'Check User Wallet Existence Logs', 'process_state' => 'Check User Wallet Existance']);
        $wallet = UserWallet::where(["user_id" => $userId, 'source' => $source,  'crypto_currency' => $cryptoCurrency, 'host' => $host])->first();
        (!isset($wallet) || $wallet == NULL) ? $walletId = UserWallet::insertGetId(['user_id' => $userId, 'source' => $source, 'crypto_currency' => $cryptoCurrency, 'host' => $host ,'is_locked'=>$request->is_locked]):$walletId =$wallet->id;
        Log::insertGetId(['request_id' => $source.$request['request_id'] ,  'user_id' => $userId, 'raw_request'=>json_encode($request->all()), 'log_type'=>'check_user_wallet_existence_logs', 'raw_response'=> 'Check User Wallet Existence Logs', 'process_state' => 'Return Response Check User Wallet Existance']);
        return $walletId;
    }

    public static function checkWalletExists($request, $cryptoCurrency, $host) {
        $user = User::where(['email' => $request->email, 'affiliate_id' => $request->id])->first();
        $source = strtoupper($request->source);
        $walletId = UserWallet::where(["user_id" => $user->id, 'crypto_currency' => $cryptoCurrency, 'host' => $host])->first();
        (isset($walletId) && $walletId != NULL) ?  $response = ['status_code' => 409, 'data' => ['message'=>'Wallet already exists.']] : (UserWallet::insertGetId(['user_id' => $user->id,'source' => $source,'crypto_currency' => $cryptoCurrency,'host' => $host,'is_locked'=>$request->is_locked]) AND $response = ['status_code' => 201, 'data' => ['message' => 'User Wallet Created']]);
        return $response;
    }
    
    public static function checkUserAndWallet($request, $cryptoCurrency, $host) {
        $user = User::where(['email' => $request->email, 'affiliate_id' => $request->id])->first();
        $source = strtoupper($request->source);
        $walletId = UserWallet::where(["user_id" => $user->id, 'crypto_currency' => $cryptoCurrency, 'host' => $host])->first();
        (isset($walletId) && $walletId != NULL) ?  $response = ['user_id' => $user->id , "user_wallet_id" => $walletId->id] : $response = ['status_code' => 409];
        return $response;
    }
}
