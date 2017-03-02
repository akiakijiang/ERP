/* $Id : common.js 4865 2007-01-31 14:04:10Z paulgao $ */
/**
 * 检测有效手机格式
 *
 */
function checkMobile(v)
{
	/*var mobile = v;
	var reg0=/^13\d{5,9}$/;   //130--139。至少7位
	var reg1=/^153\d{4,8}$/;  //联通153。至少7位
	var reg2=/^158\d{4,8}$/;  //移动158。至少7位
	var reg3=/^159\d{4,8}$/;  //移动159。至少7位
	var my=false;
	if (reg0.test(mobile))my=true;
	if (reg1.test(mobile))my=true;
	if (reg2.test(mobile))my=true;
	if (reg3.test(mobile))my=true;
	return my;*/
	var mobile = v;
	var reg=/^1\d{10}$/;
	var my=false;
	if (reg.test(mobile))my=true;
	return my;
}

function toggle_display(id)
{
    var ele = document.getElementById(id);
    if(ele.style.display == "none")
    {
        ele.style.display = "block";
    }
    else
    {
        ele.style.display = "none";
    }
}


//价格举报
function report_low_price(frm)
{
    var data = {};
    data['message'] = document.getElementById('message_low_price').value;
    data['url'] = document.getElementById('url').value.replace('&','%26'); 
    data['goods_id'] = document.getElementById('goods_id').value;
    if(data['url'] == "" || data['url'] == "请在此粘贴相关网址 http://" || data['message'] == "" || data['message'] == "请在此输入举报内容"){	
	   alert("请输入相关举报信息");
       return false; 
	}
    function rlp_response(r)
    {
       alert(r.message);
       document.getElementById('message_low_price').value = "请在此输入举报内容";
	   document.getElementById('url').value = "请在此粘贴相关网址 http://";
	}
    Ajax.call(path+'User.Controller.php?Action=add_low_price_report', data, rlp_response, 'POST', 'JSON');

}
/* *
 * 添加降价提醒
 */
function addToMark(goodsId)
{
	if(goodsId){
  		Ajax.call(path+'User.Controller.php?Action=add_to_mark&GoodsId='+goodsId, 'goodsId=' + goodsId, addToMarkResponse, 'POST', 'TEXT');
	}else{
		showDialog("请给出正确的提醒物品");
		return false;
	}
}


function	addtoCompare()
{
	var pIdd = readCookie("pId");
	var arrCookieT = pIdd.split("# ");
	var out	= '';
	for(var i=0;i<arrCookieT.length;i++)
	{
		if(arrCookieT[i]!= undefined){
			out += arrCookieT[i]+'_';
		}
	}
	if(arrCookieT.length < 2){
		showDialog('请添加两个以上的对比物品');
		return false;
	}else{
		location.href	=	path+"productcompare.php?CompareId="+out;
	}
	eraseCookie("pId");
	eraseCookie("pNm");
}
/* *
 * 接收降价提醒
 */
function	addToMarkResponse(r)
{
	var	result		=	eval('(' + r + ')');
	if(result.error)
	{
		showDialog(result.info);	
	}else{
		showDialog('未知错误');	
	}
}

//颜色功能

