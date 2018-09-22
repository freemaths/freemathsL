<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Password;
use Illuminate\Support\Facades\Storage;
use DB;
use Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Contact;
use App\Mail\ContactCopy;
use Illuminate\Support\Facades\Crypt;
use App\User;
use App\Test;
use App\Question;
use App\Help;
use App\Tutor;
use App\Message;
use App\Log as StatLog;
use Jenssegers\Agent\Agent;
//use App\Notifications\ResetPassword;

class Controller extends BaseController
{
	private $user=null;
	
	public function __construct()
	{
		//$this->middleware('guest', ['except' => 'logout']);
	}
	
	// Which routes must authenticate defined in routes web.php
	// For main autentication see AuthServiceProvider in Providers
	// auth is used to manually authenticate for non auth routes 
	private function auth($request,$object=false,$expire=false)
	{
		$method='header';
		$FMtoken=$request->header('FM-Token')=='null'?null:$request->header('FM-Token'); //laravel bug?
		if (!$FMtoken) {
			$FMtoken=$request->cookie('FM-Token'); // may change this not to use token
			$method='cookie';
		}
		if ($FMtoken && $token=json_decode(Crypt::decrypt($FMtoken))) {
			if ($request->ip() == $token->ip && $user=User::where(['id'=>$token->id])->first())
			{
				if ($object)
				{
					if ($expire && !($user->remember_token == $token->token && time()-$token->time < 30*60)) $ret=null;
					else $ret=$user;
				}
				else if (($user->remember_token == $token->token && time()-$token->time < 30*60)) {
					$ret=$this->ret_user($user,$request->ip());
				}
				else $ret='password'; // React will prompt for password
			}
			else $ret=null;
		}
		else {
			$ret=null;
			$method=false;
		}
		Log::info('auth',['method'=>$method,'ret'=>is_object($ret)&&isset($ret->id)?$ret->id:isset($ret['id'])?$ret['id']:$ret,'object'=>$object,'expire'=>$expire]); //,'token'=>$FMtoken,'header'=>$request->header('FM-Token'),'cookie'=>$request->cookie('FM-Token')]);
		return $ret;
	}
	
	public function students(Request $request)
	{
		$log=[];
		$students=[];
		if ($request->user()->isAdmin() && $request->all)
		{
			$users=User::select('id','name')->get();
			if ($request->has('last')) $log=StatLog::where('id','>',$request->last)->orderBy('id','asc')->get();
			else $log=StatLog::orderBy('id','asc')->get();
		}
		else
		{
			$students=$request->user()->students();
			$users=User::whereIn('id',$students)->select('id','name')->get();
			if ($request->has('last')) $log=StatLog::whereIn('user_id',$students)->where('id','>',$request->last)->orderBy('id','asc')->get();
			else $log=StatLog::whereIn('user_id',$students)->orderBy('id','asc')->get();
		}
		Log::debug('students',['users'=>$users]);
		return response()->json(['log'=>$log,'users'=>$users]);
	}
	
	public function users(Request $request)
	{
		if ($request->user()->isAdmin()) $users=User::select('id','name','email','created_at','updated_at')->get();
		return response()->json(['users'=>$users]);
	}
	
	public function log_event(Request $request)
	{
		Log::info('log_event',['id'=>$request->user()->id,'paper'=>$request->log['paper'],'question'=>$request->log['question'],'event'=>$request->log['event']]);
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
		$uid=0;
		if ($user=$this->auth($request,true)) $uid=$user->id;
		
		$row=StatLog::create(['user_id'=>$uid,
				'event'=>'Help',
				'paper'=> '',
				'question'=>'',
				'answer'=>$request->topic,
				'comment'=>'',
				'variables'=>'']);
		return response()->json($row);
	}
	
	public function user(Request $request) {
		return response()->json($this->auth($request));
	}
	
