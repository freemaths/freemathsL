<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Crypt;
use Log;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
        	$user=null;
        	if ($FMtoken=$request->header('FM-Token')=='null'?null:$request->header('FM-Token'))
        	{
        		try {
        			$token=json_decode(Crypt::decrypt($FMtoken));
        			$user=User::where(['id'=>$token->id,'remember_token'=>$token->token])->first();
        		} catch (DecryptException $e) {
        			Log::debug('auth fail- Decryption Exception');
        		}
        	}
        	if ($user) Log::debug('api auth success',['id'=>$user->id,'name'=>$user->name]);
        	else Log::debug('api auth fail',['FM-Token'=>$FMtoken]);
        	return $user;
        });
    }
}