function stylePiceFn(styleId,stylePrice,colorName,j,imgUrl,styleName){
	goodsStyleId = styleId;
	iSum = sum = stylePrice;
	if (stylePrice == -1) {
  	stylePrice_format = "待定";
  }else{
		stylePrice_format = '￥'+ stylePrice;
	};
	document.getElementById('shopPrice').innerHTML = stylePrice_format;
	if (document.getElementById('goods_amount_0').innerHTML) {
  	if (stylePrice == -1){
  		document.getElementById('goods_amount_0').innerHTML = '待定';
  	}else{
			document.getElementById('goods_amount_0').innerHTML = (stylePrice + total_fee) + package_fee + '元';
		}
  }
	if (document.getElementById('goods_amount_1')) {
		if (stylePrice == -1) {
	     document.getElementById('goods_amount_1').innerHTML = '待定';
		}else{
			document.getElementById('goods_amount_1').innerHTML = (stylePrice + total_fee_cod + Math.floor(stylePrice * cod_percent/100)) + package_fee + '元';
		}
  }
	if(isFitting != undefined){
		document.getElementById('fitting_sum').innerHTML = 	stylePrice_format;
		document.getElementById('fit_shop_price').innerHTML = '￥'+sum;
		if(isFitting > 0) {
			document.getElementById("fitting_sum").innerHTML = '￥' +  fittShopPrice+stylePrice;
			
			document.getElementById("fitMessage").innerHTML = '';
		}else{
			document.getElementById("fitMessage").innerHTML = '暂时还未选择配件，请点击 搭配购买 选购配件';
		}
		document.getElementById('styleFittSum').style.display = '';
		document.getElementById('mobileNum').innerHTML = '1';
		document.getElementById('showThumb').src = sImg + imgUrl;
	}
	
	if(goodsStyleId == 0){
		var html = colorName;
		if(styleName == 'shortage'){
			html += '暂缺货，请耐心等待，我们会在到货后尽快上架。';
		}else if(styleName == 'tosale'){
			html += '即将上市，敬请期待';
		}else if(styleName == 'withdrawn'){
			html += '此款机器已下市，留着纪念一下';
		}
		document.getElementById('colorName').innerHTML = html;
	}else{
		document.getElementById('colorName').innerHTML = '您已选择：<span style="color:red;">“'+colorName+'”</span>';
	}
	
	if(document.getElementById('savePrice')){
		document.getElementById('savePrice').innerHTML = '为您节省：￥'+(mp-sum);
		document.getElementById('savePrice').style.display = '';
	}
	var colorId = document.getElementById('color_id').getElementsByTagName('div');
	for(var i=0;i<colorId.length;i++){
		colorId[i].val = '0'	
		colorId[i].style.border = '0';
	}
	colorId[j].style.border = '2px solid #ccc';
	colorId[j].setAttribute('val','1');
	document.getElementById('showPicBig').src = mImg + imgUrl;
}

function stylePriceMouseover(m){
	var colorId = document.getElementById('color_id').getElementsByTagName('div');
	for(var i=0;i<colorId.length;i++){
		if(colorId[i].getAttribute('val') == '0') colorId[i].style.border = '0';
	}
	colorId[m].style.border = '1px solid #ccc';
}
function stylePriceMouseout(){
	var colorId = document.getElementById('color_id').getElementsByTagName('div');	
	for(var i=0;i<colorId.length;i++){
		if(colorId[i].getAttribute('val') == '0') colorId[i].style.border = '0';
	}	
}
/* 
 * 添加商品到购物车
 */

function addToCart(goodsId,bjStoreGoodsId,fit)
{

	var goods        = new Object();
	var spec_arr     = new Array();

	var fittings_arr = new Array();

	var number       = 1;
	var ooHref = location.href;
	var aHref = ooHref.split('#');
	// 检查是否有商品规格
    /*
	var formBuy      = document.forms['OUKOO_FORMBUY'];
	if (formBuy)
	{
		j = 0;
		for (i = 0; i < formBuy.elements.length; i ++ )
		{
			var prefix = formBuy.elements[i].name.substr(0, 5);

			if (prefix == 'spec_' && (
			(formBuy.elements[i].type == 'radio' && formBuy.elements[i].checked) ||
			formBuy.elements[i].tagName == 'SELECT'))
			{
				spec_arr[j] = formBuy.elements[i].value;
				j ++ ;
			}
		}
		
		//购买个数
		if (formBuy.elements['number'])
		{
		number = formBuy.elements['number'].value;
		}
	}
    */
    if(fit == undefined || fit != 'fit') //非套餐
    {
        try{
            number = document.getElementById('goods_number').value;
            number = parseInt(number);
            if(isNaN(number) || number <= 0)
            {
                return;
            }
        }
        catch(ex)
        {
            
        }
		location.href = aHref[0] + '#main';			
    }
    if(fit == 'fit')//套餐
    {
        // 检查是否有配件
        var fittings = document.getElementsByName("fitting_goods_id");
        for (var i = 0; i < fittings.length; ++i) {
            var num  = document.getElementById("fitting_goods_num_" + fittings[i].value).value;
            num = parseFloat(num);
            if (isNaN(num) || num <= 0) continue;
            fittings_arr[fittings_arr.length] = [fittings[i].value, num];
        }
		location.href = aHref[0] + '#goodsFitting';	
    }
	if(spec_arr){
		goods.spec     = spec_arr;
	}

	if(fittings_arr){
		goods.fittings = fittings_arr;
	}
	goods.goods_id = goodsId;

	goods.number   = number;
	if(styleMulti != ''){
		if(goodsStyleId == 0){ 
			alert('请选择一款有货颜色');
			return;
		}else{
			//alert(goodsStyleId);
			goods.style_id = goodsStyleId;

		}
	}
	
    goods.is_set_meal = (fit == 'fit');
	//移动定制机...
    try{
       goods.customized = Form.get_radio_value("customized");   
    }
    catch(ex){
       goods.customized = "not-applicable";
    }

	// 设置回跳路径
/*	if (back == null || back == '')
	{
		back = path + 'Checkout.php';
	}
	else
	{
		back = escape(back):
	}
*/
	Ajax.call(path+'Cart.Controller.php?Action=add_to_cart&GoodsId='+goodsId+"&BjStoreGoodsId="+bjStoreGoodsId+"&back="+back, 'goods=' + goods.toJSONString(), addToCartResponse, 'POST', 'JSON');

}