	public function login(Request $request)
	{
		$this->validate($request, [
				'email' => 'required',
				'password' => 'required'
		]);
		$user = User::where('email',$request->email)->first();
		if ($user && Hash::check($request->password, $user->password))
		{
			$resp=$this->ret_user($user,$request->ip(),true);
			return response()->json($resp)->cookie(new Cookie ('FM-Token',$resp['token'],'+30 days'));;
		}
		else
		{
			Log::debug('login - unknown',['email'=>$request->email]);
			return response()->json(['email'=>'These credentials do not match our records.'],401);
		}
	}
	
	public function password(Request $request)
	{
		$this->validate($request, [
				'password' => 'required'
		]);
		$to_user=$request->to?User::find($request->to['id']):null;
		$user=$this->auth($request,true);
		Log::debug('password',['user'=>$user,'to'=>$request->to,'to_user'=>$to_user]);
		if (($user && Hash::check($request->password, $user->password)) ||
			($to_user && Hash::check($request->password, $to_user->password)))
		{
			if ($request->auth && $user) return response()->json(['auth'=>true]);
			$resp=$this->ret_user($to_user?$to_user:$user,$request->ip(),true);
			return response()->json($resp)->cookie(new Cookie ('FM-Token',$resp['token'],'+30 days'));
		}
		else
		{
			Log::debug('password - fail',['email'=>$user?$user->email:null]);
			return response()->json(['password'=>'These credentials do not match our records.'],401);
		}
	}
	
