<?php
namespace Rindow\Database\Pdo\Support;

use Rindow\Database\Dao\Exception\ExceptionInterface;
use Rindow\Aop\JoinPointInterface;

class DaoExceptionAdvisor
{
    public function afterThrowingAdvice(JoinPointInterface $joinPoint)
    {
        # code...
    }
}