/* *
 * 处理添加商品到购物车的反馈信息
 */
function addToCartResponse(result)
{	
	if (result.error > 0)
	{
		// 如果需要缺货登记，跳转
		if (result.error == 2)
		{
			/* if (confirm(result.message))
			{
			location.href = path+'Booking.php?id=' + result.goods_id;
			}*/
			showDialog(result.message,(path+'Booking.php?id=' + result.goods_id),2);
		}
		else
		{
			//alert(result.message);
			showDialog(result.message);
		}
	}
	else
	{
        if(! result.is_set_meal){
            document.getElementById('addToCartId').style.display='';
            if(document.getElementById('addToCartId_fit')){
                document.getElementById('addToCartId_fit').style.display='none';
            }
            document.getElementById('totalAmout').innerHTML = "￥" + result.total_amout;
            document.getElementById('totalNum').innerHTML = document.getElementById('cartTotalNum').innerHTML = result.total_num;
        }else{
            document.getElementById('addToCartId').style.display='none';
            document.getElementById('addToCartId_fit').style.display='';
            document.getElementById('cartTotalNum').innerHTML = result.total_num;
            if(document.getElementById('totalAmout_fit')) 
                document.getElementById('totalAmout_fit').innerHTML = "￥" + result.total_amout;
            if(document.getElementById('totalNum_fit')) 
                document.getElementById('totalNum_fit').innerHTML = result.total_num;
        }
        load_cart_goods();
		/* 注释 by ychen 2008/02/01
		var cartInfo = document.getElementById('oukuCart');
		var cartList = document.getElementById('cart_list');
		if (cartInfo)
		{
			cartInfo.innerHTML = result.content;
			cartList.innerHTML = result.list;
		}
		
		showDialog(result.message,(path+'Cart.php'),1);
		*/
		//location.href = result.back;
	}
     
}

/* *
 * 添加商品到收藏夹
 */
function collect(goodsId, bjStoreGoodsId)
{
  if (bjStoreGoodsId)
  	Ajax.call(path+'Cart.Controller.php?Action=collect&GoodsId='+goodsId+'&bjStoreGoodsId='+bjStoreGoodsId, 'id=' + goodsId + '&bjStoreGoodsId=' + bjStoreGoodsId, collectResponse, 'GET', 'JSON');
  else
  	Ajax.call(path+'Cart.Controller.php?Action=collect&GoodsId='+goodsId, 'id=' + goodsId, collectResponse, 'GET', 'JSON');
}

/* *
 * 处理收藏商品的反馈信息
 */
function collectResponse(result)
{
  showDialog(result.message);
}

/* *
 * 处理会员登录的反馈信息
 */
