/**
 * OKSID，判断用户是否一直在线，更新服务器session
 * @alias pass
 */
function pass(){
	if(ouku.cookie.read('OKSID')){
		try{
			ouku.ajax.call(ouku.path.root + 'pss.php', '', function(){}, 'get', 'text', 'no');
		}
		catch (e){
		}	
	}
}
/**
 * 头部提示信息，可以调节时间段进行设置
 * @alias show_top_tips
 */
function show_top_tips(){
	var tips = {
        rest : '<a href="'+ ouku.path.root +'talk.php?z=js" target="_blank" style="color:#fff;">您好，这么晚还来欧酷！但是很抱歉，客服现在已经休息了。您可以留言咨询或直接下单。我们会及时回复和确认订单，祝您在欧酷购物愉快！</a>',  
        morning : '<a href="'+ ouku.path.root +'talk.php?z=js" target="_blank" style="color:#fff;">早上好，欢迎来到欧酷！但客服尚未上班。您可以留言咨询或直接下单。我们会及时回复和确认订单，祝您在欧酷购物愉快！</a>',
        busy : '<a href="'+ ouku.path.root +'talk.php?z=js" target="_blank" style="color:#fff;">亲爱的顾客，目前欧酷顾客流量较大，食堂同学们都在满负荷工作中，因此留言及电话的接听会稍有延误，不过我们会尽快为您处理，请您不要着急！</a>', 
        eat :  '<a href="'+ ouku.path.root +'talk.php?z=js" target="_blank" style="color:#fff;">亲爱的顾客，现在是就餐时间，欧酷的“食堂同学们”正在就餐中，您可以留言咨询或直接下单。我们会及时回复和确认订单，祝您在欧酷购物愉快！</a>',	
        repair: '<a href="'+ ouku.path.root +'talk.php?z=js" target="_blank" style="color:#fff;">啊！被您发现了，欧酷即将在凌晨3点对服务器进行维护，届时网站访问可能出现小问题，请不要担心，维护将持续10分钟左右。很晚了，早点休息！～</a>'  
		}
  var hTime=ouku.date.hours, mTime=ouku.date.minutes, dTime=ouku.date.day,mDif = 0,nDif = 0;
  if(dTime == 6 || dTime == 0){
    mDif = 1000;
    nDif = 1900;
  }else{
    mDif = 900;
    nDif = 2100;
  }
  if(mTime < 10) mTime = '0' + mTime.toString();
  var vTime = hTime.toString() + mTime.toString();
  vTime = Math.floor(vTime);
  if(vTime >= nDif || vTime < 600){
    $('#topTip').show().attr('class','topTip_sleep');
    $('#topcontent').html(tips.rest);
  }else if(vTime >=600 && vTime < mDif){
    $('#topTip').show().attr('class','topTip_normal');
    $('#topcontent').html(tips.morning);
  }else if(vTime >=1000 && vTime < 1100){
    $('#topTip').show().attr('class','topTip_busy');
    $('#topcontent').html(tips.busy);
  }else if(vTime >=1200 && vTime < 1230){
    $('#topTip').show().attr('class','topTip_eat');
    $('#topcontent').html(tips.eat); 
  }else if(vTime >=1300 && vTime < 1400){
    $('#topTip').show().attr('class','topTip_busy');
    $('#topcontent').html(tips.busy);
  }else if(vTime >=1600 && vTime < 1700){
    $('#topTip').show().attr('class','topTip_busy');
    $('#topcontent').html(tips.busy);
  }else if(vTime >=1830 && vTime < 1900){
    $('#topTip').show().attr('class','topTip_eat');
    $('#topcontent').html(tips.eat);
  }else if(vTime >=230 && vTime < 320){
    $('#topTip').show().attr('class','topTip_repair');
    $('#topcontent').html(tips.repair); 		 
  }else{
    $('#topTip').hide();
  }
	$('#topTip img').click(function(){
		$('#topTip').hide().css('top','0');
	});
}
/**
 * 首页中间图片广告轮换效果
 * @alias img_tab
 * @param {Object} aid  图片的父id
 * @param {Object} tid  链接的父id
 * @param {Object} time 循环的时间
 */
function img_tab(aid, tid, time){
  var imgs = $('#'+aid+' img');
	var links = $('#'+tid+' a');
  var n=imgs.length;
  var k=1;
  var tt = '';
  function show_img(j){
		links.removeClass();
    imgs.parent().hide();
    links.eq(j).attr('class','on');
    imgs.eq(j).parent().fadeIn();
  }
	if(n >= 2){
		tt = setInterval(function(){turn()},time); 
	  links.each(function(i){
	    $(this).mouseover(function(){
	      clearInterval(tt);
	      show_img(i);
	      k = i;
	      if(k == (n-1)){
	          k = 0;
	      }else{
	          k++;   
	      }     
	      $(this).css('on');
	    }).mouseout(function(){
				tt = setInterval(function(){turn()},time);;
			});
	  });
    function turn(){
      clearInterval(tt);
      show_img(k);
      if(k == (n-1)){
        k = 0;
      }else{
        k++;  
      }
			tt = setInterval(function(){turn()},time); 
    }			   
  } 
}

/**
 * 载入购物车商品
 * @alias load_cart_goods
 */
