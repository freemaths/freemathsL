<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Marking extends Model
{
	public $timestamps = false;
	protected $fillable = ['json'];	

	public function to()
	{
		return $this->belongsTo('App\User');
	}
	
	public function from()
	{
		return $this->belongsTo('App\User');
	}
	
}
