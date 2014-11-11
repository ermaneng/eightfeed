<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_connection = "localhost";
$database_connection = "eightfeed";
$username_connection = "ef";
$password_connection = "Bedbaht1-";
$connection = mysql_pconnect($hostname_connection, $username_connection, $password_connection) or trigger_error(mysql_error(),E_USER_ERROR); 

//select db
mysql_select_db($database_connection, $connection);
/*utf-8 language setting*/
mysql_query("SET NAMES 'utf8'");
mysql_query("SET collation_connection= 'utf8_unicode_ci'");
/*post convert*/
foreach ($_POST as $name => $value) {
		//$_POST[$name]=str_replace("&","&#38;",$_POST[$name]);
		$_POST[$name]=str_replace("<?","&#60;?",$_POST[$name]);
		$_POST[$name]=str_replace("\'","&#39;",$_POST[$name]);
		$_POST[$name]=str_replace("'","&#39;",$_POST[$name]);
		//$_POST[$name]=str_replace('\"',"&#34;",$_POST[$name]);
		//$_POST[$name]=str_replace("\'","'",$_POST[$name]);
		$_POST[$name]=str_replace('\"','"',$_POST[$name]);
	}
?>