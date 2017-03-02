<?php

// TODO 使用原来的配置文件配置数据源
require_once(realpath(dirname(__FILE__) . '/../../') .'/data/master_config.php');

list($_M_DB_HOST,$_M_DB_PORT)=explode(':', $GLOBALS['db_host']);
list($_S_DB_HOST,$_S_DB_PORT)=explode(':', $GLOBALS['slave_db_host']);

/**
 * Console应用使用的配置文件
 */
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Leqee ERP Console',
    'timeZone'=>'Asia/Shanghai',
    'language'=>'zh_CN',
    
    // preload for attachEventHandler to logger
    'preload'=>array('log'),

	'import'=>array(
		'application.models.*',
		'application.components.*',
        'application.services.*',
	),
	
    // application components
    'components'=>array(
        'db'=>array(
            'class'=>'CDbConnection',
            'connectionString'=>'mysql:host='. $_M_DB_HOST .';port='. $_M_DB_PORT .';dbname='.$GLOBALS['db_name'],
            'username'=>$GLOBALS['db_user'],
            'password'=>$GLOBALS['db_pass'],
            'emulatePrepare'=>true,
            'charset'=>'utf8',
        ),
        
        'slave'=>array(
            'class'=>'CDbConnection',
            'connectionString'=>'mysql:host='. $_S_DB_HOST .';port='. $_S_DB_PORT .';dbname='.$GLOBALS['slave_db_name'],
            'username'=>$GLOBALS['slave_db_user'],
            'password'=>$GLOBALS['slave_db_pass'],
            'emulatePrepare'=>true,
            'charset'=>'utf8',
        ),
        
        'log'=>array(
            'class'=>'CLogRouter',
            'routes'=>array(
                array(
                    'class'=>'CFileLogRoute',
                	'logFile'=>'console.log',
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
        
        // 排它锁
        'lock'=>array(
            'class'=>'application.components.CDbLock',
            'connectionID'=>'db',
        ),
        
		// 主缓存（基于文件缓存）-by baihe
        'cache'=>array(
            //'class'=>'CDbCache',
            //'connectionID'=>'db',
            //'cacheTableName'=>'cache',
            'class'=>'CFileCache',
            'directoryLevel'=>2,
        ),
        
        // Romeo Web Service
        'romeo'=>array(
            'class'=>'application.components.CSoapClient',
            'wsdlBaseUrl'=>ROMEO_WEBSERVICE_URL,
        ),
        
        // ErpSync Web Service
        'erpsync'=>array(
            'class'=>'application.components.CSoapClient',
            'wsdlBaseUrl'=>ERPSYNC_WEBSERVICE_URL,
        ),   
        
        // erptaobaosync web service 
        'syncJushita'=>array(
            'class'=>'application.components.CSoapClient',
            'wsdlBaseUrl'=>SYNCJUSHITA_WEBSERVICE_URL,
        ),  
        
        // Romeo Web Service
        'crm'=>array(
            'class'=>'application.components.CSoapClient',
            'wsdlBaseUrl'=>CRM_WEBSERVICE_URL,
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
        'emailUsername'=>$GLOBALS['emailUsername'],
        'emailPassword'=>$GLOBALS['emailPassword'],
    ),
);
