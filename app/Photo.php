<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
	// public $timestamps = false; // needed temporarirly for fixes
	public $timestamps = [ "created_at" ]; // enable only to created_at
	
	public function setUpdatedAtAttribute($value)
	{
		// Do nothing. - no updated-at column
	}
    //
	protected $fillable = ['user_id', 'json'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
