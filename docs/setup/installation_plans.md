# Installation Plans

## MongoDB Setup (Laravel)

### 1️⃣ Install MongoDB PHP Extension

Make sure MongoDB extension is installed:

```bash
pecl install mongodb
```

Add to php.ini:

```ini
extension=mongodb
```

Verify:

```bash
php -m | grep mongodb
```

### 2️⃣ Install Laravel MongoDB Package

```bash
composer require mongodb/laravel-mongodb
```

### 3️⃣ Configure Database

Update .env:

```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=your_database
DB_USERNAME=
DB_PASSWORD=
```

### 4️⃣ Update config/database.php

Add MongoDB connection:

```php
'mongodb' => [
    'driver' => 'mongodb',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 27017),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'options' => [
        'database' => 'admin'
    ],
],
```

### 5️⃣ Use MongoDB Model

```php
use MongoDB\Laravel\Eloquent\Model;

class User extends Model
{
    protected $connection = 'mongodb';
}
```