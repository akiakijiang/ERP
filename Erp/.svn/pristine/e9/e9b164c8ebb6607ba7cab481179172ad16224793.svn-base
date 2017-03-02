$(document).ready(function(){ 
	Date.prototype.Format = function(fmt)   
	{   
		var o = {   
		    "M+" : this.getMonth()+1,                 //月份   
		    "d+" : this.getDate(),                    //日   
		    "h+" : this.getHours(),                   //小时   
		    "m+" : this.getMinutes(),                 //分   
		    "s+" : this.getSeconds(),                 //秒   
		    "q+" : Math.floor((this.getMonth()+3)/3), //季度   
		    "S"  : this.getMilliseconds()             //毫秒   
		};   
		if(/(y+)/.test(fmt))   
			fmt=fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));   
		for(var k in o)   
			if(new RegExp("("+ k +")").test(fmt))   
				fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));   
			return fmt;   
		}

	/*
	*	这一部分是用于在表格中鼠标划过列表显示剩余内容
	*/

	$(".tooltip_class").mouseover(function(event){
		var title = $(this).attr("data-title");
		if(title == ""){
		}else{
			var station = $(this).offset();
			$(".tooltip_hover").text(title);
			$(".tooltip_hover").css("display","block");
			$(".tooltip_hover").css("left",station.left+$(this).width());
			$(".tooltip_hover").css("top",station.top+10);
		}	

	});
	$(".tooltip_class").mouseout(function(){
		$(".tooltip_hover").css("display","none");
	});
	// 根据叠加类型的选择切换后面的文本框
	$("#repeat_type").change(function(){
		var val = $(this).children('option:selected').val();
		// alert(val);
		if (val == "ONCE"){
			$(".show_max_gift").css('display', 'none');
		}else{
			$(".show_max_gift").css('display', 'inline-block');
		}
	});


	/*
	*	常规活动和等级活动的切换
	*/

	//  这里是等级活动和常规活动来回切换清除数据(重复部分拎出来)
	function normal_class_activity_clear(){
		//清除参与商品
		$("#goods_check").prop("data-id","unchecked");
		$("#goods_check").prop("checked",false);
		$('.goods_focus').removeAttr("disabled");
		$('.goods_focus').removeClass('input_background');
		$(".goods").find(".show_contant").removeClass("contant_border").children().remove();
		goods_cache.length = 0;

		//清除排除商品
		$("#goods_excluded_check").prop("data-id","unchecked");
		$("#goods_excluded_check").prop("checked",false);
		$('.goods_excluded_focus').removeAttr("disabled");
		$('.goods_excluded_focus').removeClass('input_background');
		$(".goods_excluded").find(".show_contant").removeClass("contant_border").children().remove();
		goods_excluded_cache.length = 0;

		//清除参与类目
		$("#cat_included_check").prop("data-id","unchecked");
		$("#cat_included_check").prop("checked",false);
		$('.cat_included_focus').removeAttr("disabled");
		$('.cat_included_focus').removeClass('input_background');
		$(".cat_included").find(".show_contant").removeClass("contant_border").children().remove();
		cat_included_cache.length = 0;

		//清除排除类目
		$("#cat_excluded_check").prop("data-id","unchecked");
		$("#cat_excluded_check").prop("checked",false);
		$('.cat_excluded_focus').removeAttr("disabled");
		$('.cat_excluded_focus').removeClass('input_background');
		$(".cat_excluded").find(".show_contant").removeClass("contant_border").children().remove();
		cat_excluded_cache.length = 0;

	}
// 选择活动类型：常规活动
	$(".normal_activity").click(function(event){
		if($(".normal_activity_display").css("display")!= "block"){
			if(confirm("此跳转会将等级活动中的数据清空,确定要跳转么？")){
				$(".class_activity_display").fadeOut();
				normal_class_activity_clear();
				$(".add_by_button").remove();
				$(".clear_class_activity_add").each(function(){
					$(this).val("");
				});
				$(".normal_activity_display").fadeIn();
				$(".normal_activity").addClass("class_check_color");
				$(".class_activity").removeClass("class_check_color");

			}
		}
	});
	// 等级活动
	$(".class_activity").click(function(event){
		if($(".class_activity_display").css("display")!= "block"){
			if(confirm("此跳转会将常规活动中的数据清空,确定要跳转么？")){
				$(".normal_activity_display").fadeOut();
				normal_class_activity_clear();
				//这里是清除赠品的信息
				$("#first_gift").val("");
				$("#gift_limit_first").val("");
				$("#second_gift").val("");
				$("#gift_limit_second").val("");
				$("#third_gift").val("");
				$("#gift_limit_third").val("");
				$("#each_num").val("");
				$("#least_number").val("");
				$("#least_number").val("");
				$("#least_payment").val("");
				$(".class_activity_display").fadeIn();
				$(".class_activity").addClass("class_check_color");
				$(".normal_activity").removeClass("class_check_color");
			}
		}
	});

	/*
	*	这里是在等级活动中点击了新增按钮增加赠品,库存,满赠价格,满赠件数,赠送件数
	*/

	var class_activity_add_new_index=3;//赠品数量标识
	// 新增赠品
	$("#class_activity_add_new").click(function(){
		var class_activity_add_html = "<div class='base_gift add_by_button'>"+
		"<div class='inline_div word ui-widget'><p>赠品"+class_activity_add_new_index+"：</p>"+
		"<div class='inline_div_small ui-widget'>"+
		"<input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift_name class_activity_add_gift class_activity_added_data class_activity_add_must_full'>"+
		"</div>"+
		"<span class='must_add margin_left_must_add'>*</span>" +
		"</div>"+
		"<div class='inline_div word second_word'><p>赠品"+class_activity_add_new_index+"数量：</p><input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift class_activity_add_gift_limit class_activity_add_must_full class_activity_add_kucun'></div>"+
		"<span class='must_add margin_left_must_add'>*</span>"+
		"<div class='inline_div word second_word'><p>满赠金额：</p><input class='new_active_input class_activity_add_input_2px class_activity_add_zengpin_gift class_activity_add_gift_least_payment class_activity_add_must_full'></div>"+
		"<span class='must_add margin_left_must_add'>*</span>"+
		"<div class='inline_div word second_word'><p>满赠件数：</p><input class='new_active_input class_activity_add_input_1px class_activity_add_zengpin_gift class_activity_add_gift_least_number class_activity_add_must_full'></div>"+
		"<span class='must_add margin_left_must_add'>*</span>"+
		"<div class='inline_div word second_word'><p>每单赠送件数：</p><input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift class_activity_add_gift_each_num class_activity_add_must_full class_activity_add_zengsongjianshu'></div>"+
		"<span class='must_add margin_left_must_add'>*</span>"+
		"</div>";

		$(".class_activity_display").find(".class_activity_add").append(class_activity_add_html);
		$(".class_activity_added_data").autocomplete({
			source: goods_gift
		});
		// var position = $( ".class_activity_added_data" ).autocomplete( "option", "position" );
		// console.log(position);
		class_activity_add_new_index++;	
		class_activity_add();//每单赠送件数与库存数量比较					  
	});

	/*
	*	这里是对每次的赠送件数与相对应的库存量的比较
	*/
	function class_activity_add(){
		// 每单赠送数量
		$(".class_activity_add_zengsongjianshu").focus(function(){
			if($(this).hasClass('input_border_color')){
				$(this).removeClass('input_border_color');
				// $(this).parent().parent().find(".class_activity_add_kucun").removeClass('input_border_color');
			}
		});
		$(".class_activity_add_zengsongjianshu").blur(function(){
			var class_activity_add_zengsongjianshu_value = $(this).val();//每单赠送数量
			var class_activity_add_kucun_value = $(this).parent().parent().find(".class_activity_add_kucun").val();//库存数量（赠品一数量）
			if(Number(class_activity_add_zengsongjianshu_value) > Number(class_activity_add_kucun_value)){
				$(this).addClass('input_border_color');//红色警告
				// $(this).parent().parent().find(".class_activity_add_kucun").addClass('input_border_color');
				// alert("赠送件数不能大于库存量");
			}
		});
	}
	class_activity_add();

	/*
	*	以下部分主要是用于新增活动的操作的数据缓存
	*/

	var goods_necessary_cache = new Array();
	var facility_cache =new Array();
	var distributor_cache =new Array();
	var region_cache =new Array();
	var goods_cache =new Array();
	var goods_excluded_cache = new Array();
	var cat_included_cache = new Array();
	var cat_excluded_cache = new Array();
	function contains(arr, val) {
		if(arr.indexOf(val) !== -1){
			return false;   //表示在数组中
		}else{
			return true;  //表示不在数组中
		}
	} 

	/*
	*	这一段是处理新增活动的增加
	*/
	 // 新增固定商品
	$(".goods_necessary_choose").click(function(){
//		if(goods_necessary_cache !=""){
//			alert("只能选择一个固定商品");
//		}else{
			var show = $("#ui-id-10").next().children("div:last").text();//获取值
			if(show != ""){
				if(contains(goods_necessary_cache,show))//如果show不存在于缓存数组中
				{
					goods_necessary_cache.push(show);//添加到缓存数组中
					var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin goods_necessary_delete' aria-hidden='true'><img src='remove.png'></span></div>";
					$(".goods_necessary").find(".show_contant").append(contants);//固定商品显示
					if(goods_necessary_cache != ""){
						$(".goods_necessary").find(".show_contant").addClass("contant_border");
					}
				}else{
					alert("请勿重复选择");
				}
			}else{
				alert("请先进行选择");
			}
//		}
	});
	// 删除
	$(document).on('click','.goods_necessary_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			goods_necessary_cache.splice($.inArray(contant,goods_necessary_cache),1);//删除缓存数组中的数据
			$(this).parent().fadeOut().remove();
			if(goods_necessary_cache == ""){
				$(".goods_necessary").find(".show_contant").removeClass("contant_border");
			}
		}
	});

