<?php

require_once dirname(__FILE__).'/Base.php';

class DataBase {
	private $con;

	private $DBSERVER;
	private $DBUSER;
	private $DBPASSWORD;
	private $DBNAME;

	private $data_dir;
	private $table_list = array();

	public function __construct($config = NULL){
		if($config != null){
			$this->setConfig($config);
		}
	}

	public function setDataDir($dir){
		$dir = preg_match('/\/$/',$dir) ? preg_replace('/\/$/','',$dir) : $dir;
		$this->data_dir = $dir;
	}

	public function setConfig($config){
		$this->DBSERVER   = isset($config['DBSERVER'])   ? $config['DBSERVER']   : $this->DBSERVER;
		$this->DBUSER     = isset($config['DBUSER'])     ? $config['DBUSER']     : $this->DBUSER;
		$this->DBPASSWORD = isset($config['DBPASSWORD']) ? $config['DBPASSWORD'] : $this->DBPASSWORD;
		$this->DBNAME     = isset($config['DBNAME'])     ? $config['DBNAME']     : $this->DBNAME;

		$this->con = $this->_connect();
	}

	public function getTableList()
	{
		return $this->table_list;
	}

	/**
	 * テーブルを空にする
	 *
	 * @access public
	 * @param string 空にするテーブル名
	 * @return boolean  成功 true/不成功 false
	 */
	public function truncecateTable($table_name = null)
	{
		if(empty($table_name)){
			throw new Exception("引数がありません");
			return false;
		}

		if($table_name == 'ALL'){
			foreach ($this->table_list as $key => $table_name){
				$query = "TRUNCATE TABLE `{$table_name}`";
				$ret = $this->_mysql_query($query);
			}
		}
		elseif(array_key_exists($table_name,$this->table_list)){
			$query = "TRUNCATE TABLE `{$table_name}`";
			$ret = $this->_mysql_query($query);
		}
		else{
			throw new Exception("{$table_name}'が存在しません");
			return false;
		}

		return true;
	}

	/**
	 * DBにファイルからデータをインポートする
	 *
	 * @access public
	 * @param string $sql_file インポートするファイルパス
	 * @param array  $options  インポートする際に指定するmysqlオプション
	 * @return boolean  成功 true/不成功 false
	 */
	public function Import(
		$sql_file,
		$options = array()
	){
		if(!$sql_file || !file_exists($sql_file)){
			throw new Exception("mysql import error '{$sql_file}'が存在しません");
			return false;
		}

		$opt_str = $this->_getImportOptionString($options);

		$cmd = "mysql".
			" -h {$this->DBSERVER}".
			" -u {$this->DBUSER}".
			" -p{$this->DBPASSWORD}".
			" {$opt_str} {$this->DBNAME} < {$sql_file} 2>&1";

		exec($cmd,$arr,$res);
		if($res == 0){
			return true;
		}
		else{
			throw new Exception("mysql import error '{$cmd}' : ".array_shift($arr));
			return false;
		}
	}
	/**
	 * Importのオプションを取得する
	 *
	 * @access private
	 */
	private function _getImportOptionString($options){
		$default = array(
			'--default-character-set' => 'binary',
		);

		if(!is_array($options)){
			$options = array($options);
		}

		$opt_list = array();
		foreach($default as $key => $val){
			if(array_key_exists($key,$options)){
				$opt_list[$key] = "{$key}={$options[$key]}";
			}
			else{
				$opt_list[$key] = "{$key}={$val}";
			}
		}

		return join(' ',$opt_list);
	}


