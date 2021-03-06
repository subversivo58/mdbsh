# MongoDB Session Handler

### Description:

Store, retrieve `PHP` sessions in **MongoDB** database

### Features:

> PSR-4 compatible (like `Composer` as autoload)
>
> force-garbage (immediately remove expired sessions)
>
> token to access in another server|services|...
>
> crypto function. Thanks Enrico Zimuel <https://github.com/ezimuel/PHP-Secure-Session>

### Requeriments:
+ [PHP 7.0](http://php.net/downloads.php) (minimum version required)

+ [Composer](https://getcomposer.org/download/) on your system

+ [MongoDB database](https://www.mongodb.com/download-center#community) on your system

+ [MongoDB PHP Driver](https://github.com/mongodb/mongo-php-driver) on your `/ext` path of extensions and directive on your `php.ini` file e.g: **php_extension=mongodb.so** (Linux) or **php_extension=ongodb.dll** (Windows)
+ [see on Pelc for manually download extensions](https://pecl.php.net/package/mongodb) [recomended version 1.1.7]

+ [MongoDB PHP Library](https://github.com/mongodb/mongo-php-library) for **CRUD** operations [installed by default]

> Requeriments for encrypt | decrypt session data:
> + [OpenSSL](https://www.openssl.org/) in your system (most Linux systems come with pre-installed SSL)
  * in their PHP configuration file (php.ini) uncomment line **extension=php_openssl.so** (Linux) or **extension=php_openssl.dll** (Windows)
>
> + **mbstring**
  * in their PHP configuration file (php.ini) uncomment line **extension=php_mbstring.so** (Linux) or **extension=php_mbstring.dll** (Windows)

-------------------------------------
### Install:

**Soft Way:**
> **step 1**: If you already have `Composer` on your project (and know how to use it):


    composer require "mdbsh/mdbsh": "^1.0"
    
>
> **step 2**: Case no have `Composer`... [download](https://getcomposer.org/download/), after open|create the path of your project and inside, run `ComandLineTool` for use `Composer` to install... recalls from step 1? Up ;)



**Hard Way:**

> Download this repository **.zip** archive (or clone for your machine), unzip this content into your path of project, download manually dependencies, create (or incorporate) this class to your personal "autoload" or use the old method ¬¬
```php
   require 'path/file.php';
```


-------------------------------------
### How to use:

Define options (**optional**) instance on top of script example using `Composer`:

```php
<?php
   // require autoloader
   require __DIR__ . "/vendor/autoload.php";
   
   /**
    *@param {string} databasename: (observe MongoDB rules e.g: database|DataBase|data-base) default: SessionManager
    *
    *@param {string} collection: (observe MongoDB rules e.g: session|SESSION|Session) default: PHPSESSID
    * NOTE: this uses defined session nane or request `session_name()` for retrieve automatic (remember PHP 'session.name' only accept alphanumeric characters. See more on: @lynk http://php.net/manual/en/function.session-name.php
    *
    *@param {number} expire: time (sec) of expire session. default get `session.gc_maxlifetime` directive or set one hour (3600 sec)
    *
    *@param {string} token: define your token to access in another server|services|... default: false
    *
    *@param {boolean} force_garbage: force the immediate removal of expired sessions. default: false
    *
    *@param {boolean} encrypt: cryptographic|decryptographic data of sessions to save in MongoDB. default: true
    */
    // example of options
    $options = [
       'databasename'  => 'PHPSESSIONS',    // define databasename
       'collection'    => 'PHPSESSID',      // define session name (cookie name of this session)
       'expire'        =>  1440,            // it's don't is majority set. It is optional case php.ini no have value set
       'token'         =>  'my-poor-token', // 'YOUR_SUPER_ENCRYPTED_TOKEN' optional
       'force_garbage' =>  true,            // clean now
       'encrypt'       =>  true             // encrypt data
    ];
    
    // instance the class
    new Handler\Manager($options);
    
    // start sessions usual
    $_SESSION['foo'] = 'bar';
    
    // for use default values: new Handler\Manager();
   
```

------------------------------------------
### Docs, examples and test cases:

[see docs folder](https://github.com/subversivo58/mdbsh/blob/master/docs/USAGE.md)


------------------------------------------
### Do you want to contribute?

You want to improve the code? **send pull request!**

Found a mistake? Got a question or a suggestion? [open new issue!](https://github.com/subversivo58/mdbsh/issues)

**!ALL COLLABORATION IS WELCOME!**


-----------------------------------------
### Acknowledgment:

Many thanks to [Enrico Zimuel](https://github.com/ezimuel) the cryptographic functions available in your [
PHP-Secure-Session](https://github.com/ezimuel/PHP-Secure-Session) repository that enables storing the contents of the encrypted session

-----------------------------------------
### LICENSE:

LICENSE MIT

Copyright (c) 2016 Lauro Moraes [https://github.com/subversivo58]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
