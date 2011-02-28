<?php

$errors=array();

$cid=(int)$_REQUEST['cid'];
$mid=(int)$_REQUEST['mid'];
$email=$_REQUEST['email'];
$name=isset($_REQUEST['name'])
	?$_REQUEST['name']:
	$email;
$phone=isset($_REQUEST['phone'])
	?$_REQUEST['phone']
	:'';
if($phone=='Your Phone Here')$phone='';

if(!$cid)$errors[]='no client id provided. please contact the webmaster.';
if(!$mid)$errors[]='no mailinglist id provided. please contact the webmaster.';
if(!$email)$errors[]='no email provided.';
if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='invalid email address.';
if(!$name || $name=='Your Name Here')$errors[]='no name provided.';

if(count($errors)){
	echo '<div class="errors">'.join('<br />',$errors).'</div>';
	exit;
}

$url='http://onetouchcontact.com/subscribe.php?mid='.$mid.'&cid='.$cid.'&email='.urlencode($email).'&name='.urlencode($name).'&mobile='.urlencode($phone).'&preferredformat=1';
echo file_get_contents($url);