	/**
	 * DBのバックアップを取る
	 *
	 * @access public
	 * @param mixed  $mysqldump_options オプション
	 * @param string $filename 保存するファイル名(オプション)
	 * @return mixed  成功 string sqlファイルパス /不成功 false
	 */
	public function backup(
		$mysqldump_options,
		$filename = ''
	){
		if(!Base::checkCommand('mysqldump')){
			throw new Exception('mysqldumpコマンドが存在しません');
			return false;
		}

		/* 保存ディレクトリ */
		$dir = ($this->data_dir) ? $this->data_dir : '.';
		if(! Base::mkdirP($dir,0777)){
			throw new Exception("ディレクトリが作成できません:{$dir}");
			return false;
		}

		/* オプション */
		$opt_str = $this->_getBackupOptionString($mysqldump_options);

		/* 保存ファイル名 */
		$date = date('Y-m-d');
		if($filename){
			$filename = str_replace('{DBNAME}',$this->DBNAME,$filename);
			$filename = str_replace('{OPTION}',$opt_str,$filename);
			$filename = str_replace('{DATE}',$date,$filename);
			$filename = $filename.'.sql';
		}
		else{
			// default
			$filename = "[{$this->DBNAME}][{$date}] {$opt_str}.sql";
		}

		$cmd = "mysqldump".
			" -u {$this->DBUSER}".
			" -h {$this->DBSERVER}".
			" -p{$this->DBPASSWORD}".
			" {$this->DBNAME} {$opt_str} > {$dir}/".Base::_escapeshellcmd($filename) .' 2>&1';

		exec($cmd,$arr,$res);

		if($res === 0
			&& is_file($dir.'/'.$filename) // ファイルの存在確認
			&& filesize($dir.'/'.$filename)// ファイルの容量確認
		){
			return "{$dir}/{$filename}";
		}
		else{
			$mes = array_shift($arr);
			throw new Exception("mysqldump error:{$cmd} {$mes}");
			return false;
		}
	}

	/**
	 * mysqldumpのオプションを取得する
	 *
	 * @access private
	 */
	private function _getBackupOptionString($options){
		if(!is_array($options)){
			$options = array($options);
		}

		$default = array(
			'--default-character-set' => 'binary',
		);

		$opt_list = array();
		foreach($this->table_list as $table_name){
			if(in_array($table_name,$options)){
				$opt_list[$table_name] = $table_name;
			}
		}
		foreach($default as $key => $val){
			if(array_key_exists($key,$options)){
				$opt_list[$key] = "{$key}={$options[$key]}";
			}
			else{
				$opt_list[$key] = "{$key}={$val}";
			}
		}

		return join(' ',$opt_list);
	}


	/**
	 * テーブル一覧を取得する
	 *
	 * @access private
	 * @return array
	 */
	private function _connect()
	{
		$this->con = mysql_connect($this->DBSERVER, $this->DBUSER, $this->DBPASSWORD);
		if(!$this->con){
			throw new Exception('SQL connection error');
			return false;
		}

		if(!mysql_select_db($this->DBNAME,$this->con)){
			throw new Exception('SQL connection error');
			return false;
		}

		$sql = "SET NAMES utf8";
		$this->_mysql_query($sql);

		// テーブル一覧取得
		$this->table_list = $this->_getTableList();

		return $this->con;
	}

	/**
	 * テーブル一覧を取得する
	 *
	 * @access private
	 * @return array
	 */
	private function _getTableList()
	{
		$query = "
			SHOW TABLES
		";
		$ret = $this->_mysql_query($query);

		$result = array();
		while($row = mysql_fetch_array($ret)){
			$result[end($row)] = end($row);
		}

		return $result;
	}

	/**
	 * クエリを発行する
	 *
	 * @access public
	 * @param string $query クエリ
	 * @param array  $debug_backtrace debug_backtrace()の戻り値
	 * @return bool
	 */
	public function _mysql_query($query,$debug_backtrace = false)
	{
		$ret = mysql_query($query);

		if(!$ret){
			$message = "<font color=red>QUERY error</font><br />";
			$message .= "<pre>".$query."</pre><br /><br />";
			$message .= mysql_errno().':'.mysql_error()."<br />";
			if($debug_backtrace){
				$message .= '<pre>';
				$message .= print_r($debug_backtrace,true);
				$message = '</pre>';
			}
			throw new Exception($message);
		}

		return $ret;
	}
}
?>
