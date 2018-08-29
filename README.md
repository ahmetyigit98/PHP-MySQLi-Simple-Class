The PHP-MySQLi-Simple-Class is a wrapper for MySQL Database.

### Usage
```php
require_once('database.class.php');

$db = new MyDatabase('localhost', 'user', 'password', 'dbname');
$db->connect();
```

### Select an one record

```php

$q = 'SELECT `users`.`user_id`, `users`.`user_name` FROM `users` WHERE `users`.`user_id` = "1" ORDER BY `users`.`user_id` DESC LIMIT 1';
$record = $db->query_first($q);

if ($db->affected_rows > 0) {
    $user_id = $record['user_id'];
    $user_name = $db->slashes($record['user_name']);
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
        $user_name = $db->slashes($record['user_name']);

        echo '<p>' . $user_id . ':' . $user_name . '</p>';
    }
}

```

### Insert a new record

```php

$data = array();

$data['user_added'] = 'NOW()';
$data['user_name'] = 'John'

$user_id = $db->query_insert('users', $data);

unset($data);

echo 'There is id of inserted record: '.$user_id; 

```

### Update an existing record

```php

$data = array();

$data['user_name'] = 'Jack'

$db->update('users', $data, 'user_id="1"');

unset($data);

```

### Update a record

```php

$q = 'DELETE FROM `users` WHERE `users`.`user_id`= "1";
$db->query($q);

```

### Do a custom query

```php

$q = 'SELECT * FROM `users` WHERE `users`.`user_name` LIKE %'.$db->escape($search).'% ORDER BY `users`.`user_id` DESC LIMIT 5
$db->query($q);

echo '<p>Rows has found: '.$db->affected_rows.'</p>';

```