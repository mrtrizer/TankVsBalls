<?php
function show_error($errorCode, $errorMessage = "")
{
	die('{"error_code":'.$errorCode.', "error_msg":"'.htmlspecialchars($errorMessage).'"}');
}

$args = NULL;

if (array_key_exists('func',$_POST))
	$args = $_POST;
else
	if (array_key_exists('func',$_GET))
		$args = $_GET;

if ($args == NULL)
	show_error(100);

require_once('default_config.php');

$link = mysql_connect($mysql_host, $mysql_login, $mysql_password)  or show_error(2);
mysql_set_charset('utf8',$link);
$selected = mysql_select_db($mysql_db, $link);

$func = preg_replace('/[^A-Za-z0-9\-]/', '', $args['func']);

if ($func == 'gamefinish')
{
	if (!array_key_exists('key',$args) || !array_key_exists('counter_red',$args) || !array_key_exists('counter_blue',$args))
		show_error(1);

	$key = preg_replace('/[^A-Za-z0-9\-]/', '', $args['key']);
	$counterRed = preg_replace('/[^0-9\-]/', '', $args['counter_red']);
	$counterBlue = preg_replace('/[^0-9\-]/', '', $args['counter_blue']);

	$request = sprintf('
		INSERT INTO `game` (`key`,`counter_red`,`counter_blue`) 
		VALUES (0x%s,%d,%d)',
		$key,$counterRed,$counterBlue);
		
	mysql_query($request) or  show_error(5,mysql_error($link));

	echo '{"error_code":0}';
	exit;
}

if ($func == 'setname')
{
	if (!array_key_exists('key',$args) || !array_key_exists('name',$args))
		show_error(1);

	$key = preg_replace('/[^A-Za-z0-9\-]/', '', $args['key']);
	$name = urlencode(trim($args['name'],'"'));

	$request = sprintf('
		REPLACE INTO `player` (`key`,`name`) 
		VALUES (0x%s,"%s")',
		$key,$name);
		
	mysql_query($request) or  show_error(5,mysql_error($link));

	echo '{"error_code":0}';
	exit;
}

show_error(899,"Wrong function: ".$func);
