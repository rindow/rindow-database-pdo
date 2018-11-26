<?php
namespace Rindow\Database\Pdo\Driver;

use Rindow\Database\Dao\Exception;
use Rindow\Database\Dao\ResultListInterface;
use Rindow\Database\Pdo\Connection;
use PDO;


class Mysql implements Driver
{
    public static $map = array (
         //{ "23000",    "Integrity constraint violation" },
        "23000" => Exception\ExceptionInterface::CONSTRAINT,
         //{ "21S01",    "Insert value list does not match column list" },
        "21S01" => Exception\ExceptionInterface::VALUE_COUNT_ON_ROW,
         //{ "42000",    "Syntax error or access violation" },
        "42000" => Exception\ExceptionInterface::SYNTAX,
         //{ "HY093",    "Invalid parameter number" }
        "HY093" => Exception\ExceptionInterface::MISMATCH,
         //{ "42S02",    "Base table or view not found" },
        "42S02" => Exception\ExceptionInterface::NOSUCHTABLE,
         //{ "42S22",    "Column not found" },
        "42S22" => Exception\ExceptionInterface::NOSUCHFIELD,
         //{ "HY000",    "General error" },
        "22007" => Exception\ExceptionInterface::INVALID_DATE,
         //{ "22007",    "Invalid datetime format" },

        "HY000" => Exception\ExceptionInterface::ERROR,
        1045 => Exception\ExceptionInterface::LOGIN_FAILED,
        1049 => Exception\ExceptionInterface::NOSUCHDB,
        2002 => Exception\ExceptionInterface::CONNECT_FAILED,
    );

    public static $transactionIsolationLevel = array(
        Connection::ISOLATION_READ_UNCOMMITTED => 'READ UNCOMMITTED',
        Connection::ISOLATION_READ_COMMITTED   => 'READ COMMITTED',
        Connection::ISOLATION_REPEATABLE_READ  => 'REPEATABLE READ',
        Connection::ISOLATION_SERIALIZABLE     => 'SERIALIZABLE',
    );

    public function getName()
    {
        return 'pdo_mysql';
    }

    public function getPlatform()
    {
        return 'mysql';
    }

    public function getCursorOptions($resultListType,$resultListConcurrency,$resultListHoldability)
    {
        $options = array();
        if($resultListType===ResultListInterface::TYPE_SCROLL_INSENSITIVE || ResultListInterface::TYPE_SCROLL_SENSITIVE)
            $options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
        else
            $options[PDO::ATTR_CURSOR] = PDO::CURSOR_FWDONLY;
        return $options;
    }

    public function errorCodeMapping($errorCode,$errorMessage)
    {
        //echo '[ErrorMessage='.mb_convert_encoding($errorMessage, 'SJIS','utf8') .']'."\n";
        if($errorCode==='23000') {
            if(strpos($errorMessage,'Integrity constraint violation: 1048 ')!==false)
                return Exception\ExceptionInterface::CONSTRAINT_NOT_NULL;
            if(strpos($errorMessage,'Integrity constraint violation: 1062 ')!==false)
                return Exception\ExceptionInterface::ALREADY_EXISTS;
        }
        if(isset(static::$map[$errorCode]))
            return static::$map[$errorCode];
        //echo '[ErrorCode='.$errorCode.']';
        return Exception\ExceptionInterface::ERROR;
    }

    public function  isSupportedMultipleRowsets()
    {
        return true;
    }

    public function createSavePointStatements($identifier)
    {
        return array("SAVEPOINT $identifier");
    }

    public function releaseSavepointStatements($identifier)
    {
        return array("RELEASE SAVEPOINT $identifier");
    }

    public function rollbackSavepointStatements($identifier)
    {
        return array("ROLLBACK TO SAVEPOINT $identifier");
    }

    public function setTransactionIsolationLevelStatements($isolation)
    {
        if(!isset(self::$transactionIsolationLevel[$isolation]))
            throw new Exception\DomainException('unsuppored transaction isolation level:'.$isolation);
        $isolation = self::$transactionIsolationLevel[$isolation];
        return array("SET SESSION TRANSACTION ISOLATION LEVEL $isolation");
    }

    public function setTransactionTimeout($timeout,$pdo)
    {
        return array("SET innodb_lock_wait_timeout=$timeout");
    }
}