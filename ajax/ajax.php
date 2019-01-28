<?php
// https://logmatic.io/blog/youre-doing-php-logging-wrong/
// https://www.ibm.com/developerworks/library/os-php-shared-memory/index.html
// http://php.net/manual/en/book.opcache.php
// see also xcache and memcache?
class Ajax {
    function request() {
        $this->init();
        $json=json_decode(file_get_contents("php://input"),true);
        $this->debug($json);
        if ($json && isset($json['req']))
        {
            $resp=['error'=>'unspecified'];
            switch ($json['req']) {
                case 'help':
                case 'tests':
                case 'books':
                case 'past':
                case 'questions':
                    $resp=$this->file($json);
                    break;
                case 'versions':
                    $resp=['versions'=>$this->versions()];
                    break;
                case 'save':
                    $resp=$this->save($json);
                    break;
                default:
                    http_response_code(404);
                    $resp=['error'=>'404 (Not Found)'];
            }
            $this->response($resp);
            $this->debug($resp);
        }
        else $this->debug($json);
    }
    private function init() {
        $this->start=microtime();
        error_reporting(E_ALL); // Reports all errors
        ini_set('display_errors','Off'); // Do not display errors for the end-users (security issue)
        ini_set('error_log',__DIR__.'/error.log'); // Set a logging file
    }
    private function response($resp) {
        header('Content-type: application/json');
        echo json_encode($resp);
    }
    private function debug($message) {
        $json=json_encode($message);
        if (strlen($json) > 200) {
            $short=[];
            foreach($message as $key=>$val) {
                $len=strlen(json_encode($val));
                if ($len > 100) $short[$key]="...($len)";
                else $short[$key]=$val;
            }
            $json=json_encode($short);
        }
        error_log("AJAX,".microtime().','.$json.','.$this->start);
    }
    private function file($json) {
        $gz=__DIR__.'/../storage/app/public/'.$json['req'].'.gz';
        $ts=filemtime($gz);
        if ($ts>$json['ts']) $results=file_get_contents($gz);
        else $results='';
        return ['file'=>$results,'ts'=>$ts];
    }
    private function save($json) {
        $path=__DIR__.'/../storage/app/public/';
        $ret=[];
        foreach($json['data'] as $name => $gz) {
            file_put_contents($path.$name.'.gz',$gz);
            $ret[$name]['ts']=filemtime($path.$name.'.gz');
        }
        return $ret;
    }
    private function versions() {
		$path=__DIR__.'/../storage/app/public/';
		$versions=json_decode(file_get_contents($path.'version.json'),true);
		foreach (['tests','questions','help','books','past'] as $name) {
			$versions[$name]['ts']=filemtime($path.$name.'.gz');
			$versions[$name]['size']=filesize($path.$name.'.gz');
		}
		return $versions;
	}
}
$ajax=new Ajax;
return $ajax;
?>