function signInResponse(result)
{
  toggleLoader(false);

  var done    = result.substr(0, 1);
  var content = result.substr(2);

  if (done == 1)
  {
    document.getElementById('member-zone').innerHTML = content;
  }
  else
  {
    showDialog(content);
  }
}

/* *
 * 评论的翻页函数
 */
function gotoPage(page, id, type)
{
  Ajax.call('comment.php?act=gotopage', 'page=' + page + '&id=' + id + '&type=' + type, gotoPageResponse, 'GET', 'JSON');
}

function gotoPageResponse(result)
{

  document.getElementById("ECS_COMMENT").innerHTML = result.content;
}

/* *
 * 取得格式化后的价格
 * @param : float price
 */
function getFormatedPrice(price)
{
  if (currencyFormat.indexOf("%s") > - 1)
  {
    return currencyFormat.replace('%s', advFormatNumber(price, 2));
  }
  else if (currencyFormat.indexOf("%d") > - 1)
  {
    return currencyFormat.replace('%d', advFormatNumber(price, 0));
  }
  else
  {
    return price;
  }
}

/* *
 * 夺宝奇兵会员出价
 */

function bid(step)
{
  var price = '';
  var msg   = '';
  if (step != - 1)
  {
    var frm = document.forms['formBid'];
    price   = frm.elements['price'].value;
    if (price.length == 0)
    {
      msg += price_not_null + '\n';
    }
    else
    {
      var reg = /^[\.0-9]+/;
      if ( ! reg.test(price))
      {
        msg += price_not_number + '\n';
      }
    }
  }
  else
  {
    price = step;
  }

  if (msg.length > 0)
  {
    showDialog(msg);
    return;
  }

  Ajax.call('snatch.php?act=bid', 'price=' + price, bidResponse, 'POST', 'JSON')
}

/* *
 * 夺宝奇兵会员出价反馈
 */

function bidResponse(result)
{
  if (result.error == 0)
  {
    document.getElementById('ECS_SNATCH').innerHTML = result.content;
    if (document.forms['formBid'])
    {
      document.forms['formBid'].elements['price'].focus();
    }
    newPrice(); //刷新价格列表
  }
  else
  {
    showDialog(result.content);
  }
}

/* *
 * 夺宝奇兵最新出价
 */

function newPrice()
{
  Ajax.call('snatch.php?act=new_price_list', '', newPriceResponse, 'GET', 'TEXT');
}

/* *
 * 夺宝奇兵最新出价反馈
 */

function newPriceResponse(result)
{
  document.getElementById('ECS_PRICE_LIST').innerHTML = result;
}

/* *
 *  返回属性列表
 */
function getAttr(cat_id)
{
  var tbodies = document.getElementsByTagName('tbody');
  for (i = 0; i < tbodies.length; i ++ )
  {
    if (tbodies[i].id.substr(0, 10) == 'goods_type')tbodies[i].style.display = 'none';
  }

  var type_body = 'goods_type_' + cat_id;
  try
  {
    document.getElementById(type_body).style.display = '';
  }
  catch (e)
  {
  }
}

/* *
 * 截取小数位数
 */
function advFormatNumber(value, num) // 四舍五入
{
  var a_str = formatNumber(value, num);
  var a_int = parseFloat(a_str);
  if (value.toString().length > a_str.length)
  {
    var b_str = value.toString().substring(a_str.length, a_str.length + 1);
    var b_int = parseFloat(b_str);
    if (b_int < 5)
    {
      return a_str;
    }
    else
    {
      var bonus_str, bonus_int;
      if (num == 0)
      {
        bonus_int = 1;
      }
      else
      {
        bonus_str = "0."
        for (var i = 1; i < num; i ++ )
        bonus_str += "0";
        bonus_str += "1";
        bonus_int = parseFloat(bonus_str);
      }
      a_str = formatNumber(a_int + bonus_int, num)
    }
  }
  return a_str;
}

