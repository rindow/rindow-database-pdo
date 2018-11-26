<?php
namespace Acme\MyApp\Persistence\Orm;

use Rindow\Database\Pdo\Orm\AbstractMapper;

class ColorMapper extends AbstractMapper
{
    const CLASS_NAME = 'Acme\MyApp\Entity\Color';
    const TABLE_NAME = 'color';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE color(id INTEGER PRIMARY KEY AUTOINCREMENT, product INTEGER, color INTEGER)";
    const DROP_TABLE = "DROP TABLE color";
    const INSERT = "INSERT INTO color ( product , color ) VALUES ( :product , :color )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE color SET product = :product , color = :color WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM color WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM color WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM color";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM color";

    const CLASS_PRODUCT = 'Acme\MyApp\Entity\Product';

    protected $productRepository;

    public function getProductRepository()
    {
        if($this->productRepository)
            return $this->productRepository;
        return $this->productRepository = $this->entityManager->getRepository(self::CLASS_PRODUCT);
    }

    public function className()
    {
        return self::CLASS_NAME;
    }

    public function tableName()
    {
        return self::TABLE_NAME;
    }

    public function primaryKey()
    {
        return self::PRIMARYKEY;
    }

    public function hash($entity)
    {
        $hash = md5(strval($this->getId($entity))) .
            md5(strval($this->getField($entity,'product')->id)) .
            md5(strval($this->getField($entity,'color')));
        return md5($hash);
    }

    public function supplementEntity($entity)
    {
        $entity->product = $this->getProductRepository()->find($entity->product);
        return $entity;
    }

    public function subsidiaryPersist($entity)
    {
    }

    public function subsidiaryRemove($entity)
    {
    }

    protected function insertStatement()
    {
        return self::INSERT;
    }

    protected function bulidInsertParameter($entity)
    {
        return array( ':product'=>$entity->product->id, ':color'=>$entity->color );
    }

    protected function updateByPrimaryKeyStatement()
    {
        return self::UPDATE_BY_PRIMARYKEY;
    }

    protected function bulidUpdateParameter($entity)
    {
        return array(':product'=>$entity->product->id, ':color'=>$entity->color,':id'=>$entity->id);
    }

    protected function deleteByPrimaryKeyStatement()
    {
        return self::DELETE_BY_PRIMARYKEY;
    }

    protected function selectByPrimaryKeyStatement()
    {
        return self::SELECT_BY_PRIMARYKEY;
    }

    protected function selectAllStatement()
    {
        return self::SELECT_ALL;
    }

    protected function countAllStatement()
    {
        return self::COUNT_ALL;
    }

    protected function createSchemaStatement()
    {
        return self::CREATE_TABLE;
    }

    protected function dropSchemaStatement()
    {
        return self::DROP_TABLE;
    }
}
