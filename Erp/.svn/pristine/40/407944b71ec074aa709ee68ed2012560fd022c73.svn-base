<?php
/*
在公元2013年8月，乐其ERP杭州的一伙人折腾了一下仓库。
原来的代码礼崩乐坏，故大鲵着手重整朝纲。

All Hail Sinri Edogawa!

@author Sinri Edogawa ljni@i9i8.com
@version Koiato
@updated 20130802
@updated 20130809
@updated 20130904
*/

require_once('init.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');
require_once(ROOT_PATH . 'admin/function.php');

function Goods_getBasicInfo_byGoodsId($goods_id){
  global $db;
  $sql="SELECT
          goods_id,
          goods_party_id,
          cat_id,
          goods_name,
          goods_sn,
          barcode,
          is_maintain_warranty
        FROM
          ecshop.ecs_goods
        WHERE
          ecshop.ecs_goods.goods_id = '$goods_id';";
  return $db->getAll($sql);
}

function get_BPSN_mapping($BPSN){
  global $db;
  $sql="SELECT
          T2.*, 
          ecshop.ecs_order_info.order_sn,
          ecshop.ecs_order_info.shipping_status,
          romeo.shipment.status,
          romeo.shipment.tracking_number,
          ecshop.ecs_order_info.party_id,
          romeo.party. NAME AS PARTY_NAME,
          ecshop.ecs_order_info.facility_id,
          romeo.facility.FACILITY_NAME,
          ecshop.ecs_carrier.name AS carrier,
          romeo.shipment.CREATED_BY_USER_LOGIN as shipment_creater,
          romeo.shipment.LAST_MODIFIED_BY_USER_LOGIN as shipment_modifier
        FROM
          (
            SELECT
              T1.grid_id,
              T1.shipment_id,
              if(T1.is_pick='Y','已批拣','未批拣')as is_pick,
              T1.created_stamp,
              T1.last_updated_stamp,
              T1.batch_pick_mapping_id,
              romeo.order_shipment.ORDER_ID
            FROM
              (
                SELECT
                  batch_pick_mapping.batch_pick_sn,
                  batch_pick_mapping.shipment_id,
                  batch_pick_mapping.grid_id,
                  batch_pick_mapping.is_pick,
                  batch_pick_mapping.created_stamp,
                  batch_pick_mapping.last_updated_stamp,
                  batch_pick_mapping.batch_pick_mapping_id
                FROM
                  romeo.batch_pick_mapping
                WHERE
                  romeo.batch_pick_mapping.batch_pick_sn = '$BPSN'
              ) AS T1
            LEFT JOIN romeo.order_shipment ON romeo.order_shipment.SHIPMENT_ID = T1.shipment_id
          ) AS T2
        LEFT JOIN ecshop.ecs_order_info ON ecshop.ecs_order_info.order_id = T2.order_id
        LEFT JOIN romeo.party ON romeo.party.PARTY_ID = ecshop.ecs_order_info.party_id
        LEFT JOIN romeo.facility ON romeo.facility.FACILITY_ID = ecshop.ecs_order_info.facility_id
        LEFT JOIN romeo.shipment ON romeo.shipment.shipment_id = T2.shipment_id
        LEFT JOIN ecshop.ecs_carrier ON romeo.shipment.carrier_id = ecshop.ecs_carrier.carrier_id
          ";
  $res=$db->getAll($sql);
  return $res;
}

function get_orders_by_shipment($shipment_id){
  global $db;
  $sql="SELECT
          ORDER_ID
        FROM
          romeo.order_shipment
        WHERE
          romeo.order_shipment.SHIPMENT_ID = '$shipment_id'
  ";
  $res=$db->getAll($sql);
  return $res;
}

function getProductsByBPSN($BPSN){
  global $db;
  $sql="SELECT DISTINCT
          PRODUCT_ID,T3.goods_id,T3.style_id
        FROM
          (
            SELECT
              ecshop.ecs_order_goods.goods_id,
              ecshop.ecs_order_goods.style_id
            FROM
              (
                SELECT
                  romeo.order_shipment.ORDER_ID
                FROM
                  (
                    SELECT
                      romeo.batch_pick_mapping.shipment_id
                    FROM
                      romeo.batch_pick_mapping
                    WHERE
                      romeo.batch_pick_mapping.batch_pick_sn = '$BPSN'
                  ) AS T1
                LEFT JOIN romeo.order_shipment ON romeo.order_shipment.SHIPMENT_ID = T1.shipment_id
              ) AS T2
            LEFT JOIN ecshop.ecs_order_goods ON T2.ORDER_ID = ecshop.ecs_order_goods.order_id
          ) AS T3
        LEFT JOIN romeo.product_mapping ON T3.goods_id = romeo.product_mapping.ECS_GOODS_ID
        AND T3.style_id = romeo.product_mapping.ECS_STYLE_ID";
  $res=$db->getAll($sql);
  $result=array();
  foreach ($res as $key => $value) {
    $result[]=$value;
  }
  return $result;
}

