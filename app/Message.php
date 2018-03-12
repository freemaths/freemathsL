<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Message extends Model
{
	public $timestamps = false;
	protected $fillable = ['json','to_uid','from_uid'];	
/*
	public function to()
	{
		return $this->belongsTo('App\User');
	}
	
	public function from()
	{
		return $this->belongsTo('App\User');
	}
	*/
}
