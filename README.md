The PHP-MySQLi-Simple-Class is a wrapper for MySQL Database.

### Usage
```php
require_once('database.class.php');

$db = new MyDatabase('localhost', 'user', 'password', 'dbname');
$db->connect();
```

### Select first row

```php

$q = 'SELECT `users`.`user_id`, `users`.`user_name` FROM `users` WHERE `users`.`user_id` = "1" ORDER BY `users`.`user_id` DESC LIMIT 1';
$record = $db->query_first($q);

if ($db->affected_rows > 0) {
    $user_id = $record['user_id'];
    $user_name = $record['user_name'];
}

echo '<p>' . $user_id . ':' . $user_name . '</p>';

```

### Select some rows

```php

$q = 'SELECT  `users`.`user_id`, `users`.`user_name` FROM `users` ORDER BY `users`.`user_id` DESC LIMIT 10';
$rows = $db->query($q);

if ($db->affected_rows > 0) {
    while ($record = $db->fetch_array($rows)) {
        $user_id = $record['user_id'];
        $user_name = $record['user_name'];

        echo '<p>' . $user_id . ':' . $user_name . '</p>';
    }
}


```