function getInventorySummary($PRODUCT_ID,$PARTY_ID,$FACILITY_ID){
  global $db;
  $sql="SELECT
          inventory_summary.FACILITY_ID,
          inventory_summary.CONTAINER_ID,
          inventory_summary.PRODUCT_ID,
          inventory_summary.STOCK_QUANTITY,
          inventory_summary.AVAILABLE_TO_RESERVED,
          inventory_summary.CREATED_STAMP,
          inventory_summary.LAST_UPDATED_STAMP,
          inventory_summary.LAST_UPDATED_TX_STAMP,
          inventory_summary.CREATED_TX_STAMP,
          inventory_summary.COMMENTS,
          inventory_summary.CURRENCY_UOM_ID,
          inventory_summary.UOM_ID,
          inventory_summary.OWNER_PARTY_ID,
          inventory_summary.PARTY_ID,
          inventory_summary.UNIT_COST,
          inventory_summary.INVENTORY_SUMMARY_ID,
          inventory_summary.STATUS_ID
        FROM
          romeo.inventory_summary
        WHERE
          romeo.inventory_summary.PRODUCT_ID = '$PRODUCT_ID'
     -- AND romeo.inventory_summary.PARTY_ID='$PARTY_ID'
        AND romeo.inventory_summary.FACILITY_ID = '$FACILITY_ID'
        AND romeo.inventory_summary.STOCK_QUANTITY <> 0;
  ";
  // Qlog::log('getInventorySummary SQL: '.$sql);
  $res=$db->getAll($sql);
  return $res;
}

function getInventoryLocation($PRODUCT_ID,$PARTY_ID,$FACILITY_ID){
  global $db;
  $sql="SELECT
          inventory_location.inventory_location_id,
          inventory_location.is_serial,
          inventory_location.goods_barcode,
          inventory_location.product_id,
          inventory_location.goods_number,
          inventory_location.available_to_reserved,
          inventory_location.validity,
          inventory_location.party_id,
          inventory_location.facility_id,
          inventory_location.status_id,
          inventory_location.action_user,
          inventory_location.created_stamp,
          inventory_location.last_updated_stamp,
          inventory_location.location_barcode
        FROM
          romeo.inventory_location
        WHERE
          romeo.inventory_location.PRODUCT_ID = '$PRODUCT_ID'
        AND romeo.inventory_location.PARTY_ID = '$PARTY_ID'
        AND romeo.inventory_location.FACILITY_ID = '$FACILITY_ID'
        AND romeo.inventory_location.goods_number <> 0
  ";
  $res=$db->getAll($sql);
  return $res;
}

function getInventoryLocationReserve_old($BPSN){
  global $db;
  $sql="SELECT
          T1.*, romeo.inventory_location.location_barcode
        FROM
          (
            SELECT
              inventory_location_reserve.inventory_location_reserve_id,
              inventory_location_reserve.inventory_location_id,
              inventory_location_reserve.batch_pick_sn,
              inventory_location_reserve.shipment_id,
              inventory_location_reserve.reserved_quantity,
              inventory_location_reserve.out_quantity,
              inventory_location_reserve.reserved_time,
              inventory_location_reserve.status_id,
              inventory_location_reserve.created_stamp,
              inventory_location_reserve.last_updated_stamp
            FROM
              romeo.inventory_location_reserve
            WHERE
              romeo.inventory_location_reserve.batch_pick_sn = '$BPSN'
          ) AS T1
        LEFT JOIN romeo.inventory_location ON romeo.inventory_location.inventory_location_id = T1.inventory_location_id
  ";
  $res=$db->getAll($sql);
  return $res;
}

function getInventoryLocationReserve($BPSN){
  global $db;
  $sql="SELECT
          reserve.shipment_id,
          CONCAT(
            goods.goods_name,
            ' ',
            IF (
              goods_style.goods_color = ''
              OR goods_style.goods_color IS NULL,
              ifnull(style.color, ''),
              ifnull(goods_style.goods_color, '')
            )
          ) AS goods_name,
          il.location_barcode,
          il.product_id,
          il.goods_barcode,
          il.validity,
          reserve.reserved_quantity,
          reserve.out_quantity,
          reserve.status_id,
          reserve.reserved_time,
          reserve.last_updated_stamp,
          il.is_serial
        FROM
          romeo.inventory_location_reserve AS reserve
        LEFT JOIN romeo.inventory_location AS il ON il.inventory_location_id = reserve.inventory_location_id
        LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID
        LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
        LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.is_delete=0
        AND goods_style.goods_id = mapping.ECS_GOODS_ID
        LEFT JOIN ecshop.ecs_style AS style ON style.style_id = mapping.ECS_STYLE_ID
        WHERE
          batch_pick_sn = '$BPSN'
  ";
  $res=$db->getAll($sql);
  return $res;
}

function getBatchPick($BPSN){
  global $db;
  $sql="SELECT
          -- batch_pick.batch_pick_id,
          -- batch_pick.batch_pick_sn,
          batch_pick.is_pick,
          -- batch_pick.action_user,
          batch_pick.created_stamp,
          batch_pick.last_updated_stamp
        FROM
          romeo.batch_pick
        WHERE
          romeo.batch_pick.batch_pick_sn = '$BPSN'
        LIMIT 1
  ";
  $res=$db->getAll($sql);
  $result=array(
    'IS_PICK'=>$res[0]['is_pick'],
    'CREATED_STAMP'=>$res[0]['created_stamp'],
    'LAST_UPDATED_STAMP'=>$res[0]['last_updated_stamp']
  );
  return $result;
}

function getAvailableSN($location_barcode,$product_id){
  global $db;
  $sql="SELECT
          romeo.location_barcode_serial_mapping.serial_number
        FROM
          romeo.location_barcode_serial_mapping
        WHERE
          romeo.location_barcode_serial_mapping.location_barcode = '$location_barcode'
        AND romeo.location_barcode_serial_mapping.product_id = '$product_id'
        AND romeo.location_barcode_serial_mapping.goods_number>0
  ";
  $res=$db->getAll($sql);
  $SNS=array();
  foreach ($res as $key => $value) {
    $SNS[]=$value['serial_number'];
  }
  return $SNS;
}

?>