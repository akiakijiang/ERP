<?php
define('IN_ECS', true);
require_once('includes/init.php');


/**
 何をしたいとたずねられると
 それをロミオより取り出して
// 取得汇总记录
try {
    $handle = soap_get_client('EduSaleService', 'ROMEO', 'Soap_Client');
    $response = $handle->$method($_SESSION['party_id'], $start, $end, $region, $goods_name, $keyword, $channel, 0, -1);
    $total = is_numeric($response->total) ? $response->total : 0 ;
    $list = wrap_object_to_array($response->result->EduSaleRet);    
} catch (SoapFault $e) {
    # print $e->faultstring;
    $total = 0;
    $list  = array();
}
*/

class EduSaleInfoWorkerItem/*stdObject*/ {
    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
    }

    public function __call($method, $arguments) {
        $arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
}

class EduSaleInfoWorker{

	/**
     * 电教报表的类型，是日报表还是月报表
     * @author qygeng
     * Type := DAY | MONTH
     */

    /**
     * 返回结果的类型 
     * ResultType := DETAIL | SUMMARY
     */

	/**
	String start, String end, String condition, Type type,
            ResultType resultType, String salesChannel
	*/
	private function getSQLString($start, $end, $condition, $type, $resultType, $salesChannel) {
        $sql="";

        //这段sql是将所有的步步高产品的销售订单找出来
        //并且根据天或者月以及发往地区统计数量
        //还要包括用户退货订单的数量
        //同样根据天或者月以及发往地区统计

        // 步步高对应的brandId和topCatId
        $brandId = "51";
        $topCatId = "(1458, 1496)";

        if (empty($start)) {
            $start = "1970-01-01";
        }
        if (empty($end)) {
            $end = "2999-12-31";
        }

        $selectSql = "";
        $groupSql = "";
        if ($resultType == "DETAIL") {
            //详细列表
            $selectSql = "select order_time as _order_time, order_sn, og.goods_name, "
                    . "province, city, district, address, "
                    . "sum(if(i.order_type_id = 'SALE', -d.quantity_on_hand_diff, 0)) as quantity, "
                    . "sum(if(i.order_type_id = 'RMA_RETURN', d.quantity_on_hand_diff, 0)) as return_quantity, "
                    . "i.tel, i.mobile, i.consignee, group_concat(ii.SERIAL_NUMBER) as serial_number ";

            $groupSql = " group by i.order_id, og.goods_id ";
        } else {
            $date = null;
            if ($type == "DAY") {
                $date = "date_format(d.created_stamp, '%Y-%m-%d') ";
            } else if ($type == "MONTH") {
                $date = "date_format(d.created_stamp, '%Y-%m') ";
            }
            // 汇总
            // 115	苏州 152	福州 234	深圳 这几个城市的订单要单独统计
            //TODO 把 27 西藏 的算在 24 四川，31 宁夏和 30 青海的算在 29 甘肃
            $selectSql = "select og.goods_name, i.province as _province, "
                    . " sum(if(i.order_type_id = 'SALE', -d.quantity_on_hand_diff, 0)) "
                    . "  as quantity, "
                    . " sum(if(i.order_type_id = 'RMA_RETURN', d.quantity_on_hand_diff, 0)) "
                    . "  as return_quantity, " . $date . " as _order_time, "
                    . " if(i.city in (115, 152, 234), i.city, 0 ) as _city ";

            $groupSql = " group by og.goods_id, _province, _city, _order_time ";
        }

        $fromSql = " from ecshop.ecs_order_info i "
                . " inner join ecshop.ecs_order_goods og on og.order_id=i.order_id "
                . " left join ecshop.ecs_goods g on g.goods_id=og.goods_id "
                . " left join romeo.inventory_item_detail d "
                . "  on d.order_id=convert(i.order_id using utf8) and d.order_goods_id=convert(og.rec_id using utf8) "
                . " left join romeo.inventory_item ii on ii.inventory_item_id=d.inventory_item_id ";

        $baseCondition = " where " . " ( "
                . "  (i.order_type_id = 'SALE' and d.quantity_on_hand_diff < 0) or "
                . "  (i.order_type_id = 'RMA_RETURN' and d.quantity_on_hand_diff > 0) "
                . " ) and (d.cancellation_flag <> 'Y') and " . " g.top_cat_id in " . $topCatId
                . " and  g.brand_id = " . $brandId . " and " . " ii.status_id in "
                . "   ('INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE') and "
                . " og.rec_id <> '' and " . " og.rec_id is not null and "
                . " ii.LAST_UPDATED_STAMP > '" . $start . "' and " . " d.created_stamp >= '"
                . $start . "' and " . " d.created_stamp < '" . $end . "' ";

        if (!empty($salesChannel)) {
            if ($salesChannel==("DISTRIBUTION")) {
                // 电教品只要不是欧酷的, 分销商不是乐其淘宝的，都是分销
                $baseCondition .= " and (i.party_id = 16 and i.distributor_id != 31)";
            } else if ($salesChannel==("TAOBAO")) {
                $baseCondition .= " and ("
                        . " (i.party_id = 1 and exists(select 1 from ecshop.order_attribute where "
                        . "   order_id = i.order_id and attr_name = 'OUTER_TYPE' and attr_value = 'taobao'))"
                        . " or " . "   (i.party_id = 16 and i.distributor_id = 31)" . " )";
            } else if ($salesChannel==("DANGDANG")) {
                $baseCondition .= " and i.party_id = 1 and exists(select 1 from "
                        . " ecshop.order_attribute where order_id = i.order_id and "
                        . " attr_name = 'OUTER_TYPE' and attr_value = 'xinhuabookstore')";
            }
        }

        $orderSql = " order by _order_time desc";

        $sql = $selectSql . $fromSql . $baseCondition . $condition . $groupSql . $orderSql;

        return $sql;
    }

