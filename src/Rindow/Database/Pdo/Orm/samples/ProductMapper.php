<?php
namespace Acme\MyApp\Persistence\Orm;

use Rindow\Database\Pdo\Orm\AbstractMapper;

class ProductMapper extends AbstractMapper
{
    const CLASS_NAME = 'Acme\MyApp\Entity\Product';
    const TABLE_NAME = 'product';
    const PRIMARYKEY = 'id';
    const CREATE_TABLE = "CREATE TABLE product(id INTEGER PRIMARY KEY AUTOINCREMENT, category INTEGER, name TEXT)";
    const DROP_TABLE = "DROP TABLE product";
    const INSERT = "INSERT INTO product (category,name) VALUES ( :category , :name )";
    const UPDATE_BY_PRIMARYKEY = "UPDATE product SET category = :category , name = :name WHERE id = :id";
    const DELETE_BY_PRIMARYKEY = "DELETE FROM product WHERE id = :id";
    const SELECT_BY_PRIMARYKEY = "SELECT * FROM product WHERE id = :id";
    const SELECT_ALL           = "SELECT * FROM product";
    const COUNT_ALL            = "SELECT COUNT(id) as count FROM product";

    const CLASS_CATEGORY = 'Acme\MyApp\Entity\Category';
    const CLASS_COLOR    = 'Acme\MyApp\Entity\Color';

    protected $categoryRepository;
    protected $colorRepository;

    public function getCategoryRepository()
    {
        if($this->categoryRepository)
            return $this->categoryRepository;
        return $this->categoryRepository = $this->entityManager->getRepository(self::CLASS_CATEGORY);
    }

    public function getColorRepository()
    {
        if($this->colorRepository)
            return $this->colorRepository;
        return $this->colorRepository = $this->entityManager->getRepository(self::CLASS_COLOR);
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
        $categoryMapper = $this->getCategoryRepository()->getMapper();
        $hash = md5(strval($this->getId($entity))) .
            md5(strval($categoryMapper->getId($this->getField($entity,'category')))) .
            md5(strval($this->getField($entity,'name')));
        return md5($hash);
    }

    public function supplementEntity($entity)
    {
        $entity->category = $this->getCategoryRepository()->find($entity->category);
        $entity->colors = $this->getColorRepository()->findBy(array('product'=>$entity->id));
        $entity->colors->setCascade(array('persist','remove'));
        return $entity;
    }

    public function subsidiaryPersist($entity)
    {
        if($entity->colors===null)
            return;
        $colorRepository = $this->getColorRepository();
        foreach ($entity->colors as $color) {
            $colorRepository->persist($color);
        }
    }

    public function subsidiaryRemove($entity)
    {
        if($entity->colors===null)
            return;
        $colorRepository = $this->getColorRepository();
        foreach ($entity->colors as $color) {
            $colorRepository->remove($color);
        }
    }

    protected function insertStatement()
    {
        return self::INSERT;
    }

    protected function bulidInsertParameter($entity)
    {
        return array(':category'=>$entity->category->id,':name'=>$entity->name);
    }

    protected function updateByPrimaryKeyStatement()
    {
        return self::UPDATE_BY_PRIMARYKEY;
    }

    protected function bulidUpdateParameter($entity)
    {
        return array(':category'=>$entity->category->id,':name'=>$entity->name,':id'=>$entity->id);
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
