<?php
namespace Rindow\Database\Pdo\Orm;

use Rindow\Database\Dao\Exception;
use Rindow\Persistence\Orm\Criteria\CriteriaMapper as CriteriaMapperInterface;

class CriteriaMapper implements CriteriaMapperInterface
{
    protected static $operatorString = array(
        'EQUAL'=>'=',
        'GREATER_THAN'=>'>',
        'GREATER_THAN_OR_EQUAL'=>'>=',
        'LESS_THAN'=>'<',
        'LESS_THAN_OR_EQUAL'=>'<=',
    );

    protected $entityManager;

    public function __construct($entityManager = null)
    {
        if($entityManager)
            $this->setContext($entityManager);
    }

    public function setContext($context)
    {
        $this->entityManager = $context;
    }

    public function prepare($criteria,$resultClass=null)
    {
        if($criteria->getRoots()===null)
            $entityClass = $resultClass;
        else
            $entityClass = $criteria->getRoots()->getNodeName();
        return new PreparedCriteria(
            $criteria,
            $this->decodeQuery($criteria),
            $entityClass,
            $resultClass);
    }

    protected function decodeQuery($criteria)
    {
        $from = $this->buildFromPhrase($criteria->getRoots());
        $where = $this->buildRestrictionPhrase($criteria->getRestriction());
        $selection = $this->buildSelectionPhrase($criteria->getSelection());
        $distinct = $criteria->isDistinct() ? 'distinct':'';
        $order = $this->buildOrderListPhrase($criteria->getOrderList());
        $group = $this->buildPathListPhrase($criteria->getGroupList());
        $having = $this->buildRestrictionPhrase($criteria->getGroupRestriction());

        $sql = 'SELECT ';
        if($distinct!='')
            $sql .= $distinct.' ';
        $sql .= $selection.' FROM '.$from;
        if($where!='')
            $sql .= ' WHERE '.$where;
        if($group!='')
            $sql .= ' GROUP BY '.$group;
        if($having!='')
            $sql .= ' HAVING '.$having;
        if($order!='')
            $sql .= ' ORDER BY '.$order;

        return $sql;
    }

    protected function buildFromPhrase($roots)
    {
        $entityClass = $roots->getNodeName();
        $classMapper = $this->entityManager->getRepository($entityClass)->getMapper();
        $phrase = $tableName = $classMapper->tableName();
        $alias = $roots->getAlias();
        if($alias)
            $phrase .= ' as '.$alias;
        $joins = $roots->getJoins();
        foreach($joins as $join) {
            $joinClassMapper = $this->entityManager->getRepository($this->getPathClass($join))->getMapper();
            $joinTable = $joinClassMapper->tableName();
            $phrase .= ' JOIN ' . $joinTable . ' ON ' . $joinTable.'.'.$joinClassMapper->primaryKey().'='.$tableName.'.'.$join->getAttribute();
            if($join->getOn())
                $phrase .= ' AND '.$this->buildRestrictionPhrase($join->getOn());
        }
        return $phrase;
    }

    /**
    * @param  Predicate  $restriction 
    * @return String  SQL string
    */
    protected function buildRestrictionPhrase($restriction)
    {
        if($restriction==null)
            return '';
        $operator = $restriction->getOperator();
        $expressions = $restriction->getExpressions();
        switch($operator) {
            case 'AND':
            case 'OR':
                $phrase = '';
                foreach ($expressions as $expression) {
                    if($phrase == '')
                        $phrase = '('. $this->buildRestrictionPhrase($expression).')';
                    else
                        $phrase .= ' '.$operator.' ('.$this->buildRestrictionPhrase($expression).')';
                }
                break;
            case 'EQUAL':
            case 'GREATER_THAN':
            case 'GREATER_THAN_OR_EQUAL':
            case 'LESS_THAN':
            case 'LESS_THAN_OR_EQUAL':
                $operatorString = self::$operatorString[$operator];
                $value1 = $this->buildValue($expressions[0]);
                $value2 = $this->buildValue($expressions[1]);
                $phrase = $value1.' '.$operatorString.' '.$value2;
                break;
            default:
                throw new Exception\DomainException('Unknown Operator "'.$operator.'" in "where" phrase.');
        }
        return $phrase;
    }

