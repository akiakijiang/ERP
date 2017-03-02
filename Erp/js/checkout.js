function E(id) {return document.getElementById(id);}
function V(id) {return document.getElementById(id).value;}
function Trim(str) {return str.replace(/(^\s*)|(\s*$)/g, "");}

var temp = {};
var checkS = 0;
var cod_info = '<p>货到付款是通过第三方快递公司来进行配送和收费，使用货到付款的顾客，需要先按订单金额，先付款给快递员，然后当其面开箱验货，如当场发现包裹有坏损或配件缺失，请立刻致电欧酷网客服4008-206-206反馈，欧酷客服会立即给予处理答复。（快递公司只对快递本身的及时性、安全性负责。因此，如快递员不允许您在签收邮件前开箱检查，请理解这是快递公司对货到付款服务的规定）</p>';
var bank_info = '<p>由于银行系统转账需要时间，因此我们会在1-2个工作日内确认您的汇款。请在电汇单上用途栏中注明订单号及用户名（非常重要！），并保证汇款人姓名和订单收货人姓名一致。（因为银行方面的原因，您留下的订单号可能无法在电汇单上显示出来，这样将造成我们无法查到订单号以致延误订单发出）</p>';
var post_info = '<p>由于邮局系统转账需要时间，因此付款确认需要3-5个工作日确认您的汇款，请在汇款单上用途栏中注明订单号及用户名（非常重要！），并保证汇款人姓名和订单收货人姓名一致。（因为邮政方面的原因，您留下的订单号可能无法在电汇单上显示出来，这样将造成我们无法查到订单号以致延误订单发出）。</p>';

function encodeHtml(text) {
  text = text.replace(/&/g, "&amp;");
  text = text.replace(/</g, "&lt;");
  text = text.replace(/>/g, "&gt;");
  text = text.replace(/\n/g, "<br>");
  return text;
}


function writeAddressList() {
  if (addresses_list.length == 0) return;
  var html = "<p class=addresses>您也可以从已保存的地址列表中选择<a href='"+ouku.path.root+"member/address.php' target='blank'>[管理收货地址]</a></p><ul style='margin-left:20px;'>";
  for (var i = 0; i < addresses_list.length; ++i) {
    html += "<li style='height:25px;line-height:25px;'><input type=radio name=seAddress id=a"+i+" onclick=\"saveAddress("+addresses_list[i].address_id+")\" /><label for=a"+i+">姓名：";
    html += addresses_list[i].consignee;
    html += "&nbsp地址：";
    if (addresses_list[i].province_name == addresses_list[i].city_name) {
      html += addresses_list[i].province_name;
    } else {
      html += addresses_list[i].province_name + "&nbsp;" + addresses_list[i].city_name;
    }
    if (addresses_list[i].district_name != "") html += "&nbsp;" + addresses_list[i].district_name;
    html += "&nbsp;" + addresses_list[i].address;

/*    html += "&nbsp;邮编：";
    html += addresses_list[i].zipcode;*/
    html += "&nbsp电话：";

    if (addresses_list[i].mobile != "") html += "&nbsp手机："+addresses_list[i].mobile +"&nbsp;";
 /*   if (addresses_list[i].tel != "") html += "&nbsp座机："+addresses_list[i].tel +"&nbsp;";*/

/*    if (addresses_list[i].email != "") html += "&nbspEmail：" + addresses_list[i].email;*/

    html += "</label></li>";
  }
  html +="</ul>"
  E("div_address_list").innerHTML = html;
}

function getAddress(id) {
  for (var i = 0; i < addresses_list.length; ++i) {
    if (addresses_list[i].address_id == id) return addresses_list[i];
  }
}

function modifyAddress(open) {
  var hidden = E("div_modify_address").style.display=='none';
  if (open == true) hidden = true;
  else if (open == false) hidden = false;
  E("link_modify_address").innerHTML = hidden?"[取消]":"[添加/修改]";
  E("link_modify_address").opened = hidden? "1" : "0";
  E("div_modify_address").style.display=  hidden?"":"none";
  E("div_current_address").style.display=  hidden?"none":"";
  E("okIcon").className = hidden?"alertIcon":"isOkIcon";
  temp["country"] = V("selCountries_0");
  temp["province"] = V("selProvinces_0");
  temp["city"] = V("selCities_0");
  temp["district"] = V("selDistricts_0");
}


function checkAddress() {
  var country  = E("selCountries_0");
  var province  = E("selProvinces_0");
  var city  = E("selCities_0");
  var district  = E("selDistricts_0");
  var zipcode = E("zipcode");
  var consignee = E('consignee');
  var tel = E('tel');
  var mobile = E('mobile');
  var address = E('address');
  var email = E('email');
  if(consignee.value == "")
  {
      alert("请输入收货人");
  	  modifyAddress(true);
      consignee.focus();
      return false;
  }
  if (province.value == 0) {
  	alert("请选择省份");
  	modifyAddress(true);
  	province.focus();
   	return false;
  }

  if (city.value == 0) {
  	alert("请选择城市");
  	modifyAddress(true);
   	city.focus();
   	return false;
  }
  if (district && district.style.display=="" && district.value == 0) {
  	alert("请选择区");
  	modifyAddress(true);
   	district.focus();
   	return false;
  }
  if(address.value == '') {
  	alert("请输入详细地址");
  	modifyAddress(true);
   	address.focus();
   	return false;
  }  
  
  mobile.value = mobile.value.replace(" ", "");
  
  if(mobile.value == "" && tel.value== "")
  {
      alert("请务必在电话、手机、小灵通当中选一个作为联系方式");
  	  modifyAddress(true);
      mobile.focus();
      return false;
  }
  
  if( ((mobile.value.indexOf('13') == 0) || (mobile.value.indexOf('15') == 0)) && mobile.value.length != 11 ) {
      alert("手机号码以13或15开头总共11位，请检查您的手机号码。");
  	  modifyAddress(true);
      mobile.focus();
      return false;	  
  }

  if(!email.value)
  {
  	alert("请输入Email");
  	modifyAddress(true);
   	email.focus();
   	return false;
  }
 
  return true;
}

function saveAddressToServer(id){
    var url = ouku.path.root +  "User.Controller.php?Action=act_edit_address_ajax";
    var params = {
		"consignee" : V("consignee"),
		"country" : V("selCountries_0"),
		"province" : V("selProvinces_0"),
		"city" : V("selCities_0"),
		"district" : V("selDistricts_0"),
		"address" : V("address"),
		"zipcode" : V("zipcode"),
		"mobile" : V("mobile"),
		"tel" : V("tel"),
		"email" : V("email")
    };
		var str = JSON.stringify(params);
    function on_response(result){
    	if(result == '0')
    	{
    		alert('保存错误.');
    	}
    	else if(result == '1')
    	{
    		//alert('保存成功.');
    	}
    }

	ouku.ajax.call(url, {JSON:str}, on_response, "POST", "JSON");
}

