<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
	
	public function setUpdatedAtAttribute($value)
	{
		// Do nothing. - no updated-at column
	}
    //
	protected $fillable = ['email'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
