<?php
namespace Rindow\Database\Pdo;

class LocalTxModule
{
    public function getConfig()
    {
        return array(
            'aop' => array(
                'plugins' => array(
                    'Rindow\\Transaction\\Support\\AnnotationHandler'=>true,
                ),
                'transaction' => array(
                    'defaultTransactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                    'managers' => array(
                        'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager' => array(
                            'transactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                            'advisorClass' => 'Rindow\\Transaction\\Support\\TransactionAdvisor',
                        ),
                    ),
                ),
            ),

            'container' => array(
                'aliases' => array(
                    'Rindow\\Database\\Dao\\DefaultSqlDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource',
                    'Rindow\\Persistence\\OrmShell\\DefaultCriteriaMapper' => 'Rindow\\Database\\Pdo\\Orm\\DefaultCriteriaMapper',
                    'Rindow\\Persistence\\OrmShell\\Repository\\DefaultQueryBuilder' => 'Rindow\\Database\\Pdo\\Repository\\DefaultQueryBuilder',
                    //'Rindow\\Persistence\\OrmShell\\DefaultResource'       => 'Rindow\\Database\\Pdo\\Orm\\DefaultResource',
                    'Rindow\\Persistence\\OrmShell\\Transaction\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionSynchronizationRegistry',
                    'Rindow\\Messaging\\Service\\Database\\DefaultDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionManager' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager',
                    'Rindow\\Messaging\\Service\\Database\\DefaultTransactionSynchronizationRegistry' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionSynchronizationRegistry',
                    'Rindow\\Security\\Core\\Authentication\\DefaultSqlUserDetailsManagerDataSource' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource',
                    'Rindow\\Security\\Core\\Authentication\\DefaultSqlUserDetailsManagerTransactionBoundary' => 'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionBoundary',
                ),
                'components' => array(
                    //
                    //  ***** Transactional EntityManager and DataSource for Local transaction ******
                    //
                    'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource' => array(
                        'class' => 'Rindow\\Database\\Pdo\\Transaction\\Local\\DataSource',
                        'properties' => array(
                            'config' => array('config'=>'database::connections::default'),
                            'transactionManager' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager'),
                            'connectionClass' => array('value'=>'Rindow\\Database\\Pdo\\Transaction\\Local\\Connection'),
                            // === for debug options ===
                            //'debug' => array('value'=>true),
                            //'logger' => array('ref'=>'Logger'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager' => array(
                        'class'=>'Rindow\\Transaction\\Local\\TransactionManager',
                        'properties' => array(
                            //'useSavepointForNestedTransaction' => array('value'=>true),
                            // === for debug options ===
                            //'debug' => array('value'=>true),
                            //'logger' => array('ref'=>'Logger'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionBoundary' => array(
                        'class'=>'Rindow\\Transaction\\Support\\TransactionBoundary',
                        'properties' => array(
                            'transactionManager' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager'),
                        ),
                        'proxy' => 'disable',
                    ),
                    'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionSynchronizationRegistry' => array(
                        'class'=>'Rindow\\Transaction\\Support\\TransactionSynchronizationRegistry',
                        'properties' => array(
                            'transactionManager' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager'),
                        ),
                        'proxy' => 'disable',
                    ),
                    //'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionAdvisor'=>array(
                    //    'class' => 'Rindow\\Transaction\\Support\\TransactionAdvisor',
                    //    'properties' => array(
                    //        'transactionManager' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultTransactionManager'),
                    //    ),
                    //),
                    /*
                     *  ORM "OrmShell"
                     */
                    'Rindow\\Database\\Pdo\\Orm\\DefaultAbstractMapper' => array(
                        'properties' => array(
                            'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource'),
                        ),
                        //'scope'=>'prototype',
                        'proxy' => 'disable',
                    ),
                    //'Rindow\\Database\\Pdo\\Orm\\DefaultResource' => array(
                    //    'class' => 'Rindow\\Database\\Pdo\\Orm\\Resource',
                    //    'properties' => array(
                    //        'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource'),
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
                     *  Repository
                     *
                    'Rindow\\Database\\Pdo\\Repository\\DefaultRepositoryFactory' => array(
                        'class' => 'Rindow\\Database\\Dao\\Repository\\GenericSqlRepositoryFactory',
                        'properties' => array(
                            'dataSource' => array('ref'=>'Rindow\\Database\\Pdo\\Transaction\\DefaultDataSource'),
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

            //'database'=>array(
            //    'connections'=>array(
            //        'default' => array(
            //            'your_database' => 'configurations',
            //        ),
            //    ),
            //),
        );
    }
}
