<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../function.php');

require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');


require_once('../includes/lib_sinri_DealPrint.php');
require_once('../includes/lib_sinri_DataBasic.php');

if(isset($_REQUEST['bpsn'])){
    $BPSN=$_REQUEST['bpsn'];
} else {
    die("Give the parameter BPSN!     *.php?bpsn= ");
}
?>
<html>
    <head>
        <TITLE>SINRI RF BATCH PICKER</TITLE>
        <style type="text/css">
        table,th,td {
            border-collapse: collapse;
        }
        </style>
    </head>
    <BODY>
        <div>
            <?php
            $batch_pick_info=getBatchPick($BPSN);
            echo "<h1>批拣单号 $BPSN [".$batch_pick_info['IS_PICK']."] CREATED ".$batch_pick_info['CREATED_STAMP']." UPDATED ".$batch_pick_info['LAST_UPDATED_STAMP']."</h1>";
            echo "<form method='GET'>
                <input type='text' id='bpsn_input' name='bpsn' value='".$BPSN."'>
                <input type='submit' value='GO'>
            </form>";
            ?>
        </div>
        <div>
            <h2>批拣单和发货单和订单的映射关系</h2>
            <?php
            //pp(get_location_pick_list('130911_0001','1T-A-01-01'));
            $BPSN_MAPPING=get_BPSN_mapping($BPSN);
            ?>
            <h3>订购信息</h3>
            <table border=1 style="text-align:center;">
                <TR>
                    <td>格子号码</td>
                    <td>发货单号</td>
                    <td>拣货状态</td>
                    <td>创建时间</td>
                    <td>更新时间</td>
                    <td>BPM_ID</td>
                    <td>订单ID</td>
                    <td>订单SN</td>
                    <td>订单发货状态</td>
                    <td>发货单状态</td>
                    <td>面单号</td>
                    <td>组织ID</td>
                    <td>组织名称</td>
                    <td>仓库ID</td>
                    <td>仓库名称</td>
                    <td>快递</td>
                    <td>发货单创建者</td>
                    <td>发货单更新者</td>
                </TR>
                <?php
                $shipment_ids=array();
                foreach ($BPSN_MAPPING as $line_no => $oneline) {
                    echo "<tr>";
                    foreach ($oneline as $in_line_key => $in_line_value) {
                        echo "<td>";
                        if($in_line_key=='ORDER_ID'){
                            echo "<a href='../order_check.php?order_id={$in_line_value}' target='new_blank'>{$in_line_value}</a>";
                        }else{
                            echo "$in_line_value";
                            if($in_line_key=='shipment_id'){
                                $shipment_ids[]=$in_line_value;
                            }
                            if($in_line_key=='party_id'){
                                $BPSN_PARTY_ID=$in_line_value;
                            }
                            if($in_line_key=='facility_id'){
                                $BPSN_FACILITY_ID=$in_line_value;
                            }
                        }
                        echo "</td>";
                    }
                    echo "</tr>";      
                }
                ?>
            </table>
            <h3>预订和批拣情况</h3>
            <table border=1>
                <TR>
                    <td>库位</td>
                    <td>发货单</td>

                    <td>货物名称</td>
                    <td>货物ID</td>
                    <td>生产日期</td>
                    <td>货物条码</td>

                    <td>预订量</td>
                    <td>出货量</td>
                    <td>预订时间</td>
                    <td>状态</td>
                    <td>更新时间</td>

                    <td>可用的串号</td>
                </TR>
                <?php
                $ILR=getInventoryLocationReserve($BPSN);
                //pp($ILR);
                foreach ($ILR as $key => $value) {
                    echo "<tr>";
                    echo "<td>".$value['location_barcode']."</td>";
                    echo "<td>".$value['shipment_id']."</td>";

                    echo "<td>".$value['goods_name']."</td>";
                    echo "<td>".$value['product_id']."</td>";
                    echo "<td>".$value['validity']."</td>";
                    echo "<td>".$value['goods_barcode']."</td>";

                    echo "<td>".$value['reserved_quantity']."</td>";
                    echo "<td>".$value['out_quantity']."</td>";
                    echo "<td>".$value['reserved_time']."</td>";
                    echo "<td>".$value['status_id']."</td>";
                    echo "<td>".$value['last_updated_stamp']."</td>";
                    echo "<td>";
                    if($value['is_serial']=='1' || $value['is_serial']=='Y' || $value['is_serial']=='y'){
                        $SNS=getAvailableSN($value['location_barcode'],$value['product_id']);
                        foreach ($SNS as $SN_no => $SN) {
                            echo "$SN<br>";
                        }
                    } else{
                        echo "无需串号";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </table>

            <h2>批拣单和货物的映射关系</h2>
            <?php
            /*
            $ORDERS_OF_SHIPMENTS=array();
            foreach ($shipment_ids as $id => $shipment_id) {
                $ORDERS=get_orders_by_shipment($shipment_id);
                $ORDERS_OF_SHIPMENTS[$shipment_id]=array();
                foreach ($ORDERS as $order_no => $order_array) {
                    $ORDERS_OF_SHIPMENTS[$shipment_id][$order_no]=$order_array['ORDER_ID'];
                }
            }
            //pp($ORDERS_OF_SHIPMENTS);
            //Sinri_GetInfoByProductID($product_id)
            */
            $products=getProductsByBPSN($BPSN);
            ?>
            <h3>库存总量 InventorySummary</h3>
            <TABLE border=1 style="text-align:center;">
                <tr>
                    <th>产品ID</th>
                    <th>商品ID</th>
                    <th>样式ID</th>
                    <th>仓库号</th>
                    <th>库存数量</th>
                    <th>可预订量</th>
                    <th>组织号</th>
                    <th>状态</th>
                    <th>更新时间</th>
                </tr>
                <?php
                    foreach ($products as $product_no => $product_info) {
                        $I_S=getInventorySummary($product_info['PRODUCT_ID'],$BPSN_PARTY_ID,$BPSN_FACILITY_ID);

                        echo "<tr>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['PRODUCT_ID']."</td>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['goods_id']."</td>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['style_id']."</td>";
                        //pp($I_S);
                        echo "<td>".$I_S[0]['FACILITY_ID']."</td>";
                        echo "<td>".$I_S[0]['STOCK_QUANTITY']."</td>";
                        echo "<td>".$I_S[0]['AVAILABLE_TO_RESERVED']."</td>";
                        echo "<td>".$I_S[0]['PARTY_ID']."</td>";
                        echo "<td>".$I_S[0]['STATUS_ID']."</td>";
                        echo "<td>".$I_S[0]['LAST_UPDATED_STAMP']."</td>";
                        echo "</tr>";
                        for ($i=1; $i < sizeof($I_S); $i++) { 
                            echo "<tr>";
                            echo "<td>".$I_S[$i]['FACILITY_ID']."</td>";
                            echo "<td>".$I_S[$i]['STOCK_QUANTITY']."</td>";
                            echo "<td>".$I_S[$i]['AVAILABLE_TO_RESERVED']."</td>";
                            echo "<td>".$I_S[$i]['PARTY_ID']."</td>";
                            echo "<td>".$I_S[$i]['STATUS_ID']."</td>";
                            echo "<td>".$I_S[$i]['LAST_UPDATED_STAMP']."</td>";
                            echo "</tr>";
                        }
                    }
                ?>
            </TABLE>
            <h3>老库存总量 </h3>
            <TABLE border=1 style="text-align:center;">
                <tr>
                    <th>产品ID</th>
                    <th>商品ID</th>
                    <th>样式ID</th>
                    <th>仓库号</th>
                    <th>库存数量</th>
                    <th>可预订量</th>
                    <th>组织号</th>
                    <th>状态</th>
                    <th>更新时间</th>
                </tr>
                <?php
                    foreach ($products as $product_no => $product_info) {
                        $I_S=getInventorySummary($product_info['PRODUCT_ID'],$BPSN_PARTY_ID,$BPSN_FACILITY_ID);

                        echo "<tr>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['PRODUCT_ID']."</td>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['goods_id']."</td>";
                        echo "<td rowspan=".sizeof($I_S).">".$product_info['style_id']."</td>";
                        //pp($I_S);
                        echo "<td>".$I_S[0]['FACILITY_ID']."</td>";
                        echo "<td>".$I_S[0]['STOCK_QUANTITY']."</td>";
                        echo "<td>".$I_S[0]['AVAILABLE_TO_RESERVED']."</td>";
                        echo "<td>".$I_S[0]['PARTY_ID']."</td>";
                        echo "<td>".$I_S[0]['STATUS_ID']."</td>";
                        echo "<td>".$I_S[0]['LAST_UPDATED_STAMP']."</td>";
                        echo "</tr>";
                        for ($i=1; $i < sizeof($I_S); $i++) { 
                            echo "<tr>";
                            echo "<td>".$I_S[$i]['FACILITY_ID']."</td>";
                            echo "<td>".$I_S[$i]['STOCK_QUANTITY']."</td>";
                            echo "<td>".$I_S[$i]['AVAILABLE_TO_RESERVED']."</td>";
                            echo "<td>".$I_S[$i]['PARTY_ID']."</td>";
                            echo "<td>".$I_S[$i]['STATUS_ID']."</td>";
                            echo "<td>".$I_S[$i]['LAST_UPDATED_STAMP']."</td>";
                            echo "</tr>";
                        }
                    }
                ?>
            </TABLE>
            <h3>库位汇总 InventoryLocation</h3>
            <TABLE border=1 style="text-align:center;">
                <tr>
                    <th>产品ID</th>
                    <th>商品ID</th>
                    <th>样式ID</th>
                    <th>仓库号</th>
                    <th>库存数量</th>
                    <th>可预订量</th>
                    <th>组织号</th>
                    <th>状态</th>
                    <th>更新时间</th>
                    <th>货物条码</th>
                    <th>库位条码</th>
                    <th>串号识别</th>
                    <th>生产日期</th>
                </tr>
                <?php
                    foreach ($products as $product_no => $product_info) {
                        $I_L=getInventoryLocation($product_info['PRODUCT_ID'],$BPSN_PARTY_ID,$BPSN_FACILITY_ID);

                        echo "<tr>";
                        echo "<td rowspan=".sizeof($I_L).">".$product_info['PRODUCT_ID']."</td>";
                        echo "<td rowspan=".sizeof($I_L).">".$product_info['goods_id']."</td>";
                        echo "<td rowspan=".sizeof($I_L).">".$product_info['style_id']."</td>";
                        //pp($I_L);
                        //echo "</tr>";
                        
                        echo "<td>".$I_L[0]['facility_id']."</td>";
                        echo "<td>".$I_L[0]['goods_number']."</td>";
                        echo "<td>".$I_L[0]['available_to_reserved']."</td>";
                        echo "<td>".$I_L[0]['party_id']."</td>";
                        echo "<td>".$I_L[0]['status_id']."</td>";
                        echo "<td>".$I_L[0]['last_updated_stamp']."</td>";
                        echo "<td>".$I_L[0]['goods_barcode']."</td>";
                        echo "<td>".$I_L[0]['location_barcode']."</td>";
                        echo "<td>".$I_L[0]['is_serial']."</td>";
                        echo "<td>".$I_L[0]['validity']."</td>";
                        echo "</tr>";
                        for ($i=1; $i < sizeof($I_L); $i++) { 
                            echo "<tr>";
                            echo "<td>".$I_L[$i]['facility_id']."</td>";
                            echo "<td>".$I_L[$i]['goods_number']."</td>";
                            echo "<td>".$I_L[$i]['available_to_reserved']."</td>";
                            echo "<td>".$I_L[$i]['party_id']."</td>";
                            echo "<td>".$I_L[$i]['status_id']."</td>";
                            echo "<td>".$I_L[$i]['last_updated_stamp']."</td>";
                            echo "<td>".$I_L[$i]['goods_barcode']."</td>";
                            echo "<td>".$I_L[$i]['location_barcode']."</td>";
                            echo "<td>".$I_L[$i]['is_serial']."</td>";
                            echo "<td>".$I_L[$i]['validity']."</td>";
                            echo "</tr>";
                        }
                        
                    }
                ?>
            </TABLE>
            <?php

            ?>
        </div>
    </BODY>
</html>