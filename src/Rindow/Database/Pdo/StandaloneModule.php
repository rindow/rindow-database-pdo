<?php
namespace Rindow\Database\Pdo;

class StandaloneModule
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'aliases' => array(
                    'Rindow\\Database\\Dao\\DefaultSqlDataSource' => 'Rindow\\Database\\Pdo\\DefaultDataSource',
                    'Rindow\\Persistence\\OrmShell\\DefaultCriteriaMapper' => 'Rindow\\Database\\Pdo\\Orm\\DefaultCriteriaMapper',
                    //'Rindow\\Persistence\\OrmShell\\DefaultResource'       => 'Rindow\\Database\\Pdo\\Orm\\DefaultResource',
                    'Rindow\\Persistence\\OrmShell\\Transaction\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionSynchronizationRegistry',
                    'Rindow\\Messaging\\Service\\Database\\DefaultDataSource' => 'Rindow\\Database\\Pdo\\DefaultDataSource',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionSynchronizationRegistry',
                ),
                'components' => array(
                    //
                    //  ***** Transactional EntityManager and DataSource for Local transaction ******
                    //
                    'Rindow\\Database\\Pdo\\DefaultDataSource' => array(
                        'class' => 'Rindow\\Database\\Pdo\\DataSource',
                        'properties' => array(
                            'config' => array('config'=>'database::connections::default'),
                            'connectionClass' => array('value'=>'Rindow\\Database\\Pdo\\Connection'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\DefaultConnection' => array(
                        'class' => 'Rindow\\Database\\Pdo\\Connection',
                        'constructor_args' => array(
                            'config' => array('config'=>'database::connections::default'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionBoundary' => array(
                        'class'=>'Rindow\\Transaction\\Support\\TransactionBoundary',
                        'properties' => array(
                            'withoutTransactionManagement' => array('value'=>true),
                        ),
                        'proxy' => 'disable',
                    ),
                    /*
                    *  ORM "Microshell"
                    */
                    'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper' => array(
                        'properties' => array(
                            'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\DefaultDataSource'),
                        ),
                        //'scope'=>'prototype',
                        'proxy' => 'disable',
                    ),
                    //'Rindow\\Database\\Pdo\\Orm\\DefaultResource' => array(
                    //    'class' => 'Rindow\\Database\\Pdo\\Orm\\Resource',
                    //    'properties' => array(
                    //        'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\DefaultDataSource'),
                    //    ),
                    //    'proxy' => 'disable',
                    //),
                    'Rindow\\Database\\Pdo\\Orm\\DefaultCriteriaMapper' => array(
                        'class' => 'Rindow\\Database\\Pdo\\Orm\\CriteriaMapper',
                    ),
                    /*
                    *  Messagining
                    */
                    //'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver' => array(
                    //    'properties' => array(
                    //        'queueDriverClass' => array('value'=>'Rindow\\Database\\Pdo\\Messaging\\QueueDriver'),
                    //    ),
                    //),
                    /*
                     *  DataStore
                     */
                    //'Rindow\\Database\\Pdo\\Crud\\DefaultDataStore' => array(
                    //    'class' => 'Rindow\\Database\\Pdo\\Crud\\PdoDataStore',
                    //    'properties' => array(
                    //        'dataSource' => array('value'=>'Rindow\\Database\\Pdo\\DefaultDataSource'),
                    //    ),
                    //),
                    /*
                     *  Repository
                     *
                    'Rindow\\Database\\Pdo\\Repository\\DefaultPdoRepositoryFactory' => array(
                        'class' => 'Rindow\\Database\\Dao\\Repository\\GenericSqlRepositoryFactory',
                        'properties' => array(
                            'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\DefaultDataSource'),
                            'queryBuilder' => array('ref'=>'Rindow\\Database\\Pdo\\Repository\\DefaultQueryBuilder'),
                            //'transactionBoundary' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionBoundary'),
                        ),
                    ),
                    'Rindow\\Database\\Pdo\\Repository\\DefaultQueryBuilder'=>array(
                        'class' => 'Rindow\\Database\\Dao\\Support\\QueryBuilder',
                    ),
                    */
                ),
            ),
        );
    }
}
