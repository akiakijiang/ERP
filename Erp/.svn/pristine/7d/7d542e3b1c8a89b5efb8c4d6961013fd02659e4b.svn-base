<?php
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel.php');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel/IOFactory.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

class baseXLS {

	// sheet的内容
    protected $content = array();

    //读取excel中名字的模板 一维数组
    protected $tpls = array(
        'date' => '日期',
        'tracking_number' => '运单号码',
        'weight' => '计费重量',
        'final_fee' => '应收费用',
        'excel_insurance' => '保价费',
    	'remark' =>'备注',
    );

	/**
     * 读取excel内容，将内容写入content变量
     * 默认读取第一个sheet的内容，如果sheet_name不为，则搜索对应的sheet_name
     *
     * @param string $file 上传文件的路径
     * @param string $sheet_name 读取sheet的名字
     */
    public function read_excel($file, $sheet_name = null) { 
        // todo 获取sheet的内容，赋值给$this->content
//    	set_time_limit(0);
        $ext = pathinfo($file, PATHINFO_EXTENSION); //文件的扩展名；函数以数组的形式返回文件路径的信息。
//         die();
        try {
//        	$m1 = memory_get_usage();
            $ext == 'xlsx' ?
            $reader = PHPExcel_IOFactory::createReader('Excel2007') : $reader = PHPExcel_IOFactory::createReader('Excel5') ;
            $reader->setReadDataOnly(true);  // 设置为只读
            list($file,) = preg_split ('/\./', $file);//通过一个正则表达式分隔给定字符串。只获取数组中的第一个值。这里的效果就是去掉文件名的后缀
//             $run_time = new runtime();
//             $run_time->start();
//             $m2 = memory_get_usage();
//             var_dump($m2-$m1);
//             var_dump($m1);
//             var_dump($m2);
            $excel = $reader->load($file);//略慢，可以接受
// 			$run_time->stop();
// 			var_dump( "Script running time:".$run_time->spent()."ms");
//             die();
            
            unset($file);
            unset($reader);
            if ($sheet_name == null) {
                $sheet_name = 0;
                $sheet[$sheet_name] = $excel->getSheet(0);
            } else {
                $sheet[$sheet_name] = $excel->getSheetByName($sheet_name);
            }
            unset($excel);
            if (is_null($sheet[$sheet_name])) {
                return '该excel文件中未找到表' . $sheet_name;
            }
        }catch (Exception $e) {
            return  '读取文件内容失败，请检查该文件格式。详细错误消息:'. $e->getMessage();
        }
        if (empty($this->tpls)) {                //要读取excel表的字段名字
            return '请定义excel模板';
        }
        $i = 0;
//         $run_time = new runtime();
//         $run_time->start();
       
        foreach ($sheet[$sheet_name]->getRowIterator() as $rowIterator) {
            $cellIterator = $rowIterator->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);//读入除了不为空的值
            if ($i == 0) {//i=0的时候，读入的是字段名，下面的才是数据
                $j = 0;
                foreach ($cellIterator as $cell) {
                    $field = trim($cell->getValue());
                    // 空列不取数据
                    if (!empty($field)) {
                        $fields[$j] = $field;
                    }
                    $j++;
                }
                if (count($fields) != count(array_unique($fields))) {
                    return $sheet_name . "表中存在重复的列名";
                }
            } else {
                $j = 0;
                $row = array();
                $empty = true;  // 是否是空行
                foreach ($cellIterator as $cell) {
                    $field = trim($cell->getValue());
                    if (isset($fields[$j])) {
                        $row[$fields[$j]] = $field;
                        if (!empty($field)) {
                            $empty = $empty && false;
                        }
                    }
                    $j++;
                }
                if (!$empty) {
                    $rowset[$sheet_name][] = $row;  // 过滤空行
                }
            }
            unset($cellIterator);
            $i++;
        }
//         $run_time->stop();
//         var_dump( "Script running time:".$run_time->spent()."ms");
//         die();
        $this->content = $rowset;
        if (!$this->check_format()) {
            return "格式不正确";
        } else {
            return true;
        }
    }

    protected function check_format(){
    	return true;
    }

    public function exportContent($file_name){
    	$cell_nos = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    	
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($file_name);
        $sheet_no = 1;

        $sheet_no ++;
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('TEST!');
        $sheet->setCellValue('A1', "日期");
		$sheet->setCellValue('B1', "运单号码");
		$sheet->setCellValue('C1', "快递费备注");

		for($i=2;$i<10;$i++){
			$sheet->setCellValue("A$i", "日期$i");
			$sheet->setCellValue("B$i", "运单号码$i");
			$sheet->setCellValue("C$i", "快递费备注$i");
		}

    	//if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
        //}else{
        //	echo "headers sent";
        //}
    }

    public function testContent(){
    	pp($this->content);
    }
}
?>