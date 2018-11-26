<?php
date_default_timezone_set('UTC');
include 'init_autoloader.php';
define('RINDOW_TEST_CACHE',     __DIR__.'/cache');

define('RINDOW_TEST_MYSQL_USER','root');
define('RINDOW_TEST_MYSQL_PASSWORD','password');
define('RINDOW_TEST_MYSQL_DBNAME','rindow');

define('RINDOW_TEST_PGSQL_USER','postgres');
define('RINDOW_TEST_PGSQL_PASSWORD','password');
define('RINDOW_TEST_PGSQL_DBNAME','postgres');

if(!file_exists(__DIR__.'/data'))
	mkdir(__DIR__.'/data');

define('RINDOW_TEST_CLEAR_CACHE_INTERVAL',100000);
Rindow\Stdlib\Cache\CacheFactory::$fileCachePath = RINDOW_TEST_CACHE;
Rindow\Stdlib\Cache\CacheFactory::$enableMemCache = true;
Rindow\Stdlib\Cache\CacheFactory::$enableFileCache = false;
//Rindow\Stdlib\Cache\CacheFactory::$notRegister = true;
Rindow\Stdlib\Cache\CacheFactory::clearCache();

if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
