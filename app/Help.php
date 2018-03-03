<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Help extends Model
{
	protected $fillable = ['title','text'];	
	protected $table = 'help'; // would default to "helps"
	
	public function user()
	{
		return $this->belongsTo('App\User');
	}
	
	public function previous()
	{
		return $this->belongsTo('App\Help', 'previous_id');
	}
	
	public function next()
	{
		return $this->belongsTo('App\Help', 'next_id');
	}
	
	/*
	public static function find_keywords($search)
	{
		return Question::whereRaw("MATCH json AGAINST ('$search' IN BOOLEAN MODE) AND next_id IS NULL")->get();
	}
	*/
}