function load_cart_goods(){
  ouku.ajax.call(ouku.path.root + "Cart.Controller.php?Action=get_cart_goods", "", load_cart_goods_response, "get", "json"); 
}
/**
 * 返回购物车商品数据
 * @alias load_cart_goods_response
 * @param {Object} result 返回的对象数据
 */
function load_cart_goods_response(result){
	var cg = $('#cartGoods');
	var num = result.total_num;
	var sumPrice = result.total_amout;
	if(result.error != 0){
	  alert(result.message);
	  return;
	} 
	var html = "";
	for(var i = 0; i< result.goods.length; i++){
    html += "<li><a href='" + result.goods[i].goods_link + "'>" 
         + result.goods[i].goods_name + "</a><span style='color:#fe6601;margin:0 10px;font-weight:bold;'>￥" + result.goods[i].shop_price + "</span>× " 
         + result.goods[i].goods_number + "</li>\n";  
	}
	$('#cart_goods_list').html(html);
	$('#cart_goods_list_2').html(html);
	html += "<li style='border:0;color:#666;text-align:left;padding-top:15px;padding-bottom:10px;'><a href='"+ouku.path.root+"Cart.php' style='float:right;margin-top:-5px;margin-left:10px;' target='_self'><img src='"+ouku.path.img+"images/checkCart.png' alt='查看购物车'></a><a href='"+ouku.path.root+"Checkout.php?z=check' style='float:right;margin-top:-5px;' target='_self'><img src='"+ouku.path.img+"images/inPayCenter.png' alt='进入结算中心'></a>合计<span style='margin:0 5px;font-weight:bold;color:#fe6601;'>"+result.total_num+"</span>件<span id='sumPrice_id' class='bRed' style='margin-left:10px;font-weight:bold;color:#fe6601;'></span></li>";
	if(result.total_num == 0){
    cg.html("<p style='color:#666;text-align:center;background:#fff;padding:5px 0;'>您的购物袋中暂无商品，赶快选择心爱的商品吧！</p>");
    num = 0;
	}else{
	  cg.html(html);
	  $('#sumPrice_id').html("￥"+sumPrice);
	} 
	$('#cartTotalNum').html(num);
}   
/**
 * 公告的滚动效果
 * @alias scroll
 * @param {Object} id   滚动区域的Id
 * @param {Object} time 滚动区域的时间
 */
function scroll(id, time){
  var sp = document.getElementById(id);
  sp.innerHTML += sp.innerHTML;
  sp.scrollTop = 0;
  var isTrue = 1;
  var t = time;
  var lastStop = 0;
  function scrollUp(){
    var now = new Date().getTime();
    if (now - lastStop >= t) {
      if (( sp.scrollTop == sp.scrollHeight/6 || sp.scrollTop == sp.scrollHeight/3 || sp.scrollTop == sp.scrollHeight/2 || sp.scrollTop == 0) && isTrue == 0) {
        return;
      } else {
        sp.scrollTop += sp.scrollHeight/176;
      }
      if(sp.scrollTop == sp.scrollHeight/6){
        lastStop = now;
      }     
      if(sp.scrollTop == sp.scrollHeight/3){
        lastStop = now;     
      }
			if(sp.scrollTop == sp.scrollHeight/2){
				sp.scrollTop = 0;
				lastStop = now;
			}
      if(sp.scrollTop == 0){
        lastStop = now;
      }
    }
  }
  setTimeout(function(){setInterval(function(){scrollUp()},30)},t);
  sp.onmouseover = function(){
    isTrue = 0;
  }
  sp.onmouseout = function(){
    isTrue = 1;
  } 
}
/**
 * 购物小贴士、调价信息切换
 * @alias tab_price
 * @param {Object} a
 */
function tab_price(a){
  for(var i=1;i<3;i++){
      if(a == i){
          $("#h_"+i).attr('class','price_a');
          $("#t_"+i).show();
          $("#a_tab").attr('href', ouku.path.root + 'article/?cat_id=4');
      }
      else{
        $("#h_"+i).attr('class','');
        $("#t_"+i).hide();
        $("#a_tab").attr('href', ouku.path.root + 'article/?cat_id=3');
      }
  }
}
/**
 * 显示首页商品的评价条数、咨询条数
 * @alias show_comment
 * @param {Object} uid ul的id
 * @param {Object} did 显示div的id
 */
function show_comment(uid, did){
   $('#'+uid+' li').each(function(i){
    $(this).mouseover(function(){
      $('#'+did+'_'+i).show();
    }).mouseout(function(){
      $('#'+did+'_'+i).hide();
    });
  });
}
/**
 * 层随滚动条滚动
 * @alias move
 * @param {Object} id    滚动层的id
 * @param {Object} time  滚动的时间间隔
 * @param {Object} top   距离浏览器顶部距离
 */

function move(id, time, top){
	var tips = document.getElementById(id);
	var tt = time;
	var theTop = top;
	var old = theTop;	
	var pos = ouku.position.sTop();
	pos=pos-tips.offsetTop+theTop;
	pos=tips.offsetTop+pos/10;
	if (pos < theTop) pos = theTop;
	if (pos != old) {
		tips.style.top = pos + "px";
		tt = 10;
	}
	old = pos;
	setTimeout(function(){move(id,time,top)},tt);
}
/**
 * 对比商品时显示层
 * @alias compare_float_tip
 */
