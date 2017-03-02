<?php

define('IN_ECS', true);
require_once('../../includes/init.php');
require_once("../lib_bw_shop.php");

/*
*   下面是调用接口
*/
$lib_bw_shop = new BWShopAgent();
set_include_path("../../includes/Classes/");
require_once("PHPExcel.php");
require_once("PHPExcel/IOFactory.php");
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['act'])){
    if($_REQUEST['act'] == 'upload_data'){
        $shop_name = $_POST['shop_name'];
        $shop_name_code_key = $lib_bw_shop::shopList();
        foreach ($shop_name_code_key as $key => $value) {
            if(is_array($value)){
                if($value['shop_name'] == $shop_name){
                    $client_id = $value['shop_code'];
                    $client_key = $value['shop_key'];
                }
            }
        }
        $url = "/order/createNewOrderForErp";
        $tmp_name = $_FILES['upload_excel']['tmp_name'];
        $tpl = array('order_sn',
            'amount',
            'post_fee',
            'goods_amount',
            'payment',
            'order_time',
            'trade_trans_no',
            'pay_time',
            'payment_code',
            'mibun_number',
            'name',
            'email',
            'phone',
            'account',
            'consignee',
            'province',
            'city',
            'district',
            'address',
            'receiver_phone'
            );

        $excel_goods_detail_data = array(
            'product_id',
            'quantity',
            'outer_id',
            'amount'
            );

        $bwshop = new BWSHOP_API_AGENT($client_id,$client_key);

        /*
        *   以下部分实现的功能是读取订单信息中除了goods信息之外的
        *
        */
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = $objReader->load($tmp_name);
        $sheet = $objPHPExcel->getSheet(0);
        $Row = $sheet->getHighestRow();
        $Column = $sheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($Column);//总列数
        // if($highestColumnIndex != 24){
        //     echo "请老老实实的按照模板来填写,出现这段话,表示你未参照模板来写,当然,也有可能是你在商品总计金额之后还设置了单元格，这会有问题,请在第一行标题栏的所有列中设置单元格格式";
        //     die();
        // }
        $excel_first_data = array();         //存储前面的几列
        $combine_first_data =array();        
        $finally_first_data = array();     
        $combine_goods_zanshi_data = array();   //暂时存放goods里面的信息
        $combine_goods_data = array();          //  最后存放商品里面的信息
        $good_in_array = array();
        $good_array = array();                  //excel中读出数据的存放点
        $order_sn_array = array();              //存放order_sn的数组
        $order_sn_goods_array = array();
        $output_data = array();
        try{
            header("content-type:text/html; charset=utf-8");
            echo "<h2>上傳訂單結果</h2><p>接口：".$bwshop->getBaseUrl()."</p>";
            if($Row>=2 && $sheet->getCellByColumnAndRow(0, 2)->getValue() != ''){
                for($m=2;$m<=$Row+1;$m++){
                    $order_sn=$sheet->getCellByColumnAndRow(0, $m)->getValue();
                    if($order_sn == ""){
                        if(is_array($combine_goods_data)&& is_array($order_sn_goods_array)){
                            foreach ($order_sn_goods_array as $key => $value) {
                                foreach ($combine_goods_data as $name => $zhi){
                                    if($key == $name){
                                        foreach ($value as $value_key => $value_name) {
                                            $value_name['goods'] = $zhi;
                                            $value_name = json_encode($value_name);
                                            $res=$bwshop->execute($url,$value_name);
                                            $resul = json_decode($res);
                                            if($resul->result == "ok"){
                                                echo "<h3>数据上传成功,如需继续上传,请点击下面的按钮</h3>";
                                                echo "<h4><a href='index.php' style='text-decoration: none;'>继续导单</a></h4>";
                                                echo "<p>order_id:".$resul->order_id."</p>";
                                            }else{
                                                echo "<h3>数据上传失败,请点击按钮返回<h3>";
                                                echo "<h4><a href='javascript :;' onClick='javascript :history.back(-1);' style='text-decoration: none;'>返回上一页</a></h4><br>";
                                                $resul = json_encode($resul);
                                                echo "错误代码：";print_r($resul);
                                                echo "<br><br>未上传成功的订单号：".$name;
                                            }
                                        }
                                    }
                                }   
                            }
                        }else{
                            $output = "这不是一个数组";
                            echo "<script>console.log('".$output."')</script>";
                        }
                        exit();
                    }
                    if (in_array($order_sn,$order_sn_array)){
                        for($n=20;$n<24;$n++){
                            $name = $sheet->getCellByColumnAndRow($n, $m)->getValue();                                   
                            $good_array[$order_sn][$n]=$sheet->getCellByColumnAndRow($n, $m)->getValue();                                   
                        }
                        $combine_goods_zanshi_data= array_combine($excel_goods_detail_data, $good_array[$order_sn]);
                        $combine_goods_data[$order_sn][] = $combine_goods_zanshi_data;
                        // var_dump($combine_goods_data[$order_sn]);die();
                    }else{
                        $order_sn_array[] = $order_sn;
                        for($i=0;$i<20;$i++){
                            $excel_first_data[$i]=$sheet->getCellByColumnAndRow($i, $m)->getValue();
                        }
                        for($n=20;$n<24;$n++){
                            $good_array[$order_sn][$n]=$sheet->getCellByColumnAndRow($n, $m)->getValue();
                        }

                        /*
                        *   这里是将前面的商品信息进行整合
                        */
                        $combine_first_data = array_combine($tpl, $excel_first_data);
                        $order_sn_goods_array[$order_sn][]=$combine_first_data;
                        // var_dump($$order_sn_goods_array[$order_sn]);

                        /*
                        *   这里是将一个对应多个商品进行整合
                        */
                        $combine_goods_zanshi_data= array_combine($excel_goods_detail_data, $good_array[$order_sn]);
                        $combine_goods_data[$order_sn][] = $combine_goods_zanshi_data;
                    }

                        // @$combine_data = array_combine($tpl, $excel_data);         //加一个@符号是为了防止错误显示在客户端
                }
            }else{
                echo "excel文件中未输入任何数据";
                exit;
            }
        }catch(Exception $e){
            print $e->getMessage();
            exit;
        }
    }
}