function saveAddress(id) {
  if (id == 0) {
    if (!checkAddress()) {
		checkS = 1;
		return;
	}
    var country  = E("selCountries_0");
    var province  = E("selProvinces_0");
    var city  = E("selCities_0");
    var district  = E("selDistricts_0");

	if(checkS == 1){
		checkShipping(country.value, province.value, city.value, district.value);
	}
	if (temp["country"] != country.value || temp["province"] != province.value || temp["city"] != city.value || temp["district"] != district.value) {
		  checkShipping(country.value, province.value, city.value, district.value);
	}
	checkS = 0;
    E("current_consignee").innerHTML = V("consignee");

    var html = province.options[province.selectedIndex].text + "&nbsp;";
    if (province.options[province.selectedIndex].text != city.options[city.selectedIndex].text) html += city.options[city.selectedIndex].text + "&nbsp;";
    if (district.value > 0)
      html += district.options[district.selectedIndex].text + "&nbsp;";
    html += V("address");

    E("current_address").innerHTML = html;
    E("current_zipcode").innerHTML = V("zipcode");
    E("current_email").innerHTML = V("email");
    var html = "";
    if (V("mobile") != "") html += "手机：" + V("mobile") + "&nbsp;";
    if (V("tel") != "") html += "座机：" + V("tel");
    E("current_phone").innerHTML = html;
  } else {
    var address = getAddress(id);
    E("current_consignee").innerHTML = address.consignee;

    var html = address.province_name + "&nbsp;";
    if (address.province_name != address.city_name) html += address.city_name + "&nbsp;";
    if (address.district_name != "")  html += address.district_name + "&nbsp;";
    html += address.address;

    E("current_address").innerHTML = html;
    E("current_zipcode").innerHTML = address.zipcode;
    E("current_email").innerHTML = address.email;
    var html = "";
    if (address.mobile != "") html += "手机：" + address.mobile + "&nbsp;";
    if (address.tel != "") html += "座机：" + address.tel;
    E("current_phone").innerHTML = html;


    var province  = E("selProvinces_0");
    var city  = E("selCities_0");
    var district  = E("selDistricts_0");

    var changed = false;
		if(province.value != address.province){
			province.value = address.province;
			if(address.province == 0 && !checkAddress()){
				return false;
			}else{
				if (province.selectedIndex < 0) province.selectedIndex =0;
				city.cacheValue = address.city;
				district.cacheValue = address.district;
				ouku.region.changed(province, 2, 'selCities_0', 'check');

        if(address.city == 0){
					 city.value = address.city;
          if(!checkAddress()){
            return false;
          }
        }				
				changed = true;
			}
		}else if(city.value != address.city){
      city.value = address.city;
			district.selectedIndex = 0;
			district.style.display = 'none';
      if(address.city==0 && !checkAddress()){
        return false;
      }else{
        if (city.selectedIndex < 0) city.selectedIndex =0;
        district.cacheValue = address.district;
        ouku.region.changed(city, 3, 'selDistricts_0', 'check');
        if(address.district == 0){
           district.value = address.district;
          if(!checkAddress()){
            return false;
          }
        } 				
        changed = true;
      }
    }else if(district.value != address.district){
			 district.value = address.district;
			 if (district.selectedIndex < 0) district.selectedIndex =0;
			 changed = true;
		}		

    if (changed) {
      checkShipping(address.country, address.province, address.city, address.district);
    }
    E("consignee").value = address.consignee;
    E("address").value = address.address;
    E("zipcode").value = address.zipcode;
    E("mobile").value = address.mobile;
    E("tel").value = address.tel;
    E("email").value = address.email;
		if(address.province != 0 && address.city != 0 && address.district !=0){
      if (!checkAddress()) {
        return false;
      } 			
		}		
  }
  
  E("div_modify_address").style.display='none';
  E("link_modify_address").innerHTML = "[选择/修改]";
  E("link_modify_address").opened = "0";
  E("div_current_address").style.display=  "";
  E("okIcon").className = 'isOkIcon';
  E("div_current_inv").innerHTML ="发票抬头："+ E("consignee").value;
  E("inv_payee").value = E("consignee").value;
  if(id == 0) {saveAddressToServer();}
  updateDeliveryTime();
  //checkAreaShipping();
}

function useCoupon() {
  var hidden = E("couponArea").style.display=='none';
  E("link_use_coupon").innerHTML = hidden?"[暂时不使用现金抵用券抵用]":"[使用现金抵用券抵用部分金额]";
  E("couponArea").style.display=  hidden?"":"none";
}