function compare_float_tip(){
  move('floatTips',50,200);
  var pId = ouku.cookie.read('pId');
  if((pId != "") && (pId != null)){
    $('#floatTips').css('display',"block");
    compare_html();
  }
}
/**
 * 对比添加的商品
 * @alias add_to_compare
 */
function add_to_compare(){
  var pIdd = ouku.cookie.read("pId");
  var arrCookieT = pIdd.split("# ");
  var out = '';
  for(var i=0;i<arrCookieT.length;i++)
  {
    if(arrCookieT[i]!= undefined){
      out += arrCookieT[i]+'_';
    }
  }
  if(arrCookieT.length < 2){
    alert('请添加两个以上的对比物品');
    return false;
  }else{
    location.href = ouku.path.root + "productcompare.php?CompareId=" + out;
  }
  ouku.cookie.del("pId");
  ouku.cookie.del("pNm");
}
/**
 * 添加对比的商品
 * @alias add_compare_goods
 * @param {Object} name   商品的名称
 * @param {Object} goodId 商品的ID
 */
function add_compare_goods(name,goodId){
	var pName = '', pId = '';
	pName = ouku.cookie.read('pNm');
	pId = ouku.cookie.read('pId');
	$('#floatTips').css('display','block');
	if((pId != "") && (pId != null)){
		var nameCookie = pName.split('# ');
		var idCookie = pId.split('# ');
		if(idCookie.length < 4){
			for(var i=0;i<idCookie.length;i++){
				if(idCookie[i].indexOf(goodId) != -1){
					alert(name + '已选择');	
				}else{
					create_compare_cookie("pNm",name,30);
					create_compare_cookie("pId",goodId,30);
				}
			}
		}else{
			alert('最多只能有4款机型参加比较！');	
		}
	}else{
		create_compare_cookie("pNm",name,30);
		create_compare_cookie("pId",goodId,30);			
	}
	compare_html();
}
/**
 * 生成显示对比商品
 * @alias compare_html
 */
function compare_html() {
	var out = [];
	var pName = ouku.cookie.read("pNm");
	var pId = ouku.cookie.read("pId");
	if(( pId!= "") && (pId != null))
	{
		var nameCookie = pName.split("# ");
		var idCookieT = pId.split("# ");
		for(key in nameCookie)
		{
			out[key]= "<li><span style=\"display:block;width:110px;overflow:hidden;white-space:nowrap;text-align:left;margin-left:5px;float:left;\">"+nameCookie[key]+"</span><img src=\""+ouku.path.img+"images/close2.gif\" alt=\"关闭\" class=\"close2\" onclick=\"del_compare_cookie("+key+")\" /></li>";
		}
		outs = out.reverse().join(""); 
		$('#com_item').html(outs); 
	}	
}
/**
 * 生成对比商品的cookie
 * @alias create_compare_cookie
 * @param {Object} name  cookie的名称
 * @param {Object} value cookie的值
 * @param {Object} days  cookie的有效时间
 */
function create_compare_cookie(name,value,days){
	var sValue = ouku.cookie.read(name);
	if((sValue == '') || (sValue == null)){
		ouku.cookie.create(name,value,days);	
	}else{
		if(sValue.indexOf(value) == -1){
			var arrCookie = sValue.split('# ');
			var aLength = arrCookie.length;
			if(aLength > 4){
				sValue = "";
				for(var i=0;i<4;i++){
					sValue += arrCookie[i] + '# ';	
				}
				sValue = sValue.substring(0, sValue.length-2);
			}
			var v = value + "# " + sValue;
			ouku.cookie.create(name,v ,days);
		}
	}
}
/**
 * 删除对比的商品
 * @alias del_compare_cookie
 * @param {Object} k
 */
function del_compare_cookie(k){
	var out = [], nameValue = '', idValue = '';
	var pName = ouku.cookie.read('pNm');
	var pId = ouku.cookie.read('pId');
	var nameCookie = pName.split('# ');
	var idCookie = pId.split('# ');
	nameCookie.remove(k);
	idCookie.remove(k);
	if (idCookie.length == 0) {
		$('#com_item').html('');
		ouku.cookie.del('pNm');
		ouku.cookie.del('pId');
	}
	else {
    for (var i = 0; i < idCookie.length; i++) {
      nameValue += nameCookie[i] + '# ';
      idValue += idCookie[i] + '# ';
    }
    nameValue = nameValue.substring(0, nameValue.length - 2);
    idValue = idValue.substring(0, idValue.length - 2);			
		for (key in nameCookie) {
			out[key] = "<li><span style=\"display:block;width:110px;overflow:hidden;white-space:nowrap;text-align:left;margin-left:5px;float:left;\">" + nameCookie[key] + "</span><img src=\"" + ouku.path.img + "/images/close2.gif\" alt=\"关闭\" class=\"close2\" onclick=\"del_compare_cookie(" + key + ")\" /></li>";
		}
		outs = out.reverse().join("");
		$('#com_item').html(outs);
		ouku.cookie.create("pNm", nameValue, 7);
		ouku.cookie.create("pId", idValue, 7);
	}
}	


/**
 * 添加收藏
 * @alias collect
 * @param {Object} goodsId        商品id
 * @param {Object} bjStoreGoodsId
 */
