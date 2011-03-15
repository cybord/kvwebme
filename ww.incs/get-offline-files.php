<?php
/**
	* retrieve jQuery files for offline use
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@verens.com>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

function Core_getOfflineJQueryScripts($jquery_versions) {
	if (!file_exists(USERBASE.'/f/.files/jquery-'.$jquery_versions[0].'.js')) {
		$f=file_get_contents('https://ajax.googleapis.com/ajax/libs/jquery/'
			.$jquery_versions[0].'/jquery.min.js');
		if ($f) {
			file_put_contents(
				USERBASE.'/f/.files/jquery-'.$jquery_versions[0].'.js',
				$f
			);
		}
		else {
			echo 'could not download jQuery files. please go online, '
				.'reload this page, then go offline.';
		}
	}
	if (!file_exists(USERBASE.'/f/.files/jqueryui-'.$jquery_versions[1].'.js')) {
		$f=file_get_contents('https://ajax.googleapis.com/ajax/libs/jqueryui/'
			.$jquery_versions[1].'/jquery-ui.min.js');
		if ($f) {
			file_put_contents(
				USERBASE.'/f/.files/jqueryui-'.$jquery_versions[1].'.js',
				$f
			);
		}
		else {
			echo 'could not download jQuery UI files. please go online, '
				.'reload this page, then go offline.';
		}
	}
	if (!file_exists(USERBASE.'/f/.files/jqueryui-'.$jquery_versions[1].'.css')) {
		$f=file_get_contents('http://ajax.googleapis.com/ajax/libs/jqueryui/'
			.$jquery_versions[1].'/themes/base/jquery-ui.css');
		if ($f) {
			file_put_contents(
				USERBASE.'/f/.files/jqueryui-'.$jquery_versions[1].'.css',
				$f
			);
		}
		else {
			echo 'could not download jQuery UI CSS. please go online, '
				.'reload this page, then go offline.';
		}
	}
	$jurls=array(
		'/f/.files/jquery-'.$jquery_versions[0].'.js',
		'/f/.files/jqueryui-'.$jquery_versions[1].'.js',
		'/f/.files/jqueryui-'.$jquery_versions[1].'.css'
	);
	return $jurls;
}
