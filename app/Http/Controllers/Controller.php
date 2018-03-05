<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Password;
use DB;
use Log;
use Illuminate\Support\Facades\Crypt;
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
	
	private function auth($request,$object=false)
	{
		if ($request->cookie('token') && $token=json_decode(Crypt::decrypt($request->cookie('token')))) {
			if ($request->ip() == $token->ip && $user=User::where(['id'=>$token->id])->first())
			{
				Log::info('auth',['uid'=>$user->id,'cookie'=>$token->time,'time'=>time(),'diff'=>time()-$token->time]);
				if ($object) return $user; // even if tokens don't match
				else if ($user->remember_token == $token->token && time()-$token->time < 30*60) return $this->ret_user($user,$request->ip());
				else return true;
			}
		}
		else return null;
	}
	
	public function students(Request $request)
	{
		$log=[];
		$students=[];
		if($request->ajax()){
			if ($students=$request->user()->students($request->only('all')))
			{
				Log::debug('ajax_stats:',['students'=>$students]);
				if ($request->only('last')) $log=StatLog::whereIn('user_id',$students)->where('id','>',$request->only('last'))->orderBy('id','asc')->get();
				else
				{
					$log=StatLog::whereIn('user_id',$students)->orderBy('id','asc')->get();
					$users=User::whereIn('id',$students)->select('id','name')->get();
				}
			}
			return response()->json(['log'=>$log,'users'=>$users]);
		}
	}
	
	public function log_event(Request $request)
	{
		// 'cookie'=>$request->cookie('token'), - cookie not working
		Log::info('log_event',['uid'=>$request->user()->id,'paper'=>$request->log['paper'],'question'=>$request->log['question'],'event'=>$request->log['event']]);
		$log=$request->user()->logs()->create([
				'event'=>$request->log['event'],
				'paper'=> $request->log['paper'],
				'question'=>$request->log['question'],
				'answer'=>@$request->log['answer']?:'',
				'comment'=>@$request->log['comment']?:'',
				'variables'=>@$request->log['vars']?json_encode($request->log['vars']):''
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
		return response()->json(['tests'=>$tests,'questions'=>$questions,'qmap'=>$qmap,'help'=>$help,'user'=>$this->auth($request)]);
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
				$resp=$this->ret_user($user,$request->ip(),true);
				return response()->json($resp)->cookie(new Cookie ('token',$resp['token'],'+30 days'));;
			}
			else
			{
				Log::debug('login - unknown',['email'=>$request->email]);
				return response()->json(['email'=>'These credentials do not match our records.'],401);
			}		
		}
	}
	
	public function password(Request $request)
	{
		$this->validate($request, [
				'password' => 'required'
		]);
		if ($request->ajax()) {
			$user=$this->auth($request,true);
			if ($user && Hash::check($request->password, $user->password))
			{
				$resp=$this->ret_user($user,$request->ip(),true);
				return response()->json($resp)->cookie(new Cookie ('token',$resp['token'],'+30 days'));
			}
			else
			{
				Log::debug('password - fail',['email'=>$user?$user->email:null]);
				return response()->json(['password'=>'These credentials do not match our records.'],401);
			}
		}
	}
	
	public function logout(Request $request) {
		return response()->json('logged out')->cookie(new Cookie ('token','',0));
	}
	
	public function ret_user($user,$ip,$set=false)
	{
		if ($set)
		{
			$user->remember_token=base64_encode(str_random(40));
			$user->save();
			Log::debug('ret_user',['id'=>$user->id,'email'=>$user->email,'remember_token'=>$user->remember_token,'ip'=>$ip]);
		}
		$token = Crypt::encrypt(json_encode(['id'=>$user->id,'token'=>$user->remember_token,'time'=>time(),'ip'=>$ip]));
		return (['uid'=>$user->id,'name'=>$user->name,'log'=>$user->log(),'isAdmin'=>$user->isAdmin(),'isTutor'=>$user->isTutor(),'token'=>$token]);
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
