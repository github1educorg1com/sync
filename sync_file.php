<?php

ini_set('display_errors',1);
error_reporting(E_ALL);


/**
 * 実行時にはDEBUGをfalseにする
 */
define('DEBUG',true);


$src_dir  = "/home/sample/src";
$dest_dir = "/home/sample/dest";

$target_list[] = '/files';
$target_list[] = '/img';

foreach($target_list as $dir){
	// sourceで指定するディレクトリ名の最後にスラッシュを付けた場合、ディレクトリ内をコピーする。
	$sorce = $src_dir.$dir.'/';
	$dest  = $dest_dir.$dir;

	$dry_run = '';
	if(!defined('DEBUG') || DEBUG){
		$dry_run = '--dry-run';
	}

	$cmd = "rsync -avz --stats --delete {$dry_run} {$sorce} {$dest}";

	echo $dir."<br />";

	// 表示出力/エラー出力共にに表示
	echo "<pre>";
	$script_name = system($cmd,$exit_code);
	echo "</pre>";

	echo "<br /><hr /><br />";
}

?>
