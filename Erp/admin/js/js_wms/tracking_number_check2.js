/**
 *  检查快递面单规则及存在性
 *  zxcheng 2013-10-22
 */
//检查运单号是否存在 以及合法性
function check_tracking_number(carrier_id,tracking_number) {
	var result = false;
	var exist = check_tracking_number_exist(tracking_number);
	if(!exist){
	   return result;
	}
	var rule = check_tracking_number_rule(carrier_id,tracking_number);
	if(!rule){
    	alert('提醒：运单号'+tracking_number+' 和发货的类型不匹配！请检查');
		return result;
	}
    return true;
}
function  check_tracking_number_exist(tracking_number){
	 var res = false;
	 $.ajax({
		async:false,
		type: "POST",
        url: "../ajax.php?act=unique_trackingNumber",
        data: "trackingNumber=" + tracking_number,
        dataType: 'json',
        error: function() {
            alert('ajax请求错误,检查面单号失败:' + tracking_number); 
 	     },
        success: function(data){
       	if (data == '') {
       		 res = true; 
         } else {
             alert("运单号:"+ tracking_number+"已存在，请更换运单,重新打印快递单");
         }
        }
	});
	return res;
}
function check_tracking_number_rule(carrier_id,tracking_number){
	 var result = false;
	//检查面单规则
	$.ajax({
		async:false,
		type: 'POST',
		url: '../ajax.php?act=check_tracking_number',
	    data: 'carrier_id=' + carrier_id + '&tracking_number=' + tracking_number,
	    dataType: 'json',
	    error: function() {
           alert('1ajax请求错误,检查面单号规则失败:' + tracking_number); 
	    },
	    success: function(data) {
	    	if(data['error']) {
	    		alert(data['error']);
	    	} else if(data == true){
	    		result = true;
	    	} 
	    }
	});
	return result;
}


/*******************检查面单号 批量版 ************************/

function  check_tracking_numbers_exist(tracking_numbers){
	 var res = false;
	 $.ajax({
		async:false,
		type: "POST",
        url: "../ajax.php?act=unique_trackingNumbers",
        data: "trackingNumbers=" + tracking_numbers,
        dataType: 'json',
        error: function() {
            alert('ajax请求错误,检查面单号失败:' + tracking_numbers); 
 	     },
        success: function(data){
       	if (data == '') {
       		 res = true; 
         } else {
             alert("运单号:"+ data+"已存在，请更换运单,重新打印快递单");
         }
        }
	});
	return res;
}