// 仓库选择，解析同上
	$(".facility_choose").click(function(){
		var show = $("#ui-id-1").next().children("div:last").text();	
		if(show != ""){
			if(contains(facility_cache,show))
			{
				facility_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin facility_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".facility").find(".show_contant").append(contants);
				if(facility_cache != ""){
					$(".facility").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.facility_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			facility_cache.splice($.inArray(contant,facility_cache),1);
			$(this).parent().fadeOut().remove();
			if(facility_cache == ""){
				$(".facility").find(".show_contant").removeClass("contant_border");
			}
		}
	});
// 分销商选择，解析同上
	$(".distributor_choose").click(function(){
		var show = $("#ui-id-2").next().children("div:last").text();
		if(show != ""){
			if(contains(distributor_cache,show))
			{
				distributor_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin distributor_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".distributor").find(".show_contant").append(contants);
				if(distributor_cache != ""){
					$(".distributor").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.distributor_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			distributor_cache.splice($.inArray(contant,distributor_cache),1);
			$(this).parent().fadeOut().remove();
			if(distributor_cache == ""){
				$(".distributor").find(".show_contant").removeClass("contant_border");
			}
		}
	});
// 区域选择，解析同上
	$(".region_choose").click(function(){
		var show = $("#region_select_id").val();
		// var show = $("#ui-id-3").next().children("div:last").text();
		if(show != ""){
			if(contains(region_cache,show))
			{
				region_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin region_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".region").find(".show_contant").append(contants);
				if(region_cache != ""){
					$(".region").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.region_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			region_cache.splice($.inArray(contant,region_cache),1);
			$(this).parent().fadeOut().remove();
			if(region_cache == ""){
				$(".region").find(".show_contant").removeClass("contant_border");
			}
		}
	});
	// 参与商品选择
	$(".goods_choose").click(function(){
		var show = $("#ui-id-3").next().children("div:last").text();
		if(show != ""){
			if(contains(goods_cache,show))
			{
				goods_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin goods_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".goods").find(".show_contant").append(contants);
				if(goods_cache != ""){
					$(".goods").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.goods_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			goods_cache.splice($.inArray(contant,goods_cache),1);
			$(this).parent().fadeOut().remove();
			if(goods_cache == ""){
				$(".goods").find(".show_contant").removeClass("contant_border");
			}
		}
	});
// 排除商品选择
	$(".goods_excluded_choose").click(function(){
		var show = $("#ui-id-4").next().children("div:last").text();
		if(show != ""){
			if(contains(goods_excluded_cache,show))
			{
				goods_excluded_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin goods_excluded_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".goods_excluded").find(".show_contant").append(contants);
				if(goods_excluded_cache != ""){
					$(".goods_excluded").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.goods_excluded_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			goods_excluded_cache.splice($.inArray(contant,goods_excluded_cache),1);
			$(this).parent().fadeOut().remove();
			if(goods_excluded_cache == ""){
				$(".goods_excluded").find(".show_contant").removeClass("contant_border");
			}
		}
	});
// 参与类目选择
	$(".cat_included_choose").click(function(){
		var show = $("#ui-id-5").next().children("div:last").text();
		if(show != ""){
			if(contains(cat_included_cache,show))
			{
				cat_included_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin cat_included_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".cat_included").find(".show_contant").append(contants);
				if(cat_included_cache != ""){
					$(".cat_included").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.cat_included_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			cat_included_cache.splice($.inArray(contant,cat_included_cache),1);
			$(this).parent().fadeOut().remove();
			if(cat_included_cache == ""){
				$(".cat_included").find(".show_contant").removeClass("contant_border");
			}
		}
	});
// 排除类目选择
	$(".cat_excluded_choose").click(function(){
		var show = $("#ui-id-6").next().children("div:last").text();
		if(show != ""){
			if(contains(cat_excluded_cache,show))
			{
				cat_excluded_cache.push(show);
				var contants = "<div class='btn_design later_add_show_contant'>"+show+"<span class='glyphicon glyphicon_margin cat_excluded_delete' aria-hidden='true'><img src='remove.png'></span></div>";
				$(".cat_excluded").find(".show_contant").append(contants);
				if(cat_excluded_cache != ""){
					$(".cat_excluded").find(".show_contant").addClass("contant_border");
				}
			}else{
				alert("请勿重复选择");
			}
		}else{
			alert("请先进行选择");
		}
	});
	$(document).on('click','.cat_excluded_delete',function(){
		if(confirm("确定要删除么？")){
			var contant = $(this).parent().text();
			cat_excluded_cache.splice($.inArray(contant,cat_excluded_cache),1);
			$(this).parent().fadeOut().remove();
			if(cat_excluded_cache == ""){
				$(".cat_excluded").find(".show_contant").removeClass("contant_border");
			}
		}
	});

	/*
	*	点击添加新增添按钮显示
	*/
	var add_new_i = 2;//只限增加两次
	$("#add_new").click(function(){
		if($(this).attr("data-add") == "new_bulid_add"){//如果属性是添加新赠品,这个是新增里的
			if(add_new_i == 2){
				$(".gift_second_show_2").fadeIn();
			}else if(add_new_i == 3){
				$(".gift_third_show_3").fadeIn();
			}else{
				alert("只限制添加两次新赠品");
			}
			add_new_i++;
		}else if($(this).attr("data-add") == "change_add"){//AAA这个是修改状态下的按钮
			if($(".gift_second_show_2").css("display") == 'none'){//如果第二赠品未显示
				$(".gift_second_show_2").fadeIn();//第二赠品出现
			}else if($(".gift_third_show_3").css("display") == 'none'){//如果第三赠品未显示
				$(".gift_third_show_3").fadeIn();//第三赠品显示
			}else{
				alert("只限制添加两次新赠品");
			}
		}
	});

	/*
	*	当点击了修改按钮之后将表格中的一大堆数据传到model中
	*/

	$(document).on('click','.change',function(){

		// if(goods_necessary_display == "show"){
			// $(".goods_necessary_display").css("display","block");

			$("#goods_necessary_check").prop("data-id","checked");//默认无固定商品标签data-id属性为checked
			$("#goods_necessary_check").prop("checked", true);
			
//			$("#goods_necessary_limit").prop("data-id","unchecked");//默认无固定商品标签data-id属性为checked
//			$("#goods_necessary_limit").prop("checked", true);
			
			$('.goods_necessary_focus').prop("disabled","disabled");
			$('.goods_necessary_focus').addClass('input_background');
			if($(".goods_necessary").find(".show_contant").text() != ""){
				$(".goods_necessary").find(".show_contant").addClass("contant_border");
			}else{
				$(".goods_necessary").find(".show_contant").removeClass("contant_border");
			}
		// }else{
		// 	$(".goods_necessary_display").css("display","none");
		// }
		// 将数据传到modal之前先清空modal中所有的选项
		$(".many_goods_included_input").val("");//批量增加参数商品
		$(".many_goods_excluded_input").val("");//批量增加排除商品

		$("#active_name").val("");//活动名称
		$("#each_num").val("");//每单赠送数量
		$("#first_gift").val("");//第一赠品名称
		$("#second_gift").val("");
		$("#third_gift").val("");
		$("#gift_limit_first").val("");//第一赠品数量
		$("#gift_limit_second").val("");
		$("#gift_limit_third").val("");
		$("#start_time").val("");
		$("#finally_time").val("");
		$("#least_number").val("");//满赠件数
		$("#least_payment").val("");//满赠金额
		$("#activity_cue").val("");//活动暗号
		

		$(".facility").find(".show_contant").text("");//仓库
		$(".distributor").find(".show_contant").text("");//分销商
		$(".region").find(".show_contant").text("");//区域
		$(".goods").find(".show_contant").text("");//参与商品
		$(".goods_excluded").find(".show_contant").text("");//排除商品
		$(".cat_included").find(".show_contant").text("");//参与类目
		$(".cat_excluded").find(".show_contant").text("");//排除类目

		// 移除class，这个就是在input下的用来显示商品。。的浅灰色框
		$(".facility").find(".show_contant").removeClass("contant_border");
		$(".distributor").find(".show_contant").removeClass("contant_border");
		$(".region").find(".show_contant").removeClass("contant_border");
		$(".goods").find(".show_contant").removeClass("contant_border");
		$(".goods_excluded").find(".show_contant").removeClass("contant_border");
		$(".cat_included").find(".show_contant").removeClass("contant_border");
		$(".cat_excluded").find(".show_contant").removeClass("contant_border");

		var this_id = $(this).attr("id");//活动ID，如果活动ID存在表示是修改，不存在表示是新增
		var limit_id = "#goods_necessary_limit"+this_id;
		var goods_necessary_limit = $(limit_id).text();
		console.log(goods_necessary_limit);
		if(goods_necessary_limit == 1 || goods_necessary_limit == '1'){
			$("#goods_necessary_limit").prop("checked", true);
		}else{
			$("#goods_necessary_limit").prop("checked", false);
		}
		
		if(this_id){
			$(".choose_activity_type").css("display","none");//活动类型选择隐藏
			if($(this).attr('data-flag') == "had"){//等级活动
				$(".class_activity_display").css("display","block");
				$(".normal_activity_display").css("display","none");
				$(".class_now_activity_type").removeClass("now_activity_type");
			}else{
				// 常规活动
				$(".normal_activity_display").css("display","block");
				$(".class_activity_display").css("display","none");
				$(".normal_now_activity_type").removeClass("now_activity_type");
			}
			$("#each_max_num").val("");
			// 根据活动ID作为后缀设置各项ID
			var gift_activity_name = "#gift_activity_name_"+this_id;
			var gift_number_once_max = "#gift_number_once_max_"+this_id;
			var gift_number_once = "#gift_number_once_"+this_id;
			var gift_first = "#gift_first_"+this_id;
			var gift_second = "#gift_second_"+this_id;
			var gift_third = "#gift_third_"+this_id;
			var time = "#begin_time_"+this_id;
			var least_number = "#least_number_"+this_id;
			var least_payment = "#least_payment_"+this_id;
			var repeat_type = "#repeat_type_"+this_id;
			var activity_cue = "#activity_cue_"+this_id;

			// var total_gift_first = $(gift_first).text();
			// var finally_total_gift_first = total_gift_first.split("///");
			/* 
			* 获取大的展示列表的值
			*/
			var first_gift = $(gift_first).find(".gift_first_content").text();//第一赠品（表头是第一二三赠品及余量）
			var gift_limit_first = $(gift_first).find(".gift_limit_first_number").text();//第一赠品余量

			// var total_gift_second = $(gift_second).text();
			// var finally_total_gift_second = total_gift_second.split("///");
			var second_gift = $(gift_second).find(".gift_second_content").text();//第二赠品
			var gift_limit_second = $(gift_second).find(".gift_limit_second_number").text();//第二赠品余量

			// var total_gift_third = $(gift_third).text();
			// var finally_total_gift_third = total_gift_third.split("///");
			var third_gift = $(gift_third).find(".gift_third_content").text();//第三赠品
			var gift_limit_third = $(gift_third).find(".gift_limit_third_number").text();//第三赠品余量

			// var total_time = $(time).text();
			// var finally_total_time = total_time.split("/");
			var finally_strart_time = $(time).find(".time_begin").text();
			var finally_end_time = $(time).find(".time_end").text();

			if($(repeat_type).attr("data-type") == "ONCE"){//ONCE表示不叠加
				$(".show_max_gift").css("display","none");//不叠加则不需要显示每单最多赠送限量
			}else{
				$(".show_max_gift").css("display","inline-block");
			}

			$("#add_new").attr("data-add","change_add");//将添加新赠品的data-add属性修改为change_add
			if(second_gift != "[]"){
				$(".gift_second_show_2").removeClass("gift_show");//如果第二赠品不为空，则让它显示出来
			}else{
				second_gift = "";
			}
			if(third_gift != "[]"){
				$(".gift_third_show_3").removeClass("gift_show");
			}else{
				third_gift = "";
			}
			var gift_activity_name_result = $(gift_activity_name).text();//含有该ID的标签的text，其实就是获取表格中的相应活动内容（活动ID，活动名称等等）
			var gift_number_once_max_result = $(gift_number_once_max).text();
			var gift_number_once_result = $(gift_number_once).text();
			var least_number_result = $(least_number).text();
			var least_payment_result = $(least_payment).text();
			var activity_cue_result = $(activity_cue).text();
			var repeat_type_result = $(repeat_type).attr("data-type");

			var now_local_time = new Date().Format("yyyy-MM-dd hh:mm:ss");  
			// 进行modal中内容的赋值，此处为唯一ID的标签内容赋值，所以不包括灰色框中的数据
			$("#active_name").val(gift_activity_name_result);//将这些从大表格中获取的值放在modal里
			$("#each_max_num").val(gift_number_once_max_result);
			$("#each_num").val(gift_number_once_result);
			$("#first_gift").val(first_gift);
			$("#second_gift").val(second_gift);
			$("#third_gift").val(third_gift);
			$("#gift_limit_first").val(gift_limit_first);
			$("#gift_limit_second").val(gift_limit_second);
			$("#gift_limit_third").val(gift_limit_third);
			$("#start_time").val(finally_strart_time);
			$("#finally_time").val(finally_end_time);
			// if(!$(this).hasClass('can_change_again')){//AA 没有看到这个class的作用
				if(finally_strart_time < now_local_time){//跟现在的时间比较，如果超时则不可修改
					$("#start_time").prop("disabled","disabled");
				}
				if(finally_end_time < now_local_time){
					$("#finally_time").prop("disabled","disabled");
				}
			// }
			$("#least_number").val(least_number_result);
			$("#least_payment").val(least_payment_result);
			$("#repeat_type").val(repeat_type_result);
			$("#activity_cue").val(activity_cue_result);
			// 此处为所有灰色框内容的赋值，利用缓存
			var total_cache_array = total_cache_array_finally;//所有缓存的数据
			for(var number in total_cache_array){//number为所有的活动ID号
				if (number == this_id){//当活动ID对应上
					for(var category in total_cache_array[number]){//category包含所有的内容标识
						if (category == "goods_necessary"){       //这里是点击了修改按钮直接把数据给拖过来的
							if(total_cache_array[number][category] != null){//固定商品不为空
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){//数组中第一个值，表示第一个固定商品
										if(total_cache_array[number][category][0] != '[0]'){//[0]表示不受限制
											if(contains(goods_necessary_cache,total_cache_array[number][category][i]))//判断该缓存是否存在于缓存数组中
											{
												goods_necessary_cache.push(total_cache_array[number][category][i]);	//不存在就放进数组									
											}
											var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin goods_necessary_delete' aria-hidden='true'><img src='remove.png'></span></div>";//下方灰色框内容
											$(".goods_necessary").find(".show_contant").append(contants);//show_contant是灰色框
											$(".goods_necessary").find(".show_contant").addClass("contant_border");
											$("#goods_necessary_check").prop("data-id","unchecked");//属性为未选择不受限制
											$('.goods_necessary_focus').removeAttr("disabled");//移除input框的disabled属性
											$("#goods_necessary_check").prop("checked", false);//选择框为未选择状态										
											$('.goods_necessary_focus').removeClass('input_background');//移除背景色
											if($(".goods_necessary").find(".show_contant").text() != ""){//如果上述内容不为空，则添加CSS样式
												$(".goods_necessary").find(".show_contant").addClass("contant_border");
											}
										}
									}else{//表示固定商品是空
										$("#goods_necessary_check").prop("data-id","checked");//默认无固定商品选项添加该属性
										$("#goods_necessary_check").prop("checked", true);
										$("#goods_necessary_limit").prop("checked", false);
										$('.goods_necessary_focus').prop("disabled","disabled");
										$('.goods_necessary_focus').addClass('input_background');
									}
								}
							}
						}
						if (category == "facility"){
							if(total_cache_array[number][category] != null){ 
								for(var i=0;i<total_cache_array[number][category].length;i++){//total_cache_array[number][category]表示所有添加的仓库
									if(total_cache_array[number][category][0] != null){
										if(contains(facility_cache,total_cache_array[number][category][i]))
										{
											facility_cache.push(total_cache_array[number][category][i]);										
										}
										var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin facility_delete' aria-hidden='true'><img src='remove.png'></span></div>";
										$(".facility").find(".show_contant").append(contants);
										$(".facility").find(".show_contant").addClass("contant_border");

									}else{
										$("#facility_check").prop("data-id","checked");
										$("#facility_check").prop("checked", true);
										$('.facility_focus').prop("disabled","disabled");
										$('.facility_focus').addClass('input_background');
									}
								}
							}
						}
						if(category == "distributor"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){
										if(contains(distributor_cache,total_cache_array[number][category][i]))
										{
											distributor_cache.push(total_cache_array[number][category][i]);
										}
										var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin distributor_delete' aria-hidden='true'><img src='remove.png'></span></div>";
										$(".distributor").find(".show_contant").append(contants);
										$(".distributor").find(".show_contant").addClass("contant_border");
									}else{
										$("#distributor_check").prop("data-id","checked");
										$("#distributor_check").prop("checked", true);
										$('.distributor_focus').prop("disabled","disabled");
										$('.distributor_focus').addClass('input_background');
									}
								}
							}
						}
						if(category == "region"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){
										if(contains(region_cache,total_cache_array[number][category][i]))
										{
											region_cache.push(total_cache_array[number][category][i]);
										}
										var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin region_delete' aria-hidden='true'><img src='remove.png'></span></div>";
										$(".region").find(".show_contant").append(contants);
										$(".region").find(".show_contant").addClass("contant_border");
									}else{
										$("#region_check").prop("data-id","checked");
										$("#region_check").prop("checked", true);
										$('#region_select_id').prop("disabled","disabled");
										$('#region_select_id').addClass('input_background');
									}
								}
							}
						}
						if(category == "goods_included"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){//第一个商品不为空
										// console.log("这里是参与商品"+total_cache_array[number][category][0]);
										if(total_cache_array[number][category][0] != '[0]'){//且不限制
											if(contains(goods_cache,total_cache_array[number][category][i]))
											{
												goods_cache.push(total_cache_array[number][category][i]);
											}
											var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin goods_delete' aria-hidden='true'><img src='remove.png'></span></div>";
											$(".goods").find(".show_contant").append(contants);
											$(".goods").find(".show_contant").addClass("contant_border");
										}else{
											$("#goods_check").prop("data-id","checked");
											$("#goods_check").prop("checked", true);
											$('.goods_focus').prop("disabled","disabled");
											$('.goods_focus').addClass('input_background');
										}
									}
								}
							}
						}
						if(category == "goods_excluded"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){
										// console.log("这里是排除商品"+total_cache_array[number][category][0]);
										if(total_cache_array[number][category][0] != '[0]'){
											if(contains(goods_excluded_cache,total_cache_array[number][category][i]))
											{
												goods_excluded_cache.push(total_cache_array[number][category][i]);
											}
											var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin goods_excluded_delete' aria-hidden='true'><img src='remove.png'></span></div>";
											$(".goods_excluded").find(".show_contant").append(contants);
											$(".goods_excluded").find(".show_contant").addClass("contant_border");
										}else{
											$("#goods_excluded_check").prop("data-id","checked");
											$("#goods_excluded_check").prop("checked", true);
											$('.goods_excluded_focus').prop("disabled","disabled");
											$('.goods_excluded_focus').addClass('input_background');
										}
									}
								}
							}
						}
						if(category == "cat_included"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){
										// console.log("这里是参与类目"+total_cache_array[number][category][0]);
										if(total_cache_array[number][category][0] != '[0]'){
											if(contains(cat_included_cache,total_cache_array[number][category][i]))
											{
												cat_included_cache.push(total_cache_array[number][category][i]);
											}
											var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin cat_included_delete' aria-hidden='true'><img src='remove.png'></span></div>";
											$(".cat_included").find(".show_contant").append(contants);
											$(".cat_included").find(".show_contant").addClass("contant_border");
										}else{
											$("#cat_included_check").prop("data-id","checked");
											$("#cat_included_check").prop("checked", true);
											$('.cat_included_focus').prop("disabled","disabled");
											$('.cat_included_focus').addClass('input_background');
										}
									}
								}
							}
						}
						if(category == "cat_excluded"){
							if(total_cache_array[number][category] != null){
								for(var i=0;i<total_cache_array[number][category].length;i++){
									if(total_cache_array[number][category][0] != null){
										// console.log("这里是排除类目"+total_cache_array[number][category][0]);
										if(total_cache_array[number][category][0] != '[0]'){
											if(contains(cat_excluded_cache,total_cache_array[number][category][i]))
											{
												cat_excluded_cache.push(total_cache_array[number][category][i]);
											}
											var contants = "<div class='btn_design later_add_show_contant'>"+total_cache_array[number][category][i]+"<span class='glyphicon glyphicon_margin cat_excluded_delete' aria-hidden='true'><img src='remove.png'></span></div>";
											$(".cat_excluded").find(".show_contant").append(contants);
											$(".cat_excluded").find(".show_contant").addClass("contant_border");
										}else{
											$("#cat_excluded_check").prop("data-id","checked");
											$("#cat_excluded_check").prop("checked", true);
											$('.cat_excluded_focus').prop("disabled","disabled");
											$('.cat_excluded_focus').addClass('input_background');
										}
									}
								}
							}
						}
						if(category == "class_activity"){
							if(total_cache_array[number][category] != null){
								if(total_cache_array[number][category].length >= 2){
									for(var i=0;i<2;i++){
										//选择对应行数添加属性
										$(".class_activity_display .base_gift").eq(i).attr("data-ecs_gift_activity_level_id",total_cache_array[number][category][i].gift_activity_level_id);
										$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_data").val(total_cache_array[number][category][i].gift);
										$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_gift_limit").val(total_cache_array[number][category][i].gift_limit);
										$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_gift_least_payment").val(total_cache_array[number][category][i].least_payment);
										$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_gift_least_number").val(total_cache_array[number][category][i].least_number);
										$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_gift_each_num").val(total_cache_array[number][category][i].gift_number);
										/*  这里是点击了修改之后要把数据拉过去了的
										*	total_cache_array[number][category][i]也是数组,这是一个二维数组
										*/
										if(Number(total_cache_array[number][category][i].gift_limit) < Number(total_cache_array[number][category][i].gift_number)){//满赠件数小于赠品数量
											$(".class_activity_display .base_gift").eq(i).find(".class_activity_add_gift_each_num").addClass('input_border_color');
										}
									}
									for(var j=2;j<total_cache_array[number][category].length;j++){//从第三件赠品开始拼接字符串
										var m = j+1;
										var if_class = "";
										if(Number(total_cache_array[number][category][j].gift_limit) < Number(total_cache_array[number][category][j].gift_number)){
											if_class = 'input_border_color';
										}
										var class_activity_add_push_html = "<div class='base_gift add_by_button' data-ecs_gift_activity_level_id='"+total_cache_array[number][category][j].gift_activity_level_id+"'>"+
										"<div class='inline_div word ui-widget'><p>赠品"+m+"：</p>"+
										"<div class='inline_div_small ui-widget'>"+
										"<input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift_name class_activity_add_gift class_activity_added_data class_activity_add_must_full' value='"+total_cache_array[number][category][j].gift+"'>"+
										"<span class='must_add margin_left_must_add'>*</span>"+
										"</div></div>"+
										"<div class='inline_div word second_word'><p>赠品"+m+"数量：</p><input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift class_activity_add_gift_limit class_activity_add_must_full class_activity_add_kucun' value='"+total_cache_array[number][category][j].gift_limit+"'></div>"+
										"<span class='must_add margin_left_must_add'>*</span>"+
										"<div class='inline_div word second_word'><p>满赠金额：</p><input class='new_active_input class_activity_add_input_2px class_activity_add_zengpin_gift class_activity_add_gift_least_payment class_activity_add_must_full' value='"+total_cache_array[number][category][j].least_payment+"'></div>"+
										"<span class='must_add margin_left_must_add'>*</span>"+
										"<div class='inline_div word second_word'><p>满赠件数：</p><input class='new_active_input class_activity_add_input_1px class_activity_add_zengpin_gift class_activity_add_gift_least_number class_activity_add_must_full' value='"+total_cache_array[number][category][j].least_number+"'></div>"+
										"<span class='must_add margin_left_must_add'>*</span>"+
										"<div class='inline_div word second_word'><p>每单赠送件数：</p><input class='new_active_input class_activity_add_input_3px class_activity_add_zengpin_gift class_activity_add_gift_each_num class_activity_add_must_full class_activity_add_zengsongjianshu "+if_class+"' value='"+total_cache_array[number][category][j].gift_number+"'></div>"+
										"<span class='must_add margin_left_must_add'>*</span>"+
										"</div>";

										$(".class_activity_display").find(".class_activity_add").append(class_activity_add_push_html);
										class_activity_add_new_index++;//赠品数量
										class_activity_add();//每单赠送件数与库存数量比较
									}
								}
							}
						}
					}
				}
			}

			$(".save_all").attr('id',this_id);
			// console.log(this_id);
		}else{
			$("#repeat_type").val("ONCE");       //默认是不叠加
			$(".show_max_gift").css('display', 'none');//每单最多赠送数量
		}
	});
	/*
	*	点击批量增加时先判断是否点击了不限制的条件
	*/

	$("#many_goods_included_button").click(function(){
		if($("#goods_check").prop("checked")){
			alert("您当前参与商品为不限制,无法批量增加");
			return false;
		}
	});
	$("#many_goods_excluded_button").click(function(){
		if($("#goods_excluded_check").prop("checked")){
			alert("您当前排除商品为不限制,无法批量增加");
			return false;
		}
	});


	function if_in(array, content) {
		for (var i = 0; i < array.length; i++) {
			if (array[i] === content) {
				return true;
			}
		}  
		return false;
	}
	/*
	*	批量增加输入商品编码后保存
	*/

	var many_goods_included = many_goods;//php中的$goods_gift列表,参与商品列表
	var many_goods_excluded = many_goods;//该变量用在下方排除商品的绑定事件上
	// console.log(many_goods_included+'included');
	// console.log(many_goods_excluded+'excluded');
	var many_goods_included_cache = new Array();
	var many_goods_excluded_cache = new Array();
	var chongfu_shangping = new Array();
	var excluded_chongfu_shangping= new Array();
	
	$(".many_goods_necessary_save").click(function(){//批量参与商品的保存按钮
	
		chongfu_shangping.length =0;
		many_goods_included_cache.length = 0;
		var many_goods_included_string = $(".many_goods_included_input").val();//批量增加的商品编码
		var many_goods_included_array = "";
		if(many_goods_included_string != ""){
			many_goods_included_array = many_goods_included_string.split(",");//用逗号区分截取再存进数组中
		}
		if(many_goods_included_array != ""){
			for(var i=0;i<many_goods_included_array.length-1;i++){
				if(many_goods_included_array[i] != undefined){//确保数组中内容不为undefined
					var many_goods_included_array_slice = many_goods_included_array[i].split("]");//AA通过]进行切割，截取]之前的内容,截取后字符串含有[。
					
					for(var j=0;j<many_goods_included.length;j++){
						if(many_goods_included[j].indexOf(many_goods_included_array_slice[0])>-1){	//如果批量增加的商品存在于商品数组中
							if(contains(goods_cache,many_goods_included[j])){//如果这个商品不在缓存中
								goods_cache.push(many_goods_included[j]);//则将它放进商品缓存数组
								console.log(many_goods_included[j]);
								var contants = "<div class='btn_design later_add_show_contant'>"+many_goods_included[j]+"<span class='glyphicon glyphicon_margin goods_delete' aria-hidden='true'><img src='remove.png'></span></div>";//将商品放在input下方灰色框中
								$(".goods_necessary").find(".show_contant").append(contants);
								goods_necessary_cache.push(many_goods_included[j]);//放进批量参与商品缓存数组
							}else{
								chongfu_shangping.push(many_goods_included[j]);//否则将它放进重复商品数组中
							}
						}
					}
					
				}
			}
			if($(".goods").find(".show_contant").text() != ""){
				$(".goods").find(".show_contant").addClass('contant_border')
			}
		}else{
			alert("请输入商品编码");
		}
		if(chongfu_shangping != ""){
			var chongfu_shuju = "";
			for(var q=0;q<chongfu_shangping.length-1;q++){
				chongfu_shuju = chongfu_shuju+chongfu_shangping[q]+",";//将重复数据拼接起来
			}
			chongfu_shuju = chongfu_shuju +chongfu_shangping[chongfu_shangping.length-1];
			alert("请勿重复选择商品编码为："+chongfu_shuju+"的商品");//提示重复的数据
		}

		$("#many_goods_included").modal('hide');
	});
	
	$(".many_goods_included_save").click(function(){//批量参与商品的保存按钮
		// $(this).attr('disabled',"true");
		chongfu_shangping.length =0;
		many_goods_included_cache.length = 0;
		var many_goods_included_string = $(".many_goods_included_input").val();//批量增加的商品编码
		var many_goods_included_array = "";
		if(many_goods_included_string != ""){
			many_goods_included_array = many_goods_included_string.split(",");//用逗号区分截取再存进数组中
		}
		if(many_goods_included_array != ""){
			for(var i=0;i<many_goods_included_array.length-1;i++){
				if(many_goods_included_array[i] != undefined){//确保数组中内容不为undefined
					var many_goods_included_array_slice = many_goods_included_array[i].split("]");//AA通过]进行切割，截取]之前的内容,截取后字符串含有[。
					
					for(var j=0;j<many_goods_included.length;j++){
						if(many_goods_included[j].indexOf(many_goods_included_array_slice[0])>-1){	//如果批量增加的商品存在于商品数组中
							if(contains(goods_cache,many_goods_included[j])){//如果这个商品不在缓存中
								goods_cache.push(many_goods_included[j]);//则将它放进商品缓存数组
								console.log(many_goods_included[j]);
								var contants = "<div class='btn_design later_add_show_contant'>"+many_goods_included[j]+"<span class='glyphicon glyphicon_margin goods_delete' aria-hidden='true'><img src='remove.png'></span></div>";//将商品放在input下方灰色框中
								$(".goods").find(".show_contant").append(contants);
								many_goods_included_cache.push(many_goods_included[j]);//放进批量参与商品缓存数组
							}else{
								chongfu_shangping.push(many_goods_included[j]);//否则将它放进重复商品数组中
							}
						}
					}
					
				}
			}
			if($(".goods").find(".show_contant").text() != ""){
				$(".goods").find(".show_contant").addClass('contant_border')
			}
		}else{
			alert("请输入商品编码");
		}
		if(chongfu_shangping != ""){
			var chongfu_shuju = "";
			for(var q=0;q<chongfu_shangping.length-1;q++){
				chongfu_shuju = chongfu_shuju+chongfu_shangping[q]+",";//将重复数据拼接起来
			}
			chongfu_shuju = chongfu_shuju +chongfu_shangping[chongfu_shangping.length-1];
			alert("请勿重复选择商品编码为："+chongfu_shuju+"的商品");//提示重复的数据
		}
		// var many_goods_included_finally_cache = new Array();
		// if(many_goods_included_cache !=""){
		// 	var leave_data = "";
		// 	for(var q=0;q<many_goods_included_array.length;q++){
		// 		if(if_in(many_goods_included_cache,many_goods_included_array[q])){
		// 		}else{
		// 			many_goods_included_finally_cache.push(many_goods_included_array[q]);
		// 		}
		// 	}
		// 	if(many_goods_included_finally_cache != ""){
		// 		for(var q=0;q<many_goods_included_finally_cache.length-1;q++){
		// 			leave_data = leave_data+many_goods_included_finally_cache[q]+",";
		// 		}
		// 		leave_data = leave_data+many_goods_included_finally_cache[many_goods_included_finally_cache.length-1];
		// 	}
		// 	$(".many_goods_included_input").val(leave_data);
		// 	if(leave_data == ""){
		// 		$("#many_goods_included").modal('hide');
		// 	}

		// }
		$("#many_goods_included").modal('hide');
		// $(this).removeAttr('disabled');
	});

