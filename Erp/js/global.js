
function showPrice(price)
{
	var checkObject = document.getElementsByName('fittings'); 
	for (var i = 0; i < checkObject.length; i++) 
	{ 
		if(checkObject[i].checked == true){
			price_total	=	parseFloat(price_total) + parseFloat(checkObject[i].title);
		}
	}
	var	totalPrice	=	price_total + parseFloat(shop_price_total);
	document.getElementById('TotalPrice').innerHTML =  '￥'+totalPrice ;
	price_total = 0;
}

function showImgs(id, _path, y)
{
	if (typeof(_path) == 'undefined'){
		_path = '';
	}
	if (typeof(y) == 'undefined'){
		y = '';
	}
	show_opinion(id);
	for(var i = 1;i <= id;i++){
		document.getElementById('star'+i).src = _path + 'themes/ouku/images/'+y+'star1.gif';
	}
	for(var j = (id+1);j <= 5;j++){
		document.getElementById('star'+j).src = _path + 'themes/ouku/images/'+y+'star2.gif';
	}
	try {
		document.getElementById('user_rank').value = id;
	} catch(e) {
		//
	}
}

var tempscore = 0;
var lastscore = 0;

function	outImgs(_path, y)
{
	if (typeof(_path) == 'undefined'){
		_path = '';
	}
	if (typeof(y) == 'undefined'){
		y = '';
	}
	for(var i=1;i<=lastscore;i++)
		document.getElementById('star'+i).src = _path + 'themes/ouku/images/'+y+'star1.gif';
	for(var i=(lastscore+1);i<=5;i++)
		document.getElementById('star'+i).src = _path + 'themes/ouku/images/'+y+'star2.gif';
	show_opinion(lastscore);
}

function show_opinion(id){
	if(id == 1)
		document.getElementById('opinion').innerHTML = '太令人失望了!';	
	else if(id == 2)
		document.getElementById('opinion').innerHTML = '很普通呀!';
	else if(id == 3)
		document.getElementById('opinion').innerHTML = '值得购买了!';
	else if(id == 4)
		document.getElementById('opinion').innerHTML = '强烈推荐!';
	else if(id == 5)
		document.getElementById('opinion').innerHTML = '简直就是极品!';
	else 
		document.getElementById('opinion').innerHTML = '请给个评价';	
}

function innerPF(pollvalue, id)
{
	if (typeof(path) == 'undefined'){
		path = '';
	}
	var	Action	=	'Poll';
	Ajax.call(path+'User.Controller.php?Action='+Action, 'id='+id+'&PollValue=' +pollvalue, PollResponse, 'POST', 'JSON');
	tempscore = pollvalue;
}	

function innerPF2(pollvalue, id, price, path)
{
	if (typeof(path) == 'undefined'){
		path = '';
	}
	var	Action	=	'Poll';
	Ajax.call(path+'User.Controller.php?Action='+Action, 'id='+id+'&PollValue=' +pollvalue + '&price=' + price, PollResponse2, 'POST', 'JSON');	

}

function PollResponse(result)
{
	 showDialog(result.message);
	 
	 // 修改商品信息页面的用户评分显示
	 if (result.comment_rank != null) {
		 url = document.getElementById('comment_rank_img').src;
		 sm_index = url.indexOf('images/smile/sm');
		 new_url = url.substring(0, sm_index) + 'images/smile/sm' + result.comment_rank + '.gif';
		 document.getElementById('comment_rank_img').src = new_url;
	 }
	 if(result.error == 0)
	 {
		lastscore = tempscore;	
	 }	 
}


function SubmitComment(storeGoodsId, storeId, size){
	var comment	=	new	Object;
	comment.storeGoodsId = storeGoodsId;
	comment.storeId = storeId;
	comment.message	=	document.getElementById('message').value;
	comment.message	=	comment.message.replace(/\\/g, '\\\\');
	comment.message	=	comment.message.replace(/\r\n/g,'');
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
	comment.type	=	messagetype;
	comment.my ='my';
	//comment.user_mobile	=	document.getElementById('user_mobile').value;
	if(!ochecked){
		alert('请选择对应的分类，以便我们最快为您解答！');
		return false;
	}
	if(comment.message == ''){
		alert('请输入咨询内容');	
		return false;
	}
	var Action		=	path + 'biaoju/submitComment.php';
	if (size) Action += "?size=" + size;
	Ajax.call(Action, 'comment=' +comment.toJSONString(), SubmitCommentResponse, 'POST', 'JSON');	
	location.href = aHref[0] + '#commentTitle';
}
function	SubmitCommentResponse(r) {
	if (r.errno < 0) {
		if (r.errno == -100) {
			alert(r.error);
			return false;
		}
	} else {
		document.getElementById("comments_my").innerHTML = r.html;
		document.getElementById('message').value = "";
		if (r.storeId == 0)
			//alert("留言发表成功，请耐心等待管理员的回复");
			return true;
		else
			//alert("留言发表成功，请耐心等待掌柜的回复");
			return true;
	}
}

