// JavaScript Document

/**
 * CloseId ：关闭层的ID
 * OpenId ：所打开层的ID
 * OpenClass ： 触发层蒙板效果的 class
*/
(function($){
	$.fn.cDiv = function(CloseId, OpenId, OpenClass){
		return this.each(function(){
			$(this).css("visibility","hidden");
			$(OpenId+" :input").css("visibility","hidden");
			$(OpenClass).click(function(){
				$(OpenId).css("visibility","visible");
				$("#oDiv").css("visibility","visible");
				$("td :input").css("visibility","hidden");
				$(".selectt").css("visibility","hidden");
				$(OpenId+" :input").css("visibility","visible");
			});
			$(CloseId).click(function(){
				$(OpenId).css("visibility","hidden");
				$("#oDiv").css("visibility","hidden");
				$("td :input").css("visibility","visible");
				$(".selectt").css("visibility","visible");
				$(OpenId+" :input").css("visibility","hidden");
			});
		});
	}
})(jQuery);