<?php
return array(
    'module_manager' => array(
        'modules' => array(
            'Rindow\Persistence\Orm\Module' => true,
            'Rindow\Database\Pdo\Module' => true,
        ),
    ),
    'container' => array(
        'aliases' => array(
            'Database'      => 'Rindow\Database\Pdo\DefaultConnection',
            'EntityManager' => 'Rindow\Persistence\Orm\EntityManager',
            'QueryBuilder'  => 'Rindow\Persistence\Orm\Criteria\QueryBuilder',
            'PaginatorFactory' => 'Rindow\Persistence\Orm\Paginator\PaginatorFactory',
        ),
        'components' => array(
            'Rindow\Persistence\Orm\EntityManager' => array(
                'properties' => array(
                    'criteriaMapper' => array('ref'=>'Rindow\Database\Pdo\Orm\CriteriaMapper'),
                ),
            ),
            'Acme\MyApp\Persistence\Orm\CategoryMapper' => array(
                'properties' => array(
                    'database' => array('ref'=>'Database'),
                ),
            ),
            'Acme\MyApp\Persistence\Orm\ColorMapper' => array(
                'properties' => array(
                    'database' => array('ref'=>'Database'),
                ),
            ),
            'Acme\MyApp\Persistence\Orm\ProductMapper' => array(
                'properties' => array(
                    'database' => array('ref'=>'Database'),
                ),
            ),
        ),
    ),
    'database' => array(
        'connections' => array(
            'default' => array(
                'dsn' => "sqlite:".RINDOW_TEST_DATA."/test.db.sqlite",
            ),
        ),
    ),
    'persistence' => array(
        'mappers' => array(
            // O/R Mapping for PDO
            'Acme\MyApp\Entity\Product'  => 'Acme\MyApp\Persistence\Orm\ProductMapper',
            'Acme\MyApp\Entity\Category' => 'Acme\MyApp\Persistence\Orm\CategoryMapper',
            'Acme\MyApp\Entity\Color'    => 'Acme\MyApp\Persistence\Orm\ColorMapper',
        ),
    ),
);