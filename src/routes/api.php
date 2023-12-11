<?php


use Illuminate\Http\Request;
use WalletApi\Configuration\Http\Controllers\WalletApiController;
use WalletApi\Configuration\Models\KycToken;


Route::group(['namespace'=>'WalletApi\Configuration\Http\Controllers'], function(){
    Route::post('authenticate', [WalletApiController::class , 'token']);
    Route::post('create_address', [WalletApiController::class , 'createAddress']);
    Route::post('address_transactions', [WalletApiController::class , 'transactionsAgainstAddress']);
    Route::post('user_address', [WalletApiController::class , 'getAddresses']);
    Route::post('validate_address', [WalletApiController::class , 'validateAddress']);
    Route::post('received_by_address', [WalletApiController::class , 'getReceivedByAddress']);
    Route::post('set_label', [WalletApiController::class , 'storeLabel']);
    Route::post('address_by_label',[WalletApiController::class, 'getAddressByLabel']);

    



    
    

    

    Route::group(['middleware'=>['api_auth']], function(){

    });

    Route::post('backend-token-with-userid', [AliceController::class , 'backendTokenWithUserId']);


    Route::post('/validate-kyc-token' , [AliceController::class , 'performKyc']);
    Route::post('/kyc-user-report' , [AliceController::class , 'getKycUserReport']);
    Route::post('/update-user-status' , [AliceController::class , 'updateUserStatusAfterDocumentCheck']);
    Route::post('/update-user-report' , [AliceController::class , 'updateUserReportAgainstThresholdCheck']);





});