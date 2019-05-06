<?php
date_default_timezone_set('UTC');
include 'init_autoloader.php';

define('RINDOW_TEST_MYSQL_USER','root');
define('RINDOW_TEST_MYSQL_PASSWORD','password');
define('RINDOW_TEST_MYSQL_DBNAME','rindow');

define('RINDOW_TEST_PGSQL_USER','postgres');
define('RINDOW_TEST_PGSQL_PASSWORD','password');
define('RINDOW_TEST_PGSQL_DBNAME','postgres');

if(!file_exists(__DIR__.'/data'))
	mkdir(__DIR__.'/data');


if(!class_exists('PHPUnit\Framework\TestCase')) {
    include __DIR__.'/travis/patch55.php';
}
