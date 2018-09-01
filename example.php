<?php

require_once('database.class.php');

define('DBHOST', 'localhost');
define('DBUSER', 'root');
define('DBNAME', 'testdb');
define('DBPASSWD', '');

$db = new MyDatabase(DBHOST, DBUSER, DBPASSWD, DBNAME);

$db->connect();

$q = 'SELECT  `users`.`user_id`, `users`.`user_name` FROM `users` ORDER BY `users`.`user_id` DESC LIMIT 10';
$rows = $db->query($q);

echo '<p>Num of rows: ' . $db->affected_rows . '</p>';

if ($db->affected_rows > 0) {
    while ($record = $db->fetch_array($rows)) {
        $user_id = $record['user_id'];
        $user_name = $db->slashes($record['user_name']);

        echo '<p>' . $user_id . ':' . $user_name . '</p>';
    }
}

$db->close();

?>