	/**
	String partyId, String start, String end,
            String province, String goodsName, String condition, String channel, int offset,
            int limit
	*/
    public function getEduSaleDailyInfo($partyId, $start, $end,
            $province, $goodsName, $condition, $channel, $offset,
            $limit) {

        $tempCondition = "";
        if (!empty($province)) {
            $tempCondition .= " and i.province = '" . $province . "' ";
        }
        if (!empty($goodsName)) {
            $tempCondition .= " and og.goods_name = '" . $goodsName . "' ";
        }

        if (!empty($partyId)) {
            $tempCondition .= " and ecshop.func_filter_user_party(i.party_id," . $partyId . ") ";
        }

        $sql="";
        if (empty($condition)) {
            $sql = $this->getSQLString($start, $end, $tempCondition, "DAY", "SUMMARY",
                    $channel);
        } else {
            $cdt = " and (og.goods_name like '%" . $condition . "%') " . $tempCondition;
            $sql = $this->getSQLString($start, $end, $cdt, "DAY", "SUMMARY", $channel);
        }

        global $slave_db;
        $result=$slave_db->getAll($sql);

        // List<EduSaleRet> er = new ArrayList<EduSaleRet>();
        // for (Object[] obj : list) {
        //     EduSaleRet e = new EduSaleRet();
        //     e.setGoodsName(WorkerUtil.getStringValue(obj[0]));
        //     e.setProvince(WorkerUtil.getStringValue(obj[1]));
        //     e.setSaleQuantity(WorkerUtil.getIntegerValue(obj[2]));
        //     e.setReturnQuantity(WorkerUtil.getIntegerValue(obj[3]));
        //     e.setDate(WorkerUtil.getStringValue(obj[4]));
        //     e.setCity(WorkerUtil.getStringValue(obj[5]));
        //     e.setStartDate(start);
        //     e.setEndDate(end);
        //     er.add(e);
        // }

        // result.setResult(er);

        return $result;
    }

