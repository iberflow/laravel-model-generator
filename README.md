# Project abandoned
I'm very sorry to announce that I no longer have time to maintain this package. This project was originally created over a couple of days when I needed to migrate an existing project onto Laravel. Even though it's being actively used (over 50k installs!), I can't find the time to keep track of the PRs and what changes might break things in new/old Laravel versions, nor have I had the need to use it after the initial release. The codebase is a mess and it desperately cries for a rewrite.

**Please use [reliese/laravel](https://github.com/reliese/laravel) package instead.**

------

# Model Generator
> [Laravel 5](https://laravel.com/docs/5.3/) model generator for an existing schema. 

It plugs into your existing database and generates model class files based on the existing tables.

# Installation

```sh
composer require ignasbernotas/laravel-model-generator --dev
```

You'll only want to use these generators for local development, so you don't want to update the production providers array in `config/app.php`. Instead, add the provider in `app/Providers/AppServiceProvider.php`, like so:

```php
<?php

public function register()
{
    if ($this->app->environment() == 'local') {
        $this->app->register('Iber\Generator\ModelGeneratorProvider');
    }
}
```

# Help & Options

```sh
php artisan help make:models

Usage:
  make:models [options]

Options:
      --tables[=TABLES]          Comma separated table names to generate
      --prefix[=PREFIX]          Table Prefix [default: DB::getTablePrefix()]
      --dir[=DIR]                Model directory [default: "Models/"]
      --extends[=EXTENDS]        Parent class [default: "Illuminate\Database\Eloquent\Model"]
      --fillable[=FILLABLE]      Rules for $fillable array columns [default: ""]
      --guarded[=GUARDED]        Rules for $guarded array columns [default: "ends:_guarded"]
      --timestamps[=TIMESTAMPS]  Rules for $timestamps columns [default: "ends:_at"]
  -i, --ignore[=IGNORE]          Ignores the tables you define, separated with ,
  -f, --force[=FORCE]            Force override [default: false]
  -s, --ignoresystem             If you want to ignore system tables.
                                              Just type --ignoresystem or -s
  -m, --getset                   Defines if you want to generate set and get methods
  -h, --help                     Display this help message
  -d, --connection               Database connection name
  -q, --quiet                    Do not output any message
  -c, --variables                Set columns as variables set [TRUE] to set as variables and [FALSE] for comments.
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -n, --no-interaction           Do not ask any interactive question
      --env[=ENV]                The environment the command should run under.
  -v|vv|vvv, --verbose           Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  Build models from existing schema.
```

# Running the generator

```sh
php artisan make:models
```

# Examples

## Table users
### SQL

```sql
CREATE TABLE `users` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(64) NULL DEFAULT NULL,
	`password` VARCHAR(45) NULL DEFAULT NULL,
	`email` VARCHAR(45) NULL DEFAULT NULL,
	`name` VARCHAR(45) NULL DEFAULT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
```
### Generated Models/Users.php class

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Users extends Model {

    public $timestamps = false;

    protected $table = 'users';

    protected $fillable = ['username', 'email', 'name'];

    protected $guarded = ['id', 'password'];

}
```

## Table posts
### SQL

```sql
CREATE TABLE `posts` (
	`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	`title` VARCHAR(64) UNSIGNED NOT NULL DEFAULT '',
	`content` TEXT NOT NULL DEFAULT '',
	`created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
	`updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
```

### Generated Models/Posts.php class

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model {

    public $timestamps = true;

    protected $table = 'posts';

    protected $fillable = ['title', 'content', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

}
```