function collect(goodsId, bjStoreGoodsId){
  if (bjStoreGoodsId)
    ouku.ajax.call(ouku.path.root + 'Cart.Controller.php?Action=collect&GoodsId='+goodsId+'&bjStoreGoodsId='+bjStoreGoodsId, 'id=' + goodsId + '&bjStoreGoodsId=' + bjStoreGoodsId, collect_response, 'get', 'json');
  else
    ouku.ajax.call(ouku.path.root + 'Cart.Controller.php?Action=collect&GoodsId='+goodsId, 'id=' + goodsId, collect_response, 'get', 'json');
}
function collect_response(result){
  alert(result.message);
}
/**
 * 未登录用户跳转到页面登录
 * @alias need_login
 * @param {Object} msg  提示信息
 */

function need_login(msg) {
  if(confirm(msg)){
		location.href = ouku.path.root + 'loginregister.php?back_act=' + ouku.path.uri;
	}
}

/**
 * 添加商品到购物车
 * @alias add_to_cart
 * @param {Object} goodsId         商品ID
 * @param {Object} bjStoreGoodsId
 * @param {Object} fit             是否购买配件
 */

function add_to_cart(goodsId, bjStoreGoodsId, fit){
  var goods        = new Object();
  var spec_arr     = new Array();
  var fittings_arr = new Array();
  var number       = 1;
  var oHref = location.href;
  var aHref = oHref.split('#');
  if(fit == undefined || fit != 'fit'){
    try{
      number = $('#goods_number').val();
      number = parseInt(number);
      if(isNaN(number) || number <= 0){
          return;
      }
    }
    catch(ex){
        
    }
  location.href = aHref[0] + '#main';     
  }
  if(fit == 'fit'){
    // 检查是否有配件
    var fittings = document.getElementsByName("fitting_goods_id");
    for (var i = 0; i < fittings.length; ++i) {
        var num  = $("#fitting_goods_num_" + fittings[i].value).val();
        num = parseFloat(num);
        if (isNaN(num) || num <= 0) continue;
        fittings_arr[fittings_arr.length] = [fittings[i].value, num];
    }
    location.href = aHref[0] + '#goodsFitting'; 
  }
	
  if(spec_arr){
    goods.spec = spec_arr;
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
  ouku.ajax.call(ouku.path.root + 'Cart.Controller.php?Action=add_to_cart&GoodsId='+goodsId+"&BjStoreGoodsId="+bjStoreGoodsId+"&back="+back, 'goods=' + JSON.stringify(goods), add_to_cart_response, 'post', 'json');

}
/**
 * 添加商品到购物车ajax返回函数
 * @alias add_to_cart_response
 * @param {Object} result   返回的结果
 */
function add_to_cart_response(result){
  if (result.error > 0) {
  	alert(result.message);
  }
  else {
  	if (!result.is_set_meal) {
  		$('#addToCartId').show();
  		$('#addToCartId_fit').hide();
  		$('#totalAmout').html("￥" + result.total_amout);
  		$('#totalNum').html(result.total_num);
  		$('#cartTotalNum').html(result.total_num);
  	}
  	else {
  		$('#addToCartId').hide();
  		$('#addToCartId_fit').show();
  		$('#cartTotalNum').html(result.total_num);
  		$('#totalAmout_fit').html("￥" + result.total_amout);
  		$('#totalNum_fit').html(result.total_num);
		}	
  load_cart_goods();
  }  
}
/**
 * 在一定时间内关闭弹出的购物车
 * @alias slideCart
 */
function slideCart(){
  $('#cartId').attr('class','myCart cartOn');
  $('#cartGoods').css('display','');
  slideCartTime = setTimeout("$('#cartId').attr('class','myCart');$('#cartGoods').css('display','none')",3000);
}
/**
 * 选择商品的颜色
 * @alias style_price
 * @param {Object} styleId     颜色的id
 * @param {Object} stylePrice  颜色的价格
 * @param {Object} colorName   颜色的名称
 * @param {Object} j           当前选择的颜色
 * @param {Object} imgUrl      颜色的商品图片
 * @param {Object} stateName   商品状态
 */

function style_price(styleId, stylePrice, colorName, j, imgUrl, stateName){
  goodsStyleId = styleId;
  iSum = sum = stylePrice;
  if (stylePrice == -1) {
    stylePrice_format = "待定";
  }else{
    stylePrice_format = '￥'+ stylePrice;
  };
  $('#shopPrice').html(stylePrice_format);

    if (stylePrice == -1){
      $('#goods_amount_0').html('待定');
			$('#goods_amount_1').html('待定');
    }else{
      $('#goods_amount_0').html((stylePrice + total_fee) + package_fee + '元');
			$('#goods_amount_1').html((stylePrice + total_fee_cod + Math.floor(stylePrice * cod_percent/100)) + package_fee + '元');
    }

  if(isFitting != undefined){
    $('#fitting_sum').html(stylePrice_format);
    $('#fit_shop_price').html('￥'+sum);
    if(isFitting > 0) {
      $("#fitting_sum").html('￥' +  (fittShopPrice + stylePrice));
      $("#fitMessage").html('');
    }else{
      $("#fitMessage").html('暂时还未选择配件，请点击 搭配购买 选购配件');
    }
    $('#styleFittSum').show();
    $('#mobileNum').html('1');
    $('#showThumb').attr('src', sImg + imgUrl);
  }
  
  if(goodsStyleId == 0){
    var html = colorName;
    if(stateName == 'shortage'){
      html += '暂缺货，请耐心等待，我们会在到货后尽快上架。';
    }else if(stateName == 'tosale'){
      html += '即将上市，敬请期待';
    }else if(stateName == 'withdrawn'){
      html += '此款机器已下市，留着纪念一下';
    }
    $('#colorName').html(html);
  }else{
    $('#colorName').html('您已选择：<span style="color:red;">“'+colorName+'”</span>');
  }
  
  $('#savePrice').html('为您节省：￥'+(mp-sum));
  $('#savePrice').show();
  $('#color_id .colorStyle').attr('val','0');
	$('#color_id .colorBox img').remove();
//	$('#color_id .colorStyle').css('border','0');
//  $('#color_id .colorStyle:eq('+j+')').css('border','2px solid #ccc');
//  $('#color_id .colorStyle:eq('+j+')').attr('val','1');
  var img_right = '<img src="'+ouku.path.img + 'images/select_goods_color.png" alt="选择"';
	$('#color_id .colorStyle:eq('+j+')').parent().append(img_right);
  $('#showPicBig').attr('src', mImg + imgUrl);
}
/**
 * 根据配件的分类id显示配件
 * @param {Object} goodsId 商品id
 * @param {Object} catId   分类id
 */
function show_fittings(goodsId, catId){
	$('#fittingUl').attr('cid',catId[0]);
//	alert(catId[0]);
	if(catId[0] == -1){
		$('#fittingUl li').show();
		$('#noneFitt').hide();
	}else{
	  $('#fittingUl li').hide();
	  $('#fittingUl li.l_'+catId[0]).show();		
	}
	suit_ul('fittingUl');
}
/**
 * 根据配件的分类id，取得相应的配件
 * @param {number} parentId   主商品的id
 * @param {Array} catId       配件的分类id，以数组形式传递过来
 */
function get_fittings(parentId, catId){
	if (parentId <= 0) {
  	return false;
  }
	var fittings = {};
	fittings.parent_id = 	parentId;
	fittings.cat_id = catId.toString();
	var is_in = false;
	var firstCatId = catId[0];
	for(k in chooseFittingsCatId){
		if(chooseFittingsCatId[k] == firstCatId){
			is_in = true;
		}
	}
  if (is_in) {
    $('#fittingUl li').hide();
    $('li .l_'+catId[0]).fadeIn();
		suit_ul('fittingUl');		
  }else{
	  var is_show = {
	    showLoader: function(){
	      $('#overLi').append('<div id="loader" style="position:absolute;top:0;left:0;bottom:auto;">正在读取配件信息，请稍候...</div>')
	    },
	    hideLoader: function(){
	      $('#loader').remove()
	    }
	  }		
    ouku.ajax.call(ouku.path.root + 'Cart.Controller.php?Action=get_fittings', fittings, get_fittings_response, 'post', 'json', is_show);
	}
}

function get_fittings_response(r){
	if(r.error == 1){
		alert(r.content);
	}
	if(r.error == 2){
		alert(r.content);
	}
	if(r.error == 0){
		var fittings = r.fittings;
		var oHtml = '';
		chooseFittingsCatId.push(r.cat_id);
		for(var i=0; i < fittings.length; i++){
      oHtml += '<li class="l_'+ r.cat_id +'">';
      oHtml += '<input type=hidden name=fitting_goods_id value="' + fittings[i].group_goods_id + '"></input>';
      oHtml += '<input type=hidden id=fitting_goods_price_'+ fittings[i].group_goods_id + ' value="' + fittings[i].goods_price + '"></input>';
      oHtml += '<input type=hidden id=fitting_shop_price_' + fittings[i].group_goods_id + ' value="' + fittings[i].org_price + '"></input>';
      oHtml += '<div class="imgBorder">';
      oHtml += '<a target="_blank" href="' + ouku.path.root + 'goods' + fittings[i].goods_id + '">';
      oHtml += '<img src="' + sImg + fittings[i].goods_thumb + '" alt="' + fittings[i].goods_name + '" height="89px">';
      oHtml += '</a>';
      oHtml += '</div>';
      oHtml += '<h3>';
      oHtml += '<a target="_blank" href="' + ouku.path.root + 'goods' + fittings[i].goods_id + '">' + fittings[i].goods_name + '</a>';
      oHtml += '</h3>';
      oHtml += '<p>原价：' + fittings[i].formatted_org_price + '</p>';
      oHtml += '<p class="bRed">套餐价：' + fittings[i].formatted_goods_price + '</p>';
      oHtml += '<p>';
      oHtml += '<input name=fitting_goods_num type=hidden value="0" id="fitting_goods_num_' + fittings[i].group_goods_id + '"></input>';
      oHtml += '<input type="image" src="' + ouku.path.img + 'images/buyGoods_1.gif" alt="购买商品" id="btn_fitting_'+ fittings[i].group_goods_id + '" onclick="add_sub_cart(' + fittings[i].group_goods_id + ')" />';
      oHtml += '</p>';
      oHtml += '</li>';
		}
		
		$('#fittingUl li').hide();
		$('#fittingUl').attr('cid',r.cat_id).append(oHtml).show();
		suit_ul('fittingUl');
	}
}
/**
 * 添加商品配件
 * @alias add_sub_cart
 * @param {Object} id   配件的id
 */
function add_sub_cart(id) {
  if(goodsStyleId == 0){ 
    alert('请选择一款有货颜色');
    return false;
  }
  var btn = $("#btn_fitting_" + id);
  var chk = $("#fitting_goods_num_" + id);
  var is_display = 0;
  if (chk.val() == "1") {
    chk.val('0');
		btn.attr('src', ouku.path.img + 'images/buyGoods_1.gif');
		var cid = $('#fitting_goods_price_' + id).parent().parent().attr('cid');
		if(cid == 0){
			$('#fitting_goods_price_' + id).parent().hide();
			var liLength = $('#fittingUl li').length;
			for (var i=0; i<liLength; i++) {
				if($('#fittingUl li').eq(i).css('display') != 'none'){
					is_display++;
				}
			}
			if(is_display == 0){
				$('#noneFitt').show();
			}
		}
		suit_ul('fittingUl'); 		
  } else {
    chk.val('1');
		btn.attr('src', ouku.path.img + 'images/cancelGoods_1.gif');
		suit_ul('fittingUl');
  }

  var fittings = document.getElementsByName("fitting_goods_id");
  var cnt = 0;
  var orisum = sum = iSum;
  for (var i = 0; i < fittings.length; ++i) {
    var num  = $("#fitting_goods_num_" + fittings[i].value).val();
    if (num == "0") continue;
    cnt += 1;
    sum += parseFloat($("#fitting_goods_price_" + fittings[i].value).val());
    orisum += parseFloat($("#fitting_shop_price_" + fittings[i].value).val());
  }
  isFitting = cnt;
  fittShopPrice = sum - iSum;
  $("#fitting_cnt").html(cnt.toString());
  $("#fitting_sum").html('￥' + ouku.format_price(sum));
  $("#fitting_ex").html(cnt > 0 ? ('节省:<span class="bRed">￥' +  ouku.format_price(orisum - sum) + '</span>元') : '');
  $("#fitMessage").html(cnt > 0 ? '' : goodsDesc);
	$('#chooseFittNum').html(cnt.toString());
}

function suit_ul(id){
	var oli = $('#' + id + ' li');
	var uWidth = 0;
	for (var i = 0; i < oli.length; i++) {
		if(oli.eq(i).css('display') != 'none'){
      uWidth += 135;			
		}
	}
	$('#' + id).width(uWidth);
}


/**
 * 快速帮助显示
 * @alias show_tag
 * @param {Object} id  层的id
 */
function show_tag(id){
	if($('#'+id).css('display') == 'none'){
    $('blockquote').css('display','none');		
	}   
  $('#'+id).css('display', $('#'+id).css('display')=='none'? '':'none');
}
/**
 * 商品详细页的tab切换效果
 * @alias show_info
 * @param {Object} a tab的当前位置
 */
//function show_info(a){
//	var oHtml='';
//	var oLiNum = $('#top li').length;
//  for(var i =1;i<oLiNum-1;i++){
//      oHtml += $("#s"+i).html();
//  }
//  for(var i=0;i<oLiNum;i++){
//    if(a == i){
//      $('#b'+i).attr('class','pTab');
//      $('#s'+i).show();
//      if(i==0){
//        $('#s0').html(oHtml);
//      }
//    }
//    else{
//      $("#b"+i).removeClass();
//      if( i!=7 ){
//        $("#s"+i).hide();
//      }
//    }
//  }
//}
/**
 * 切换tab
 * @param  {Object} tab_id
 * @param  {Object} content_id 
 * @param {Object}  i
 */
function change_goods_info(tab_id,content_id,i){
  $('#' + tab_id + ' li').attr('class', '');
  $('#' + tab_id + ' li:eq('+i+')').attr('class','on');
  $('#' + content_id + ' .goods-tab').hide();
  $('#' + content_id + ' .goods-tab:eq('+i+')').show();
}

/**
 * 价格举报js
 * @param {Object} frm 表单
 */

function report_low_price(frm){
    var data = {};
    data['message'] = $('#message_low_price').val();
    data['url'] = document.getElementById('url').value.replace('&','%26'); 
    data['goods_id'] = $('#goods_id').val();
		var sData = JSON.stringify(data);
    if(data['message'] == "" || data['message'] == "请在此输入举报内容"){ 
      alert("请输入举报内容");
      return false; 
    }else if(data['url'] == "" || data['url'] == "请在此粘贴相关网址 http://" ){
      alert("请输入举报地址");
      return false; 			
		}
    function rlp_response(r){
       alert(r.message);
       $('#message_low_price').val("请在此输入举报内容");
       $('url').val("请在此粘贴相关网址 http://");
  }
    ouku.ajax.call(ouku.path.root+'User.Controller.php?Action=add_low_price_report', {JSON:sData}, rlp_response, 'POST', 'JSON');
}

/**
 * 提交留言
 * @alias SubmitComment
 * @param {Object} storeGoodsId 商品id
 * @param {Object} storeId      店的id，ouku是1
 * @param {Object} size         留言的页数
 */
function SubmitComment(storeGoodsId, storeId, size){
  var comment = new Object;
  comment.storeGoodsId = storeGoodsId;
  comment.storeId = storeId;
  comment.message = $('#message').val();
  comment.message = comment.message.replace(/\\/g, '\\\\');
  comment.message = comment.message.replace(/\r\n/g,'');
  var ooHref = location.href;
  var aHref = ooHref.split('#');
  var typeradios = document.getElementsByName('type');
  var ochecked = false;
  var messagetype = 'goods';
  for(var i=0; i< typeradios.length; i++) {
    if(typeradios[i].checked) {
    ochecked = true;
      messagetype = typeradios[i].value;
      break;
    }
  }
  comment.type  = messagetype;
  comment.my ='my';
  if(!ochecked){
    alert('请选择对应的分类，以便我们最快为您解答！');
    return false;
  }
  if(comment.message == ''){
    alert('请输入咨询内容'); 
    return false;
  }
  var Action    = ouku.path.root + 'biaoju/submitComment.php';
  if (size) Action += "?size=" + size;
  ouku.ajax.call(Action, 'comment='+JSON.stringify(comment), SubmitCommentResponse, 'POST', 'JSON'); 
  location.href = aHref[0] + '#commentTitle';
}
/**
 * 留言回调函数
 * @alias SubmitCommentResponse
 * @param {Object} r
 */
function  SubmitCommentResponse(r) {
  if (r.errno < 0) {
    if (r.errno == -100) {
      alert(r.error);
      return false;
    }
  } else {
    $("#comments").html(r.html);
    $('#message').val('');
    return true;
  }
}
/**
 * 按留言分类读取数据
 * @alias GotoCommentType
 * @param {Object} type         留言的类型
 * @param {Object} storeGoodsId 商品id
 * @param {Object} storeId      店的id ouku 为 0
 */
function GotoCommentType(type, storeGoodsId, storeId, my){
	var my = my ? my : '';
  var Action = ouku.path.root +'biaoju/getComment.php?type=' + type+ '&storeGoodsId=' + storeGoodsId + "&storeId=" + storeId + '&my=' + my;
  ouku.ajax.call(Action,'',GotoCommentPageResponse,'post','json');
  var typeLi = document.getElementById('goodsAskType').getElementsByTagName('li');
  for(var i=0;i<typeLi.length;i++){
    if(typeLi[i].attributes['types'].value == type){
      typeLi[i].className = 'on';
    }else{
      typeLi[i].className = ''; 
    }
  }	
  $('#stabId li').attr('class','');	
	if(type == ''){
		$('#stabId li:eq(1)').attr('class','on');
	}
	if(type == 'my'){
		$('#stabId li:eq(0)').attr('class','on');
	}
	if(type=='all'){
		$('#stabId li:eq(1)').attr('class','on');
		$('#goodsAskType li:eq(0)').attr('class','on');
	}
}
/**
 * 留言翻页函数
 * @alias GotoCommentPage
 * @param {Object} storeGoodsId   商品id
 * @param {Object} storeId        店的id
 * @param {Object} page           页数
 * @param {Object} size           每页的条数
 * @param {Object} type           商品的留言类型
 */
function GotoCommentPage(storeGoodsId, storeId, page, size, type, my){
	var my = my ? my : '';
  var Action    = ouku.path.root + 'biaoju/getComment.php?storeGoodsId=' + storeGoodsId + "&storeId=" + storeId + "&page=" + page + "&my=" + my;
  if (size) Action += "&size=" + size;
  if (type) Action += "&type=" + type;
  ouku.ajax.call(Action, "", GotoCommentPageResponse, 'POST', 'JSON'); 
}
/**
 * 跳转函数
 * @alias GotoCommentPage_
 * @param {Object} g
 * @param {Object} s
 * @param {Object} ps
 * @param {Object} pc
 * @param {Object} t
 */
function GotoCommentPage_(g, s, ps, pc, t, my) {
  var pid = $("#page_id").val();
  pid = pid < pc ? pid : pc;
  pid = pid > 0  ? pid : 1;
	var my = my ? my : '';
  GotoCommentPage(g, s, pid, ps, t, my);
}
/**
 * 翻页的回调函数
 * @alias GotoCommentPageResponse
 * @param {Object} r
 */
function  GotoCommentPageResponse(r) {
  if (r.errno < 0) {
    if (r.errno == -100) {
      alert(r.error);
      return false;
    }
  } else {
    $("#comments").html(r.html);
  }
}
/**
 * 提交留言处显示不同提示信息
 * @alias show_tip
 * @param {Object} num
 */
function show_tip(num){
  if(num==4){
    $('#beforeOrder').hide();
    $('#low_price_report').show();
  }else{
    $('#low_price_report').hide();
    $('#beforeOrder').show();
    var typeLength = $('input[name="type"]').length;
    for(var i=0;i<typeLength-1;i++){
      $('#cat'+i+'_tip').hide(); 
    }
    $('#cat'+num+'_tip').show();
  } 
    
}

/**
 * 个人档案页面保存个人信息地址到收货地址
 * @alias send_to_addressbook
 * @param {Object} e
 */
function send_to_addressbook(){
  var form = document.getElementById('formedit');
  if(userEdit(form)){
    form.Action.value = "act_edit_address";
    form.user_realname.name = "consignee";
    form.user_address.name = "address";
    form.user_mobile.name = "mobile";
    var address_id = document.createElement('input');
    address_id.type ="hidden";
    address_id.name = "address_id";
    address_id.value = "";
    form.appendChild(address_id);
    form.submit();
  }
}
  
/**
 * 更换头像
 * @param {Object} k
 * @param {Object} pUrl
 */
function headChange(k, pUrl){
  for (var a in __profile){
    if (__profile[a] == k) {
      $('#profile_name').html(__profile[a]);
    }
  }
  $('#bigHeadPic').attr('src',pUrl);
}
/**
 * 表单中的判断
 * @alias userEdit
 * @param {Object} id
 */
function userEdit(obj){
	var id = '';
	if(typeof(obj)=='object'){
		id = obj.id;
	}else{
		id = obj;
	}
  ouku.validate.is_empty(id,'user_realname','请输入您的真实姓名');
	if(!ouku.validate.is_ok){
		return false;
	};
  ouku.validate.is_phone(id,'user_mobile','您输入的手机号码格式不对');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_zip(id,'zipcode','您输入的邮编格式不对');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_empty(id,'user_address','请输入您的联系地址');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_selected(id,'province','请选择所在的省');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_selected(id,'city','请选择所在的市');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_selected(id,'district','请选择所在的区');
  if(!ouku.validate.is_ok){
    return false;
  };
	return true;  	 	  						
}
/**
 * 离开表单，判断表单的值是否符合条件
 * @alias userEdit_1
 * @param {Object} i
 */
function userEdit_1(i){
  var frm = document.forms['formEdit'];
  var msg = '';
  var reg = null;
  switch(i){
  case 1: 
    if (frm.user_realname.value == 0){
			msg = '请输入您的真实姓名！';
      $('#msg_1').html(msg);
      return false;
    }else{
      $('#msg_1').html('必填');  
    }
    break;
    //}}}
    // {{{ 会员制 Zandy 2007-12-21
  case 2: 
    if (! /1\d{10}/.test(frm.user_mobile.value)){
			msg = '您输入的手机号码格式不对！';
      $('#msg_2').html(msg);
      return false;
    }else{
      $('#msg_2').html( '必填');  
    }
    break;
  case 3: 
    if (frm.province.value == 0 || frm.city.value == 0){
			msg = '您输入的省市不对！';
      $('#msg_3').html(msg);
      return false;
    }else{
      $('#msg_3').html('必选');  
    }
    break;
  case 4: 
    if (! /^\d{6}$/.test(frm.zipcode.value)){
      msg = '您输入的邮编格式不对！';
			$('#msg_4').html(msg);
      return false;
    }else{
      $('#msg_4').html('必填');  
    }
    break;
  case 5: 
    if(frm.user_address.value == ""){
      msg = '请输入正确地址！'; 
			$('#msg_5').html(msg);
      return false; 
    }else{
      $('#msg_5').html('必填');  
    }
    break;
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
/**
 * 判断表单
 * @alias addrCheck
 * @param {Object} obj
 */
function addrCheck(obj) {
	var id = '';
	if(typeof(obj) == 'object'){
		id = obj.id;
	}else{
		id = obj;
	}
  ouku.validate.is_empty(id,'consignee','请输入收获人姓名');
  if(!ouku.validate.is_ok){
    return false;
  };	
  ouku.validate.is_phone(id,'mobile','您输入的手机号码格式不对');
  if(!ouku.validate.is_ok){
    return false;
  };	
  ouku.validate.is_zip(id,'zipcode','您输入的邮编格式不对');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_email(id,'email','您输入的Email格式不对');
  if(!ouku.validate.is_ok){
    return false;
  };	
  ouku.validate.is_selected(id,'province','请选择所在的省');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_selected(id,'city','请选择所在的市');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_selected(id,'district','请选择所在的区');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_empty(id,'address','请输入详细地址');
  if(!ouku.validate.is_ok){
    return false;
  };  	
}
/**
 * 修改密码
 * @alias editPassword
 * @param {Object} obj
 */
function editPassword(obj){
  if(typeof(obj) == 'object'){
    id = obj.id;
  }else{
    id = obj;
  }	
  ouku.validate.is_empty(id,'old_password','请输入旧的密码');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_empty(id,'new_password','请输入新的密码');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_empty(id,'comfirm_password','请再次输入新的密码');
  if(!ouku.validate.is_ok){
    return false;
  };	
  ouku.validate.is_same(id,'new_password','comfirm_password','两次输入的密码不一样，请重新输入');
  if(!ouku.validate.is_ok){
    return false;
  }; 	  		
}
/**
 * 检查现金抵用券是否合法
 * @alias checkbonusCode
 * @param {Object} obj
 */
function checkbonusCode(obj) {
  if(typeof(obj) == 'object'){
    id = obj.id;
  }else{
    id = obj;
  }
  ouku.validate.is_empty(id,'bonusCode','请输入抵用券代码');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_size(id,'bonusCode',16,'抵用券为16位，请重新输入');
  if(!ouku.validate.is_ok){
    return false;
  };  		 	
}
/**
 * 红包处小提示
 * @alias appear
 * @param {Object} id
 */
function appear(id){
  var helpCont = document.getElementById(id); 
  helpCont.style.display = helpCont.style.display == "block" ? "none" : "block";
  helpCont.style.right = 0 + "px";
  helpCont.style.top = 30 +"px";
  helpCont.style.zIndex = "100";
}
/**
 * 验证用户名和密码
 * @alias capinfo
 * @param {Object} obj
 */

function capinfo(obj){
	var id = '';
  if(typeof(obj)=='object'){
    id = obj.id
  }else{
    id = obj;
  }
  ouku.validate.is_empty(id,'username','用户名不能为空');
  if(!ouku.validate.is_ok){
    return false;
  };
  ouku.validate.is_empty(id,'userauthkey','密码不能为空');
  if(!ouku.validate.is_ok){
    return false;
  };        
}