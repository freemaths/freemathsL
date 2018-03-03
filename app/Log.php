<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
	// public $timestamps = false; // needed temporarirly for fixes

	public function setUpdatedAtAttribute($value)
	{
		// Do nothing. - no updated-at column
	}
    //
	protected $fillable = ['answer', 'comment', 'event', 'paper', 'question', 'variables'];
	
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
