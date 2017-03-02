<?php

require_once(realpath(dirname(__FILE__) . '/../../') .'/data/master_config.php');
$_master_db_host = explode(':', $db_host);
$MASTER_DB_HOST = $_master_db_host[0];
$MASTER_DB_PORT = isset($_master_db_host[1])?$_master_db_host[1]:3306;

$_slave_db_host = explode(':', $slave_db_host);
$SLAVE_DB_HOST = $_slave_db_host[0];
$SLAVE_DB_PORT = isset($_slave_db_host[1])?$_slave_db_host[1]:3306;

return CMap::mergeArray(
	require(dirname(__FILE__).'/main.php'),
	array(
        'modules'=>array(
            'gii'=>array(
            'class'=>'system.gii.GiiModule',
            'password'=>'admin',
            // 'ipFilters'=>array(...a list of IPs...),
            // 'newFileMode'=>0666,
            // 'newDirMode'=>0777,
            ),
        ),
		'components'=>array(
			'fixture'=>array(
				'class'=>'system.test.CDbFixtureManager',
			),
	        // master数据库连接
	        'db'=>array(
	            'class'=>'CDbConnection',
	            'connectionString' => 'mysql:host='. $MASTER_DB_HOST .';port='. $MASTER_DB_PORT .';dbname='.$db_name,
	            'username' => $db_user,
	            'password' => $db_pass,
	            'emulatePrepare' => true,
	            'charset' => 'utf8',
	        ),
	        // slave数据库连接
	        'slave'=>array(
	            'class'=>'CDbConnection',
	            'connectionString' => 'mysql:host='. $SLAVE_DB_HOST .';port='. $SLAVE_DB_PORT .';dbname='.$slave_db_name,
	            'username' => $slave_db_user,
	            'password' => $slave_db_pass,
	            'emulatePrepare' => true,
	            'charset' => 'utf8',
	        ),
		),
	)
);