function modifyShipping() {
  var hidden = E("div_shipping_area").style.display=='none';
  if (last_order == null && current_shipping_id == 0) {
    E("link_modify_shipping").style.display = "none";
    hidden = true;
  } else {
    E("link_modify_shipping").style.display = "";
  }
  E("link_modify_shipping").innerHTML = hidden?"[取消]":"[选择/修改]";
  E("link_modify_shipping").opened = hidden ? "1" : "0";
  E("shipOkIcon").className = hidden?"alertIcon":"isOkIcon";
  E("div_shipping_area").style.display=  hidden?"":"none";
  E("div_current_shipping").style.display=  hidden?"none":"";
  E("delivery_time").style.display = hidden ? "none" : "";
  //E("pay_info_content").innerHTML = "";
  E("pay_info_content").style.display = hidden?"none":""; 
  
}
var val;
var sHtml="<table class='feeStyle'>";
function saveShipping() {
	     pay_fee = 0;
  var codName = '';
	var oTd = '';
  var chks = document.getElementsByName("shipping");
  var headHtml = "<table class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th>到货时间</th><th>描述</th></tr>";
  var zheadHtml = "<table class='feeStyle'><tr><th width='18%'>用户自提</th><th>自提时间</th><th>到货时间</th><th>描述&nbsp;&nbsp;&nbsp;&nbsp;先款后货：免运费&nbsp;&nbsp;&nbsp;&nbsp;货到付款：上海免运费，其他地区运费<a href='http://www.ouku.com/help/index.php?id=20' target='_blank'>查看</a></th></tr>";
  var codHeadHtml = "<table class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th width='8%'>2%手续费</th><th>到货时间</th><th>描述<span class=feed>需先付钱给快递员才能打开包装，检查机器，验货后有问题请联系客服。</span></th></tr>";
  var sucessCod = is_sucess_cod == 1;
  for (var i =0; i < chks.length; ++i) {
    if (chks[i].checked) {
      current_shipping_id = chks[i].value;
	  for(var j =0; j < chks.length; ++j){
	  if(shipping_list[j].shipping_id == current_shipping_id){
      if(shipping_list[j].shipping_proxy_fee == 0){
       val="&nbsp;" 
      }else{
      val="</td><td>￥"+shipping_list[j].shipping_proxy_fee;
      }			
		  if(shipping_list[j].support_cod == '1'&&shipping_list[j].support_no_cod == '0'){
			  sHtml = codHeadHtml;
			  codName = '(货到付款)';
        oTd = "<td>￥"+(shipping_list[j].shipping_fee - shipping_list[j].shipping_proxy_fee)+val+"</td><td>"+shipping_list[j].delivery_time+"天</td>";				
		  }
		  if(shipping_list[j].support_cod == '0'&&shipping_list[j].support_no_cod == '1'){
			  sHtml = headHtml;
        oTd = "<td>￥"+(shipping_list[j].shipping_fee - shipping_list[j].shipping_proxy_fee)+val+"</td><td>"+shipping_list[j].delivery_time+"天</td>";				
		  }	
		  if(shipping_list[j].support_cod == '1'&&shipping_list[j].support_no_cod == '1'){
			  sHtml = zheadHtml;
        oTd = "<td>"+shipping_list[j].self_work_time+"</td><td>"+shipping_list[j].delivery_time+"天</td>";
		  }		  
      E("div_current_shipping").innerHTML =sHtml+"<tr id=shipping_r_"+shipping_list[j].shipping_id+"><td>"+shipping_list[j].shipping_name+codName+"</td>"+oTd+"<td>"+shipping_list[j].shipping_desc+"</td></tr></table>";
	  }
	}
      E("div_current_shipping").style.display = "";
      E("div_shipping_area").style.display = "none";

      var shipping = getShipping(current_shipping_id);
      pack_fee = shipping.pack_fee;
//			alert(pack_fee);
      var supportCod = shipping.support_cod == "1";
	    var supportNoCod = shipping.support_no_cod == "1";
      var isOK = false;
      var pays = document.getElementsByName("payment");
      for (var j =0; j < pays.length; ++j) {
		if (supportCod) {
			pays[j].disabled = !(supportNoCod || (pays[j].attributes['isCod'].value == "1"));
      if(pays[j].disabled){
        $('#take_self_'+pays[j].value).parent().hide();
      }else{
				$('#take_self_'+pays[j].value).parent().show();
			}
			E('payAtten').style.display = 'none';
			E('sCod').style.display = 'none';
			if(pays[j].attributes['take_self'].value == "0"){
				E('take_self_'+pays[j].value).style.display = '';
				pays[j].disabled = false;
			}
			if(pays[j].attributes['take_self'].value == "1"){
				E('take_self_'+pays[j].value).style.display = 'none';
				pays[j].disabled = true;
			}
		} else {
			pays[j].disabled = !(supportNoCod && (pays[j].attributes['isCod'].value == "0"));
      if (pays[j].disabled) {
	  	  $('#take_self_' + pays[j].value).parent().hide();
	    }else{
        $('#take_self_'+pays[j].value).parent().show();
      }
			E('payAtten').style.display = '';
			E('sCod').style.display = '';
			E('check_money').innerHTML = '';
			if(pays[j].attributes['take_self'].value == "0"){
				E('take_self_'+pays[j].value).style.display = '';
				pays[j].disabled = true;
			}
			if(pays[j].attributes['take_self'].value == "1"){
				E('take_self_'+pays[j].value).style.display = 'none';
				pays[j].disabled = true;
			}		
		}
		if(supportCod&&supportNoCod){
			E('payAtten').style.display = '';
			E('pay_re').style.display = 'none';
			pays[j].setAttribute('isAll','1');
			if(pays[j].attributes['take_self'].value == "0"){
				E('take_self_'+pays[j].value).style.display = 'none';
				pays[j].disabled = true;
			}
			if(pays[j].attributes['take_self'].value == "1"){
				E('take_self_'+pays[j].value).style.display = '';	
        if (shipping.region_id == 10) {
          pays[j].attributes['pay_fee'].value = 0;
					if(pays[j].checked == true){
						pay_fee = 0;
					}
        }else if(shipping.region_id == 11 || shipping.region_id == 12 || shipping.region_id == 13){
          pays[j].attributes['pay_fee'].value = 4;
          if(pays[j].checked == true){
            pay_fee = 4;
          }					
        }else if(shipping.region_id == 8 || shipping.region_id == 31 || shipping.region_id == 29 || shipping.region_id == 24 || shipping.region_id == 23 || shipping.region_id == 30 || shipping.region_id == 25 || shipping.region_id == 21 || shipping.region_id == 26 || shipping.region_id == 22 || shipping.region_id == 32 || shipping.region_id == 27 || shipping.region_id == 9 || shipping.region_id == 6){
          pays[j].attributes['pay_fee'].value = 12;
          if(pays[j].checked == true){
            pay_fee = 12;
          }			
				}else{
          pays[j].attributes['pay_fee'].value = 8;
          if(pays[j].checked == true){
            pay_fee = 8;
          }					
        }				
				pays[j].disabled = false;
			}
			if(!sucessCod && (ouku_total_price > 4000) && (pays[j].value == 17 || pays[j].value == 18)){
			E('check_money').innerHTML = '很抱歉，由于您的订单金额超过4000元，不能选择货到付款支付，为了保证您的商品安全快速送达，欧酷推荐您选用先款后货进行支付。';
			pays[j].disabled = true;
			}
		}else{
			E('pay_re').style.display = '';	
			pays[j].setAttribute('isAll','0');
		}
		
		if(pays[j].attributes['take_self'].value == "1" && (chks[i].value != 19 && pays[j].value == 18)){
			E('take_self_'+pays[j].value).style.display = 'none';
			pays[j].disabled = true;
		}
    if (!pays[j].disabled && pays[j].checked) isOK = true;
    if (pays[j].disabled) pays[j].checked = false;
		/*支付宝推广进来的用户只能使用支付宝支付*/
    if(pays[j].attributes['alipay'].value == "0"){
      pays[j].disabled = 'disabled';
			E('take_self_'+pays[j].value).style.display = 'none';
    } 			
  }
	var alipay = is_alipay == "1";
	if(alipay){
		 $('.payStyle:not(:first)').hide();
	}
  if (!isOK && E("div_payment").style.display=='none') {
    modifyPayment();
    E("link_modify_payment").style.display = "none";
  }
  
    E("link_modify_shipping").innerHTML ="[选择/修改]";
    E("link_modify_shipping").opened = "0";
    E("link_modify_shipping").style.display = "";
	  E("shipOkIcon").className = "isOkIcon";
    updateOrderFee();
    updateDeliveryTime();
    return;
    }
  }
  alert("请选择运货方式");
}

