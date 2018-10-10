<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Question extends Model
{
	public $timestamps = false;
	protected $fillable = ['json'];	
	
	public function tests()
	{
		return $this->belongsToMany('App\Test')->withPivot('number', 'marks');
	}
	
	public function user()
	{
		return $this->belongsTo('App\User');
	}
	
	public function previous()
	{
		return $this->belongsTo('App\Question', 'previous_id');
	}
	
	public function next()
	{
		return $this->belongsTo('App\Question', 'next_id');
	}
	
	public static function find_keywords($search)
	{
		return Question::whereRaw("MATCH json AGAINST ('$search' IN BOOLEAN MODE) AND next_id IS NULL")->get();
	}
}