$(".many_goods_excluded_save").click(function(){//批量排除商品的保存按钮
		// $(this).attr('disabled',"true");
		excluded_chongfu_shangping.length =0;
		many_goods_excluded_cache.length = 0;
		// for(var i = 0;i<goods_cache.length;i++){
		// 	console.log(goods_cache[i]);
		// }
		var many_goods_excluded_string = $(".many_goods_excluded_input").val();
		var many_goods_excluded_array = "";
		if(many_goods_excluded_string != ""){
			many_goods_excluded_array = many_goods_excluded_string.split(",");
		}
		if(many_goods_excluded_array != ""){
			for(var i=0;i<many_goods_excluded_array.length-1;i++){
				if(many_goods_excluded_array[i] != undefined){
					var many_goods_excluded_array_slice = many_goods_excluded_array[i].split("]");
					for(var j=0;j<many_goods_excluded.length;j++){
						if(many_goods_excluded[j].indexOf(many_goods_excluded_array_slice[0])>-1){
							if(contains(goods_excluded_cache,many_goods_excluded[j])){
								goods_excluded_cache.push(many_goods_excluded[j]);
								var contants = "<div class='btn_design later_add_show_contant'>"+many_goods_excluded[j]+"<span class='glyphicon glyphicon_margin goods_excluded_delete' aria-hidden='true'><img src='remove.png'></span></div>";
								$(".goods_excluded").find(".show_contant").append(contants);
								many_goods_excluded_cache.push(many_goods_excluded[j]);
							}else{
								excluded_chongfu_shangping.push(many_goods_excluded[j]);
							}
						}
					}
				}
			}
			if($(".goods_excluded").find(".show_contant").text() != ""){
				$(".goods_excluded").find(".show_contant").addClass('contant_border')
			}
		}else{
			alert("请输入商品编码");
		}
		if(excluded_chongfu_shangping != ""){
			var excluded_chongfu_shuju = "";
			for(var q=0;q<excluded_chongfu_shangping.length-1;q++){
				excluded_chongfu_shuju = excluded_chongfu_shuju+excluded_chongfu_shangping[q]+",";
			}
			excluded_chongfu_shuju = excluded_chongfu_shuju +excluded_chongfu_shangping[excluded_chongfu_shangping.length-1];
			alert("请勿重复选择商品编码为："+excluded_chongfu_shuju+"的商品");
		}
		// var many_goods_excluded_finally_cache = new Array();
		// if(many_goods_excluded_cache !=""){
		// 	var many_goods_excluded_leave_data = "";
		// 	for(var q=0;q<many_goods_excluded_array.length;q++){
		// 		if(if_in(many_goods_excluded_cache,many_goods_excluded_array[q])){
		// 		}else{
		// 			many_goods_excluded_finally_cache.push(many_goods_excluded_array[q]);
		// 		}
		// 	}
		// 	if(many_goods_excluded_finally_cache != ""){
		// 		for(var q=0;q<many_goods_excluded_finally_cache.length-1;q++){
		// 			many_goods_excluded_leave_data = many_goods_excluded_leave_data+many_goods_excluded_finally_cache[q]+",";
		// 		}
		// 		many_goods_excluded_leave_data = many_goods_excluded_leave_data+many_goods_excluded_finally_cache[many_goods_excluded_finally_cache.length-1];
		// 	}
		// 	$(".many_goods_excluded_input").val(many_goods_excluded_leave_data);
		// 	if(many_goods_excluded_leave_data == ""){
		// 		$("#many_goods_excluded").modal('hide');
		// 	}
		// }
		$("#many_goods_excluded").modal('hide');
		// $(this).removeAttr('disabled');
	});




	/*
	*	当模态框关闭后后内容清空，不然如果用户先点击新建，然后再点击新建按钮，会有数据缓存
	*/

	$(".close_button").click(function(){
		$("#active_name").val("");
		$("#each_max_num").val("");
		$("#each_num").val("");
		$("#first_gift").val("");
		$("#second_gift").val("");
		$("#third_gift").val("");
		$("#gift_limit_first").val("");
		$("#gift_limit_second").val("");
		$("#gift_limit_third").val("");
		$("#start_time").val("");
		$("#finally_time").val("");
		$("#least_number").val("");
		$("#least_payment").val("");
		$("#repeat_type").val("");
		$(".gift_second_show_2").fadeOut();
		$(".gift_third_show_3").fadeOut();
		$(".save_all").attr("id","");

		//这里是新增的等级活动的清缓存
		class_activity_add_new_index = 3;
		$(".choose_activity_type").css("display","block");
		$(".normal_activity_display").css("display","block");
		$(".class_activity_display").css("display","none");
		$(".add_by_button").remove();
		$(".clear_class_activity_add").each(function(){
			$(this).val("");
		});

		$(".class_now_activity_type").addClass("now_activity_type");
		$(".normal_now_activity_type").addClass("now_activity_type");


		$(".facility").find(".show_contant").text("");
		$(".distributor").find(".show_contant").text("");
		$(".region").find(".show_contant").text("");
		$(".goods").find(".show_contant").text("");
		$(".goods_necessary").find(".show_contant").text("");
		$(".goods_excluded").find(".show_contant").text("");
		$(".cat_included").find(".show_contant").text("");
		$(".cat_excluded").find(".show_contant").text("");

		$('.facility_focus').val("").removeClass('input_background');
		$('.distributor_focus').val("").removeClass('input_background');
		$('.goods_focus').val("").removeClass('input_background');
		$('#region_select_id').removeClass('input_background');
		$('.goods_necessary_focus').val("").removeClass('input_background');
		$('.goods_excluded_focus').val("").removeClass('input_background');
		$('.cat_included_focus').val("").removeClass('input_background');
		$('.cat_excluded_focus').val("").removeClass('input_background');

		$("#facility_check").prop("data-id","unchecked");
		$("#facility_check").prop("checked", false);
		$('.facility_focus').prop("disabled","");
		$("#distributor_check").prop("data-id","unchecked");
		$("#distributor_check").prop("checked", false);
		$('.distributor_focus').prop("disabled","");
		$("#region_check").prop("data-id","unchecked");
		$("#region_check").prop("checked", false);
		$('#region_select_id').prop("disabled","");
		$("#goods_check").prop("data-id","unchecked");
		$("#goods_check").prop("checked", false);
		$('.goods_focus').prop("disabled","");
		$("#goods_necessary_check").prop("data-id","unchecked");
		$("#goods_necessary_limit").prop("data-id","unchecked");
		$("#goods_necessary_check").prop("checked", false);
		$("#goods_necessary_limit").prop("checked", false);
		$('.goods_necessary_focus').prop("disabled","");
		$("#goods_excluded_check").prop("data-id","unchecked");
		$("#goods_excluded_check").prop("checked", false);
		$('.goods_excluded_focus').prop("disabled","");
		$("#cat_included_check").prop("data-id","unchecked");
		$("#cat_included_check").prop("checked", false);
		$('.cat_included_focus').prop("disabled","");
		$("#cat_excluded_check").prop("data-id","unchecked");
		$("#cat_excluded_check").prop("checked", false);
		$('.cat_excluded_focus').prop("disabled","");
		$('#start_time').prop("disabled","");
		$('#finally_time').prop("disabled","");

		// 清理缓存

		facility_cache.length = 0;
		distributor_cache.length = 0;
		region_cache.length = 0;
		goods_cache.length = 0;
		goods_excluded_cache.length = 0;
		cat_included_cache.length = 0;
		cat_excluded_cache.length = 0;
		goods_necessary_cache.length = 0;
	});


	/*
	*	对check-box进行控制，当点击了check-box时,将相应的 input设置为disabled
	*/

	$("#goods_necessary_check").click(function(){//点击默认无固定商品
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.goods_necessary_focus').prop("disabled","disabled");
			$(".goods_necessary").find(".show_contant").children().fadeOut();
			$('.goods_necessary_focus').addClass('input_background');
			$(".goods_necessary").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.goods_necessary_focus').removeAttr("disabled");
			$(".goods_necessary").find(".show_contant").children().fadeIn();
			$('.goods_necessary_focus').removeClass('input_background');
			if($(".goods_necessary").find(".show_contant").text() != ""){
				$(".goods_necessary").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#facility_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.facility_focus').attr("placeholder","");
			$('.facility_focus').prop("disabled","disabled");
			$('.facility_focus').addClass('input_background');
			$(".facility").find(".show_contant").children().fadeOut();
			$(".facility").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.facility_focus').attr("placeholder","请选择仓库");
			$('.facility_focus').removeAttr("disabled");
			$('.facility_focus').removeClass('input_background');
			$(".facility").find(".show_contant").children().fadeIn();
			if($(".facility").find(".show_contant").text() != ""){
				$(".facility").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#distributor_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.distributor_focus').attr("placeholder","");
			$('.distributor_focus').prop("disabled","disabled");
			$('.distributor_focus').addClass('input_background');
			$(".distributor").find(".show_contant").children().fadeOut();
			$(".distributor").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.distributor_focus').attr("placeholder","请选择分销商");
			$('.distributor_focus').removeAttr("disabled");
			$('.distributor_focus').removeClass('input_background');
			$(".distributor").find(".show_contant").children().fadeIn();
			if($(".distributor").find(".show_contant").text() != ""){
				$(".distributor").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#region_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('#region_select_id').prop("disabled","disabled");
			$('#region_select_id').addClass('input_background');
			$(".region").find(".show_contant").children().fadeOut();
			$(".region").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('#region_select_id').removeAttr("disabled");
			$('#region_select_id').removeClass('input_background');
			$(".region").find(".show_contant").children().fadeIn();
			if($(".region").find(".show_contant").text() != ""){
				$(".region").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#goods_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.goods_focus').prop("disabled","disabled");
			$('.goods_focus').addClass('input_background');
			$(".goods").find(".show_contant").children().fadeOut();
			$(".goods").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.goods_focus').removeAttr("disabled");
			$('.goods_focus').removeClass('input_background');
			$(".goods").find(".show_contant").children().fadeIn();
			if($(".goods").find(".show_contant").text() != ""){
				$(".goods").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#goods_excluded_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.goods_excluded_focus').prop("disabled","disabled");
			$('.goods_excluded_focus').addClass('input_background');
			$(".goods_excluded").find(".show_contant").children().fadeOut();
			$(".goods_excluded").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.goods_excluded_focus').removeAttr("disabled");
			$('.goods_excluded_focus').removeClass('input_background');
			$(".goods_excluded").find(".show_contant").children().fadeIn();
			if($(".goods_excluded").find(".show_contant").text() != ""){
				$(".goods_excluded").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#cat_included_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.cat_included_focus').prop("disabled","disabled");
			$('.cat_included_focus').addClass('input_background');
			$(".cat_included").find(".show_contant").children().fadeOut();
			$(".cat_included").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.cat_included_focus').removeAttr("disabled");
			$('.cat_included_focus').removeClass('input_background');
			$(".cat_included").find(".show_contant").children().fadeIn();
			if($(".cat_included").find(".show_contant").text() != ""){
				$(".cat_included").find(".show_contant").addClass("contant_border");
			}
		}
	});
	$("#cat_excluded_check").click(function(){
		if($(this).prop("checked")){
			$(this).prop("data-id","checked");
			$('.cat_excluded_focus').prop("disabled","disabled");
			$('.cat_excluded_focus').addClass('input_background');
			$(".cat_excluded").find(".show_contant").children().fadeOut();
			$(".cat_excluded").find(".show_contant").removeClass("contant_border");
		}else{
			$(this).prop("data-id","unchecked");
			$('.cat_excluded_focus').removeAttr("disabled");
			$('.cat_excluded_focus').removeClass('input_background');
			$(".cat_excluded").find(".show_contant").children().fadeIn();
			if($(".cat_excluded").find(".show_contant").text() != ""){
				$(".cat_excluded").find(".show_contant").addClass("contant_border");
			}
		}
	});

	/*
	*	以下部分是处理新增活动中的点击保存所有之后的操作
	*	
	*/

	$(".save_all").click(function(){//modal保存按钮
		
		var this_id = $(this).attr('id');

		/*
		*	这里是提取基本信息内容，包含活动名称...叠加类型...
		*/
		var active_name = $("#active_name").val();
		var each_max_num = $("#each_max_num").val();
		var each_num = $("#each_num").val();
		var first_gift = $("#first_gift").val();
		var second_gift = $("#second_gift").val();
		var third_gift = $("#third_gift").val();
		var gift_limit_first = $("#gift_limit_first").val();
		var gift_limit_second = $("#gift_limit_second").val();
		var gift_limit_third = $("#gift_limit_third").val();
		var start_time = $("#start_time").val();
		var finally_time = $("#finally_time").val();
		var least_number = $("#least_number").val();
		var least_payment = $("#least_payment").val();
		var repeat_type = $("#repeat_type").val();
		var activity_cue = $("#activity_cue").val();

		/*
		*	这里是对第二第三赠品处理,必须大于0,如果等于0,则赠品废弃
		*/
		if(second_gift && gift_limit_second < 0){
			alert("第二赠品数量必须大于0");
			return false;
		}else if(second_gift && gift_limit_second == 0){
			$(".gift_destory").css("display",'block');
			return false;
		}

		if(third_gift && gift_limit_third < 0){
			alert("第三赠品数量必须大于0");
			return false;
		}else if(third_gift && gift_limit_third == 0){
			$(".gift_destory").css("display",'block');
			return false;
		}

		/*
		*	这一部分是将数据缓存中的数据转换成字符串传到后台
		*/
		var goods_necessary_string = "";            //注意：如果未有任何操作,那么就默认为空
		for(var i = 0;i<goods_necessary_cache.length;i++){
			if(i!=goods_necessary_cache.length-1){
				goods_necessary_string = goods_necessary_string + goods_necessary_cache[i] +",";//将缓存数组中的数据全部用逗号拼接起来
			}else if(i == goods_necessary_cache.length-1){
				goods_necessary_string = goods_necessary_string + goods_necessary_cache[i];//最后一个数据后面不需要逗号
			}
		}
		var facility_cache_string = "";
		for(var i = 0;i<facility_cache.length;i++){
			if(i!=facility_cache.length-1){
				facility_cache_string = facility_cache_string + facility_cache[i] +",";
			}else if(i == facility_cache.length-1){
				facility_cache_string = facility_cache_string + facility_cache[i];
			}
		}
		var distributor_cache_string = "";
		for(var i = 0;i<distributor_cache.length;i++){
			if(i!=distributor_cache.length-1){
				distributor_cache_string = distributor_cache_string + distributor_cache[i] +",";
			}else if(i == distributor_cache.length-1){
				distributor_cache_string = distributor_cache_string + distributor_cache[i];
			}
		}
		var region_cache_string = "";
		for(var i = 0;i<region_cache.length;i++){
			if(i!=region_cache.length-1){
				region_cache_string = region_cache_string + region_cache[i] +",";
			}else if(i == region_cache.length-1){
				region_cache_string = region_cache_string + region_cache[i];
			}
		}
		var goods_cache_string = "";
		for(var i = 0;i<goods_cache.length;i++){
			if(i!=goods_cache.length-1){
				goods_cache_string = goods_cache_string + goods_cache[i] +",";
			}else if(i == goods_cache.length-1){
				goods_cache_string = goods_cache_string + goods_cache[i];
			}
		}
		var goods_excluded_cache_string = "";
		for(var i = 0;i<goods_excluded_cache.length;i++){
			if(i!=goods_excluded_cache.length-1){
				goods_excluded_cache_string = goods_excluded_cache_string + goods_excluded_cache[i] +",";
			}else if(i == goods_excluded_cache.length-1){
				goods_excluded_cache_string = goods_excluded_cache_string + goods_excluded_cache[i];
			}
		}
		var cat_included_cache_string = "";
		for(var i = 0;i<cat_included_cache.length;i++){
			if(i!=cat_included_cache.length-1){
				cat_included_cache_string = cat_included_cache_string + cat_included_cache[i] +",";
			}else if(i == cat_included_cache.length-1){
				cat_included_cache_string = cat_included_cache_string + cat_included_cache[i];
			}
		}
		var cat_excluded_cache_string = "";
		for(var i = 0;i<cat_excluded_cache.length;i++){
			if(i!=cat_excluded_cache.length-1){
				cat_excluded_cache_string = cat_excluded_cache_string + cat_excluded_cache[i] +",";
			}else if(i == cat_excluded_cache.length-1){
				cat_excluded_cache_string = cat_excluded_cache_string + cat_excluded_cache[i];
			}
		}
		// 提交表单前的检查
		/*
		*	这里是判断时候有点击复选按钮，如果点击，则表示可以直接忽略该条件
		*/
		var facility_check_state = $("#facility_check").prop("data-id");
		var distributor_check_state = $("#distributor_check").prop("data-id");
		var region_check_state = $("#region_check").prop("data-id");
		var goods_check_state = $("#goods_check").prop("data-id");
		var goods_necessary_check_state = $("#goods_necessary_check").prop("data-id");
		var goods_necessary_limit_state = $("#goods_necessary_limit").prop("checked");
		console.log( $("#goods_necessary_limit").prop("checked"));
		var goods_excluded_check_state = $("#goods_excluded_check").prop("data-id");
		var cat_included_check_state = $("#cat_included_check").prop("data-id");
		var cat_excluded_check_state = $("#cat_excluded_check").prop("data-id");
		// console.log("这里是check_id:"+checked_id);
		/*
		*	这里是对内容进行过滤，如果为空，进行相关的操作,Format函数是对日期格式进行格式化，从而可以和活动日期进行比较
		*/
		
		var now_time = new Date().Format("yyyy-MM-dd hh:mm:ss");   
		var start_time_disabled = $("#start_time").prop("disabled");
		var finally_time_disabled = $("#finally_time").prop("disabled");
		if(active_name == ""){
			alert("活动名称不能为空");
			$("#active_name").focus();			
			return false;
		}else if(!start_time_disabled && (start_time > finally_time || start_time < now_time)){
			alert("开始时间不能大于结束时间或者活动开始时间应该在当前时间之后");
			$("#start_time").focus();
			return false;
		}else if(!finally_time_disabled && (finally_time < now_time || start_time > finally_time)){
			alert("活动结束时间应该在当前时间之后");
			$("#finally_time").focus();
			return false;
		}else if(goods_cache_string == "" && goods_check_state != "checked"){
			alert("至少选择一个参与商品或者不限制");
			$("#goods_focus").focus();
			return false;
		}else if(cat_included_cache_string == "" && cat_included_check_state != "checked"){
			alert("至少选择一个参与类目或者不限制");
			$("#cat_included_focus").focus();
			return false;
		}else if(facility_cache_string == "" && facility_check_state != "checked"){
			alert("至少选择一个仓库或者不限制");
			$("#facility_focus").focus();
			return false;
		}else if(distributor_cache_string == "" && distributor_check_state != "checked"){
			alert("至少选择一个分销商或者不限制");
			$("#distributor_focus").focus();
			return false;
		}else if(region_cache_string == "" && region_check_state != "checked"){
			alert("至少选择一个省份或者不限制");
			$("#region_select_id").focus();
			return false;
		}
		var class_activity_add_final_string = ""; //这里是对等级活动中的赠品、库存等信息的字符串拼接,如果为空，表示是常规活动，如果不为空则为等级活动
		var activity_type = "";
		if($(".normal_activity_display").css("display")== "block"){
			activity_type = "NORMAL";
			if(first_gift == ""){
				alert("第一赠品必选");
				$("#first_gift").focus();
				return false;
			}else if(gift_limit_first == ""){
				alert("第一赠品余量必填");
				$("#gift_limit_first").focus();
				return false;
			}else if(isNaN(gift_limit_first) || gift_limit_first < 0){
				alert("第一赠品余量必须是数字或者必须大于0");
				$("#gift_limit_first").focus();
				return false;
			}else if(gift_limit_first == 0){
				$(".gift_destory").css("display",'block');
				return false;
			}else if(each_num == ""){
				alert("赠送件数必填");
				$("#each_num").focus();
				return false;
			}else if(isNaN(each_num) || each_num <= 0){
				alert("赠送件数必须是数字或者必须大于0");
				$("#each_num").focus();
				return false;
			}else if(least_number == ""){
				alert("满赠件数不能为空");
				$("#least_number").focus();
				return false;
			}else if(isNaN(least_number) || least_number < 0){
				alert("满赠件数必须是数字或者必须大于等于0");
				$("#least_number").focus();
				return false;
			}else if(least_payment == ""){
				alert("满赠金额不能为空");
				$("#least_payment").focus();
				return false;
			}else if(isNaN(least_payment) || least_payment < 0){
				alert("满赠金额必须是数字或者必须大于等于0");
				$("#least_payment").focus();
				return false;
			}else if(repeat_type == "BY_NUMBER"){
				if(least_number == 0){
					alert("按件数叠加时满赠件数必须大于0");
					$("#least_number").focus();
					return false;
				}
				if(isNaN(each_max_num)){
					alert("每单最多赠送限量必须是数字");
					$("#each_max_num").focus();
					return false;
				}
				if((each_num-each_max_num)>0){
					alert("按件数叠加时每单最多赠送限量必须大于赠送件数");
					$("#each_max_num").focus();
					return false;
				}		
			}else if(repeat_type == "BY_PAYMENT"){
				if(least_payment == 0){
					alert("按价格时满赠金额必须大于0");
					$("#least_payment").focus();
					return false;
				}
			}
			if(repeat_type == "ONCE"){
				each_max_num = 999;
			}
		}else if($(".class_activity_display").css("display")== "block"){
			activity_type = "LEVEL";
			var i=0;
			var j=0;
			var kucun = 0;
			var zengsongjianshu = 0;
			var kucun_zengsongjianshu_boolen = true;
			var zengsong_array = new Array();
			$(".class_activity_add_must_full").each(function(){//modal中赠品的相关内容
				i++;
				if($(this).val() != ""){	
					j++;	
				}
				if($(this).hasClass('class_activity_add_kucun')){//赠品数量
					kucun = $(this).val();
				}
				if($(this).hasClass('class_activity_add_zengsongjianshu')){//每单赠送件数
					zengsongjianshu = $(this).val();
					zengsong_array.push(kucun);
					zengsong_array.push(zengsongjianshu);
				}
			});
			console.log(zengsong_array.length);
			for(var m=0;m<zengsong_array.length;){
				var q = m+1;//赠品数量和每单赠送件数全部放在一个数组中且相邻
				if(Number(zengsong_array[m])<Number(zengsong_array[q])){//判断赠品数量和每单赠送件数
					kucun_zengsongjianshu_boolen = false;
				}
				m=m+2;
			}
			// return false;
			if(i!=j){
				alert("赠品、库存、满赠金额、满赠件数、赠送件数均不能为空");
				return false;
			}
			if(!kucun_zengsongjianshu_boolen){
				alert("如果赠送件数为红框,请查看同行的库存量是否大于等于赠送件数");
				return false;
			}
			// 等级活动赠品
			$(".class_activity_add").find(".base_gift").each(function(){
				var class_activity_add_local_string = "";
				var class_activity_add_gift_level_id = $(this).attr("data-ecs_gift_activity_level_id");
				var class_activity_add_gift = $(this).find(".class_activity_add_gift").val();//赠品名称
				var class_activity_add_gift_limit = $(this).find(".class_activity_add_gift_limit").val();//赠品数量
				var class_activity_add_gift_least_payment = $(this).find(".class_activity_add_gift_least_payment").val();//满赠金额
				var class_activity_add_gift_least_number = $(this).find(".class_activity_add_gift_least_number").val();//满赠件数
				var class_activity_add_gift_each_num = $(this).find(".class_activity_add_gift_each_num").val();//每单赠送数量
				// 将赠品1（2，3...）的所有值拼接起来
				class_activity_add_local_string = class_activity_add_gift+","+class_activity_add_gift_limit+","+class_activity_add_gift_least_payment+","+class_activity_add_gift_least_number+","+class_activity_add_gift_each_num+","+class_activity_add_gift_level_id;
				// 将赠品1，赠品2,...拼接起来
				class_activity_add_final_string = class_activity_add_final_string + class_activity_add_local_string+"?";
			});
// 提取字符串
class_activity_add_final_string = class_activity_add_final_string.substring(0,class_activity_add_final_string.length-1);

}
		//点击了保存所有按钮，将按钮的状态设置成禁用，这样就算后台卡了，前端用户也无法再次点击
		$(this).text("Loading");
		$(this).prop("disabled","disabled");
		/*
		*	将转换成字符串的数据传到后台
		*/
		$.ajax({  
			type:'post',  
			traditional :true,  
			url:'gift_activity.php?act=save_all',  
			data:{
				'id':this_id,
				'active_name':active_name,
				'each_max_num':each_max_num,
				'each_num':each_num,
				'first_gift':first_gift,
				'second_gift':second_gift,
				'third_gift':third_gift,
				'gift_limit_first':gift_limit_first,
				'gift_limit_second':gift_limit_second,
				'gift_limit_third':gift_limit_third,
				'class_activity_add_string':class_activity_add_final_string,
				'start_time':start_time,
				'finally_time':finally_time,
				'least_number':least_number,
				'least_payment':least_payment,
				'activity_cue':activity_cue,
				'repeat_type':repeat_type,
				'activity_type':activity_type,
				'facility_check_state':facility_check_state,
				'distributor_check_state':distributor_check_state,
				'region_check_state':region_check_state,
				'goods_check_state':goods_check_state,
				'goods_excluded_check_state':goods_excluded_check_state,
				'cat_included_check_state':cat_included_check_state,
				'cat_excluded_check_state':cat_excluded_check_state,
				'goods_necessary_check_state':goods_necessary_check_state,
				'goods_necessary_limit_state':goods_necessary_limit_state,
				'facility':facility_cache_string,
				'distributor':distributor_cache_string,
				'region':region_cache_string,
				'goods':goods_cache_string,
				'goods_necessary':goods_necessary_string,
				'goods_excluded_cache':goods_excluded_cache_string,
				'cat_included_cache':cat_included_cache_string,
				'cat_excluded_cache':cat_excluded_cache_string
			},  
			success:function(data){ 
				alert(data);
				location.href = location.href.split("?")[0]+"?delete_session=delete";//当修改任意一个活动时，删除筛选条件的session
				// if(location.search.substr(1).split("&")[0] == 'form_search_hidden=form_search_hidden'){
				// 	location.href = location.href.split("?")[0];
				// }else{
				// 	location.reload();
				// }
			}  
		});
});

$(".delete_button").click(function(){
	var delete_id = $(this).attr('delete_id');
	var flag = $(this).attr('data-flag');
	if(confirm("确定要删除么？")){
		$.ajax({  
			type:'post',  
			traditional :true,  
			url:'gift_activity.php?act=delete',
			data:{
				'detele_id':delete_id,
				'data-flag':flag
			},
			success:function(data){
				alert(data);
				location.href = location.href.split("?")[0]+"?delete_session=delete";//当删除任意一个活动时，删除筛选条件的session
				//这里要来这么判断,主要是因为如果先前表单提交了的话,刷新的时候还是会把筛选条件传过去
				// if(location.search.substr(1).split("&")[0] == 'form_search_hidden=form_search_hidden'){
				// 	location.href = location.href.split("?")[0];
				// }else{
				// 	location.reload();
				// }
			}
		});
	}

});
});