function getShipping(id) {
  for (var i = 0; i < shipping_list.length; ++i) {
    if (shipping_list[i].shipping_id == id) return shipping_list[i];
  }
  return null;
}

function checkShipping(country, province, city, district) {
  E("div_shipping_list").innerHTML = "正在刷新...";
  E("div_shipping_area").style.display=  "";
  E("div_current_shipping").style.display=  "none";
  ouku.ajax.call("shipping.php", 'country=' + country + '&province=' + province + "&city=" + city + "&district=" + district , shippingResponse, "GET", "JSON");
}

function shippingResponse(result) {
  shipping_list = result;
  if (E("link_modify_shipping").opened == "1") {
    E("div_shipping_area").style.display=  "";
    E("div_current_shipping").style.display=  "none";
  } else {
    E("div_shipping_area").style.display=  "none";
    E("div_current_shipping").style.display=  "";
  }

  var isOK = writeShippingList();
  updateOrderFee();

  if (last_order == null && current_shipping_id == 0) {
    E("link_modify_shipping").style.display = "none";
  } else {
    E("link_modify_shipping").style.display = "";
  }
  if (!isOK) {
    E("div_shipping_area").style.display=  "";
    E("div_current_shipping").style.display=  "none";
    E("div_current_shipping").innerHTML =  "请选择送货方式";
    E("link_modify_shipping").style.display = "none";
    E("link_modify_shipping").innerHTML = "取消";
  }
    //checkAreaShipping();
}

function writeShippingList() {
  var html='';
	var dis='';
  var titleHtml = '';
  var codHtml = '';
  var noCodHtml = '';
  var isOk = false;
  var fiveText = '';
  var sucessCod = is_sucess_cod == 1;
  for (var i =0; i < shipping_list.length; ++i) {
  var oEnabled = shipping_list[i].enabled == "1";
	var alipay = is_alipay == "1";
  if(oEnabled){
		oEnabled = '';
	  if(shipping_list[i].support_cod == "1"&&shipping_list[i].support_no_cod == "0"){
	  	if(!sucessCod && ouku_total_price > 4000){
			  oEnabled = 'disabled="disabled"';
			  fiveText = '<span style="color:red;font-size:12px;margin-left:10px;font-weight:normal;">很抱歉，由于您的订单金额超过4000元，不能选择货到付款支付，为了保证您的商品安全快速送达，欧酷推荐您选用先款后货进行支付。</span>';
		  }
	  }
  }else{
	   oEnabled = 'disabled="disabled"';  
  }
	if(alipay&&shipping_list[i].support_cod == "1"&&shipping_list[i].support_no_cod == "0"){
		oEnabled = 'disabled="disabled"';
		var dis = "style='display:none;'"; //决定是否来自支付宝推广的用户
	}
  if(shipping_list[i].shipping_proxy_fee == 0){
	 val="&nbsp;" 
  }else{
	val="</td><td>￥"+  shipping_list[i].shipping_proxy_fee;
  }  
	var id  = "shipping_" + shipping_list[i].shipping_id;
if(shipping_list[i].support_cod == "1"&&shipping_list[i].support_no_cod == "1"){
	titleHtml = '<h5>上门取货</h5>';
	html += "<tr id=shipping_r_"+shipping_list[i].shipping_id+"><td ><input "+oEnabled+" name=\"shipping\" type=\"radio\" onclick='saveShipping();' id="+id+" value=\""+shipping_list[i].shipping_id+"\" ";
	if (current_shipping_id == shipping_list[i].shipping_id && !oEnabled) {
	  html += "checked ";
	  isOk = true;
	  E("div_current_shipping").innerHTML = "<table class='feeStyle'><tr><th width='18%'>用户自提</th><th>自提时间</th><th>到货时间</th><th>描述&nbsp;&nbsp;&nbsp;&nbsp先款后货：免运费&nbsp;&nbsp;&nbsp;&nbsp;货到付款：上海免运费，其他地区运费<a href='http://www.ouku.com/help/index.php?id=20' target='_blank'>查看</a></th></tr><tr><td>"+shipping_list[i].shipping_name+"</td><td>"+shipping_list[i].self_work_time+"</td><td>"+shipping_list[i].delivery_time+"天</td><td>"+shipping_list[i].shipping_desc+"</td><td></tr></table>";
	}
	html += " region_id=\""+shipping_list[i].region_id+"\" supportCod=\"" + shipping_list[i].support_cod + "\" insure=\"" + shipping_list[i].insure + "\" /><span id=\"shipping_desc_"+shipping_list[i].shipping_id+"\">" + makeLabel(id, shipping_list[i].shipping_name) + "</td><td>"+shipping_list[i].self_work_time+"</td><td>"+shipping_list[i].delivery_time+"天</td><td>" + shipping_list[i].shipping_desc+ "</td></tr>";
	
  }else if(shipping_list[i].support_cod == "1"&&shipping_list[i].support_no_cod == "0"){	  
	codHtml += "<tr id=shipping_r_"+shipping_list[i].shipping_id+"><td><input "+oEnabled+" name=\"shipping\" type=\"radio\" onclick='saveShipping();' id="+id+" value=\""+shipping_list[i].shipping_id+"\" ";
	if (current_shipping_id == shipping_list[i].shipping_id  && !oEnabled) {
	  codHtml += "checked ";
	  isOk = true;
	  E("div_current_shipping").innerHTML = "<table class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th width='8%'>2%手续费</th><th>到货时间</th><th>描述</th></tr><tr><td>"+shipping_list[i].shipping_name+"(货到付款)</td><td>￥"+(shipping_list[i].shipping_fee - shipping_list[i].shipping_proxy_fee)+val+"</td><td>"+shipping_list[i].delivery_time+"天</td><td>"+shipping_list[i].shipping_desc+"</td><td></tr></table>";
	}
	codHtml += " region_id=0 supportCod=\"" + shipping_list[i].support_cod + "\" insure=\"" + shipping_list[i].insure + "\" /><span id=\"shipping_desc_"+shipping_list[i].shipping_id+"\">" + makeLabel(id, shipping_list[i].shipping_name) + "</td><td >￥"+(shipping_list[i].shipping_fee - shipping_list[i].shipping_proxy_fee)+"</td><td>￥"+shipping_list[i].shipping_proxy_fee+ "</td><td>"+shipping_list[i].delivery_time+"天</td><td>" + shipping_list[i].shipping_desc+"</td></tr>";
	
  }else if(shipping_list[i].support_cod == "0"&&shipping_list[i].support_no_cod == "1"){
	noCodHtml += "<tr id=shipping_r_"+shipping_list[i].shipping_id+"><td ><input "+oEnabled+" name=\"shipping\" type=\"radio\" onclick='saveShipping();' id="+id+" value=\""+shipping_list[i].shipping_id+"\" ";	  
	if (current_shipping_id == shipping_list[i].shipping_id  && !oEnabled) {
	  noCodHtml += "checked ";
	  isOk = true;
	  E("div_current_shipping").innerHTML = "<table class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th>到货时间</th><th>描述</th></tr><tr><td>"+shipping_list[i].shipping_name+"</td><td>￥"+(shipping_list[i].shipping_fee - shipping_list[i].shipping_proxy_fee)+val+"</td><td>"+shipping_list[i].delivery_time+"天</td><td>"+shipping_list[i].shipping_desc+"</td><td></tr></table>";
	}
	  noCodHtml += " regiond_id=0 supportCod=\"" + shipping_list[i].support_cod + "\" insure=\"" + shipping_list[i].insure + "\" /><span id=\"shipping_desc_"+shipping_list[i].shipping_id+"\">" + makeLabel(id, shipping_list[i].shipping_name) + "</td><td >￥"+(shipping_list[i].shipping_fee - shipping_list[i].shipping_proxy_fee)+"</td><td>"+shipping_list[i].delivery_time+"天</td><td>" + shipping_list[i].shipping_desc+ "</td></tr>";
	  
  }

}
  all_html = "";
  var headHtml = "<table class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th>到货时间</th><th>描述</th></tr>";
  var zheadHtml = "<table class='feeStyle'><tr><th width='18%'>用户自提</th><th>自提时间</th><th>到货时间</th><th>描述&nbsp;&nbsp;&nbsp;&nbsp;先款后货：免运费&nbsp;&nbsp;&nbsp;&nbsp;货到付款：上海免运费，其他地区运费<a href='http://www.ouku.com/help/index.php?id=20' target='_blank'>查看</a></th></tr>";
  var codHeadHtml = "<table "+ dis+" class='feeStyle'><tr><th width='18%'>快递公司</th><th width='8%'>快递费用</th><th width='8%'>2%手续费</th><th>到货时间</th><th>描述<span class=feed>需先付钱给快递员才能打开包装，检查机器，验货后有问题请联系客服。</span></th></tr>";
  if(noCodHtml != "")
  {
     all_html += '<h5>先款后货</h5>'+headHtml+noCodHtml + '</table>';
  } 
  if(codHtml != "")
  {
     all_html += "<h5 "+ dis+">货到付款"+fiveText+"</h5>"+codHeadHtml+ codHtml + "</table>";
  } 
  if(titleHtml != "")
  {
      all_html += titleHtml + zheadHtml + html + '</table>';
  }
  E("div_shipping_list").innerHTML = all_html;
  return isOk;
}

