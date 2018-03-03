<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use App\Notifications\CanResetPassword;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use DB;
use Illuminate\Notifications\Notifiable;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable, Authorizable, Notifiable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
    public function logs()
    {
    	return $this->hasMany(Log::class);
    }
    
    public function tutors()
    {
    	return $this->hasMany(Tutor::class);
    }
    
    public function students($all = false)
    {
    	if ($all && $this->email == 'ed@darnell.org.uk') return DB::table('users')->pluck('id');
    	return DB::table('tutors')->where('email', $this->email)->pluck('user_id');
    }
    
    public function student($uid)
    {
    	if (DB::table('tutors')->where(['email' => $this->email,'user_id'=>$uid])->first()) return true;
    	else return false;
    }
}
