<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Address extends Model
{
    use HasFactory;
    protected $table = "addresses";
    protected $fillable = ['request_id', 'wallet_id', 'created_at', 'updated_at', 'psp_mid'];

    public $timestamps = FALSE;

    /**
     * This function is used to get addresses from database after applying filters
     * @param int $userWalletId
     * @param string $fields
     * @param int $page
     * @return array||bool $resposne
     */
    public static function getAddress($userWalletId, $fields, $page){
        $filter = json_decode($fields, true);
        $address_sql = Address::select('address','label', 'address_type', 'updated_at as created_at')->where(['user_wallet_id' => $userWalletId, 'address_type' => config('constants.NICKNAME') ]);
        if($filter['items_per_page'] >= 0)
        {
            foreach($filter['search_filter'] as $key => $value)
            {
                if($value != null)
                    $address_sql->where($key, 'LIKE' ,'%'.$value.'%');
            }
        }
        $total_count = $address_sql->get()->count();
        if($filter['items_per_page'] > 0){
            $address_sql->limit($filter['items_per_page']);
            $address_sql->offset($filter['items_per_page'] * ($page - 1));
        }
        $userAddress = $address_sql->orderBy('updated_at','DESC')->get()->toArray();
        if(!empty($userAddress)){
            $response['total'] = $total_count;
            $response['addresses'] = $userAddress;
            return $response;
        }else{
            $response['total'] = 0;
            $response['addresses'] = [];
            return $response;
        }
    }
    
    /**
     * This function is used to update address labels
     * @param string $address
     * @param string $label
     * @return string||bool address_type
     */
    public static function updateLabel($address , $label){
        try{
            $updateAddress = Address::where('address', $address)->update(['label' => $label]);
            if($updateAddress){
                $response = ['data' => 'Label is set as ' . $label, 'status_code' => 200];
            } else {
                $response = ['data' => 'Label is not set for address ' . $address, 'status_code' => 404];
            }
            return $response;
        } catch (\Exception $e) {
            ErrorLog::insert(['error' => $e->getMessage()." Line Number ". $e->getLine(),'method'=>'Update Address Label']);
            return false;
        }
    }

    /**
     * This function is used to update address labels
     * @param Object $request
     * @return Object Address
     */
    public function getAllTranscationsOnAddress($request){
        try {
            return Address::where('address', $request->address)->get();
        } catch (\Exception $e){
            ErrorLog::insert(['error' => $e->getMessage()." Line Number ". $e->getLine(),'method'=>'Get All Transcations WRT to Address']);
            return false;
        }
    }
}