function modifyPayment() {
  var hidden = E("div_payment").style.display=='none';
  E("link_modify_payment").innerHTML = hidden?"[取消]":"[选择/修改]";
  E("link_modify_payment").opened = hidden ? "1" : "0";
  E("link_modify_payment").style.display = "";
  E("div_payment").style.display=  hidden?"":"none";
  E("div_current_payment").style.display=  hidden?"none":"";
  E("payIcon").className = hidden?"alertIcon":"isOkIcon";
  var chks = document.getElementsByName("payment");
  for (var i =0; i < chks.length; ++i) {
    if (chks[i].checked) {
	    if(chks[i].attributes['isCod'].value == "1"&&chks[i].attributes['isAll'].value == '0'){
		  E("pay_info_content").style.display = "block";
		  E("pay_info_content").innerHTML = cod_info;
		  E("payAtten").style.display = 'none';
	   }else if(chks[i].attributes['isCod'].value == "1"&&chks[i].attributes['isAll'].value == '1'){
		  E("pay_info_content").style.display ='none';
	   }else if(chks[i].attributes['isCod'].value == "0"){
		  E("pay_info_content").innerHTML = "";
		  E("pay_info_content").style.display = "none";
		  E("payAtten").style.display = '';
		  if(chks[i].attributes['payOrder'].value > 300 && chks[i].attributes['payOrder'].value < 400){
				E("pay_info_content").style.display = "";
				E("pay_info_content").innerHTML = bank_info;
		  }
		  if(chks[i].attributes['payOrder'].value == 300){
				E("pay_info_content").style.display = "";
				E("pay_info_content").innerHTML = post_info;
		  }
	  }
	}
  }
   E("pay_info_content").style.display = hidden?"none":"";
}

function savePayment() {
  var chks = document.getElementsByName("payment");
	var oHtml = '';
  for (var i =0; i < chks.length; ++i) {
    if (chks[i].checked) {
      current_payment_id = chks[i].value;
      if(E("payment_time_" + current_payment_id)){
        oHtml = E("payment_time_" + current_payment_id).innerHTML + '，';
      }			
      E("div_current_payment").innerHTML =E("payment_name_" + current_payment_id).innerHTML +"："+ oHtml + E("payment_desc_" + current_payment_id).innerHTML;
      E("div_current_payment").style.display = "";
      E("div_payment").style.display = "none";
      E("link_modify_payment").innerHTML ="[选择/修改]";
      E("link_modify_payment").opened = "0";
      E("link_modify_payment").style.display = "";
	    E("payIcon").className = "isOkIcon";
	  if(chks[i].attributes['isCod'].value == "1"&&chks[i].attributes['isAll'].value == '0'){
		  E("pay_info_content").style.display = "block";
		  E("pay_info_content").innerHTML = cod_info;
		  E("payAtten").style.display = 'none';
	  }else if(chks[i].attributes['isCod'].value == "1"&&chks[i].attributes['isAll'].value == '1'){
		  E("pay_info_content").style.display ='none';
	  }else if(chks[i].attributes['isCod'].value == "0"){
		  E("pay_info_content").innerHTML = "";
		  E("pay_info_content").style.display = "none";
		  E("payAtten").style.display = '';
		  if(chks[i].attributes['payOrder'].value > 300 && chks[i].attributes['payOrder'].value < 400){
				E("pay_info_content").style.display = "";
				E("pay_info_content").innerHTML = bank_info;
		  }
		  if(chks[i].attributes['payOrder'].value == 300){
				E("pay_info_content").style.display = "";
				E("pay_info_content").innerHTML = post_info;
		  }		  
	  }
		if(chks[i].attributes['pay_fee'].value == 0){
			pay_fee = 0;
		}else if(chks[i].attributes['pay_fee'].value == 4){
			pay_fee = 4;
		}else if(chks[i].attributes['pay_fee'].value == 8){
      pay_fee = 8;
    }else if(chks[i].attributes['pay_fee'].value == 12){
      pay_fee = 12;
    }
    updateOrderFee();	
	  return;
    }
  }
	
  alert("请选择支付方式");
}

