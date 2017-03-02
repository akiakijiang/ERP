/**
 * 由于筛选有多个页面，所以公用函数放在这里
 * ljzhou 2013-11-19
 */


/**
 * 检查是否存在未预定成功的合并订单
 * @shipment_ids ','号连接的字符串
 */ 
function check_merge_order_no_reserved(shipment_ids) {
	 var result = false;
     $.ajax({
        mode: 'abort',
        async : false,
        type: 'POST',
        dataType: 'json',
        url : 'ajax.php?act=check_merge_order_no_reserved', 
        data: 'shipment_ids=' + shipment_ids,
        success: function(data) {
	       	if(data.success){
	       		result = true;
	       	}
        },
        error: function() {
        	alert('检查批拣单的库位总预订数是否足够多时ajax请求错误shipment_ids:' + shipment_ids);
        	result = false;
        }
     });
     return result;
}