function formatNumber(value, num) // 直接去尾
{
  var a, b, c, i;
  a = value.toString();
  b = a.indexOf('.');
  c = a.length;
  if (num == 0)
  {
    if (b != - 1)
    {
      a = a.substring(0, b);
    }
  }
  else
  {
    if (b == - 1)
    {
      a = a + ".";
      for (i = 1; i <= num; i ++ )
      {
        a = a + "0";
      }
    }
    else
    {
      a = a.substring(0, b + num + 1);
      for (i = c; i <= b + num; i ++ )
      {
        a = a + "0";
      }
    }
  }
  return a;
}

/* *
 * 根据当前shiping_id设置当前配送的的保价费用，如果保价费用为0，则隐藏保价费用
 *
 * return       void
 */
function set_insure_status()
{
  // 取得保价费用，取不到默认为0
  var shippingId = getRadioValue('shipping');
  var insure_fee = 0;
  if (shippingId > 0)
  {
    if (document.forms['theForm'].elements['insure_' + shippingId])
    {
      insure_fee = document.forms['theForm'].elements['insure_' + shippingId].value;
    }
    // 每次取消保价选择
    if (document.forms['theForm'].elements['need_insure'])
    {
      document.forms['theForm'].elements['need_insure'].checked = false;
    }

    // 设置配送保价，为0隐藏
    if (document.getElementById("ecs_insure_cell"))
    {
      if (insure_fee > 0)
      {
        document.getElementById("ecs_insure_cell").style.display = '';
        setValue(document.getElementById("ecs_insure_fee_cell"), getFormatedPrice(insure_fee));
      }
      else
      {
        document.getElementById("ecs_insure_cell").style.display = "none";
        setValue(document.getElementById("ecs_insure_fee_cell"), '');
      }
    }
  }
}

/* *
 * 当支付方式改变时出发该事件
 * @param       pay_id      支付方式的id
 * return       void
 */
function changePayment(pay_id)
{
  // 计算订单费用
  calculateOrderFee();
}

function getCoordinate(obj)
{
  var pos =
  {
    "x" : 0, "y" : 0
  }

  pos.x = document.body.offsetLeft;
  pos.y = document.body.offsetTop;

  do
  {
    pos.x += obj.offsetLeft;
    pos.y += obj.offsetTop;

    obj = obj.offsetParent;
  }
  while (obj.tagName.toUpperCase() != 'BODY')

  return pos;
}

function showCatalog(obj)
{
  var pos = getCoordinate(obj);
  var div = document.getElementById('ECS_CATALOG');

  if (div && div.style.display != 'block')
  {
    div.style.display = 'block';
    div.style.left = pos.x + "px";
    div.style.top = (pos.y + obj.offsetHeight - 1) + "px";
  }
}

function hideCatalog(obj)
{
  var div = document.getElementById('ECS_CATALOG');

  if (div && div.style.display != 'none') div.style.display = "none";
}
/* *
 * 留言满意不满意
 */
var cId;
function comment_rank(id,num){
	cId = id;
	Ajax.call(path+'User.Controller.php?Action=comment_rank&comment_id='+id+"&rank="+num,'',rank_response , 'POST', 'JSON');
}
function rank_response(result){
	var html = '';
	var is_close = false;
	if(result.error == 0){
		if(result.rank == 1){
			html = "[&nbsp;<span style='color:#9933CC'>满意！</span>&nbsp;不满意！]";
			document.getElementById('satisfied_'+cId).className = 'satisfied';
			var is_close = true;
		}else{
			html = "[&nbsp;满意！&nbsp;<span style='color:#000'>不满意！</span>]";	
			document.getElementById('satisfied_'+cId).className = 'satisfied no_satisfied';
		}
		document.getElementById('s_'+cId).innerHTML = html;
		document.getElementById('satisfied_'+cId).innerHTML = result.message;
		document.getElementById('satisfied_'+cId).style.display = '';
		//if(is_close){
			document.getElementById('satisfied_'+cId).style.textAlign = 'center';
			setTimeout("document.getElementById('satisfied_"+cId+"').style.display = 'none'",4000);
		//}
	}
	if(result.error == 2){
		alert(result.message);
		var oHref = location.href;
		location.href = path + 'loginregister.php?back_act='+oHref;	
	}
	if(result.error == 1){
		alert(result.message);
		location.href = location.href;
	}
}