function checkUserPoint() {
  updateOrderFee();
  var points = V("pointValue");
  if (points == "") {
    E("CurrencyNoticeInfo").innerHTML == "";
    return;
  }
  var patrn=/^[0-9]+$/;
  if (!patrn.exec(points)) {
    E("CurrencyNoticeInfo").innerHTML = "请输入正确的欧币数目";
    return;
  }

  var points = parseFloat(points);
  if (points > limit_integral) {
    E("CurrencyNoticeInfo").innerHTML = "本次订单最多可以使用<font style='color:red'>" + limit_integral + "</font>个欧币，请输入正确的欧币数目";
    return;
  }

  if (points > user_points) {
    E("CurrencyNoticeInfo").innerHTML = "您只有<font style='color:red'>" + user_points + "</font>个欧币，请输入正确的欧币数目";
    return;
  }

  var money = points * exchange_rate / 100;
  E("CurrencyNoticeInfo").innerHTML = "您即将使用<font style='color:red'>" + points + "</font>个欧币，可以抵扣&nbsp;" + formatPrice(money) + "&nbsp;元";
}

function showPersonInv(){
  var ecs = E("inv_payee");
//  ecs.style.display = "none";
//  ecs.value = E("current_consignee").innerText;
  ecs.value = E("current_consignee").innerHTML;
  ecs.readOnly=false;
  E("comname").style.display = "none";
  E("pername").style.display = "inline";
}
function showCompanyInv(){
  var ecs = E("inv_payee");
//  ecs.style.display = "inline";
  ecs.readOnly = false;
  E("comname").style.display = "inline";
  E("pername").style.display = "none";
  E("inv_payee").value = "";
}

function saveInv(ignore) {
  if (ignore != true && E("incom").checked && Trim(V("inv_payee")) == "") {
    alert("请输入公司名");
    E("inv_payee").focus();
    return;
  }
  if(ignore != true && E("inper").checked && Trim(V("inv_payee")) == ""){
    alert("请输入需要打印在发票上的姓名");
    E("inv_payee").focus();
    return;		  
  }

  var html = "发票抬头：";
//  if (E("inper").checked) {
 //   html += E("current_consignee").innerHTML;
//  } else {
    html += encodeHtml(V("inv_payee"));
//  }
/*  html += "<br>订单附言：";
  var vpostscript = V("postscript");
  if (vpostscript != "") {
    html +=  encodeHtml(vpostscript);
  } else {
    html +=  "无";
  }
*/ 
if(V("inv_address") != ''){
	  html += "<br/>";
	  html += "发票寄送地址："  + encodeHtml(V("inv_address"));
	  html += "<br/>";
	  html += "邮政编码：" + encodeHtml(V("inv_zipcode"));
	  html += "<br/>";
	  html += "电话：" + encodeHtml(V("inv_phone"));
	  html += "<br/>";
	  E('inv_shipping').style.display = '';
	  inv_shipping_fee = 5;
	  E('inv_shipping_fee').innerHTML = '5元';		
}else{
	  E('inv_shipping').style.display = 'none';
	  inv_shipping_fee = 0;
	  E('inv_shipping_fee').innerHTML = '';		
}
  E("div_current_inv").innerHTML = html;
  E("div_inv").style.display = "none";
  E("div_current_inv").style.display = "";
  E("link_modify_inv").innerHTML = "[选择/修改]";
  E("link_modify_inv").opened = "0";
  E("link_modify_inv").style.display = "";
  E("invIcon").className = 'isOkIcon';
  updateOrderFee();
}

function modifyInv(show) {
  var hidden = false;
  if (show) {
    hidden = true;
  } else {
    hidden = E("div_inv").style.display=='none';
  }
  if (hidden) {
    inv_type = E("inper").checked ? 1 : 0;
    order_postscript = V("postscript");
    company_name = V("inv_payee");
  } else {
    E("inper").checked = inv_type == 1? true : false;
    E("incom").checked = inv_type == 0? true : false;
    E("postscript").value = order_postscript;
    E("inv_payee").value = company_name;
    if (inv_type == 1) showPersonInv();
    else showCompanyInv();
  }
  E("link_modify_inv").innerHTML = hidden?"[取消]":"[选择/修改]";
  E("link_modify_inv").opened = hidden ? "1" : "0";
  E("div_inv").style.display=  hidden?"":"none";
  E("div_current_inv").style.display=  hidden?"none":"";
  E("invIcon").className = hidden?"alertIcon":"isOkIcon";
}

function setBonusSn(bonusSn) {
	document.getElementById('giftTicketValue').value = bonusSn;
	checkGiftTicket();
}

function show(a) {
  for(var i=1;i<=2;i++) {
    if(a == i) {
      document.getElementById("b"+i).className= "button3 button11";
      document.getElementById("t"+i).style.display = "";
    } else {
      document.getElementById("b"+i).className= "button3";
      document.getElementById("t"+i).style.display = "none";
    }
  }
}

function appear(id,event){
  var helpCont = document.getElementById(id);
  helpCont.style.display = helpCont.style.display == "block" ? "none" : "block";
  helpCont.style.right = 0 + "px";
  helpCont.style.top = 30 +"px";
  helpCont.style.zIndex = "100";
}

function formatPrice(price) {
  var text = "" + (parseFloat(price) + 0.005);
  if (text.indexOf(".") == 0) text = "0" + text;
  else if (text.indexOf(".") > 0) text = text + "00";
  else text = text + ".00";
  var index = text.indexOf(".");
  text = text.substring(0, index + 3);
  if(text.charAt(text.indexOf(".")+1) == '0' && text.charAt(text.indexOf(".") + 2) == '0')
  {
      text = text.substring(0, text.indexOf("."));
  }
  return text;
}

