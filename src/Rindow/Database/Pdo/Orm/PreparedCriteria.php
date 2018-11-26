<?php
namespace Rindow\Database\Pdo\Orm;

class PreparedCriteria /* implements PreparedCriteriaInterface */
{
    protected $criteria;
    protected $sql;
    protected $entityClass;
    protected $resultClass;
    
    public function __construct(
        $criteria,
        $sql,
        $entityClass,
        $resultClass=null)
    {
        $this->criteria = $criteria;
        $this->sql = $sql;
        $this->entityClass = $entityClass;
        $this->resultClass = $resultClass;
    }

    public function getCriteria()
    {
        return $this->criteria;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getResultClass()
    {
        return $this->resultClass;
    }

    public function getEntityClass()
    {
        return $this->entityClass;
    }
}