function GotoCommentType(type, storeGoodsId, storeId){
	var Action = path +'biaoju/getComment.php?type=' + type+ '&storeGoodsId=' + storeGoodsId + "&storeId=" + storeId;
	Ajax.call(Action,'',GotoCommentPageResponse,'POST','JSON');
	var typeLi = document.getElementById('goodsAskType').getElementsByTagName('li');
	for(var i=0;i<typeLi.length;i++){
		if(typeLi[i].attributes['types'].value == type){
			typeLi[i].className = 'on';
		}else{
			typeLi[i].className = '';	
		}
	}
	
}

function GotoCommentPage(storeGoodsId, storeId, page, size,type){
	var Action		=	path + 'biaoju/getComment.php?storeGoodsId=' + storeGoodsId + "&storeId=" + storeId + "&page=" + page;
	if (size) Action += "&size=" + size;
	if (type) Action += "&type=" + type;
	Ajax.call(Action, "", GotoCommentPageResponse, 'POST', 'JSON');	
}

function GotoCommentPage_(g, s, ps, pc, t) {
  var pid = document.getElementById("page_id").value;
  pid = pid < pc ? pid : pc;
  pid = pid > 0  ? pid : 1;
  GotoCommentPage(g, s, pid, ps, t);
}

function	GotoCommentPageResponse(r) {
	if (r.errno < 0) {
		if (r.errno == -100) {
			alert(r.error);
			return false;
		}
	} else {
		document.getElementById("comments").innerHTML = r.html;
	}
}


function SumbitBBS(_do){
	if (typeof (__bbsApiPath) == 'undefined') {
		__bbsApiPath = '';
	}
	var bbsInfo	=	new	Object;
	try {
		bbsInfo.tid		=	document.getElementById('tid').value;
		bbsInfo.message	=	document.getElementById('message').value;
	} catch (ee) {
		bbsInfo.tid		=	'';
		bbsInfo.message	=	'';
	}
	if (_do != 1) {
		_do = '';
	}
	var Action		=	'bbsPost';
	bbsInfo.message	=	bbsInfo.message.replace(/\\/g, '\\\\');
	bbsInfo.info	=	GoodId+','+goods_name;
	bbsInfo.username	=	document.getCookie('ECS[username]');

	if(bbsInfo.tid){
		Ajax.call(__bbsApiPath + 'bbsApi.Post.php?Action='+Action, 'bbsInfo=' +bbsInfo.toJSONString()+'&_do='+_do, SumbitBBSResponse, 'POST', 'JSON');	
	}else{
		Ajax.call(__bbsApiPath + 'bbsApi.Post.php?Action='+Action, 'bbsInfo=' +bbsInfo.toJSONString()+'&_do='+_do, SumbitBBSResponse, 'POST', 'JSON');	 // JSON TEXT
	}
	try {
		___do_load = 0;
	} catch (eee) {
	}
}

