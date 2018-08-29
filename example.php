<?php

require_once('database.class.php');

define('DBHOST', 'localhost');
define('DBUSER', 'ruroot');
define('DBNAME', 'testdb');
define('DBPASSWD', '');

$db = new MyDatabase(DBHOST, DBUSER, DBPASSWD, DBNAME);

$db->connect();

?>