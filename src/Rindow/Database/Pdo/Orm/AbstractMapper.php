<?php
namespace Rindow\Database\Pdo\Orm;

use Rindow\Stdlib\Entity\PropertyAccessPolicy;
use Rindow\Database\Pdo\Paginator\SqlAdapter;
use Rindow\Database\Dao\Exception;
use PDO;

use Rindow\Persistence\OrmShell\DataMapper;

abstract class AbstractMapper implements DataMapper
{
    abstract public function className();
    abstract public function supplementEntity($entityManager,$entity);
    abstract public function subsidiaryPersist($entityManager,$entity);
    abstract public function subsidiaryRemove($entityManager,$entity);

    abstract public function tableName();
    abstract public function primaryKey();
    abstract protected function insertStatement();
    abstract protected function bulidInsertParameter($entity);
    abstract protected function updateByPrimaryKeyStatement();
    abstract protected function bulidUpdateParameter($entity);
    abstract protected function deleteByPrimaryKeyStatement();
    abstract protected function selectByPrimaryKeyStatement();
    abstract protected function selectAllStatement();
    abstract protected function countAllStatement();
    abstract protected function queryStatements();

    //protected $resource;
    //protected $entityManager;
    protected $hydrator;
    protected $dataSource;

    //public function setResource($resource)
    //{
    //    $this->resource = $resource;
    //}

