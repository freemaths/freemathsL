<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Test extends Model
{
	protected $fillable = ['title'];	
	
	public function questions()
	{
		return $this->belongsToMany('App\Question')->withPivot('number', 'marks');
	}
	
	public function user()
	{
		return $this->belongsTo('App\User');
	}
	
	public static function find_keywords($search)
	{
		return Test::whereRaw("MATCH (title,keywords) AGAINST ('$search' IN BOOLEAN MODE)")->get();
	}
	
	public static function find_title($topic)
	{
		return Test::where('title',$topic)->where('user_id',1)->first();
	}
}