class BWSHOP_API_AGENT
{
    private $client_id="mock_id";
    private $client_key="mock_key";

    private $base_url="http://testbwshop.leqee.com";
    // private $base_url="http://localhost/Leqee/bonded_warehouse";

    function __construct($in_client_id,$in_client_key)
    {
        $this->client_id=$in_client_id;
        $this->client_key=$in_client_key;

        if(strpos($_SERVER['HTTP_HOST'],'testerp.leqee.com')===0){
            //testerp
            $this->base_url="https://testerpbrand.leqee.com/bwshop";
        }elseif(strpos($_SERVER['HTTP_HOST'],'ecadmin.leqee.com')===0){
            //erp
            $this->base_url="https://erpbrand.leqee.com/bwshop";
        }else{
            //$this->base_url="https://erpbrand.leqee.com/bwshop";
        }
    }

    public function getBaseUrl(){
        return $this->base_url;
    }


/**
* Here, data should be a json string.
* You n generate in the following method.
        * $data=json_encode(array('XX'=>'YY'));
* {"client_id":"CLIENT_ID","data":"{\"PARAMETER\":\"VALUE\"}","checksum":"CHECKSUM"}
**/
    function execute($url,$data){
        $checksum=md5('client_id='.$this->client_id.'&data='.$data.'&client_key='.$this->client_key);
        $body=json_encode(array('client_id'=>$this->client_id,'data'=>$data,'checksum'=>$checksum));

        // echo "<p>".$body."</p>";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url.$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        //ssl
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

}

?>