<?php

class Base {
	public function __construct()
	{
	}

	public static function checkCommand($command='')
	{
		if(empty($command)){
			return false;
		}
		if(is_array($command)){
			$command = join(' ',$command);
		}

		if(preg_match('/,/',$command)){
			$command = str_replace(',',' ',$command);
		}

		$cmd="which ".escapeshellcmd($command)." >/dev/null 2>&1";
		exec($cmd,$arr,$res);
		if($res == 0){
			return true;
		}
		else{
			return false;
		}
	}

	public static function mkdirP($path,$chmod = 0755)
	{
		if(!is_dir($path) ){
			if ( !mkdir( $path,$chmod, true ) ) {
				return false;
			}
		}
		else{
		}

		return true;
	}

	public static function getRandomString(
		$nLengthRequired = 8,
		$sCharList = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
	){
		mt_srand();
		$sRes = '';
		for($i = 0; $i < $nLengthRequired; $i++){
			$sRes .= $sCharList[mt_rand(0, strlen($sCharList) - 1)];
		}

		return $sRes;
	}


	public static function _escapeShellCmd($string){
		$string = str_replace('\\','\\\\',$string);
		$string = str_replace('#','\#',$string);
		$string = str_replace('&','\&',$string);
		$string = str_replace('`','\`',$string);
		$string = str_replace('|','\|',$string);
		$string = str_replace('*','\*',$string);
		$string = str_replace('?','\?',$string);
		$string = str_replace('~','\~',$string);
		$string = str_replace('<','\<',$string);
		$string = str_replace('^','\^',$string);
		$string = str_replace('(','\(',$string);
		$string = str_replace(')','\)',$string);
		$string = str_replace('[','\[',$string);
		$string = str_replace(']','\]',$string);
		$string = str_replace('{','\{',$string);
		$string = str_replace('}','\}',$string);
		$string = str_replace('$','\$',$string);
		$string = str_replace("'","\'",$string);
		$string = str_replace('"','\"',$string);
		$string = str_replace(';','\;',$string);
		$string = str_replace('/','／',$string);
	
		$string = str_replace(' ','\ ',$string);
		$string = str_replace('-','\-',$string);
	
		return $string;
	}
}
?>
