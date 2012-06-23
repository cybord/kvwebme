<?php
/**
	* installer template header
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

error_reporting(0);
session_start();
if (file_exists('../.private/config.php')
	&&!isset($_SESSION['config_written'])
) {
	echo '<p>'
		.__(
			'<strong>Config file already exists</strong>. Please remove the '
			.'/install directory.'
		)
		.'</p>';
	exit;
}

$home_dir=DistConfig::get('installer-userbase');
$cms_name=DistConfig::get('cms-name');
echo '
<!doctype html>
<html>
<head>
	<title>'.__('%1 Installer', array($cms_name), 'core').'</title>

	<link rel="stylesheet" type="text/css" href="/j/cluetip/jquery.cluetip.css" />
	<link rel="stylesheet" href="/ww.admin/theme/admin.css" type="text/css" />
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui'
		.'/1.8.14/themes/base/jquery-ui.css" />

	<!-- Installer specific javascript -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min'
		.'.js"></script>
 	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-'
		.'ui.min.js"></script>
	<script src="/j/validate.jquery.min.js"></script>

	<script defer="defer" type="text/javascript">
        function error_handler( msg ){
                $( "#errors" ).html( msg );
        }
	$( function( ){
		// set the current page in install-menu
	        var link= window.location.href.split( "?" );
		link = link[ 0 ].split( "/" );
	        var path = link[ link.length - 1 ];
		$("#install-menu li a[href=\'"+path+"\']").addClass("current");
		$( "#howto" ).click( function( ){
			$( "#dialog" ).dialog( );
		} );
	} );
	</script>

	<!-- Installer specific CSS -->
	<style type="text/css">
		table{
			border-spacing: 6px;
		}
		table th{
			text-align: left;
		}
		#install-menu{
		        margin: 0;
		        padding: 0 0 20px;    
		}
		#install-menu li{ 
		        margin: 0;
		        padding: 0;
		}
		#install-menu li a{
		        border: 0 none;
		        display: block;
		        text-decoration: none;
		        padding: 3px 0 3px 5px;
		}
		#install-menu li a.current{
		        color: #d36042;
		} 
		#content{
			width: 70%;
			margin-left: 190px;
		}
		#errors{
			color:#D36042
		}
		.error{
			border:1px solid #600;
			background:#D36042;
		}
	</style>

</head>
<body> 
	<div id="header"> 
	</div>

	<div id="wrapper">
		<div id="main">

		<h1>'.__('%1 Installer', array($cms_name), 'core').'</h1>

		<div class="sub-nav">
			<ul id="install-menu">
				<li><a href="index.php">'.__('Installation Requirements').'</a></li>
				<li><a href="step1.php">'.__('Add Database').'</a></li>
				<li><a href="step3.php">'.__('Create User').'</a></li>
				<li><a href="step4.php">'.__('User Files').'</a></li>
				<li><a href="step6.php">'.__('Select Theme').'</a></li>
				<li><a href="step7.php">'.__('Finish').'</a></li>
			</ul>
		</div>

		<div id="pages-wrapper">

			<div id="content">
';
