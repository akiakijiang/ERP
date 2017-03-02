/*
	* 商品详细页显示快递到达时间
	* area_all 展示区域
	* area_select 显示选定的城市
    * shipping_cod
    * shipping_nocod
	
*/
var all_area = [];
var ouku_shipping_data = {};
var current_area = "";
var ouku_region_child = [];
var tip_img = "<img src=\"" + ouku.path.img + "images/target_icon_3.png\" alt=\"向下\"/>";
function on_get_shipping_data_response(data)
{	
    ouku_shipping_data[current_area] = data.region;
		ouku_region_child = data.region_child;
		if(data.type == 2){
     get_child('area_city',data.parent_id);
     $('#change_city').html('[请选择所在市区'+tip_img+']');
		 $('#change_dis').hide().html('[请选择所在县'+tip_img+']'); 				
		}else if(data.type == 3){
			get_child('area_dis',data.parent_id);
		  if(data.region_child != '') $('#change_dis').show().html('[请选择所在区县'+tip_img+']');  
		}
    showShippingData(data.region);
}
function showShippingData(data)
{
		if (data.commend_cod_html){
		  $('#shipping_cod').html(data.commend_cod_html);
	  }else{
			$('#shipping_cod').html('该地区暂不支持货到付款');
		}
		if(data.commend_nocod_html){
			$('#shipping_nocod').html(data.commend_nocod_html);
		}else{
			$('#shipping_nocod').html('该地区暂不支持货到付款先款后货');
		}
    
	total_fee = data.total_fee;
	total_fee_cod = data.total_fee_cod;
	cod_percent = data.cod_percent;
}

function showShippingList(region_id, area_name, type)
{
	  var type = type ? type : 0;
    current_area = area_name;
//    if (ouku_shipping_data[area_name] == undefined)
//    {
      var  url = ouku.path.root + "take_time.php?region_id=" + region_id + "&type=" + type;
//        if(is_biaoju == 1)
//        {
//            url += "&is_biaoju=1";
//        }

		if(typeof(goods_id) == "number"){
			url +='&goods_id=' + goods_id;	
		}
		if(typeof(goodsStyleId) != -1){
			url +='&style_id=' + goodsStyleId;		
		}
        params = {};
        ouku.ajax.call(url, params, on_get_shipping_data_response, "get", "json", true);
//    }else{
//        showShippingData(ouku_shipping_data[area_name]);
//    }
}

function get_child(id,parent_id){
	  var parent_id = parent_id ? parent_id : 0;
	  var html = '';
		var type = 0;
		var len = (id == 'area_all') ? all_area.length : ouku_region_child.length;
		var arr = (id == 'area_all') ? all_area : ouku_region_child;
    var m = 0;
		var n = 0;
		if(id == 'area_all'){
      n = 8;
		}
    if(id == 'area_city' || id == 'area_dis'){
			n = 5;
		}
		if(parent_id == 22){
			n = 3;
		}
    var m = 2*n + 1;      		
		html += '<tr>';
    for(var i=1; i<len+1; i++){ 
        html += '<td><a href="#" id="' + id +'_' + (i-1) + '" region_id="' + arr[i-1].region_id + '">' + arr[i-1].region_name +  '</a></td>';
        if (i % n == 0) {
			    html += '</tr><tr>';
		    }
    }
		html += '</tr>';
    $('#'+id).html('<table cellpadding="0" cellspacing="0">'+html+'</table>');
		
    for(var i = 0; i < len; i++){
      document.getElementById(id+'_'+i).onclick = function(){         
		    if(id == 'area_all'){
		      var change_id = 'change_area';
		      type = 2;
		    }
		    if(id == 'area_city'){
		      var change_id = 'change_city';
		      type = 3;
		    }
		    if(id == 'area_dis'){
		      var change_id = 'change_dis';
					type = 4;
		    }
        $('#'+change_id).html('[' + this.innerHTML + tip_img + ']');				
				$('#'+id).hide();
			  if(id == 'area_all' || id == 'area_city' || id == 'area_dis'){
          showShippingList(this.getAttribute('region_id'), this.innerHTML, type);
        }
        return false;
      } 			
	  }			
}
function showClose(id){
	$('#'+id[0]).mouseover(function(){
	 $('#'+id[1]).show();
	 $('#iframeId').show();
	 var h = $('#'+id[1]).height() > 120 ? $('#'+id[1]).height() : 120;
	 $('#iframeId').height(h);
	}).mouseout(function(){
	 $('#'+id[1]).hide();
	 $('#iframeId').hide();
	})
}
function init_shipping_info()
{
    all_area = JSON.parse(all_area_json);
		ouku_region_child = JSON.parse(area_city_json);
    current_area = $('#change_area').html();
    get_child('area_all');
		get_child('area_city');
    
    showClose(['change_area','area_all']);
		showClose(['change_city','area_city']);
		showClose(['change_dis','area_dis']);

    showClose(['area_all','area_all']);
    showClose(['area_city','area_city']);
    showClose(['area_dis','area_dis']);
		

		$('#change_area').click(function(){
			return false;
		})

}

