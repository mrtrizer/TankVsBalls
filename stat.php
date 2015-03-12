<?php
require_once('default_config.php');

$link = mysql_connect($mysql_host, $mysql_login, $mysql_password)  or show_error(2);
mysql_set_charset('utf8',$link);
$selected = mysql_select_db($mysql_db, $link);


	
$request = sprintf('
			SELECT  `user_id`,`time`,`counter_red`,`counter_blue` 
			FROM `game` ORDER BY `counter_red`');
echo $request;
	
$result = mysql_query($request, $link) or die('Ubable to get an list.');

echo "<table>";
while ($row = mysql_fetch_array($result))
	echo sprintf("<tr><td>%s</td><td></td><td>%s</td><td>%d</td><td>%d</td></tr>",$row['user_id'],$row['time'],$row['counter_red'],$row['counter_blue']);
echo "</table><br />";

$request = sprintf('
	SELECT  `player`.`user_id`,`player`.`name`
	FROM `player`');
echo $request;
	
$result = mysql_query($request, $link) or die('Ubable to get an list.');

echo "<table>";
while ($row = mysql_fetch_array($result))
	echo sprintf("<tr><td>%d</td><td>%s</td></tr>",$row['user_id'],urldecode($row['name']));
echo "</table>";