    public function setDataSource($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function getConnection()
    {
        if($this->dataSource==null)
            throw new Exception\DomainException('DataSource is not specifed.');
            
        return $this->dataSource->getConnection();
    }

    //public function setEntityManager($entityManager)
    //{
    //    $entityManager = $entityManager;
    //}

    public function setHydrator($hydrator)
    {
        $this->hydrator = $hydrator;
    }

    public function getMappedEntityClass($field)
    {
        if(!array_key_exists($field, $this->mappingClasses))
            return null;
        return $this->mappingClasses[$field];
    }

    protected function setField($entity,$name,$value)
    {
        if($entity instanceof PropertyAccessPolicy) {
            $entity->$name = $value;
        } else {
            $setter = 'set'.ucfirst($name);
            $entity->$setter($value);
        }
        return $entity;
    }

    protected function getField($entity,$name)
    {
        if(!is_object($entity)) {
            throw new Exception\DomainException('entity is not object.:'.gettype($entity));
        }

        if($entity instanceof PropertyAccessPolicy) {
            return $entity->$name;
        } else {
            $getter = 'get'.ucfirst($name);
            return $entity->$getter();
        }
    }

    protected function bulidQueryStatement($query)
    {
        $tableName = $this->tableName();
        $where = '';
        foreach ($query as $key => $value) {
            if(empty($where))
                $where = ' WHERE '.$key.' = :'.$key;
            else
                $where .= ' AND '.$key.' = :'.$key;
        }
        return "SELECT * FROM ".$tableName." ".$where;
    }

    protected function bulidQueryParameter(array $query)
    {
        $params = array();
        foreach ($query as $key => $value) {
            $params[':'.$key] = $value;
        }
        return $params;
    }

    protected function buildQueryLimit($firstPosition,$maxResult)
    {
        if($firstPosition===null || $maxResult===null) {
            return '';
        }
        $limit = ' LIMIT '.$maxResult;
        if($firstPosition!==null)
            $limit .= ' OFFSET '.$firstPosition;
        return $limit;
    }

    public function getId($entity)
    {
        return $this->getField($entity,$this->primaryKey());
    }

    public function create($entity)
    {
        $sql = $this->insertStatement();
        $params = $this->bulidInsertParameter($entity);
        $this->executeUpdate($sql,$params);
        $id = $this->getLastInsertId($this->tableName(),$this->primaryKey());
        $this->setField($entity,$this->primaryKey(),$id);
        return $entity;
    }

    protected function getLastInsertId($table,$column)
    {
        if($this->getConnection()->getDriver()->getPlatform()==='pgsql')
            return $this->getConnection()->lastInsertId($table.'_'.$column.'_seq');
        else
            return $this->getConnection()->lastInsertId();
    }

    public function save($entity)
    {
        $id = $this->getField($entity,$this->primaryKey());
        $sql = $this->updateByPrimaryKeyStatement();
        $params = $this->bulidUpdateParameter($entity);
        $affected = $this->executeUpdate($sql,$params);
    }

    public function remove($entity)
    {
        $primaryKey = $this->primaryKey();
        $id = $this->getField($entity,$primaryKey);
        $sql = $this->deleteByPrimaryKeyStatement();
        $params = array(':'.$primaryKey => $id);
        $affected = $this->executeUpdate($sql,$params);
    }

    public function find($id,$entity=null,$lockMode=null,array $properties=null)
    {
        $sql = $this->selectByPrimaryKeyStatement();
        $primaryKey = $this->primaryKey();
        if($entity)
            $class = $entity;
        else
            $class = $this->className();
        $params = array(':'.$primaryKey => $id);
        $result = $this->executeQuery($sql,$params,$class);
        $data = $result->current();
        return $data;
    }
/*
    public function findAll($pagination=false)
    {
        $sql = $this->selectAllStatement();
        $class = $this->className();
        $params = array();
        if($pagination) {
            $sqlAdapter = new SqlAdapter($this->getConnection());
            $sqlAdapter->setQuery($sql,$params,$class)
                    ->setCountQuery($this->countAllStatement());
            return $sqlAdapter;
        } else {
            $result = 
                $entityManager->createResultList(
                    $this->executeQuery($sql,$params,$class));
            return $result;
        }
    }
*/
    public function findBy(
        $resultListFactory,
        $query,
        $params=null,
        $firstPosition=null,
        $maxResult=null,
        $lockMode=null)
    {
        $mapped = true;
        $compoundSelection = false;
        if(is_array($query)) {
            //
            // CrudRepository style query filter 
            //
            $sql = $this->bulidQueryStatement($query);
            $params = $this->bulidQueryParameter($query);
        } elseif($query instanceof PreparedCriteria) {
            //
            // prepared query criteria
            //
            $sql = $query->getSql();
            $criteria = $query->getCriteria();
            if($criteria) {
                $selection = $criteria->getSelection();
                if($selection->getExpressionType()!='ROOT') {
                    $mapped = false;
                    $compoundSelection = $selection->isCompoundSelection();
                }
            }
        } elseif(is_string($query)) {
            //
            // Native SQL
            //
            $sql = $query;
        } else {
            throw new Exception\InvalidArgumentException('Invalid Type of Query for "'.$this->className().'".');
        }

        $sql .= $this->buildQueryLimit($firstPosition,$maxResult);
        if($params===null)
            $params = array();

        if($mapped) {
            $class = $this->className();
            $cursorFactory = $this->createQueryExecutor($sql,$params,$class);
            $result = call_user_func($resultListFactory,$cursorFactory);
        } else {
            $cursorFactory = $this->createQueryExecutor($sql,$params);
            $result = call_user_func($resultListFactory,$cursorFactory);
            $result->setMapped(false);
            if(!$compoundSelection)
                $result->addFilter(array($this,'singleValue'));
        }
        return $result;
    }

    public function singleValue($row)
    {
        return current($row);
    }

    public function getNamedQuery($name,$resultClass=null)
    {
        $querys = $this->queryStatements();
        if(!isset($querys[$name]))
            return null;
        $prepared = new PreparedCriteria(null,$querys[$name],$this->className(),$resultClass);
        return $prepared;
    }

    protected function executeUpdate($sql,$params)
    {
        try {
            $result = $this->getConnection()->executeUpdate($sql,$params);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    protected function determineFetchMode($class)
    {
        if($class===null)
            $fetchMode = PDO::FETCH_ASSOC;
        elseif(is_string($class))
            $fetchMode = PDO::FETCH_CLASS;
        elseif(is_object($class))
            $fetchMode = PDO::FETCH_INTO;
        else
            throw new Exception\DomainException('"class" parameter is invalid type:'.gettype($class));
        return $fetchMode;
    }

    protected function executeQuery($sql,$params,$class=null,$resultList=null)
    {
        $fetchMode = $this->determineFetchMode($class);
        return $this->getConnection()->executeQuery($sql,$params,$fetchMode,$class,null,$resultList);
    }

    protected function createQueryExecutor($sql,$params,$class=null,$resultList=null)
    {
        $fetchMode = $this->determineFetchMode($class);
        $connection = $this->getConnection();
        return function () use ($connection,$sql,$params,$fetchMode,$class,$resultList) {
            return $connection->executeQuery($sql,$params,$fetchMode,$class,null,$resultList);
        };
    }

    public function createSchema()
    {
        $sql = $this->createSchemaStatement();
        return $this->executeUpdate($sql,array());
    }

    public function dropSchema()
    {
        $sql = $this->dropSchemaStatement();
        return $this->executeUpdate($sql,array());
    }

    public function close()
    {
        // *** CAUTION ***
        // Don't close the connection.
        // Because other entity managers use same connection.
    }
}