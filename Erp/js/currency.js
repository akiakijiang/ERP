function	createPoint(){
	var	currencyCode	=	document.getElementById("currencyCode");
	var	address_id =	document.getElementById('address_id');
	if(currencyCode.value.length != 0){
		    Ajax.call(path+'currency.Controller.php?Action=jsCreatePoint', 'currencyCode=' + currencyCode.value +'&address_id='+address_id.value, createPointResponse, "POST", "TEXT");
	}
}

function	createPointResponse(result){
	var	currencyResponse	=	document.getElementById("ECS_ORDERTOTAL");
	var res	=		result.parseJSON();
	
	if(res.error == true ){
		// 扣除欧币
		showDialog(res.info);
	}else{
		currencyResponse.innerHTML	=	res.info;
	}
}

function	editPoint(pointValue){
	var	pointValue	=	pointValue;
	if((pointValue.length != 0)||(pointValue <= 0)){
		var	address_id =	document.getElementById('address_id').value;
	  Ajax.call(path+'currency.Controller.php?Action=testingPoint', 'pointValue=' + pointValue +'&address_id='+address_id, pointValueResponse, "POST", "TEXT");
	}else{
		alert('给的欧币数量不对哦');
		return false;
	}
}

function	pointValueResponse(result){
	try{
		var	currencyResponse	=	document.getElementById("ECS_CURRENCY");
		var	CurrencyNoticeInfo	=	document.getElementById("CurrencyNoticeInfo");
		var res	=		result.parseJSON();
		
		if(res.error == true ){
			// 扣除欧币
			showDialog(res.info);
		}else{
			currencyResponse.innerHTML	=	(typeof res == "object") ? res.info:res;
			CurrencyNoticeInfo.innerHTML	=	(typeof res == "object") ? res.CurrencyNoticeInfo:res;
		}
	}catch(ex){currencyResponse.innerHTML	= '未知错误请重新购买或联系欧酷服务人员'}
}



function	textingGiftTicket(){
		var	GiftTicketValue	=	document.getElementById('giftTicketValue').value;
	
		var	address_id =	document.getElementById('address_id').value;
		
		if (GiftTicketValue == "") {
			showDialog("请输入现金抵用券号码");
			return false;
		}		
		
		if (GiftTicketValue.length != 16) {
			showDialog("抵用券号码不正确");
			document.getElementById('giftTicketValue').focus();
			return false;
		}
			
		if(GiftTicketValue.length != 0){
		    Ajax.call(path+'currency.Controller.php?Action=textingGiftTicket', 'giftTicketValue=' + GiftTicketValue +'&address_id='+address_id, GiftTicketValueResponse, "POST", "TEXT");
	}	

}


function	GiftTicketValueResponse(result){
	 	
		try{
			var	giftTicketNoticeInfo	=	document.getElementById("giftTicketNoticeInfo");
			
			var res	=		result.parseJSON();
			if(res.error == true ){
				// 扣除欧币
				showDialog(res.info);
			}else{
				giftTicketNoticeInfo.innerHTML	=	(typeof res == "object") ? res.info:res;
			}
			
		}catch(ex){currencyResponse.innerHTML	= '未知错误请重新购买或联系欧酷服务人员'}
}



function	useGiftTicket(){
	var	giftTicket	=	document.getElementById("giftTicketValue");
	var	theFormObj	=	document.forms['theForm'];
	
	var	address_id =	document.getElementById('address_id').value;
	var	payment		=	theFormObj.elements['payment'].value
	
	if(giftTicket.value.length != 0){
		    Ajax.call(path+'currency.Controller.php?Action=useGiftTicket', 'giftTicketValue=' + giftTicket.value +'&address_id='+address_id, giftTicketValueResponse, "POST", "TEXT");
	}	
}

function	giftTicketValueResponse(result){
	try{
		var	currencyResponse	=	document.getElementById("ECS_ORDERTOTAL");
	}catch(ex){currencyResponse.innerHTML	= '未知错误请重新购买或联系欧酷服务人员'}
}