/* $Id : user.js 4865 2007-01-31 14:04:10Z paulgao $ */

/* *
 * 修改会员信息
 */
function userEdit()
{
	if (typeof(email_empty) == 'undefined') {
		email_empty = "email地址不能为空！";
	}
	var frm = document.forms['formEdit'];
	//var email = frm.elements['email'].value;
	var msg = '';
	var reg = null;

	/*if (email.length == 0)
	{
		msg += email_empty + '\n';
	}
	else
	{
		if ( ! (Utils.isEmail(email)))
		{
			msg += email_error + '\n';
		}
	}*/
    
    // {{{ 地址信息 Tao Fei 2008-4-9
	if (frm.user_realname.value == 0){
		msg = '请输入您的真实姓名！';
		showDialog(msg);
		return false;
	}
	if (frm.province.value == 0 || frm.city.value == 0){
		msg = '您输入的省市不对！';
		showDialog(msg);
		return false;
	}
	if (! /^\d{6}$/.test(frm.zipcode.value)){
		msg = '您输入的邮编格式不对！';
		showDialog(msg);
		return false;
	}
    //}}}
	// {{{ 会员制 Zandy 2007-12-21
	if (! /1\d{10}/.test(frm.user_mobile.value)){
		msg = '您输入的手机号码格式不对！';
		showDialog(msg);
		return false;
	}
	// }}}
	
    if (msg.length > 0)
	{
		return false;
	}
	else
	{
		return true;
	}
}
function userEdit_1(i)
{
	if (typeof(email_empty) == 'undefined') {
		email_empty = "email地址不能为空！";
	}
	var frm = document.forms['formEdit'];
	//var email = frm.elements['email'].value;
	var msg = '';
	var reg = null;

	/*if (email.length == 0)
	{
		msg += email_empty + '\n';
	}
	else
	{
		if ( ! (Utils.isEmail(email)))
		{
			msg += email_error + '\n';
		}
	}*/
    
    // {{{ 地址信息 Tao Fei 2008-4-9
	switch(i){
	case 1:	
		if (frm.user_realname.value == 0){
			document.getElementById('msg_1').innerHTML = msg = '请输入您的真实姓名！';
			return false;
		}else{
			document.getElementById('msg_1').innerHTML = '必填';	
		}
		break;
		//}}}
		// {{{ 会员制 Zandy 2007-12-21
	case 2:	
		if (! /1\d{10}/.test(frm.user_mobile.value)){
			document.getElementById('msg_2').innerHTML = msg = '您输入的手机号码格式不对！';
			return false;
		}else{
			document.getElementById('msg_2').innerHTML = '必填';	
		}
		break;
	case 3:	
		if (frm.province.value == 0 || frm.city.value == 0){
			document.getElementById('msg_3').innerHTML = msg = '您输入的省市不对！';
			return false;
		}else{
			document.getElementById('msg_3').innerHTML = '必选';	
		}
		break;
	case 4:	
		if (! /^\d{6}$/.test(frm.zipcode.value)){
			document.getElementById('msg_4').innerHTML = msg = '您输入的邮编格式不对！';
			return false;
		}else{
			document.getElementById('msg_4').innerHTML = '必填';	
		}
		break;
	case 5:	
		if(frm.user_address.value == ""){
			document.getElementById('msg_5').innerHTML = msg = '请输入正确地址！';
			return false;	
		}else{
			document.getElementById('msg_5').innerHTML = '必填';	
		}
		break;
	
		// }}}
	}		
	if (msg.length > 0)
	{
		return false;
	}
	else
	{
		return true;
	}

}
/* 会员修改密码 */
function editPassword()
{
  var frm              = document.forms['formPassword'];
  var old_password     = frm.elements['old_password'].value;
  var new_password     = frm.elements['new_password'].value;
  var confirm_password = frm.elements['comfirm_password'].value;

  var msg = '';
  var reg = null;

  if (old_password.length == 0)
  {
    msg += old_password_empty + '\n';
  }

  if (new_password.length == 0)
  {
    msg += new_password_empty + '\n';
  }

  if (confirm_password.length == 0)
  {
    msg += confirm_password_empty + '\n';
  }

  if (new_password.length > 0 && confirm_password.length > 0)
  {
    if (new_password != confirm_password)
    {
      msg += both_password_error + '\n';
    }
  }

  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 对会员的留言输入作处理
 */
function submitMsg()
{
  var frm         = document.forms['formMsg'];
  var msg_title   = frm.elements['msg_title'].value;
  var msg_content = frm.elements['msg_content'].value;
  var msg = '';

  if (msg_title.length == 0)
  {
    msg += msg_title_empty + '\n';
  }
  if (msg_content.length == 0)
  {
    msg += msg_content_empty + '\n'
  }
  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 会员找回密码时，对输入作处理
 */
function submitPwdInfo()
{
  var frm = document.forms['getPassword'];
  var user_name = frm.elements['user_name'].value;
  var email     = frm.elements['email'].value;

  var errorMsg = '';
  if (user_name.length == 0)
  {
    errorMsg += user_name_empty + '\n';
  }

  if (email.length == 0)
  {
    errorMsg += email_address_empty + '\n';
  }
  else
  {
    if ( ! (Utils.isEmail(email)))
    {
      errorMsg += email_address_error + '\n';
    }
  }

  if (errorMsg.length > 0)
  {
    alert(errorMsg);
    return false;
  }

  return true;
}

/* *
 * 会员找回密码时，对输入作处理
 */
function submitPwd()
{
  var frm = document.forms['getPassword2'];
  var password = frm.elements['new_password'].value;
  var confirm_password = frm.elements['confirm_password'].value;

  var errorMsg = '';
  if (password.length == 0)
  {
    errorMsg += new_password_empty + '\n';
  }

  if (confirm_password.length == 0)
  {
    errorMsg += confirm_password_empty + '\n';
  }

  if (confirm_password != password)
  {
    errorMsg += both_password_error + '\n';
  }

  if (errorMsg.length > 0)
  {
    alert(errorMsg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 处理会员提交的缺货登记
 */
function addBooking()
{
  var frm      = document.forms['formBooking'];
  var goods_id = frm.elements['id'].value;
  var rec_id   = frm.elements['rec_id'].value;
  var number   = frm.elements['number'].value;
  var desc     = frm.elements['desc'].value;
  var linkman  = frm.elements['linkman'].value;
  var email    = frm.elements['email'].value;
  var tel      = frm.elements['tel'].value;
  var msg = "";

  if (number.length == 0)
  {
    msg += booking_amount_empty + '\n';
  }
  else
  {
    var reg = /^[0-9]+/;
    if ( ! reg.test(number))
    {
      msg += booking_amount_error + '\n';
    }
  }

  if (desc.length == 0)
  {
    msg += describe_empty + '\n';
  }

  if (linkman.length == 0)
  {
    msg += contact_username_empty + '\n';
  }

  if (email.length == 0)
  {
    msg += email_empty + '\n';
  }
  else
  {
    if ( ! (Utils.isEmail(email)))
    {
      msg += email_error + '\n';
    }
  }

  if (tel.length == 0)
  {
    msg += contact_phone_empty + '\n';
  }

  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }

  return true;
}

/* *
 * 会员登录
 */
function userLogin()
{
  var frm      = document.forms['formLogin'];
  var username = frm.elements['username'].value;
  var password = frm.elements['password'].value;
  var msg = '';

  if (username.length == 0)
  {
    msg += username_empty + '\n';
  }

  if (password.length == 0)
  {
    msg += password_empty + '\n';
  }

  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 处理注册用户
 */
function register()
{
  var frm              = document.forms['formUser'];
  var username         = Utils.trim(frm.elements['username'].value);
  var email            = frm.elements['email'].value;
  var password         = Utils.trim(frm.elements['password'].value);
  var confirm_password = Utils.trim(frm.elements['confirm_password'].value);

  var msg = "";

  // 检查输入
  var msg = '';
  if (username.length == 0)
  {
    msg += username_empty + '\n';
  }
  else if (username.match(/^\s*$|^c:\\con\\con$|[%,\'\*\"\s\t\<\>\&\\]/))
  {
    msg += username_invalid + '\n';
  }
  else if (username.length < 3)
  {
    //msg += username_shorter + '\n';
  }

  if (email.length == 0)
  {
    msg += email_empty + '\n';
  }
  else
  {
    if ( ! (Utils.isEmail(email)))
    {
      msg += email_invalid + '\n';
    }
  }
  if (password.length == 0)
  {
    msg += password_empty + '\n';
  }
  else if (password.length < 6)
  {
    msg += password_shorter + '\n';
  }else if (password.length > 18)
  {
    msg += password_longer + '\n';
  }
  if (confirm_password != password )
  {
    msg += confirm_password_invalid + '\n';
  }

  if (msg.length > 0)
  {
    showDialog(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 用户中心订单保存地址信息
 */
function saveOrderAddress(id)
{
  var frm           = document.forms['formAddress'];
  var consignee     = frm.elements['consignee'].value;
  var email         = frm.elements['email'].value;
  var address       = frm.elements['address'].value;
  var zipcode       = frm.elements['zipcode'].value;
  var tel           = frm.elements['tel'].value;
  var mobile        = frm.elements['mobile'].value;
  var sign_building = frm.elements['sign_building'].value;
  var best_time     = frm.elements['best_time'].value;

  if (id == 0)
  {
    alert(current_ss_not_unshipped);
    return false;
  }
  var msg = '';
  if (address.length == 0)
  {
    msg += address_name_not_null + "\n";
  }
  if (consignee.length == 0)
  {
    msg += consignee_not_null + "\n";
  }

  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 合并订单
 */
function mergeOrder()
{
  var frm        = document.forms['formOrder'];
  var from_order = frm.elements['from_order'].value;
  var to_order   = frm.elements['to_order'].value;
  var msg = "";

  if (from_order == 0)
  {
    msg = from_order_not_null + "\n";
  }
  if (to_order == 0)
  {
    msg  += to_order_not_null + "\n";
  }
  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 会员余额申请
 */
function submitSurplus()
{
  var frm            = document.forms['formSurplus'];
  var surplus_type   = frm.elements['surplus_type'].value;
  var surplus_amount = frm.elements['amount'].value;
  var process_notic  = frm.elements['user_note'].value;
  var payment_id     = 0;
  var msg = '';

  if (surplus_amount.length == 0 )
  {
    msg += surplus_amount_empty + "\n";
  }
  else
  {
    var reg = /^[\.0-9]+/;
    if ( ! reg.test(surplus_amount))
    {
      msg += surplus_amount_error + '\n';
    }
  }

  if (process_notic.length == 0)
  {
    msg += process_desc + "\n";
  }

  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }

  if (surplus_type == 0)
  {
    for (i = 0; i < frm.elements.length ; i ++)
    {
      if (frm.elements[i].name=="payment_id" && frm.elements[i].checked)
      {
        payment_id = frm.elements[i].value;
        break;
      }
    }

    if (payment_id == 0)
    {
      alert(payment_empty);
      return false;
    }
  }

  return true;
}

/* *
 *  处理用户添加一个红包
 */
function addBonus()
{
  var frm      = document.forms['addBouns'];
  var bonus_sn = frm.elements['bonus_sn'].value;

  if (bonus_sn.length == 0)
  {
    alert(bonus_sn_empty);
    return false;
  }
  else
  {
    var reg = /^[0-9]{10}$/;
    if ( ! reg.test(bonus_sn))
    {
      alert(bonus_sn_error);
      return false;
    }
  }

  return true;
}

/* *
 *  合并订单检查
 */
function mergeOrder()
{
  var frm        = document.forms['formOrder'];
  var from_order = frm.elements['from_order'].value;
  var to_order   = frm.elements['to_order'].value;
  var msg = '';

  if (from_order == 0)
  {
    msg += from_order_empty + '\n';
  }
  if (to_order == 0)
  {
    msg += to_order_empty + '\n';
  }
  else if (to_order == from_order)
  {
    msg += order_same + '\n';
  }
  if (msg.length > 0)
  {
    alert(msg);
    return false;
  }
  else
  {
    return true;
  }
}

/* *
 * 订单中的商品返回购物车
 * @param       int     orderId     订单号
 */
function returnToCart(orderId)
{
  Ajax.call('user.php?act=return_to_cart', 'order_id=' + orderId, returnToCartResponse, 'POST', 'JSON');
}

function returnToCartResponse(result)
{
  alert(result.message);
}


function	selectAddress(addressId)
{
	 Ajax.call(path+'User.Controller.php?Action=selectAddress', 'addressId=' + addressId, selectAddressResponse, 'GET', 'JSON');
}

function	selectAddressResponse(r)
{
	var Consignee = document.getElementById("consignee");
	Consignee.value = r.info.consignee;	
    var Tel = document.getElementById("tel");
	Tel.value = r.info.tel;	
	var Mobile = document.getElementById("mobile");
	Mobile.value = r.info.mobile;
	var Address = document.getElementById("address");
	Address.value = r.info.address;
	var Email = document.getElementById("email");
	Email.value = r.info.email;
	var Zipcode = document.getElementById("zipcode");
	Zipcode.value = r.info.zipcode;
	var Country = document.getElementById("selCountries_0");
	Country.options[0].value = r.info.country;
    Country.options[0].text = r.info.country_name;
	Country.options[0].selected = true;
	
	var Provinces = document.getElementById("selProvinces_0");
	
	Provinces.options[0].value = r.info.province;
    Provinces.options[0].text = r.info.province_name;
	Provinces.options[0].selected = true;
	
	var Cities = document.getElementById("selCities_0");
	Cities.options[0].value = r.info.city;
	Cities.options[0].text = r.info.city_name;
	Cities.options[0].selected = true;
	
	var Districts = document.getElementById("selDistricts_0");
	Districts.options[0].value = r.info.district;
	var dValue = Districts.options[0].value;
	//alert(dValue);
	Districts.options[0].text = r.info.district_name;
	Districts.options[0].selected = true;
	if(dValue == 0){
	Districts.style.display = "none";
	}else{
	Districts.style.display = "";	
	}
}