function initCheckout() {
  E("iForm").reset();
  var helpInfoId1 = E('helpInfo1');
  if (helpInfoId1) helpInfoId1.onclick = function(event){appear('helpContent1',event);};
  var helpInfoId2 = E('helpInfo2');
  if (helpInfoId2) helpInfoId2.onclick = function(event){appear('helpContent2',event);};
  writeAddressList();
  if(current_shipping_id != null && getShipping(current_shipping_id) != null){

	  var supportCod = getShipping(current_shipping_id).support_cod == "1";
	  var supportNoCod = getShipping(current_shipping_id).support_no_cod == "1";
    var pays = document.getElementsByName("payment");
    for (var j =0; j < pays.length; ++j) {
		if (supportCod) {
			pays[j].disabled = !(supportNoCod || (pays[j].attributes['isCod'].value == "1"));
		  if(pays[j].disabled){
        $('#take_self_'+pays[j].value).parent().hide();				
			}else{
        $('#take_self_'+pays[j].value).parent().show();
      }
		} else {
			pays[j].disabled = !(supportNoCod && (pays[j].attributes['isCod'].value == "0"));
      if(pays[j].disabled){
        $('#take_self_'+pays[j].value).parent().hide();       
      }else{
        $('#take_self_'+pays[j].value).parent().show();
      }			
			/*来源是支付宝的只显示支付宝*/
      if(pays[j].attributes['alipay'].value == "0"){
        pays[j].disabled = true;
				E('take_self_'+pays[j].value).style.display = 'none';
      }			
		}
		var alipay = is_alipay == "1";
		if(alipay){
		  $('.payStyle:not(:first)').hide();
		}
    if (pays[j].disabled) pays[j].checked = false;
		if(pays[j].checked && pays[j].attributes['isCod'].value == "0") E('payAtten').style.display = '';
		if(pays[j].checked && pays[j].attributes['isCod'].value == "1") E('payAtten').style.display = 'none';
		if(pays[j].attributes['isCod'].value == "1"&&pays[j].disabled){
			E('sCod').style.display = '';
		}
      }
  }
  if (has_default_consignee) {
    var isOK = writeShippingList();
    if (!isOK && E("div_shipping_area").style.display=='none') {
      updateOrderFee();
      modifyShipping();
    }
	if(current_shipping_id >0) updateDeliveryTime();
  }

  // 确定发票信息
  /*
  if (last_order == null) {
    E("inper").checked = true;
  } else {
    var isperson = last_order.inv_payee=="";
    if (isperson) {
      E("inper").checked = true;
      showPersonInv();
    } else {
      E("incom").checked = true;
      E("inv_payee").value = last_order.inv_payee;
      showCompanyInv();
    }
  }
  saveInv(true);*/

  // 如果没有以前的订单或者地址
  if (!has_default_consignee) {
    E("link_modify_shipping").style.display = "none";
    E("div_shipping_area").style.display=  "none";
    //E("div_current_shipping").style.display=  "none";

    E("link_modify_payment").style.display = "none";
    E("div_payment").style.display=  "none";
    //E("div_current_payment").style.display=  "none";
  }

  // 修复付款label
  var ps = document.getElementsByName("payment");
  for (var i=0; i < ps.length; ++i) {
    var id = ps[i].id;
    var lb = E("payment_desc_" + ps[i].value);
    lb.innerHTML = makeLabel(id, lb.innerHTML);
  }
  E('inv_ad').style.display = 'none';
  E('inv_zip').style.display = 'none';
  E('inv_ph').style.display = 'none';
  updateOrderFee();
  checkAreaShipping();
}
function checkAreaShipping(){

	if(E('shipping_48')){
		if(ouku_total_price > 2960) E('shipping_r_48').style.display = 'none';
		if(ouku_total_price < 2960) E('shipping_r_49').style.display = 'none';
	}
}

function updateOrderFee() {
  var shipping = getShipping(current_shipping_id);
  var shipping_fee = 0;
//	alert(pay_fee);
  E("sum_total_price").innerHTML = formatPrice(ouku_total_price + biaoju_total_price) + "&nbsp;元";
  if (shipping != null) {
      shipping.shipping_basic_fee = parseFloat(shipping.shipping_fee) - parseFloat(shipping.shipping_proxy_fee);
      shipping_fee = parseFloat(shipping.shipping_basic_fee) * shippingFeeOrders + parseFloat(shipping.shipping_proxy_fee);
      shipping_fee += total_addtional_shipping_fee;

      E("self_shipping_fee").innerHTML = formatPrice(pay_fee) + "&nbsp;元";
      E("tr_self_shipping_fee").style.display = "";
			E("tr_sum_shipping_fee").style.display = '';				

      if (parseFloat(shipping_fee) == 0) {
        E("sum_shipping_fee").innerHTML = "0&nbsp;元";
        E("tr_shipping_proxy_fee").style.display = "none";
        E("tr_sum_shipping_fee").style.display = 'none';        
      } else {
        E("sum_shipping_fee").innerHTML = formatPrice(shipping_fee - shipping.shipping_proxy_fee) + "&nbsp;元";
        if (shipping.shipping_proxy_fee > 0)
        {
            E("tr_shipping_proxy_fee").style.display = "";
            if(shipping.conf_percent)
            {
            	var  ht = formatPrice(ouku_total_price + biaoju_total_price) + "*" + shipping.conf_percent + "% = ";
            }
			ht += formatPrice(shipping.shipping_proxy_fee) + "&nbsp;元";
            E("shipping_proxy_fee").innerHTML = ht;
        }
        else
        {
            E("tr_shipping_proxy_fee").style.display = "none";
        }
      }
		  if(pay_fee==0){
        E("tr_self_shipping_fee").style.display = "none";
        E("tr_sum_shipping_fee").style.display = "";          
      }
  } else {
    E("sum_shipping_fee").innerHTML = "未指定";
  }
  E("package_fee").innerHTML = pack_fee +"&nbsp;元";
  var total_fee = ouku_total_price + biaoju_total_price + shipping_fee + pay_fee + inv_shipping_fee + pack_fee;
  var points_money = 0;
  if (ouku_total_price == 0 || limit_integral == 0 || user_points == 0) {
    E("tr_sum_integral").style.display = "none";
  } else {
    var points = V("pointValue");
    if (points == "") {
      E("sum_integral").innerHTML = "-0&nbsp;元";
    } else {
      var patrn=/^[0-9]+$/;
      var upoints = 0;
      if (patrn.exec(points)) {
        upoints = parseFloat(points);
        points_money = upoints * exchange_rate / 100;
        if (upoints >= 0 && upoints <= limit_integral && upoints <= user_points) {
          total_fee -= points_money;
          E("sum_integral").innerHTML = "-"+formatPrice(points_money)+"&nbsp;元";
        } else {
          E("sum_integral").innerHTML = "错误";
        }
      } else {
        E("sum_integral").innerHTML = "错误";
      }
    }	
  }

  if (ouku_total_price == 0 || limit_bonus <= 0) {
    E("tr_sum_bonus").style.display = "none";
  } else {
    var bm = bonus_money;
    if (bm > limit_bonus) bm = limit_bonus;
    if (ouku_total_price - points_money - bm >= 0) {
      E("sum_bonus").innerHTML = "-"+formatPrice(bm)+"&nbsp;元";
      total_fee -= bm;
    } else {
      E("sum_bonus").innerHTML = "-"+formatPrice(ouku_total_price - points_money)+"&nbsp;元";
      total_fee -= ouku_total_price - points_money;
    }
  }
  var chks = document.getElementsByName("payment");
  for (var i = 0; i < chks.length; ++i) {
    if (chks[i].checked) {
      if( (chks[i].value == 19) && (is_tenpay == 1)){
        E('tenpay').style.display = '';
				total_fee = total_fee - tenpay_fee;
				E('td_tenpay_info').innerHTML = '注意：无法与其他现金抵用券同时抵用';
      }else{
        E('tenpay').style.display = 'none';
      }
		  //快钱优惠
		  if(chks[i].value == 20 && bill99_fee > 0){
		    E('tr_bill99').style.display = ''
				total_fee = total_fee - bill99_fee;
				E('td_bill99_fee').innerHTML = '-'+bill99_fee + '&nbsp;元';
				E('td_bill99_info').innerHTML = '注意：无法与其他现金抵用券同时抵用';
		  }else{
				E('tr_bill99').style.display = 'none';
			}			
    }
  }

	if (bm > 0 && (is_tenpay == 1)) {
  	E('td_tenpay_fee').innerHTML = '0&nbsp;元';
		E('td_tenpay_info').innerHTML = '注意：无法与其他现金抵用券同时抵用';
  }
  if (bm > 0 && (bill99_fee > 0)) {
    E('td_bill99_fee').innerHTML = '0&nbsp;元';
    E('td_bill99_info').innerHTML = '注意：无法与其他现金抵用券同时抵用';
  }	 
  E("sum_total").innerHTML = formatPrice(total_fee) + "&nbsp;元";
}


