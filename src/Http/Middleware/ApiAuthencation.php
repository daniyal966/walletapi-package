<?php

namespace WalletApi\Configuration\Http\Middleware;

use Illuminate\Support\Facades\Route;
use Closure;
use Illuminate\Http\Request;
use JWTAuth;
use Exception;
use WalletApi\Configuration\Models\Affiliate;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use WalletApi\Configuration\Models\Log;
use Illuminate\Support\Facades\DB;
use WalletApi\Configuration\Models\ErrorLog;

class ApiAuthencation extends BaseMiddleware
{
    private $arrayRoutes = [
        'send_many_crypto' => 2,
        'send_crypto' => 2,
        'verify_chain' => 2,
        'validate_address' => 2,
        'set_label' => 2,
        'address_by_label' => 2,
        'address_info' => 2,
        'received_by_address' => 2,
        'received_by_label' => 2,
        'create_wallet' => 2,
        'address_transactions' => 2,
        'info_by_tag' => 2,
        'list_transactions' => 2,
        'transaction' => 2,
        'reset_wallet_password' => 2,
        'bcp_buy' => 1,
        'buy_sell_contract' => 1,
        'check_nft_confirmations' => 1,
        'assets' => 1,
        'create_address' => 1,
        'user_address' => 1,
        'wallet' => 1,
        'rates' => 1,
        'transactions' => 1,
        'create_user' => 1,
        'get_reserves' => 1,
        'user_info' => 1,
        'notification' => 1,
        'quantoz_callback' => 1,
        'get_ethereum_transactions' => 1,
        'list_nfts' => 1,
        'buy_nft' => 1,
        'reserve_nft' => 1,
        'get_cleanPay_transactions' => 1,
        'get_matic_balance' => 1,
        'get_nft_images' => 1,
        'get_tier_status' => 1,
        'get_nft_transaction' => 1,
        'update_merchant_address_request_id' => 1,
        'verify_address' => 1
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {   
        try {
            $affiliate = JWTAuth::parseToken()->authenticate();
            DB::beginTransaction();
            $currentAffiliate = DB::table('affiliates')->where('email', $affiliate->email)->lockForUpdate()->first();
            $request->request->add(['affiliate_code' => $affiliate->affiliate_code, 'id' => $affiliate->id, 'source' => $affiliate->source, 'affiliate_email' => $affiliate->email, 'is_locked' => $affiliate->is_locked, 'request_limit' => $affiliate->request_limit, 'allowed_addresses' => $affiliate->allowed_addresses]);
            $route = Route::getFacadeRoot()->current()->uri();
            $logId = Log::insertGetId(['request_id' => $affiliate->source . $request->request_id, 'raw_request' => json_encode($request->all()), 'log_type' => $route . '_middleware', 'raw_response' => 'Affiliate Request Limit : ' . $currentAffiliate->request_limit, 'process_state' => 'Affiliate Request Limit']);
            if (array_key_exists($route, $this->arrayRoutes)) {
                $limit = $this->arrayRoutes[$route];
                if ($currentAffiliate->request_limit != 0 && $currentAffiliate->request_limit >= $limit) {
                    $response = DB::table('affiliates')->where('email', $affiliate->email)->update(['request_limit' => $currentAffiliate->request_limit - $limit]);
                    DB::commit();
                } else {
                    DB::commit();
                    $data = ['data' => '', 'status_code' => 429];
                    return response()->generate_response($data['data'], $data['status_code']);
                }
            } else {
                $data = ['data' => '', 'status_code' => 404];
                return response()->generate_response($data['data'], $data['status_code']);
            }
        } catch (Exception $e) {
            DB::rollBack();
            ErrorLog::insert(['error' => $e->getMessage() . " Line Number " . $e->getLine(), 'method' => 'API Authentication Middleware']);
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['status' => 'Token is Invalid'], 403);
            } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['status' => 'Token is Expired'], 401);
            } else {
                return response()->json(['status' => 'Something Went Wrong'], 404);
            }
        }
        return $next($request);
    }
}
