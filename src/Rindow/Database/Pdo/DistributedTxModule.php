<?php
namespace Rindow\Database\Pdo;

class DistributedTxModule
{
    public function getConfig()
    {
        return array(
            'container' => array(
                'aliases' => array(
                    'Rindow\\Database\\Dao\\DefaultSqlDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource',
                    'Rindow\\Persistence\\OrmShell\\DefaultCriteriaMapper' => 'Rindow\\Database\\Pdo\\Orm\\DefaultCriteriaMapper',
                    //'Rindow\\Persistence\\OrmShell\\DefaultResource'       => 'Rindow\\Database\\Pdo\\Orm\\DefaultResource',
                    'Rindow\\Persistence\\OrmShell\\Transaction\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Transaction\\Distributed\\DefaultTransactionSynchronizationRegistry',
                    'Rindow\\Messaging\\Service\\Database\\DefaultDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Transaction\\Distributed\\DefaultTransactionSynchronizationRegistry',
                    'Rindow\\Security\\Core\\Authentication\\DefaultSqlUserDetailsManagerDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource',
                    'Rindow\\Security\\Core\\Authentication\\DefaultSqlUserDetailsManagerTransactionBoundary' => 'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultTransactionBoundary',
                ),
                'components' => array(
                    // Distributed DataSource
                    'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource' => array(
                        'class' => 'Rindow\\Database\\Pdo\\Transaction\\Xa\\DataSource',
                        'properties' => array(
                            'config' => array('config'=>'database::connections::default'),
                            'transactionManager' => 'Rindow\\Transaction\\Distributed\\DefaultTransactionManager',
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultTransactionBoundary' => array(
                        'class'=>'Rindow\\Transaction\\Support\\TransactionBoundary',
                        'properties' => array(
                            'transactionManager' => array('ref'=>'Rindow\\Transaction\\Distributed\\DefaultTransactionManager'),
                        ),
                        'proxy' => 'disable',
                    ),

                    // ORM
                    'Rindow\\Database\\Pdo\\Orm\\DefaultCriteriaMapper' => array(
                        'class' => 'Rindow\\Database\\Pdo\\Orm\\CriteriaMapper',
                    ),
                    'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper' => array(
                        'properties' => array(
                            'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource'),
                        ),
                        'scope'=>'prototype',
                        'proxy' => 'disable',
                    ),
                    //'Rindow\\Database\\Pdo\\Orm\\DefaultResource' => array(
                    //    'class' => 'Rindow\\Database\\Pdo\\Orm\\Resource',
                    //    'properties' => array(
                    //        'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource'),
                    //    ),
                    //    'proxy' => 'disable',
                    //),

                    // Messaging
                    //'Rindow\\Messaging\\Service\\Database\\DefaultDestinationResolver' => array(
                    //    'properties' => array(
                    //        'queueDriverClass' => array('value'=>'Rindow\\Database\\Pdo\\Messaging\\QueueDriver'),
                    //    ),
                    //),
                    //  Repository
                    'Rindow\\Database\\Pdo\\Repository\\DefaultRepositoryFactory' => array(
                        'class' => 'Rindow\\Database\\Dao\\Repository\\GenericSqlRepositoryFactory',
                        'properties' => array(
                            'dataSource' => array('value'=>'Rindow\\Database\\Pdo\\Transaction\\Xa\\DefaultDataSource'),
                            'queryBuilder' => array('ref'=>'Rindow\\Database\\Pdo\\Repository\\DefaultQueryBuilder'),
                        ),
                    ),
                    'Rindow\\Database\\Pdo\\Repository\\DefaultQueryBuilder'=>array(
                        'class' => 'Rindow\\Database\\Dao\\Support\\QueryBuilder',
                    ),
                ),
            ),
        );
    }
}
