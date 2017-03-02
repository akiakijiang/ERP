<?php
define('IN_ECS',true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';


/**
 * BWSHOP RMA Request Processing
 * 
 * @author sinri
 *
 */
class BwshopCustomObserverCommand extends CConsoleCommand{
	private $slave;

	/**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getSlave()
    {
        if(!$this->slave)
        {
            if(($this->slave=Yii::app()->getComponent('slave'))===null){
           		$this->slave=Yii::app()->getDb();
           	}
            $this->slave->setActive(true);
        }
        return $this->slave;
    }

    /**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex()
	{
		// What to do?
		echo "All Green... Vanishment this world!";
	}

	public function actionObserveXRLao($days=10){
		echo "BwshopCustomObserver ObserveXRLao begins at ".date_format(date_create("now"), 'Y-m-d H:i:s').PHP_EOL;
		$group=$this->getData($days);
		print_r($group);
		echo PHP_EOL;
		$filepath=$this->outputExcel($group);
		echo 'output ...'.PHP_EOL;
		$this->send_alert_mail($filepath);
		echo 'Over at '.date_format(date_create("now"), 'Y-m-d H:i:s').PHP_EOL;
	}

	private function getData($days=60,$is_debug=false){
		$sql="SELECT
			boi.order_id bw_order_id,
			boi.order_sn bw_order_sn,
			boi.outer_order_sn,
			bs.shop_id,
			bs.ecs_distributor_id,
			bs.shop_name,
			boi.apply_status,
			boi.shipping_status,
			boi.custom_history
		FROM
			ecshop.bw_order_info boi
		LEFT JOIN ecshop.bw_shop bs ON bs.shop_id = boi.shop_id
		WHERE
			boi.shipping_status = '22'
		AND boi.tracking_number != ''
		AND boi.tracking_number IS NOT NULL
		AND boi.update_time >= DATE_SUB(now(), INTERVAL {$days} DAY)
		";

		$list=$this->getSlave()->createCommand($sql)->queryAll();

		foreach ($list as $key => $bo) {
			$ch=json_decode($bo['custom_history'],true);
			// if($is_debug)echo "[".$bo['bw_order_sn']."]:".PHP_EOL;
			try {
				// if($is_debug)print_r($ch);
				$list[$key]['doc_ready']='';date_format(date_create("now"), 'Y-m-d H:i:s');
				$list[$key]['doc_go']=date_format(date_create("now"), 'Y-m-d H:i:s');
				$list[$key]['goods_go']=date_format(date_create("now"), 'Y-m-d H:i:s');
				$list[$key]['days_inter']=5;
				foreach ($ch['mft']['history'] as $h) {
					if($h['Status']=='22'){
						$list[$key]['doc_go']=$h['CreateTime'];
					}elseif($h['Status']=='24'){
						$list[$key]['goods_go']=$h['CreateTime'];
					}elseif($h['Status']=='11'){
						$list[$key]['doc_ready']=$h['CreateTime'];
					}
				}
				$days_inter=date_diff(date_create($list[$key]['doc_go']),date_create($list[$key]['goods_go']))->format('%R%a');
				$list[$key]['days_inter']=0+$days_inter;

				unset($list[$key]['custom_history']);
			} catch (Exception $e) {
				
			}
			
			// if($is_debug)echo "Doc: ". date_format(date_create($list[$key]['doc_go']), 'Y-m-d H:i:s'). PHP_EOL;
			// if($is_debug)echo "Goods: ". date_format(date_create($list[$key]['goods_go']), 'Y-m-d H:i:s'). PHP_EOL;
			// if($is_debug)echo "INTERVAL: ".$list[$key]['days_inter'].PHP_EOL;
		}

		$group=array(
			'1'=>array(),
			'2'=>array(),
			'3'=>array(),
			'4'=>array(),
			'5'=>array(),
			);

		foreach ($list as $key => $bo) {
			if($bo['days_inter']>=1){
				$sql="SELECT
					eoi.order_sn erp_order_sn,
					f.FACILITY_NAME,
					eoi.order_time,
					FROM_UNIXTIME(eoi.confirm_time) as confirm_time
				FROM
					ecshop.bw_order_info boi
				LEFT JOIN ecshop.bw_shop bs ON bs.shop_id = boi.shop_id
				LEFT JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn = boi.outer_order_sn
				LEFT JOIN romeo.facility f ON f.facility_id = eoi.facility_id
				WHERE
					eoi.taobao_order_sn = '{$bo['outer_order_sn']}'
				";

				$eo=$this->getSlave()->createCommand($sql)->queryAll();
				if(!empty($eo)){
					$bo=array_merge($bo,array(
							'erp_order_sn'=>$eo[0]['erp_order_sn'],
							'facility_name'=>$eo[0]['FACILITY_NAME'],
							'order_time'=>$eo[0]['order_time'],
							'confirm_time'=>$eo[0]['confirm_time'],
						));
				}else{
					$bo=array_merge($bo,array(
							'erp_order_sn'=>'N/A',
							'facility_name'=>'N/A',
							'order_time'=>'N/A',
							'confirm_time'=>'N/A',
						));
				}
			}
			if($bo['days_inter']>=5){
				$group['5'][]=$bo;
			}elseif($bo['days_inter']>=4){
				$group['4'][]=$bo;
			}elseif($bo['days_inter']>=3){
				$group['3'][]=$bo;
			}elseif($bo['days_inter']>=2){
				$group['2'][]=$bo;
			}elseif($bo['days_inter']>=1){
				$group['1'][]=$bo;
			}
		}

		return $group;

	}

	private function outputExcel($group){
		set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
    	require_once 'PHPExcel.php';
    	require_once 'PHPExcel/IOFactory.php'; 
    	require_once 'PHPExcel/Writer/Excel2007.php'; 

    	$out_file=date_format(date_create("now"), 'YmdHis').'.xlsx';

    	// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();

		$sheet_id=-1;
		foreach ($group as $latency => $list) {
			$sheet_id+=1;
			if($latency>1){
				// Create a new worksheet, after the default sheet
				$objPHPExcel->createSheet();
			}

			// Create a first sheet, representing sales data
			$objPHPExcel->setActiveSheetIndex($sheet_id);

			$objPHPExcel->getActiveSheet()->setCellValue('A1', 'ERP订单号');
			$objPHPExcel->getActiveSheet()->setCellValue('B1', '外部订单号');
			$objPHPExcel->getActiveSheet()->setCellValue('C1', '分销商');
			$objPHPExcel->getActiveSheet()->setCellValue('D1', '仓库');
			$objPHPExcel->getActiveSheet()->setCellValue('E1', '订单时间');
			$objPHPExcel->getActiveSheet()->setCellValue('F1', '订单确认时间');
			$objPHPExcel->getActiveSheet()->setCellValue('G1', '申报时间');
			$objPHPExcel->getActiveSheet()->setCellValue('H1', '单证放行时间');
			$objPHPExcel->getActiveSheet()->setCellValue('I1', '发货状态');

			foreach ($list as $row => $rec) {
				if($rec['shipping_status']=='24'){
					$ss='海关货物放行';
				}elseif($rec['shipping_status']=='22'){
					$ss='海关单证放行';
				}else{
					$ss='N/A';
				}
				//setCellValueExplicit('A1', '0029', PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('A'.($row+2), $rec['erp_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.($row+2), $rec['outer_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('C'.($row+2), $rec['shop_name'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('D'.($row+2), $rec['facility_name'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('E'.($row+2), $rec['order_time'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('F'.($row+2), $rec['confirm_time'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('G'.($row+2), $rec['doc_ready'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('H'.($row+2), $rec['doc_go'], PHPExcel_Cell_DataType::TYPE_STRING);
				$objPHPExcel->getActiveSheet()->setCellValueExplicit('I'.($row+2), $ss, PHPExcel_Cell_DataType::TYPE_STRING);
			}

			// Rename sheet
			$objPHPExcel->getActiveSheet()->setTitle("".$latency.'-day Latency');
		}

		$output = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	    $output->setOffice2003Compatibility(true);
	    $output->save('/var/log/bwshop/'.$out_file);
	    return '/var/log/bwshop/'.$out_file;
	}

	private function send_alert_mail($filename){
		exec('php /home/ljni/mailer.php -s "'."跨境发货跟踪报表[".$filename."]".'" -a "'.$filename.'" -p "跨境发货跟踪报表如附件。" -t kj-sh@leqee.com');
	}
}
?>