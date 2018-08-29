The PHP-MySQLi-Simple-Class is a wrapper for MySQL Database.

### Usage

```php
require_once('database.class.php');

$db = new MyDatabase('localhost', 'user', 'password', 'dbname');
$db->connect();
```