    /**
    String partyId, String start, String end,
            String province, String goodsName, String condition, String channel, int offset,
            int limit
    */
    public function getEduSaleMonthInfo($partyId, $start, $end,
            $province, $goodsName, $condition, $channel, $offset,
            $limit) {

        $tempCondition = "";
        if (!empty($province)) {
            $tempCondition .= " and i.province = '" . $province . "' ";
        }

        if (!empty($goodsName)) {
            $tempCondition .= " and og.goods_name = '" . $goodsName . "' ";
        }

        if (!empty($partyId)) {
            $tempCondition .= " and ecshop.func_filter_user_party(i.party_id," . $partyId . ") ";
        }

        $sql="";
        if (empty($condition)) {
            $sql = $this->getSQLString($start, $end, $tempCondition, "MONTH", "SUMMARY",
                    $channel);
        } else {
            $cdt = " and (og.goods_name like '%" . $condition . "%' ) " . $tempCondition;
            $sql = $this->getSQLString($start, $end, $cdt, "MONTH", "SUMMARY", $channel);
        }

        global $slave_db;
        $result=$slave_db->getAll($sql);

        // List<EduSaleRet> er = new ArrayList<EduSaleRet>();
        // for (Object[] obj : list) {
        //     EduSaleRet e = new EduSaleRet();
        //     e.setGoodsName(WorkerUtil.getStringValue(obj[0]));
        //     e.setProvince(WorkerUtil.getStringValue(obj[1]));
        //     e.setSaleQuantity(WorkerUtil.getIntegerValue(obj[2]));
        //     e.setReturnQuantity(WorkerUtil.getIntegerValue(obj[3]));
        //     e.setDate(WorkerUtil.getStringValue(obj[4]));
        //     e.setCity(WorkerUtil.getStringValue(obj[5]));
        //     e.setStartDate(start);
        //     e.setEndDate(end);
        //     er.add(e);
        // }

        // result.setResult(er);

        return $result;
    }

    /**
    String partyId, String start, String end,
            String province, String goodsName, String condition, String channel
    */
    public function getEduSaleDetail($partyId, $start, $end,
            $province, $goodsName, $condition, $channel) {
        $tempCondition = "";
        if (!empty($province)) {
            $tempCondition .= " and i.province = '" . $province . "' ";
        }

        if (!empty($goodsName)) {
            $tempCondition .= " and og.goods_name = '" . $goodsName . "' ";
        }

        if (!empty($condition)) {
            $tempCondition .= " and (og.goods_name like '%" . $condition . "%' ) ";
        }

        if (!empty($partyId)) {
            $tempCondition .= " and ecshop.func_filter_user_party(i.party_id," . $partyId . ") ";
        }

        $sql = $this->getSQLString($start, $end, $tempCondition, "DAY", "DETAIL", $channel);

        global $slave_db;
        $result=$slave_db->getAll($sql);

        // Query query = factory.getCurrentSession().createSQLQuery(sql);
        // List list = query.list();
        // List<EduSaleRetDetail> result = new ArrayList<EduSaleRetDetail>();
        // for (Object o : list) {
        //     Object[] _o = (Object[]) o;
        //     EduSaleRetDetail e = new EduSaleRetDetail();
        //     e.setOrderTime(WorkerUtil.getStringValue(_o[0]));
        //     e.setOrderSn(WorkerUtil.getStringValue(_o[1]));
        //     e.setGoodsName(WorkerUtil.getStringValue(_o[2]));
        //     e.setProvince(WorkerUtil.getStringValue(_o[3]));
        //     e.setCity(WorkerUtil.getStringValue(_o[4]));
        //     e.setDistrict(WorkerUtil.getStringValue(_o[5]));
        //     e.setAddress(WorkerUtil.getStringValue(_o[6]));
        //     e.setSaleQuantity(WorkerUtil.getIntegerValue(_o[7]));
        //     e.setReturnQuantity(WorkerUtil.getIntegerValue(_o[8]));
        //     e.setTel(WorkerUtil.getStringValue(_o[9]));
        //     e.setMobile(WorkerUtil.getStringValue(_o[10]));
        //     e.setConsignee(WorkerUtil.getStringValue(_o[11]));
        //     e.setSerialNumber(WorkerUtil.getStringValue(_o[12]));
        //     result.add(e);
        // }

        return $result;
    }
}

?>