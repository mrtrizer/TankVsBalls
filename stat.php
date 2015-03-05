<?php
require_once('default_config.php');

$link = mysql_connect($mysql_host, $mysql_login, $mysql_password)  or show_error(2);
mysql_set_charset('utf8',$link);
$selected = mysql_select_db($mysql_db, $link);


	
$request = sprintf('
	SELECT  `game`.`key`,`game`.`time`,`game`.`counter_red`,`game`.`counter_blue` 
	FROM `game`');
echo $request;
	
$result = mysql_query($request, $link) or die('Ubable to get an list.');

echo "<table>";
while ($row = mysql_fetch_array($result))
	echo sprintf("<tr><td>%s</td><td></td><td>%s</td><td>%d</td><td>%d</td></tr>",bin2hex($row['key']),$row['time'],$row['counter_red'],$row['counter_blue']);
echo "</table><br />";

$request = sprintf('
	SELECT  `player`.`key`,`player`.`name`
	FROM `player`');
echo $request;
	
$result = mysql_query($request, $link) or die('Ubable to get an list.');

echo "<table>";
while ($row = mysql_fetch_array($result))
	echo sprintf("<tr><td>%s</td><td>%s</td></tr>",bin2hex($row['key']),urldecode($row['name']));
echo "</table>";
