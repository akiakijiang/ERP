<?php

/** 
 * mps 生产系统所用配置文件
 */

// TODO 使用原来的配置文件配置数据源
require_once(realpath(dirname(__FILE__) . '/../../') .'/data/master_config.php');

list($_M_DB_HOST,$_M_DB_PORT)=explode(':', $GLOBALS['mps_db_host']);

return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'MPS System',
    'defaultController' => 'mps',
    'timeZone'=>'Asia/Shanghai',
    'language'=>'zh_cn',

	// preloading 'log' component
	'preload'=>array('log'),

	// autoloading model and component classes
	'import'=>array(
		'application.models.*',
		'application.components.*',
        'application.controllers.MpsCommonController',
        
        'application.modules.rights.*', 
        'application.modules.rights.components.*',
	),

	/*
	'aliases'=>array(
	),
    */
    
	'modules'=>array(
        'gii'=>array(
			'class'=>'system.gii.GiiModule',
			'password'=>'123456',
            'ipFilters' => array('127.0.0.*', '192.168.*.*'),
		), 
        
        'rights'=>array(
            'install'=>true, // Enables the installer. 
            
            'appLayout' => '//layouts/mps',
            'superuserName' => 'superadmin',
            'userClass' => 'MpsUser',
            'userIdColumn'=>'user_id', // Name of the user id column in the database. 
            'userNameColumn'=>'user_name', // Name of the user name column in the database.
        ),
	),

	// 组件
	'components'=>array(
		'user'=>array(
            'class'=>'RWebUser',
            'loginUrl' => array('mps/login'),
            'allowAutoLogin'=>true,
            
            /*
            'class'=>'CWebUser',
			'allowAutoLogin'=>true,
            
            */
		),
		// uncomment the following to enable URLs in path-format
		'urlManager'=>array(
			'urlFormat'=>'path',
			'rules'=>array(
			    //'bc_<barcode:\w+>_<height:\d+>.gif' => 'mps/barcode',
				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
			//'showScriptName' => false,
		),
		// 主数据库
        'db'=>array(
            'class'=>'CDbConnection',
            'connectionString' => 'mysql:host='. $_M_DB_HOST .';port='. $_M_DB_PORT .';dbname='.$GLOBALS['mps_db_name'],
            'username' => $GLOBALS['mps_db_user'],
            'password' => $GLOBALS['mps_db_pass'],
            'emulatePrepare' => true,
            'charset' => 'utf8',
        ),
        
        // 错误处理
		'errorHandler'=>array(
            'errorAction'=>'mps/error',
        ),
        // Log Router
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning, info',
				),
				array(
					'class'=>'CDbLogRoute',
				    'connectionID'=>'db',
				    'logTableName'=>'log',
                    'levels'=>'error, warning',
				),
			),
		),
		// 主缓存（基于数据库）
        'cache'=>array(
            'class'=>'CDbCache',
            'connectionID'=>'db',
            'cacheTableName'=>'cache',
        ),
        // 高速缓存
        'memcache'=>array(
            'class'=>'CFileCache',
            'directoryLevel'=>2,
            /*
            'class'=>'CMemCache',
            'servers'=>array(
                array(
                    'host'=>'server1',
                    'port'=>11211,
                    'weight'=>60,
                ),
                array(
                    'host'=>'server2',
                    'port'=>11211,
                    'weight'=>40,
                )
            ),
            */
        ),
        // 会话,使用dbhttpsession有时候会存不上
        /*
        'session'=>array(
            'class'=>'CDbHttpSession',
            'connectionID'=>'db',
            'sessionTableName'=>'session',
        ),
        */
        // 排它锁
        'lock'=>array(
            'class'=>'application.components.CDbLock',
            'connectionID'=>'db',
        ),
        // 认证管理
        'authManager'=>array(
            'class'=>'RDbAuthManager',
            
            /*
            'class'=>'CDbAuthManager',
            'connectionID'=>'db',
            'itemTable'=>'auth_item',
            'itemChildTable'=>'auth_itemchild',
            'assignmentTable'=>'auth_assignment',
            */
        ),
        // Romeo Web Service
        'romeo'=>array(
            'class'=>'application.components.CSoapClient',
            'wsdlBaseUrl'=>ROMEO_WEBSERVICE_URL,
        ),
        // 邮件服务
		'mail'=>array(
			'class'=>'application.components.CMail',
			'Mailer'=>'mail',
			'From'=>'erp@leqee.com',
			'FromName'=>'乐其网络科技',
        ),
        // 短信服务
        'msg'=>array(
			'class'=>'application.components.CMsg',
			'host'=>$GLOBALS['message_rpc_host'],
			'path'=>$GLOBALS['message_rpc_path'],
			'port'=>$GLOBALS['message_rpc_port'],
        )
    ),

	// application-level parameters that can be accessed
	// using Yii::app()->params['paramName']
	'params'=>array(
		// this is used in contact page
		'adminEmail'=>'ychen@i9i8.com',
	),
);
