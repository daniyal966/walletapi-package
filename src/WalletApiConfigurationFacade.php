<?php
namespace WalletApi\Configuration;

use Illuminate\Support\Facades\Facade;

class WalletApiConfigurationFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'walletapi-configuration'; // This should match the key used in the service provider
    }
}