function	SumbitBBSResponse(r)
{
	var comments = _('comments');

	//alert(r);alert('test');return false;
	for (var a in r) {
		//alert(a+': '+r[a]);
	}
	if (r.errno < 0) {
		if (r.errno == -100) {
			alert(r.error);
			return false;
		}
		//alert(r.error);
		//comments.innerHTML = '<p align="center">数据读取错误...</p>';
		comments.innerHTML = '<p align="center">'+ r.error +'</p>';
		return false;
	}

	var strx = '<p align="center">暂无评论...</p>';

	//document.getElementById('message').value = r;return false;
	if (typeof r['forumLink'] != 'undefined') {
		//_('viewAllComment').href = r['forumLink'];
		//_('viewAllComment').target = "_blank";
	}

	if (typeof r['list'] == 'undefined') {
		comments.innerHTML = strx;
		return false;
	}
	//return false;

	var str = '';
	for (var m in r['list']) {
		if (typeof r['list'][m]['message'] == 'undefined') continue;
		str += '<div style="border-bottom:1px dashed #ccc;padding-bottom:5px;">';
		str += '<p><strong>'+ r['list'][m]['author']+'\：</strong><span class="comContent">'+r['list'][m]['message'].replace("<br>","")+'</span>发表于 '+ r['list'][m]['dateline'] +' </p>';
		if (typeof r['list'][m]['comment'] != 'undefined') {
			str += '<div style="padding: 6px 24px;">';
			for (var nn in r['list'][m]['comment']) {
				if (typeof r['list'][m]['comment'][nn]['message'] == 'undefined') continue;
				str += '<p><strong style="color: #f00;">'+ r['list'][m]['comment'][nn]['author'] +'</strong>回复于 '+ r['list'][m]['comment'][nn]['dateline'] +' </p>';
				str += '<p class="comContent" style="color:#f00;">'+ r['list'][m]['comment'][nn]['message'] +'</p>';
			}
			str += '</div>';
		}
		str += '</div>';
		/*
		if (typeof r['forumLink'] != 'undefined') {
			//_('viewAllComment').href = r['forumLink'];
			//_('viewAllComment').target = "_blank";
			_('commentTitle').innerHTML = '<span style="float: right; padding-right: 12px;"><a href="'+ r['forumLink'] +'">查看更多</a></span>用户咨询';
		} else {
			_('commentTitle').innerHTML = '<span style="float: right; padding-right: 12px;"></span>最新咨询';
		}
		*/
	}
/*
	for (var m in r['list']) {
		if (typeof r['list'][m]['message'] == 'undefined') continue;
		str += '<div class="Comm_Ma">';
		str += r['list'][m]['message'];
		str += '<span class="Font_B">[<a href="'+r['list'][m]['url']+'" target="_blank">查看更多</a>]</span>';
		str += '</div>';
		str += '<div class="Comm_Th" style="text-align:right"><span class="Font_B">'+r['list'][m]['author']+'</span> <!--[黄金会员] -->发表于 '+r['list'][m]['dateline']+'</div>';
		if (typeof r['list'][m]['comment'] != 'undefined') {
			str += '<div class="Comm_Ma3" style="width:698px; margin-left:45px;">';
			str += '<div style="float:left;background-color:#fafbfd; border:#eaeaeb 1px solid">';
			str += '<div style="float:left;color:#ff6600">回复</div>';
			for (var nn in r['list'][m]['comment']) {
				if (typeof r['list'][m]['comment'][nn]['message'] == 'undefined') continue;
				str += '<div style="float:right;width:672px">';
				str += '<span style="float:left;"> <font style="color:#ff6600">&raquo; </font>'+r['list'][m]['comment'][nn]['message']+'</span>';
				str += '<span style="width:348px;float:right;text-align:right"><font class="Font_B">'+r['list'][m]['comment'][nn]['author']+'</font> <!--[黄金会员] -->发表于 '+r['list'][m]['comment'][nn]['dateline']+'</span>';
				str += '</div>';
			}
			str += '</div>';
			str += '</div>';
		} // end reply
		str += '<div class="Comm_Ma2">';
		str += '<span style="float:left">评分：</span>';
		str += '<span style="float:left;width:490px">';
		str += '</span>';
		str += '<span style="width:60px;float:left;text-align:center;height:18px;color:#ff6600"><a href="javascript:SumbitAA('+r['list'][m]['tid']+', -1);">支持</a> (<span id="agree_'+r['list'][m]['tid']+'">'+r['list'][m]['agree']+'</span>)</span>';
		str += '<span style="width:60px;float:left;text-align:center;height:18px;color:#ff6600"><a href="javascript:SumbitAA('+r['list'][m]['tid']+', -2);">反对</a> (<span id="against_'+r['list'][m]['tid']+'">'+r['list'][m]['against']+'</span>)</span>';
		str += '<span style="width:40px;float:left;text-align:center;height:18px;color:#ff6600"><A href="'+r['list'][m]['reply']+'" target="_blank">回复</A></span>';
		str += '<span style="width:40px;float:left;text-align:center;height:18px;color:#ff6600"><a href="'+r['list'][m]['report']+'" target="_blank">举报</a></span>';
		str += '</div>';
	} // end comment
*/
	if (str == '') {
		comments.innerHTML = strx;
		return false;
	}
	//alert(str);
	comments.innerHTML = str;
	_('message').value = '';
}

function SumbitAA(tid, aa){
	if (typeof (__bbsApiPath) == 'undefined') {
		__bbsApiPath = '';
	}
	var bbsInfo	=	new	Object;
	bbsInfo.tid		=	tid;
	var Action		=	'bbsPost';
	bbsInfo.message = '';
	bbsInfo.aa	=	aa;
	bbsInfo.info	=	partent_show+','+brand_name+','+goods_name;
	//alert(bbsInfo.toJSONString());
	Ajax.call(__bbsApiPath + 'bbsApi.Post.php?Action='+Action, 'bbsInfo=' +bbsInfo.toJSONString(), SumbitAAResponse, 'POST', 'JSON'); 
}