	public function logout(Request $request) {
		return response()->json('logged out')->cookie(new Cookie ('FM-Token','',0));
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
		$agent=new Agent();
		return (['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'log'=>$user->log(),'isAdmin'=>$user->isAdmin(),'isMobile'=>$agent->isMobile(),'tutors'=>$user->tutor_details(),'isTutor'=>$user->isTutor(),'token'=>$token]);
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
		//Log::debug('reset',['email'=>$request->email]);
		$this->validate($request, [
				'email' => 'required',
				'token' => 'required',
				'password' => 'required|min:6',
				'password_confirmation' => 'required|same:password'
		]);
		
		$ret = Password::reset($request->only('email','password','password_confirmation','token'),function($u,$p){$this->set_password($u,$p);});
		Log::debug('reset',['email'=>$request->email,'ret'=>$ret]);
		//$ret=$user->notify(new ResetPassword($user));
		
		if ($ret == 'passwords.reset') {
			$resp=$this->ret_user($this->user,$request->ip(),true);
			return response()->json($resp)->cookie(new Cookie ('FM-Token',$resp['token'],'+30 days'));
		}
		else return response()->json(['error'=>"password reset expired or does not match email"],401);
	}
	
	public function marking(Request $request)
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
	
	public function forgot(Request $request)
	{
		if ($request->ajax()) {
			$this->validate($request,[
					'email' => 'required|email|max:255|exists:users',
			]);
			$ret = Password::sendResetLink(['email'=>$request->email]);
			return response()->json($ret);
		}
	}
	
	public function register(Request $request)
	{
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
	
	public function update(Request $request)
	{
		$user=$request->user();
		$rules['name']='required|max:255';
		$user->name=$request->name;
		if ($user->email != $request->email) {
			$rules['email']='required|email|max:255|unique:users';
			$user->email=$request->email;
		}
		if ($request->password) {
			$rules['password']='required|min:6';
			$rules['password_confirmation']='required|same:password';
			$user->password=Hash::make($request->password);
		}
		Log::debug('update',['rules'=>$rules]);
		$this->validate($request,$rules);
		$user->save(); // save changes if passes validation
		return response()->json(['name'=>$user->name,'email'=>$user->email]);
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
	
	public function saveHelp(Request $request)
	{
		Log::debug('saveHelp',['title'=>$request->title,'text'=>$request->text,'id'=>$request->id]);
		if ($request->user()->isAdmin()) {
			/*
			if (isset($request->delete))
			{
				if ($request->delete>0) Help::find($request->delete)->delete();
				return response()->json(['deleted'=>$request->delete]);
			}
			*/
			if (!$h=Help::find($request->id)) $h=new Help;
			$h->previous_id=$h->next_id=0;
			$h->title=$request->title;
			$h->user_id=$request->user()->id;
			$h->text=$request->text;
			$h->save();
			return response()->json($h);
		}
		else return response()->json(['error'=>'Unauthorised.'],401);
	}
	
	
	public function tutor(Request $request)
	{
		$added=0;$removed=0;
		Log::debug('tutor',['tutor'=>$request->only('tutor'),'remove'=>$request->only('remove')]);
		if (!$request->remove && $request->tutor[1] == '' && $request->tutor[2] == '' && $request->tutor[3] == '') {
			return response()->json(['tutor.1'=>'Specify at least one tutor to add'],422);
		}
		if ($request->has('tutor'))
		{
			$this->validate($request, [
					'tutor.1' => 'email',
					'tutor.2' => 'email',
					'tutor.3' => 'email',
			]);
			$errors=[];
			//TODO - add as custom rule to validator
			for ($i=1; $i<=3; $i++) if (($tutor=$request->tutor[$i]) != '')
			{
				if ($tutor == $request->user()->email)
				{
					$errors["tutor.$i"]="You can't add yourself as a tutor";
				}
				else if ($t=Tutor::where(['email'=>$tutor,'user_id'=>$request->user()->id])->first())
				{
					Log::debug('tutor',['t'=>$t,'user'=>$request->user()]);
					$errors["tutor.$i"]='Tutor already added';
				}
			}
			if (count($errors) > 0) return response()->json($errors,422);
			else for ($i=1; $i<=3; $i++) if (($tutor=$request->tutor[$i]) != '')
			{
				if ($request->user()->tutors()->create(['email'=>$tutor]))
					$added++;
					else return response()->json(['error'=>'Internal Database Error','tutors'=>Tutor::where('user_id', $request->user()->id)->get()],500);
			}
			
		}
		if ($request->has('remove')) 
			if ($request->user()->tutors()->whereIn('email',$request->remove)->delete()) $removed=count($request->remove);

		return response()->json(['added'=>$added,'removed'=>$removed,'tutors'=>$request->user()->tutor_details()]);
	}	
	
	public function contact(Request $request)
	{
		//Log::debug('contact',['request'=>$request]);
		$message=new Message;
		if ($request->to) {
			if ($request->to['id'] && $to=User::find($request->to['id'])) $message->to_uid=$to->id;
			else if ($to=User::where('email',$request->to['email'])->first()) $message->to_uid=$to->id;
			else {
				$to=$request->to;
				$message->to_uid=0;
			}
		}
		else {
			$to=User::find(1);
			$message->to_uid=1;
		}
		if ($request->from) {
			if ($request->from['id'] && $user=User::find($request->from['id'])) $message->from_uid=$user->id;
			else if ($user=User::where('email',$request->from['email'])->first()) $message->from_uid=$user->id;
			else {
				Log::error('contact - unkown sender',['from'=>$request->from]);
				$user=$request->from;
				$message->from_uid=0;
			}
		}
		else if ($user=$this->auth($request,true)) // removed expire
		{
			//Log::debug('contact',['user'=>$user->id]);
			$this->validate($request, [
					'message' => 'required'
			]);
			$message->from_uid=$user->id;
		}
		else
		{
			//Log::debug('contact',['anon'=>$request->email]);
			$this->validate($request, [
					'name' => 'required',
					'email' => 'email|required',
					'message' => 'required'
			]);
			if ($user=User::where('email',$request->email)->first()) $message->from_uid=$user->id; // if known email
			else $message->from_uid=0;
		}
		$from=$user?['name'=>$user->name,'email'=>$user->email,'id'=>$user->id]:['name'=>$request->name,'email'=>$request->email];
		$message->json=json_encode(['to'=>$to,'from'=>$from,'message'=>$request->message,'qkey'=>$request->qkey,'maths'=>$request->maths,'ts'=>time()]);
		$message->save();
		$token=Crypt::encrypt(json_encode(['id'=>$message->id]));
		if (isset($to['id']) && $to['id']==1) Mail::to('epdarnell@gmail.com')->send(new Contact($message->json,$token,$request->question));
		else Mail::to($to['email'])->send(new Contact($message->json,$token,$request->question));
		//Mail::to('epdarnell@gmail.com')->send(new Contact('Freemaths',$user?"$user->name <$user->email>":"$request->name <$request->email>",$request->message,$token,$request->header('FM-Env')));
		return response()->json(['sent'=>$token]);
	}
	
	public function mail(Request $request)
	{
		if ($id=json_decode(Crypt::decrypt($request->token)))
		{
			$message=Message::find($id->id);
			return response($message->json,200)->header('Content-Type','application/json');
		}
		else return response()->json(['error'=>'Invalid mail token'],422);
	}
	
	public function data(Request $request)
	{
		if ($request->user()->isAdmin()) {
			$ts=DB::raw('SELECT max(t.updated_at) as t_ts,max(q.updated_at) as q_ts,max(h.updated_at) as h_ts FROM tests as t,questions as q,help as h');
			Log::info('data',['env'=>$request->header('FM-Env')]); //,'ETag'=>$request->header('If-None-Match')]);
			$tests=Test::orderBy('id')->get();
			$questions=Question::where('next_id',0)->orderBy('id')->get();
			$qmap=DB::table('question_test')->get(); // needs more work if to remain - better to enclose in tests json
			$help = Help::where('next_id',0)->orderBy('id')->get();
			Storage::put('tables.json',json_encode(['tests'=>$tests,'questions'=>$questions,'qmap'=>$qmap,'help'=>$help]));
			return response()->json(['tests'=>$tests,'questions'=>$questions,'qmap'=>$qmap,'help'=>$help]);
		}
		else return response()->json(['error'=>'Unauthorised.'],401);
	}
	
	public function data2(Request $request)
	{
		if ($request->user()->isAdmin())
		{
			$data=Storage::get('data.json');
			return response()->json(['file'=>$data]);
		}
	}
	
	public function update_data(Request $request)
	{
		if ($request->user()->isAdmin() && $lz=$request->lz)
		{
			Storage::put('data.json',json_encode($request->data));
			Storage::put('tests.lz',$lz['tests']);
			Storage::put('books.lz',$lz['books']);
			Storage::put('past.lz',$lz['past']);
			Storage::put('help.lz',$lz['help']);
			return response()->json(['saved'=>true]);
		}
		else return response()->json(['error'=>'Unauthorised'],422);
	}
	
	public function past(Request $request)
	{
		$t = null;
		if ($test = @$request->test)
		{
			Log::debug('ajax_past',['test'=>$test['id']]); //,'name'=>$test['name']]);
			if (@$test['id'] != '') $t = Test::find($test['id']);
			if (@$t && $t->user->id == $request->user()->id) // don't use user()->id on relationship
			{
				// nothing special
			}
			else
			{
				$t = new Test;
				$t->user()->associate($request->user());
			}
			if ($request->has('copy')) $test['copy']=$request->copy;
			else unset($test['copy']);
			$t->keywords = $test['name']." ".$test['board']." ".$test['month']." ".$test['year'];
			$t->json = json_encode($test);
			$t->title = $test['name']."_".$test['board']."_".$test['month']."_".$test['year'];
			$t->type="past";
			$t->save();
			Storage::put('tests/' . "{$t->title}.{$t->id}.json",json_encode($t));
		}
		return response()->json(['id'=>$t['id']]);
	}
	
	public function book(Request $request, $type='book')
	{
		$t = null;
		if ($book = @$request->book)
		{
			//Log::debug('ajax_book:'.print_r($test,true));
			if (@$book['id'] != '') $t = Test::find($book['id']);
			if (@$t && $t->user->id == $request->user()->id) // don't use user()->id on relationship
			{
				// nothing special
			}
			else
			{
				$t = new Test;
				$t->user()->associate($request->user());
			}
			$t->keywords = $book['name'];
			$t->json = json_encode($book);
			$t->title = $book['name']."_".$book['board'];
			$t->type=$type;
			$t->save();
			Storage::put('books/' . "{$t->title}.{$t->id}.json",json_encode($t));
		}
		return response()->json(['id'=>$t['id']]);
		}
}
