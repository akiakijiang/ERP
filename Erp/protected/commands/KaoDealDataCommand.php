<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'admin/includes/lib_kao_deal.php';
require_once (ROOT_PATH . 'includes/helper/array.php');

class KaoDealDataCommand extends CConsoleCommand {
	
    private $master; // Master数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {

		//处理花王数据
		$this->run(array("DealDataActual"));
		
 		//send_email  （暂时不需要）
 		//$this->run(array("SendEmailActul"));

		
    }

 	/**
 	 * 处理数据
 	 */
 	public function actionDealDataActual() {
		logRecord("DealDataActual begin");
				
        kaoActualDoAction('DEALDATA');
        
        logRecord("DealDataActual end");
	}
	
    /**
     * 生成excel 并  发送email（未开启调度）
     */
    public function actionSendEmailActul() {
    	logRecord(" SendEmailActul begin ");
  
        kaoActualDoAction('SENDEMAIL');
        
 		logRecord("SendEmailActul end");
    }

}	
	

