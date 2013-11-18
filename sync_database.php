<?php

ini_set('display_errors',1);
error_reporting(E_ALL);

require_once dirname(__FILE__).'/base/DataBase.php';


$src_var  = array();
$dest_var = array();

$src_var['DBSERVER']   = '';
$src_var['DBUSER']     = '';
$src_var['DBPASSWORD'] = '';
$src_var['DBNAME']     = '';

$dest_var['DBSERVER']   = '';
$dest_var['DBUSER']     = '';
$dest_var['DBPASSWORD'] = '';
$dest_var['DBNAME']     = '';


try {
	/**
	 * srcのDBをバックアップ
	 */
	$D = new DataBase($dest_var);
	$D->setConfig($dest_var);
	$D->setDataDir('data/src');
	foreach($D->getTableList() as $table_name){
		$src_file_list[$table_name] = $D->Backup($table_name,$table_name);
	}

	/**
	 * destのDBをバックアップ
	 */
	$D = new DataBase($src_var);
	$D->setDataDir('data/dest');
	foreach($D->getTableList() as $table_name){
		$D->Backup($table_name,$table_name);
	}

	/**
	 * member.sql以外をdestにインポート
	 */
	foreach($src_file_list as $file_name){
		if($file_name != 'data/main/member.sql'){
			$D->import($file_name);
		}
	}
} catch (Exception $e) {
    echo 'Caught exception: <br />',  $e->getMessage(), "<br />";
	exit;
}




?>
