<!-- -->

var tips; var theTop = 200/*这是默认高度,越大越往下*/; var old = theTop;
function initFloatTips() {
	tips = document.getElementById('floatTips');
	moveTips(tips);
	var pIdd = readCookie("pId");
	if((pIdd != "") && (pIdd != null))
	{
		tips.style.display = "block";
		draw();
	}
};
function moveTips() {
  var tt=50;
  if (window.innerHeight) {
    pos = window.pageYOffset
  }
  else if (document.documentElement && document.documentElement.scrollTop) {
    pos = document.documentElement.scrollTop
  }
  else if (document.body) {
    pos = document.body.scrollTop;
  }
  pos=pos-tips.offsetTop+theTop;
  pos=tips.offsetTop+pos/10;
  
  if (pos < theTop) pos = theTop;
  if (pos != old) {
    tips.style.top = pos+"px";
    tt=10;
  }
  old = pos;
  setTimeout(moveTips,tt);
}

Array.prototype.remove=function(m){
	if(m<0)
	return this;
	else
	return this.slice(0,m).concat(this.slice(m+1,this.length));
}
/*
 *添加产品函数，nm:产品名称，goodId:产品ID
 */
function add(nm,goodId) {
	var pName="";
	var pIdd="";
	var pName = readCookie("pNm"); /*读取产品名称的cookie*/
	var pIdd = readCookie("pId");  /*读取产品Id的cookie*/
	if(document.getElementById("floatTips").style.display != "block") 
	{
		document.getElementById("floatTips").style.display = "block";
	}/*判断层的显示，如果原先隐藏现在将显示*/
	if((pIdd != "") && (pIdd != null))
	{
		var arrCookie = pName.split("# ");
		var arrCookieT = pIdd.split("# "); /*分离cookie字符为数组*/
  		if(arrCookieT.length < 4) /*判断对比产品的数量*/
  		{
			for(var i=0;i<arrCookieT.length;i++){
    			if(arrCookieT[i].indexOf(goodId) != -1) /*判断有没有相同产品Id,如有提示该产品已选择*/
				{
					showDialog( nm+'已选择！');
				}
				else
				{
					floatCookie("pNm",nm,30);
					floatCookie("pId",goodId,30); /*创建新产品cookie*/
				}
			}	
  		}
		else
		{
			showDialog('最多只能有4款机型参加比较！');
		}
	}
	else
	{
		floatCookie("pNm",nm,30);
		floatCookie("pId",goodId,30);
	}
	draw();
}

/*
 *对比篮内容显示函数
 */
 
function draw() {
	var out = [];
	var pName = readCookie("pNm");
	var pIdd = readCookie("pId");
	if(( pIdd!= "") && (pIdd != null))
	{
		var arrCookie = pName.split("# ");
		var arrCookieT = pIdd.split("# ");
		for(key in arrCookie)
		{
			out[key]= "<li><span style=\"display:block;width:110px;overflow:hidden;white-space:nowrap;text-align:left;margin-left:5px;float:left;\">"+arrCookie[key]+"</span><img src=\""+path+"themes/ouku/images/close2.gif\" alt=\"关闭\" class=\"close2\" onclick=\"delCookie("+key+")\" /></li>";
		}/*将显示的格式存储在数组里*/
		outs = out.reverse().join(""); /*反转数组，使用空进行链接*/
		document.getElementById('com_item').innerHTML = outs; /*插入html*/
	}	
}

/*
 *删除对比产品函数
 */

function delCookie(k){
	var out = [];
	var nameValue = "";
	var idValue = "";
	var pName = readCookie("pNm");
	var pIdd = readCookie("pId");
	var arrCookie = pName.split("# ");
	var arrCookieT = pIdd.split("# ");
	arrCookie.remove(k);
	arrCookieT.remove(k);/*删除指定的产品cookie*/
	if((pIdd != null)&&(pIdd != ""))
	{
		for(var i=0;i<arrCookie.length;i++)
		{	
			nameValue += arrCookie[i] +"# ";
			idValue += arrCookieT[i] + "# ";
		}
		nameValue =nameValue.substring(0,nameValue.length-2);
		idValue =idValue.substring(0,idValue.length-2);
		if(arrCookie.length == 0)
		{
			document.getElementById('com_item').innerHTML = "";
			eraseCookie("pNm");
			eraseCookie("pId");
		}
		else
		{		
			for(key in arrCookie)
			{
				out[key]= "<li><span style=\"display:block;width:110px;overflow:hidden;white-space:nowrap;text-align:left;margin-left:5px;float:left;\">"+arrCookie[key]+"</span><img src=\""+path+"themes/ouku/images/close2.gif\" alt=\"关闭\" class=\"close2\" onclick=\"delCookie("+key+")\" /></li>";
			}
			outs = out.reverse().join("");
			document.getElementById('com_item').innerHTML = outs;	
			createCookie("pNm",nameValue,7);
			createCookie("pId",idValue,7);
		}
	}	
}



/*
 *创建对比产品cookie函数
 */

function floatCookie(name,value,days){
	var nameValue = readCookie(name);
	if((nameValue == "") || (nameValue == null))
	{
		createCookie(name,value,days);
	}
	else
	{
		if(nameValue.indexOf(value) == -1)
		{
			var arrCookie = nameValue.split("# ");
			var valueLength = arrCookie.length;
			if(valueLength > 4)
			{
				nameValue = "";
				for(var v=0; v<4;v++)
				{
					nameValue += arrCookie[v] +"# " ;
				}
				nameValue =nameValue.substring(0,nameValue.length-1);
			}
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*1000));
			var expires = "; expires=" + date.toGMTString();
			document.cookie = name+"="+encodeURIComponent(value+"# " +nameValue)+expires+"; path=/";
		}	
	}		
}

/*
 *创建cookie函数
 */
 
function createCookie(name,value,days){
	if(days){
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+encodeURIComponent(value)+expires+"; path=/";	
}

/*
 *读取cookie函数
 */

function readCookie(name){
	var aCookie = document.cookie.split("; ");
	for (var i=0; i < aCookie.length; i++)
	{
    var aCrumb = aCookie[i].split("=");
    if (name == aCrumb[0])
      return decodeURIComponent(aCrumb[1]);
  	}
  	return null;
}

/*
 *删除cookie函数
 */
 
function eraseCookie(name) {
	createCookie(name,"",-1);
}
