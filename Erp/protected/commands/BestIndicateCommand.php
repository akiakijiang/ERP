<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once(ROOT_PATH . 'RomeoApi/lib_RMATrack.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
require_once ROOT_PATH . 'admin/includes/lib_best_indicate.php';
require_once ROOT_PATH . 'admin/function.php';
 
//class BestIndicateCommand {
class BestIndicateCommand extends CConsoleCommand {
	
    private $master; // Master数据库    
    private $party_id = 65553;
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {

		//处理入库实绩、自动入库
		$this->run(array("InventoryInActual"));
		
 		//处理出库实绩、自动出库发货
 		$this->run(array("InventoryOutActul"));
		
		//处理退货实绩、自动退货入库
		$this->run(array("InventoryReturnActual"));
		
		//处理退库实绩、自动-gt出库
		$this->run(array("SupplierReturnActual"));
		
		//处理退库实绩被关闭、拒绝-gt申请
		$this->run(array("SupplierClosedActual"));
		
		//处理入库实绩被关闭、废除订单
		$this->run(array("InventoryInClosedActual"));
		
    }

 	/**
 	 * 处理入库实绩、自动入库
 	 */
 	public function actionInventoryInActual() {
		logRecord("InventoryInActual begin");
				
        bestActualDoAction('INVENTORY_IN');
        
        logRecord("InventoryInActual end");
	}
	
    /**
     * 处理出库实绩、自动出库发货
     */
    public function actionInventoryOutActul() {
    	logRecord(" InventoryOutActul begin ");
  
        bestActualDoAction('INVENTORY_OUT');
        
 		logRecord("InventoryOutActul end");
    }
	
	
	/**
	 * 处理退货实绩、自动退货入库
	 */
 	public function actionInventoryReturnActual() {
		logRecord("InventoryReturnActual begin");
		
		bestActualDoAction('INVENTORY_RETURN');
        
 		logRecord("InventoryOutActul end");
	}
	
	
	/**
	 * 处理出库实绩、自动-gt出库
	 */
 	public function actionSupplierReturnActual() {
		logRecord("SupplierReturnActual begin");
		
		bestActualDoAction('SUPPLIER_RETURN');
        
 		logRecord("InventoryOutActul end");
	}

	/**
	 * 处理退库实绩被关闭、拒绝-gt申请
	 */
	public function actionSupplierClosedActual(){
		logRecord("SupplierClosedActual begin");
		
		bestActualClosedDoAction('SUPPLIER_RETURN');
        
 		logRecord("SupplierClosedActual end");
	}
	
	/**
	 * 处理入库实绩被关闭、废除订单
	 */
	public function actionInventoryInClosedActual(){
		logRecord("InventoryInClosedActual begin");
		
		bestActualClosedDoAction('INVENTORY_IN');
        
 		logRecord("InventoryInClosedActual end");
	}
}	
	

