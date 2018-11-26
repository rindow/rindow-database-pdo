<?php
namespace Rindow\Database\Pdo\Driver;

use Rindow\Database\Dao\ResultListInterface;
use Rindow\Database\Dao\Exception;
use Rindow\Database\Pdo\Connection;

class Pgsql implements Driver
{
    public static $map = array (
         //{ "23505",    "Unique violation" },
        "23505" => Exception\ExceptionInterface::ALREADY_EXISTS,
         //{ "23502",    "Not null violation" },
        "23502" => Exception\ExceptionInterface::CONSTRAINT_NOT_NULL,
         //{ "22P02",    "Invalid text representation" },
        "22P02" => Exception\ExceptionInterface::INVALID,
         //{ "22007",    "Invalid datetime format" },
        "22007" => Exception\ExceptionInterface::INVALID_DATE,
         //{ "22008",    "Datetime field overflow" },
        "22008" => Exception\ExceptionInterface::INVALID_DATE,
         //{ "42601",    "Syntax error" },
        "42601" => Exception\ExceptionInterface::SYNTAX,
        //08P01: protocol_violation
        "08P01" => Exception\ExceptionInterface::MISMATCH,
        //{ "HY093",    "Invalid parameter number" },
        "HY093" => Exception\ExceptionInterface::MISMATCH,
         //{ "42P01",    "Undefined table" },
        "42P01" => Exception\ExceptionInterface::NOSUCHTABLE,
         //{ "42703",    "Undefined column" },
        "42703" => Exception\ExceptionInterface::NOSUCHFIELD,
        7 => Exception\ExceptionInterface::LOGIN_FAILED,
    );

    public static $transactionIsolationLevel = array(
        Connection::ISOLATION_READ_UNCOMMITTED => 'READ UNCOMMITTED',
        Connection::ISOLATION_READ_COMMITTED   => 'READ COMMITTED',
        Connection::ISOLATION_REPEATABLE_READ  => 'REPEATABLE READ',
        Connection::ISOLATION_SERIALIZABLE     => 'SERIALIZABLE',
    );

    public function getName()
    {
        return 'pdo_pgsql';
    }

    public function getPlatform()
    {
        return 'pgsql';
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
        return array("SET SESSION CHARACTERISTICS AS TRANSACTION ISOLATION LEVEL $isolation");
    }

    public function setTransactionTimeout($timeout,$pdo)
    {
        return array();
    }
}