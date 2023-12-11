<?php

namespace WalletApi\Configuration\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;


class Affiliate extends Authenticatable implements JWTSubject
{
    use HasFactory;
    protected $table = 'affiliates';
    protected $fillable = ['name','email','password','created_at','updated_at', 'affiliate_code', 'source', 'token'];

    public $timestamps = FALSE;

    public function getJWTIdentifier() {
        return $this->getKey();
    }
    
    public function getJWTCustomClaims() {
        return [];
    }
}   