//现金抵用卷相应函数
function	checkGiftTicket(){
  var GiftTicketValue = V('giftTicketValue');
  if (GiftTicketValue == "") {
    alert("请输入抵用券号码");
    return false;
  }

  if (GiftTicketValue.length != 16) {
    alert("抵用券号码不正确");
    E('giftTicketValue').focus();
    return false;
  } else {
    ouku.ajax.call(ouku.path.root+'currency.Controller.php?Action=textingGiftTicket', 'giftTicketValue=' + GiftTicketValue, GiftTicketValueResponse, "POST", "TEXT");
  }
}

function GiftTicketValueResponse(result){
  try{
    var  giftTicketNoticeInfo  =  E("giftTicketNoticeInfo");
    var res  =    JSON.parse(result);
    if(res.error == true ){
      giftTicketNoticeInfo.innerHTML = res.info;
      bonus_money = 0;
      updateOrderFee();
    }else{
      giftTicketNoticeInfo.innerHTML = res.info;
      bonus_money = parseFloat(res.money);
			tenpay_fee = 0;
			bill99_fee = 0;
      if(bonus_money == 0) {
        E("giftTicketValue").value = '';
        alert(res.info);
      }
      updateOrderFee();
    }
  }catch(ex){E("giftTicketNoticeInfo").innerHTML  = '未知错误请重新购买或联系欧酷服务人员'}
}


function checkOrder() {
  var isOK = false;
  // 检查地址
  if (E("link_modify_address").opened == "1") {
    alert("请先保存地址");
    return false;
  }
  isOK = checkAddress();
  if (!isOK) return false;

  // 检查送货方式
  /*if (E("link_modify_shipping").opened == "1") {
    alert("请先保存送货方式");
    return false;
  }*/
  isOK = false;
  var chks = document.getElementsByName("shipping");
  for (var i =0; i < chks.length; ++i) {
    if (chks[i].checked) {
      isOK = true;
      break;
    }
  }
  if (!isOK) {
    alert("请选择运货方式");
    return false;
  }

  // 检查支付方式
  /*if (E("link_modify_payment").opened == "1") {
    alert("请先保存付款方式");
    return false;
  }*/
  isOK = false;
  var chks = document.getElementsByName("payment");
  for (var i =0; i < chks.length; ++i) {
    if (chks[i].checked) {
      isOK = true;
      break;
    }
  }
  if (!isOK) {
    alert("请选择付款方式");
    return false;
  }

  // 检查发票信息
  if (E("link_modify_inv").opened == "1") {
    alert("请先保存发票信息");
    return false;
  }
  if (E("incom").checked && Trim(V("inv_payee")) == "") {
    modifyInv(true);
    alert("请输入公司名");
    E("inv_payee").focus();
    return false;
  }

  return isOK;
}


function makeLabel(id, text) {
  var index = text.indexOf("：");
  if (index < 0) index = text.indexOf(":");
  if (index < 0) index = text.indexOf("<a");
  if (index < 0) return "<label for="+id+">" + text + "</label>";
  else return "<label for="+id+">" + text.substring(0,index) + "</label>" + text.substring(index);
}

function selectInv(){
	E('inv_ad').style.display= E('inv_ad').style.display == 'none'? '':'none';
	E('inv_zip').style.display = E('inv_zip').style.display == 'none'?'':'none';
	E('inv_ph').style.display = E('inv_ph').style.display == 'none'?'':'none';
	E('edit_inv').innerHTML = E('inv_ad').style.display == 'none' ? '修改发票寄送地址' : '取消修改发票寄送地址';
}

function updateDeliveryTime(){
    var url = ouku.path.root +  "User.Controller.php?Action=get_delivery_time_ajax";
    var params = {
		"country" : V("selCountries_0"),
		"province" : V("selProvinces_0"),
		"city" : V("selCities_0"),
		"district" : V("selDistricts_0"),
		"shipping_id" : [current_shipping_id]
    };
		var str = JSON.stringify(params);
    function on_response(result){
		if (result[current_shipping_id] != -1) {
			if (result['shipping_name'] == 'EMS快递') {
				E("delivery_time").innerHTML = "<p style='line-height:150%;border-top:0;'>1、我们会在订单确认后48小时内完成配货发货，若发生缺货情况，我们将与您取得联系，确认是否取消订单、更换商品或选择等待到货；<br />2、您订购的商品预计会在" + result[current_shipping_id] + "个工作日送达，届时请确保您的电话的畅通，以便快递公司或欧酷能方便的与您取得联系；<br/>3、提示：EMS快递在周末和节假日休假，无法取货和送货，因此周末订单会延迟到周一发货，造成的延迟请您谅解。邮政快递服务说明<a href='http://www.ouku.com/help/index.php?id=42#fuwushuoming' target='_blank'>[点击查看]</a></p>";
			}
			else {
				E("delivery_time").innerHTML = "<p style='line-height:150%;border-top:0;'>1、我们会在订单确认后48小时内完成配货发货，若发生缺货情况，我们将与您取得联系，确认是否取消订单、更换商品或选择等待到货；<br />2、您订购的商品预计会在" + result[current_shipping_id] + "个工作日送达，届时请确保您的电话的畅通，以便快递公司或欧酷能方便的与您取得联系。</p>";
			}
			
			E("delivery_time").style.display = "block";
		}
		else {
			E("delivery_time").innerHTML = "";
			E("delivery_time").style.display = "none";
		}
	}
	ouku.ajax.call(url, {JSON:str}, on_response, "POST", "JSON");
}