    protected function buildValue($expression)
    {
        $type = $expression->getExpressionType();
        switch ($type) {
            case 'CONSTANT':
                $phrase = $expression->getValue();
                if(is_string($phrase))
                    $phrase = "'".$phrase."'";
                break;
            
            case 'PARAMETER':
                $phrase = ':'.$expression->getName();
                break;
            
            case 'FUNCTION':
                $phrase = $this->buildFunction($expression);
                break;
            
            case 'ROOT':
            case 'JOIN':
            case 'PATH':
                $phrase = $this->buildPath($expression);
                break;
            
            default:
                throw new Exception\DomainException('Unknown expression type "'.$type.' as value type.');
                break;
        }
        return $phrase;
    }

    protected function buildFunction($functionExpression)
    {
        $name = $functionExpression->getOperator();
        $args = '';
        foreach ($functionExpression->getExpressions() as $expression) {
            if($args == '')
                $args = $this->buildValue($expression);
            else
                $args .= ' , '.buildValue($expression);
            if($name=='COUNT' && $expression->getExpressionType()=='ROOT')
                $args = $this->buildPath($expression).
                        '.'.$this->entityManager->getRepository($this->getPathClass($expression))->getMapper()->primaryKey();
        }
        return $name . '( ' . $args . ' )';
    }

    protected function buildPath($path)
    {
        if(!method_exists($path, 'getParentPath'))
            throw new Exception\InvalidArgumentException('invalid path type.');
            
        $parent = $path->getParentPath();
        if($parent!=null) {
            if($alias = $parent->getAlias()) {
                $tableName = $alias;
            } else {
                $entityClass = $this->getPathClass($parent);
                $tableName = $this->entityManager->getRepository($entityClass)->getMapper()->tableName();
            }
            return $tableName.'.'.$path->getNodeName();
        }
        $alias = $path->getAlias();
        if($alias)
            return $alias;
        $entityClass = $path->getNodeName();
        return $this->entityManager->getRepository($entityClass)->getMapper()->tableName();
    }
    protected function getPathClass($path)
    {
        $parent = $path->getParentPath();
        if($parent==null) {
            return $path->getNodeName();
        }
        $entityClass = $this->getPathClass($parent);
        return $this->entityManager->getRepository($entityClass)->getMapper()->getMappedEntityClass($path->getNodeName());
    }

    protected function buildSelectionPhrase($selection)
    {
        if(!method_exists($selection, 'isCompoundSelection')) {
            throw new Exception\InvalidArgumentException('invalid selection type.');            
        }
        if($selection->isCompoundSelection()) {
            return $this->buildPathListPhrase($selection->getCompoundSelectionItems());
        } else {
            if($selection->getExpressionType()=='ROOT') {
                $phrase = $this->buildPath($selection).'.*';
            } else {
                $phrase = $this->buildValue($selection);
                $alias = $selection->getAlias();
                if($alias) {
                    $phrase .= ' as '.$alias;
                }
            }
            return $phrase;
        }
    }

    protected function buildPathListPhrase($pathList)
    {
        $phrase = '';
        foreach ($pathList as $item) {
            if($phrase == '')
                $phrase = $this->buildSelectionPhrase($item);
            else
                $phrase .= ' , '.$this->buildSelectionPhrase($item);
        }
        return $phrase;
    }

    protected function buildOrderListPhrase($orderList)
    {
        $phrase = '';
        foreach ($orderList as $item) {
            if($phrase == '')
                $phrase = $this->buildSelectionPhrase($item->getExpression());
            else
                $phrase .= ','.$this->buildSelectionPhrase($item->getExpression());
            if(!$item->isAscending())
                $phrase .= ' DESC';
        }
        return $phrase;
    }
}