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
	if (!array_key_exists('user_id',$args) || 
			!array_key_exists('counter_red',$args) || 
				!array_key_exists('counter_blue',$args))
		show_error(1);

	$counterRed = preg_replace('/[^0-9\-]/', '', $args['counter_red']);
	$counterBlue = preg_replace('/[^0-9\-]/', '', $args['counter_blue']);
	$userId = preg_replace('/[^0-9]/', '', $args['user_id']);
	$authKey = preg_replace('/[^0-9a-fA-F]/', '', $args['auth_key']);

	$request = sprintf('
		INSERT INTO `game` (`counter_red`,`counter_blue`,`user_id`) 
		VALUES (%d,%d,%d)',
		$counterRed,$counterBlue,$userId);
		
	mysql_query($request) or  show_error(5,mysql_error($link));

	echo '{"error_code":0}';
	exit;
}

if ($func == 'setname')
{
	if (!array_key_exists('user_id',$args) || 
			!array_key_exists('auth_key',$args) || 
				!array_key_exists('name',$args))
		show_error(1);

	$name = urlencode(trim($args['name'],'"'));
	$userId = preg_replace('/[^0-9]/', '', $args['user_id']);
	$authKey = preg_replace('/[^0-9a-fA-F]/', '', $args['auth_key']);

	$request = sprintf('
		REPLACE INTO `player` (`name`,`user_id`,`auth_key`) 
		VALUES ("%s",%d,0x%s)',
		$name,$userId,$authKey);
		
	mysql_query($request) or  show_error(5,mysql_error($link));

	echo '{"error_code":0}';
	exit;
}

if ($func == 'getrecords')
{
	$request = sprintf('
		SELECT  `user_id`,`time`,MAX(`counter_red`) as `counter_red`,`counter_blue` 
		FROM `game` GROUP BY `user_id` ORDER BY `counter_red` DESC LIMIT 10 ');
	$result = mysql_query($request, $link) or show_error(5,mysql_error($link));

	$records = '[';
	while ($row = mysql_fetch_array($result))
		$records .= sprintf('{"user_id":%d,"time":"%s","counter_red":%d,"counter_blue":%d},',$row['user_id'],$row['time'],$row['counter_red'],$row['counter_blue']);
	$records .= ']';
	
	echo '{"error_code":0, "data":'.$records.'}';
	exit;
}


show_error(899,"Wrong function: ".$func);
