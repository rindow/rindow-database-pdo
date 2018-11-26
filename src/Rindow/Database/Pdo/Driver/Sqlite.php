<?php
namespace Rindow\Database\Pdo\Driver;

use Rindow\Database\Dao\Exception;
use Rindow\Database\Dao\ResultListInterface;
use Rindow\Database\Pdo\Connection;
use PDO;

class Sqlite implements Driver
{
    public static $map = array (
         //{ "23000",    "Integrity constraint violation" },
        "23000" => Exception\ExceptionInterface::CONSTRAINT,
         //{ "HY000",    "General error" },
        "HY000" => Exception\ExceptionInterface::ERROR,
        14 => Exception\ExceptionInterface::CONNECT_FAILED,
    );

    public static $transactionIsolationLevel = array(
        Connection::ISOLATION_READ_UNCOMMITTED => 'read_uncommitted = true',
        Connection::ISOLATION_SERIALIZABLE     => 'read_uncommitted = false',
    );

    public function getName()
    {
        return 'pdo_sqlite';
    }

    public function getPlatform()
    {
        return 'sqlite';
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
        if($errorCode==='HY000') {
            if(strpos($errorMessage,': syntax error')!==false)
                return Exception\ExceptionInterface::SYNTAX;
            if(strpos($errorMessage,' no such table:')!==false)
                return Exception\ExceptionInterface::NOSUCHTABLE;
            if(strpos($errorMessage,' no such column:')!==false)
                return Exception\ExceptionInterface::NOSUCHFIELD;
            if(strpos($errorMessage,' bind or column index out of range')!==false)
                return Exception\ExceptionInterface::MISMATCH;
            if(strpos($errorMessage,' column index out of range')!==false)
                return Exception\ExceptionInterface::MISMATCH;
            if(strpos($errorMessage,' datatype mismatch')!==false)
                return Exception\ExceptionInterface::INVALID;
        } else if($errorCode==='23000') {
            if(strpos($errorMessage,' may not be NULL')!==false)
                return Exception\ExceptionInterface::CONSTRAINT_NOT_NULL;
            if(strpos($errorMessage,' NOT NULL constraint failed')!==false)
                return Exception\ExceptionInterface::CONSTRAINT_NOT_NULL;
            if(strpos($errorMessage,' must be unique')!==false)
                return Exception\ExceptionInterface::ALREADY_EXISTS;
            if(strpos($errorMessage,' is not unique')!==false)
                return Exception\ExceptionInterface::ALREADY_EXISTS;
            if(strpos($errorMessage,' are not unique')!==false)
                return Exception\ExceptionInterface::ALREADY_EXISTS;
            if(strpos($errorMessage,' UNIQUE constraint failed')!==false)
                return Exception\ExceptionInterface::ALREADY_EXISTS;
        }
        if(isset(static::$map[$errorCode]))
            return static::$map[$errorCode];
        //echo '[ErrorCode='.$errorCode.']';
        //echo '[ErrorMessage='.$errorMessage.']';
        return Exception\ExceptionInterface::ERROR;
    }

    public function  isSupportedMultipleRowsets()
    {
        return false;
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
        return array("PRAGMA $isolation");
    }

    public function setTransactionTimeout($timeout,$pdo)
    {
        $pdo->setAttribute(PDO::ATTR_TIMEOUT,$timeout);
        return array();
    }
}
