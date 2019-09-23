The PHP-MySQLi-Simple-Class is a wrapper for MySQL functions.

### Usage

It's pretty simply. Just include and initialize it as usual.

```php
require_once 'database.class.php';

$db = new MyDatabase('localhost', 'user', 'password', 'dbname');

$db->connect();
```

### Select an one record

If we want to get only first found record use the following:

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

Use array for your data with names of fields and values.

```php

$data = [];

$data['user_added'] = 'NOW()';
$data['user_name'] = 'John';

$user_id = $db->query_insert('users', $data);

unset($data);

echo 'There is id of inserted record: '.$user_id; 

```

### Update an existing record

```php

$data = [];

$data['user_name'] = 'Jack';

$db->update('users', $data, 'user_id="1"');

unset($data);

```

### Delete a record

```php

$q = 'DELETE FROM `users` WHERE `users`.`user_id`= "1";
$db->query($q);

```

### Do a custom query

Don't forget to use method $db->escape() for fields with text.

```php

$q = 'SELECT * FROM `users` WHERE `users`.`user_name` LIKE "%'.$db->escape($search).'%" ORDER BY `users`.`user_id` DESC LIMIT 5
$db->query($q);

echo '<p>Rows has found: '.$db->affected_rows.'</p>';

```

### Clean a string

If you need to clean input string from user e.g. from $_POST array use the method $db->clean()

```php

$user_city = $db->clean($_POST['city']);

```
And after that you can store this data into your db.

```php

$data = [];

$data['user_city'] = $user_city;

$db->update('users', $data, 'user_id="1"');

unset($data);

```

### Debug and logging

You can use different debug methods for analysis your queries.

```php

$db = new MyDatabase('localhost', 'user', 'password', 'dbname');

$db->debug_sql = 1;
$db->error_page = SITEURL . '500.html';

$db->connect();

```