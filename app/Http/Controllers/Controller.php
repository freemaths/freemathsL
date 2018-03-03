<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Password;
use DB;
use Log;
use App\User;
use App\Test;
use App\Question;
use App\Help;
use App\Log as StatLog;
//use App\Notifications\ResetPassword;

class Controller extends BaseController
{
	private $user=null;
	public function __construct()
	{
		//$this->middleware('guest', ['except' => 'logout']);
	}
	
	public function log_event(Request $request)
	{
		// 'cookie'=>$request->cookie('token'), - cookie not working
		Log::info('log_event',['uid'=>$request->user()->id,'paper'=>$request->paper,'question'=>$request->question,'event'=>$request->event]);
		$log=$request->user()->logs()->create([
				'event'=>$request->event,
				'paper'=> $request->paper,
				'question'=>$request->question,
				'answer'=>$request->has('answer')?$request->answer:'',
				'comment'=>$request->has('comment')?$request->comment:'',
				'variables'=>$request->has('vars')?json_encode($request->vars):''
		]);
		return response()->json(['log'=>$log]);
	}
	
	public function help(Request $request)
	{
		Log::info('log_help',['uid'=>$request->user()->id,'topic'=>$request->topic]);
		$row=$request->user()->logs()->create([
				'event'=>'Help',
				'paper'=> '',
				'question'=>'',
				'answer'=>$request->topic,
				'comment'=>'',
				'variables'=>''
		]);
		return response()->json($row);
	}
	
	public function data(Request $request)
	{
		Log::info('data');
		$tests=Test::all();
		$questions=Question::where('next_id',0)->get();
		$qmap=DB::table('question_test')->get();
		$help = Help::where('next_id',0)->get();
		return response()->json(['tests'=>$tests,'questions'=>$questions,'qmap'=>$qmap,'help'=>$help]);
	}
	
	
	public function login(Request $request)
	{
		$this->validate($request, [
				'email' => 'required',
				'password' => 'required'
		]);
		if ($request->ajax()) {
			$user = User::where('email',$request->email)->first();
			if ($user && Hash::check($request->password, $user->password))
			{
				return $this->ret_user($user);
			}
			else
			{
				Log::debug('login - unknown',['email'=>$request->email]);
				return response()->json(['email'=>'These credentials do not match our records.'],401);
			}		
		}
	}
	
	public function ret_user($user,$remeber=false)
	{
		Log::debug('ret_user',['id'=>$user->id,'email'=>$user->email]);
		$user->remember_token=$user->id.'_'.base64_encode(str_random(40));
		$user->save();
		$log=StatLog::where('user_id',$user->id)->orderBy('id','asc')->get();
		return response()->json(['uid'=>$user->id,'name'=>$user->name,'log'=>$log,'isAdmin'=>$user->id==1,'token'=>$user->remember_token]); //->cookie(new Cookie ('token',$user->remember_token,10));
	}
	
	private function set_password($user,$password)
	{
		$this->user=$user;
		Log::debug('set_password',['email'=>$user->email]);
		$user->password=Hash::make($password);
		$user->save();
	}
	
	public function reset(Request $request)
	{
		$this->validate($request, [
				'email' => 'required',
				'password' => 'required|min:6',
				'password_confirmation' => 'same:password'
		]);
		
		$ret = Password::reset($request->only('email','password','password_confirmation','token'),function($u,$p){$this->set_password($u,$p);});
		Log::debug('reset',['email'=>$request->email,'ret'=>$ret]);
		//$ret=$user->notify(new ResetPassword($user));
		
		if ($ret == 'passwords.reset') return $this->ret_user($this->user); //user set by set_password
		return response()->json(['error'=>$ret],401);
		//return $this->login($request);
	}
	
	public function marking(Request $request)
	{
		if ($request->ajax())
		{
			$parts=explode(":",$request->test_id);
			$tid=$parts[0];
			Log::debug('ajax_marking:',['test_id'=>$request->test_id,'tid'=>$tid,'info'=>json_encode($request->info)]);
			if (($info=$request->info) && $tid && ($test=Test::find($tid)))
			{
				$log = $request->user()->logs()->create([
						'event'=>'✓✗',
						'paper'=> $request->test_id,
						'question'=>0,
						'answer'=>'',
						'comment'=>'',
						'variables'=>json_encode($info)
				]);
				return response()->json(['log'=>$log]);
			}
		}
	}
	
	public function forgot(Request $request)
	{
		if ($request->ajax()) {
			$this->validate($request,[
					'email' => 'required|email|max:255|exists:users',
			]);
			//$user=User::where('email',$request->email)->first();
			$ret = Password::sendResetLink(['email'=>$request->email]);

			//$ret=$user->notify(new ResetPassword($user));

			return response()->json($ret);
		}
	}
	
	public function register(Request $request)
	{
		if ($request->ajax()) {
			$this->validate($request,[
					'name' => 'required|max:255',
					'email' => 'required|email|max:255|unique:users',
					'password' => 'required|min:6',
					'password_confirmation' => 'required|same:password'
			]);
			Log::debug('register',['email'=>$request->email]);
			$user=User::create([
					'name' => $request['name'],
					'email' => $request['email'],
					'password' => Hash::make($request['password'])
			]);
			return $this->login($request);
		}
	}
	
	public function saveQ(Request $request)
	{
		if($request->ajax() && ($question = $request->question)){
			$question = $request->question;
			Log::debug('saveQ',['question'=>$question]);
			if (isset($question['delete']))
			{
				if ($question['delete']>0) Question::find($question['delete'])->delete();
				return response()->json($question);
			}
			else
			{
				if ($question['id'] != 0) $q = Question::find($question['id']);
				else $q = new Question;
				if (!isset($q->previous_id) && isset($question['previous_id'])) $q->previous_id=$question['previous_id'];
				$q->next_id=0;
				unset($question['number']); // stored on test_question
				unset($question['tests']); // stored on test_question
				//unset($question['marks']); // stored on test_question
				$q->json = json_encode($question);
				$q->user()->associate($request->user());
				$q->save(); // json id corrected
				$question['id']=$q->id;
				return response()->json($question);
			}
		}
	}
}
