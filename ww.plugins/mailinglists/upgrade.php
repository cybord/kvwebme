<?php
/**
	* upgrade script for the mailinglists plugin
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

if ($version==0) {
	dbQuery(
		'create table mailinglists_lists('
		.'id int auto_increment not null primary key,'
		.'name text,'
		.'meta text'
		.')default charset=utf8'
	);
	dbQuery(
		'create table mailinglists_people('
		.'id int auto_increment not null primary key,'
		.'name text,'
		.'email text,'
		.'meta text'
		.')default charset=utf8'
	);
	$version=1;
}
if ($version==1) {
	dbQuery(
		'create table mailinglists_lists_people('
		.'people_id int default 0,'
		.'lists_id int default 0,'
		.'meta text'
		.')default charset=utf8'
	);
	$version=2;
}