function SumbitAAResponse(r)
{
	//document.getElementById('message').value = r;return false;
	for (var a in r) {
		//alert(a+': '+r[a]);
	}
	try {
		_('agree_'+r['tid']).innerHTML = r['agree'];
		_('against_'+r['tid']).innerHTML = r['against'];
	} catch(e) {
		alert(e.message);
	}
	alert(r['message']);
}


function _(s) {
	return document.getElementById(s);
}

/*
if (document.all) {
	window.attachEvent('onload', SumbitBBS);
	//document.getElementById('message').value = '';
} else {
	window.addEventListener('load', SumbitBBS, false);
	//document.getElementById('message').value = '';
}*/

function	showBBS(GoodsId)
{
	document.getElementById('BBSurl').src = 'Page.php?GoodsId='+GoodsId;
}


/**************************************************** 专区用 ***********************************************************/


function SumbitBBS2(_do){
	if (typeof (__bbsApiPath) == 'undefined') {
		__bbsApiPath = '';
	}
	var bbsInfo	=	new	Object;
	try {
		bbsInfo.tid		=	document.getElementById('tid').value;
		bbsInfo.message	=	document.getElementById('message').value;
	} catch (ee) {
		bbsInfo.tid		=	'';
		bbsInfo.message	=	'';
	}
	if (_do != 1) {
		_do = '';
	}
	var Action		=	'bbsPost';
	bbsInfo.message	=	bbsInfo.message.replace(/\\/g, '\\\\');
	bbsInfo.info	=	GoodId+','+goods_name;
	bbsInfo.username	=	document.getCookie('ECS[username]');

	if(bbsInfo.tid){
		Ajax.call(__bbsApiPath + 'bbsApi.Post.php?Action='+Action, 'bbsInfo=' +bbsInfo.toJSONString()+'&_do='+_do, SumbitBBSResponse2, 'POST', 'JSON');	
	}else{
		Ajax.call(__bbsApiPath + 'bbsApi.Post.php?Action='+Action, 'bbsInfo=' +bbsInfo.toJSONString()+'&_do='+_do, SumbitBBSResponse2, 'POST', 'JSON');	 // JSON TEXT
	}
	try {
		___do_load = 0;
	} catch (eee) {
	}
}

function	SumbitBBSResponse2(r)
{
	var comments = _('comments');

	if (r.errno < 0) {
		if (r.errno == -100) {
			alert(r.error);
			return false;
		}
		comments.innerHTML = '<p align="center">'+ r.error +'</p>';
		return false;
	}

	var strx = '<p class="bgc">暂无评论...</p>';

	if (typeof r['forumLink'] != 'undefined') {
	}

	if (typeof r['list'] == 'undefined') {
		comments.innerHTML = strx;
		return false;
	}

	var str = '';
	for (var m in r['list']) {
		if (typeof r['list'][m]['message'] == 'undefined') continue;
		str += '<div>';
		str += '<p class="bgc"><strong>'+ r['list'][m]['author'] +'</strong><!--[VIP]-->发表于 '+ r['list'][m]['dateline'] +' </p>';
		str += '<p class="text">'+ r['list'][m]['message'] +'</p><p style="text-align: right;" class="text"><a href="'+ r['list'][m]['reply'] +'" target="_blank">[回复]</a> <a href="'+ r['list'][m]['url'] +'" class="read" target="_blank">[查看全文]</a></p>';
		if (typeof r['list'][m]['comment'] != 'undefined') {
			str += '<div style="padding: 6px 24px;">';
			for (var nn in r['list'][m]['comment']) {
				if (typeof r['list'][m]['comment'][nn]['message'] == 'undefined') continue;
				str += '<p class="bgc"><strong>'+ r['list'][m]['comment'][nn]['author'] +'</strong><!--[VIP]-->回复于 '+ r['list'][m]['comment'][nn]['dateline'] +' </p>';
				str += '<p class="text">'+ r['list'][m]['comment'][nn]['message'] +'</p>';
			}
			str += '</div>';
		}
		str += '</div>';
		_('commentTitle').innerHTML = '<span style="float: right; padding-right: 12px;"><a href="'+ r['list'][m]['url'] +'">查看更多</a></span>最新评论';

	}

	if (str == '') {
		comments.innerHTML = strx;
		return false;
	}
	//alert(str);
	comments.innerHTML = str;
	_('message').value = '';
}

/**************************************************** 专区用 ***********************************************************/
