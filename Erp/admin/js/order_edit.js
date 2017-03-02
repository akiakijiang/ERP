$(document).ready(function(){
		//使用订单和商品级别红包的业务组
		var exclude_party = new Array("65619","16","65553","65586");
		$("#payInfoForm .editBtn").css("top","30px").css("color","black");
	
        //定义正则表达式
        var regNumber = new RegExp("^[0-9]*$");
        //var regMobile = new RegExp("^((13[0-9]{9})|(18[0-9]{9})|(15[89][0-9]{8}))$");
        var regMobile = new RegExp("^((1[0-9]{10}))$");
        var regZipcode = new RegExp("^[0-9]{6}$");
        var regDate = new RegExp("^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$");
        var regTid = new RegExp("^[0-9]{8}");
        // var regPhone = /^[+]{0,1}(\d){1,3}[ ]?([-]?((\d)|[ ]){1,12})+$/;
        /*
        全局jquery函数绑定，主要针对HTML初始化的时候不存在的元素的函数绑定，例如ajax动态生成的商品列表中商品的修改以及删除操作，也用于添加商品新生成的商品的修改以及删除操作。
        */
        
        //订单详细信息加载
        var nav_action_id =$("#source_order_info").attr("id");
        var detailLiInd =0;
        navList_action_ajax(detailLiInd,nav_action_id);
        var is_baoda = $("#is_baoda").val();
 
        // 中粮业务组 
        if($party_id == 65625 ){

        	// 中粮合并订单 需要到单独的页面中合并 生成新订单 取消原订单 
        	if($("#uniOrder") != undefined){
        		$("#uniOrder").html("<a href='order_shipment_new_order.php'>中粮合并订单</a>"); 
        	}
        	
        	// 中粮的订单不能 恢复订单
        	$("#order_recover").parents("li").remove();

        	// 中粮不能 取消合并 订单 
        	$(".disUniOrder_force").remove();
        	$(".disUniOrder").remove(); 

        	// 参与合并的订单不能拆分订单 
        	$.ajax({
        		type:'get',
        		dataType:'json',
        		url:"ajax.php?act=is_merged_order&order_id="+$order_id,
        		success:function(data){
        			var is = data.is ;
        			var order_ids = data.order_ids; 
        			if(is != 0){
        				$("#order_split").parents("li").remove(); 
        			}
        			if(is == 1){ // 该订单和其他订单合并 
        			   var order_id = order_ids[0]["order_id"];
        			   var order_sn  =order_ids[0]['order_sn']; 
                       // $("#add_note").remove();  // 去掉添加备注的功能 
                       $("#uniOrder").html("合并成："+ "<a href='order_edit.php?order_id="+order_id+"' target='_blank'>"+order_sn+"</a>" ); 
                       $("#uniOrder").css("width","150px").css("color","red"); 
        			}else if( is == 2){ // 该订单为合并生成的订单 
        				var html = "";
        				html = "订单由 ";
        				for(var i in order_ids){
        					html += "<a href='order_edit.php?order_id="+order_ids[i]['root_order_id']+"' target='_blank'>"+order_ids[i]['root_order_sn']+"</a>"; 
        					html += "&nbsp;"
        				}
        				html += " 合并生成";
        				$("#uniOrder").html(html).css("width","400px").css("color","red");
        			}
        		}, 
        		error:function(){
        		}
        	}); 
        }
       
        // 刷新操作记录信息
        function refresh_act_history() {
           var data= "order_id="+$order_id+"&content_type=action_records&action_type=query";
             
             $.ajax({
               type:'get',
               dataType:'json',
               url:'orderV2/sales_order_edit_ajax.php',
               data:data,
               success:function(data){
                 if(data.error_info_.err_no == 0){
                   $("#actHistoryBox table").html("<tr><th>订单状态</th><th>操作人</th><th>操作时间</th><th>操作备注</th></tr>");
                     var ahTable = "";
                      for(var key in data.action_list_){
                        ahTable = ahTable + "<tr><td rowspan='"+data.action_list_[key].action_count+"'>"+data.action_list_[key].order_status+"</td>";
                        for(var key1 in data.action_list_[key].action_list){
                          if(data.action_list_[key].action_list[key1].note_type_ == 'SHIPPING') {
                                 ahTable = ahTable + "<td>"+data.action_list_[key].action_list[key1].action_user_+"</td><td>"+data.action_list_[key].action_list[key1].action_time_+"</td><td style='background:yellow'>"+data.action_list_[key].action_list[key1].note_+"</td></tr>"
                          } else {
                                 ahTable = ahTable + "<td>"+data.action_list_[key].action_list[key1].action_user_+"</td><td>"+data.action_list_[key].action_list[key1].action_time_+"</td><td>"+data.action_list_[key].action_list[key1].note_+"</td></tr>"
                          }
                        }
                     }
                     $("#actHistoryBox table").append(ahTable);
                 }else{
                   alert(data.error_info_.message);
                 }
               },
               error:function(){
                 alert("refresh_act_history AJAX加载失败");
               }
             });
             
        }
        
        /**
         * 显示订单支付情况
         * 
         * data为ajax传回的值，其中有data.fee_list_为传回的数组，里面包括了标题和内容
         * */
        function show_order_pay(data){
        	var feeNum = data.fee_list_.length;
            var payInfoChild = $(".payInfoChild");
            payInfoChild.html("");
          payInfoChild.prepend("<p>"+data.currency_+"</p>");
            for(var i=feeNum-1;i>=0;i--){  
           	 	//只有当组织为使用订单和商品级别红包的业务组时才能修改订单优惠，其他组织只能修改抵用券
            	//由于其他组织的订单或商品可能拥有各自的优惠价格，为防止变化，因此需要将优惠券信息隐藏在页面中
    
               if( data.fee_list_[i].id_ == "order_discount" ){
                   payInfoChild.prepend("<p id='"+data.fee_list_[i].id_+"'><span class='name_'>"+data.fee_list_[i].name_ +"</span><input type='text' style='width:85px;padding:0;' id='"+data.fee_list_[i].id_+"_' class='value_' value='"+ data.fee_list_[i].value_ +"'></input></p>");
                 }else if(data.fee_list_[i].id_ == "bonus"){
                	 payInfoChild.prepend("<p id='"+data.fee_list_[i].id_+"'><span class='name_'>"+data.fee_list_[i].name_ +"</span><input type='text' style='width:85px;padding:0;' disabled='disabled' id='"+data.fee_list_[i].id_+"_' class='value_' value='"+ data.fee_list_[i].value_ +"'></input></p>");
                 }else{
                	 payInfoChild.prepend("<p id='"+data.fee_list_[i].id_+"'><span class='name_'>"+data.fee_list_[i].name_ +"</span><span class='value_'>"+ data.fee_list_[i].value_ +"</span></p>");
                 }
               
            }
            $(".goodsTotalPrice span").text(data.goods_total_price_);
        }
        // 刷新订单金额信息
        function refresh_pay_info() {                       
           var data= "order_id="+$order_id+"&content_type=pay_info&action_type=query";
             
             $.ajax({
               type:'get',
               dataType:'json',
               url:'orderV2/sales_order_edit_ajax.php',
               data:data,
               success:function(data){
                 if(data.error_info_.err_no == 0){
                	 show_order_pay(data);            
                     //$(".editBtn").show();
                 }else{
                   alert(data.error_info_.message);
                 }
               },
               error:function(){
                 alert("refresh_pay_info AJAX加载失败");
               }
             });
             
        }

        
        //当订单应付总额改变时，自动刷新订单状态
        function refresh_orderStatus_info() {
      var data= "order_id="+$order_id+"&content_type=order_header&action_type=query";
                $.ajax({
                   type:'get',
                   dataType:'json',
                   url:'orderV2/sales_order_edit_ajax.php',
                   data:data,
                   success:function(data){
                     if(data.error_info_.err_no == 0){
                       $("#stateList").html("<li>订单状态:</li>");
                       for(var key in data.status_list_){
                         $("#stateList").append("<li><span>"+data.status_list_[key]+"</span></li>");
                       }
                       if (data.refund_apply_enabled_) {
                           $("#stateList").append("<li><a href='refund_apply_unshipping.php?order_id="+data.order_id_+"' target='_blank'>退款申请</a></li>");
                       }
                       var $actList = $("#actList");
                       $actList.html(" ");
                       for(var key in data.allowed_action_list_){
                         $actList.append("<li><span id='"+key+"'>"+data.allowed_action_list_[key]+"</span></li>");
                       }                                          
                     } else {
                       alert(data.error_info_.message);
                     }
                   },
                   error:function(){
                     alert("refresh_orderStatus_info AJAX加载失败");
                   }
               });
            }
               
        

         $("#goods").delegate(".goodsDeleteBtn","click",function(){//删除商品
                var that = $(this);
                var data = 'content_type=goods_list&action_type=delete&order_goods_id='+that.parent().parent().find(".order_goods_id").text()+'&order_id='+$order_id;
                data += '&changeBonus=1';
                if(confirm('确定删除该商品吗？')){
                    $.ajax({
                      type:'get',
                      dataType:'json',
                      url:'orderV2/sales_order_edit_ajax.php',
                      data:data,
                      beforeSend:function(){
                        that.text("正在努力删除");
                        $("#disable-mask").show();
                      },
                      success:function(data){
                        that.text("删除");
                        $("#disable-mask").hide();
                        if(data.error_info_.err_no == 0){
                          that.parent().parent().hide();
                          // 刷新订单支付信息
                          refresh_pay_info();
                          alert("删除成功");
                          // 刷新操作记录
                          refresh_act_history();
                        }else{
                          alert(data.error_info_.message);
                        }
                        
                      },
                      error:function(){
                        that.text("删除");
                        $("#disable-mask").hide();
                        alert("AJAX加载失败");
                      }
                    });
                    
                }
         });

        $("#bestExp").delegate(".changeTrackBtn","click",function(){//最优快递修改
          var that = $(this);
          var shipping_id  = $(this).next().text();
          var facility_id  = $(this).next().next().text();
          
          // 先修改快递
          var check_shipping = modify_shipping(that,$order_id,shipping_id);
          if(!check_shipping) {
            return false;
          }
          // 再修改仓库
          var check_facility = modify_facility(that,$order_id,facility_id);
          if(check_facility) {
              alert("快递和仓库修改成功");
          }
         
        });
        
        // 修改快递
        function modify_shipping(that,order_id,shipping_id) {           
           var result = false;
           var data="content_type=express&action_type=update&order_id="+order_id+"&shipping_id="+shipping_id;
             $.ajax({
               async:false,
               type:'get',
               dataType:'json',
               url:'orderV2/sales_order_edit_ajax.php',
               data:data,
               beforeSend:function(){
                 that.attr("disabled",true).val("正在努力修改快递");
               },
               success:function(data){
                 that.attr("disabled",false).val("修改快递和仓库");
                 if(data.error_info_.err_no == 0){
                   result = true;
                   //$("#shipping_id").html("<option value='"+data.shipping_id_+"'>"+data.shipping_name_+"</option>");

                   $("#shipping_id option[value='"+data.shipping_id_+"']").attr("selected","selected");
                 
                   // 刷新订单支付信息
                   refresh_pay_info();
                   alert("修改快递成功！");
                   // 刷新操作记录
                   refresh_act_history();
                   
                 }else{
                   alert(data.error_info_.message);
                 }
                
                 
               },
               error:function(){
                 that.attr("disabled",false).val("修改快递和仓库");
                 alert("AJAX加载失败");
               }
             });
             
             return result;
        }
        
        // 修改仓库
        function modify_facility(that,order_id,facility_id) {
           var result = false;
           var data="content_type=facility&action_type=update&order_id="+order_id+"&facility_id="+facility_id;
             $.ajax({
               async:false,
               type:'get',
               dataType:'json',
               url:'orderV2/sales_order_edit_ajax.php',
               data:data,
               beforeSend:function(){
                 that.attr("disabled",true).val("正在努力修改仓库");
               },
               success:function(data){
                 that.attr("disabled",false).val("修改快递和仓库");
                 if(data.error_info_.err_no == 0){
                   result = true;
                   $("#facility_id").html("<option value='"+data.facility_id_+"'>"+data.facility_name_+"</option>");

                   // 刷新操作记录
                   refresh_act_history();
                   // 判断该仓库下是否支持该快递
                   check_facility_shipping(); 
                 }else{
                   alert(data.error_info_.message);
                 }
                 
               },
               error:function(){
                 that.attr("disabled",false).val("修改快递和仓库");
                 alert("AJAX加载失败");
               }
             });
             
             return result;
        }
        
        /**
         * 判断订单中是否有“【秒杀】美滋滋番茄味腰果198g*30罐”商品
         */
        function check_goods(order_id) {
            if(!order_id){
                alert("check_goods订单号为空！");
                return false;
              }
              var result = false;
              $.ajax({
                    async : false,
                    type: 'POST',
                    dataType: 'json',
                    url : 'ajax.php?act=check_goods', 
                    data: 'order_id=' + order_id,
                    success: function(data) {
                      result = true;
                      if(data.length == 0) {
                           result = false;
                        }
                    },
                    error: function() {
                        result = false;
                      alert("check_goods时ajax请求错误order_id:" + order_id); 
                    }
                });
              return result;  	
        }
        
        /**
         * 判断OR百万增值活动 商品级别红包是否有误
         * 
         */
        function check_discountfee(order_id) {
            if(!order_id){
                alert("check_goods订单号为空！");
                return false;
              }
              var result = true;
              $.ajax({
                    async : false,
                    type: 'POST',
                    dataType: 'json',
                    url : 'ajax.php?act=check_discountfee', 
                    data: 'order_id=' + order_id,
                    success: function(data) {
                      if(data.success == false) {
                           result = false;
                        }
                    },
                    error: function() {
                        result = false;
                      alert("check_discountfee时ajax请求错误order_id:" + order_id); 
                    }
                });
              return result; 
        }
        
        /**
         * 判断订单是否已批拣
         */
        function is_picked(order_id) {
            if(!order_id){
                alert("check_goods订单号为空！");
                return false;
              }
              var result = true;
              $.ajax({
                    async : false,
                    type: 'POST',
                    dataType: 'json',
                    url : 'ajax.php?act=is_picked', 
                    data: 'order_id=' + order_id,
                    success: function(data) {
                      if(data.is_picked == false) {
                           result = false;
                        }
                    },
                    error: function() {
                        alert("is_picked时ajax请求错误order_id:" + order_id); 
                        return false;
                    }
                });
              return result;
        }
        
        /**
         * 显示商品列表
         * */
        function showGoodsList(data, message){
        	var addedGoods = "";

        	var goodsHtml = "<th class='goodsNameTh'>商品名</th><th>样式</th><th>配送仓123<br>物理库存</th><th>配送仓<br>可预订库存</th><th style='width:80px;'>预定情况</th><th>发票</th><th>串号</th><th>单价</th><th>数量</th>";
        	var fenxiao_type = data.goods_list_.fenxiao_type_; 
          console.log(fenxiao_type); 
          if( fenxiao_type == "DEALER"){
            goodsHtml += "<th>箱数</th>";
            goodsHtml += "<th>立方数cm³</th>"; 
            goodsHtml += "<th>重量g</th>"; 
          }
          goodsHtml += "<th>优惠券 </th>";
        	goodsHtml += "<th>小计</th><th>操作</th>";
        	$("#goods table").html(goodsHtml);

          console.log(data.goods_list_);
          for(var key in data.goods_list_.goods_list_){
            addedGoods = "<tr class='goodsList' id='"+data.goods_list_.goods_list_[key].goods_id_+"'>";
            addedGoods += "<td class='order_goods_id' style='display:none'>" + data.goods_list_.goods_list_[key].rec_id_+"</td>";
            addedGoods += "<td>"+data.goods_list_.goods_list_[key].goods_name_ +"</td>";
            addedGoods += "<td><select class='styleList'><option value='"+data.goods_list_.goods_list_[key].style_id_+"'>"+data.goods_list_.goods_list_[key].style_name_ +"</option><optgroup></optgroup></select></td>";
            addedGoods += "<td>" + data.goods_list_.goods_list_[key].qoh_in_facility_ + "</td>";
            addedGoods += "<td>" + data.goods_list_.goods_list_[key].atp_in_facility_ + "</td>";
            addedGoods += "<td>" + data.goods_list_.goods_list_[key].reserve_status_ + "</td>";
            addedGoods += "<td>";
            var Invlen = data.goods_list_.goods_list_[key].shipping_invoices_.length;
            if(Invlen > 0){
              for(var i=0;i<Invlen;i++){
                addedGoods = addedGoods + data.goods_list_.goods_list_[key].shipping_invoices_[i]+"</br>";
              } 
            }
            addedGoods = addedGoods + "</td>";
            addedGoods += "<td>";
                    // 串号
            var Serlen = data.goods_list_.goods_list_[key].serial_numbers_.length;
            if(Serlen > 0){
              for(var i=0;i<Serlen;i++){
                addedGoods = addedGoods + data.goods_list_.goods_list_[key].serial_numbers_[i]+"</br>";
              } 
            }
                   
            addedGoods = addedGoods + "</td>";
            addedGoods +="<td><input class='goods_price' style='width:80px' readonly='readonly' type='text' value='" + data.goods_list_.goods_list_[key].p_goods_price_+ "'></td>";

            if( parseInt( data.goods_list_.goods_list_[key].atp_in_facility_ ) < parseInt( data.goods_list_.goods_list_[key].p_goods_number_ ) ){
              addedGoods +="<td><input class='goods_number' style='width:50px;color:red;text-align:center' readonly='readonly' type='text' value='" + data.goods_list_.goods_list_[key].p_goods_number_ + "'></td>";
            }else{
              addedGoods +="<td><input class='goods_number' style='width:50px;text-align:center' readonly='readonly' type='text' value='" + data.goods_list_.goods_list_[key].p_goods_number_ + "'></td>";
            }

            // 如果是经销订单 显示 箱数 立方数 重量 
            if(fenxiao_type =="DEALER"){
              var spec = data.goods_list_.goods_list_[key].spec; 
              var goods_number = data.goods_list_.goods_list_[key].p_goods_number_ ; 
              var goods_volume = data.goods_list_.goods_list_[key].goods_volume;
              var goods_weight = data.goods_list_.goods_list_[key].goods_weight; 
              goods_number = parseInt(goods_number); 
              if(parseInt(spec) > 0){
                spec = parseInt(spec); 
                addedGoods += "<td>";  
                var box = parseInt(goods_number / spec); 
                addedGoods +=  box+"箱";
                var left = goods_number - box*spec; 
                if(left > 0 ){
                  addedGoods += "零"+left; 
                }
                addedGoods += "</td>"; 
              }else{
                addedGoods += "<td>"+"</td>";
              }
              addedGoods += "<td>"+(goods_volume*goods_number)+"</td>";
              addedGoods += "<td>"+(goods_weight*goods_number)+"</td>";
            }
            var goods_discount = data.goods_list_.goods_list_[key].goods_discount_;
            goods_discount = goods_discount ? goods_discount : 0;
            addedGoods += "<td><input class='goods_discount' readonly='readonly' type='text' style='width:50px;text-align:center' value='" + goods_discount + "' /></td>";
            addedGoods += "<td>" + data.goods_list_.goods_list_[key].total_price_ + "</td><td><p class='goodsEditBtn'>修改</p><p class='goodsDeleteBtn'>删除</p></td></tr>"; 
            $("#goods table").append(addedGoods);
          } 

            
            
           
            if(message != null){
            	// 刷新订单支付信息
            	refresh_pay_info();
            	//刷新订单的状态信息
                refresh_orderStatus_info();
            	
                alert(message);
            	// 刷新操作记录
                refresh_act_history();
            }
            
            
        }
        $(".addGoodsBtn").click(function(){//增加商品
                var data="order_id="+$order_id+"&group_goods_id=-1"+"&goods_id="+$("#searchGoodsId").val()+"&style_id="+$("#searchStyleList").val()+"&goods_price="+$("#searchGoodsPrice").val()+"&goods_number="+$("#searchGoodsNumber").val()+"&content_type=goods_list&action_type=insert";
                var that=$(this);
                if($("#searchGoodsNumber").val() > 0 && $("#searchGoodsPrice").val() >=0 && $("#searchGoodsPrice").val() !='' && $("#searchName").val()!=''){
                  $.ajax({
                    type:'get',
                    dataType:'json',
                    url:'orderV2/sales_order_edit_ajax.php',
                    data:data,
                    beforeSend:function(){
                      $("#disable-mask").show();
                      that.text("正在努力添加").animate({"width":"90px"},"fast");
                    },
                    success:function(data){
                      $("#disable-mask").hide();
                      that.text("添加").animate({"width":"32px"},"fast");
                      if(data.error_info_.err_no == 0){
                    	  //显示商品列表
                    	  showGoodsList(data, "添加商品成功");
                      }else{
                        alert(data.error_info_.message);
                        $("#searchGoodsPrice").val("");
                        $("#searchGoodsNumber").val("");
                      }
                    },
                    error:function(){
                      $("#disable-mask").hide();
                      that.text("添加").animate({"width":"32px"},"fast");
                      alert("AJAX加载失败");
                      $("#searchGoodsPrice").val("");
                      $("#searchGoodsNumber").val("");
                    }
                  });
                }else{
                  that.text("添加");
                  alert("商品名不能为空，商品单价不能为负，商品数量必须大于0");
                }
        });
        
        $(".addGroupGoodsBtn").click(function(){//增加套餐商品
            var data="order_id="+$order_id+"&group_goods_id="+$("#searchGroupGoodsId").val()+"&goods_id=-1"+"&style_id=-1"+"&goods_price=0"+"&goods_number=1"+"&content_type=goods_list&action_type=insert";
            var that=$(this);
            if($("#searchGroupGoodsId").val()){
              $.ajax({
                type:'get',
                dataType:'json',
                url:'orderV2/sales_order_edit_ajax.php',
                data:data,
                beforeSend:function(){
                  $("#disable-mask").show();
                  that.text("正在努力添加").animate({"width":"90px"},"fast");
                },
                success:function(data){
                  $("#disable-mask").hide();
                  that.text("添加").animate({"width":"32px"},"fast");
                  if(data.error_info_.err_no == 0){
                	  //显示商品列表
                	  showGoodsList(data,"添加商品成功");
                  
                  }else{
                    alert(data.error_info_.message);
                    $("#searchGoodsPrice").val("");
                    $("#searchGoodsNumber").val("");
                  }
                },
                error:function(){
                  $("#disable-mask").hide();
                  that.text("添加").animate({"width":"32px"},"fast");
                  alert("AJAX加载失败");
                  $("#searchGoodsPrice").val("");
                  $("#searchGoodsNumber").val("");
                }
              });
            }else{
              that.text("添加");
              alert("套餐名不能为空");
            }
       });

        $("#actList").delegate("span","click",function(){
              var left = $(this).position().left - 300;
              var action_id = $(this).attr("id");
              $('#actListTab').find('span').css('color','#333');
          	 $('#actListTab').find('label').css('color','#333');
              $('.subActBtn').css('cssText','background:#1590d2;');
              $('.subActBtn').css('cssText','background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0,#79bbf7), color-stop(1, #1590d2));');
              if(action_id == 'order_abandon'){
             	 $('#actListTab').find('span').css('color','red');
             	 $('#actListTab').find('label').css('color','red');
             	 $('.subActBtn').css('cssText','background:#f9595d');
               }
              if(action_id == "order_confirm" && $party_id == 65625 && check_goods($order_id)) {
            	  alert("是否需要将发货仓库更换到百世广州仓？");
              }
              if(action_id == "order_confirm" && $party_id == 65619 && !check_discountfee($order_id)) {
            	  alert("该订单红包信息有误，无法确认订单！");
            	  return;
              }
              if((action_id=='order_cancel' || action_id=='order_abandon') && $party_id==65574){
            	  if(!confirm("订单成功预定后取消不可再恢复，请确认是否仍要取消？")){
            		  return;
            	  }
              }
              if((action_id=='order_cancel' || action_id=='order_abandon') && $party_id==65614){
//              if(action_id=='order_cancel' && ( $("#distributor_id").val()=='1921' || $("#distributor_id").val()=='2524' )){
            	  if(!confirm("百威订单拒绝需要时间较长，是否确认继续？")){
            		  return;
            	  }
              }
              if((action_id=='order_cancel' || action_id=='order_abandon') && $party_id==65558){
              	  if(!confirm("金佰利订单拒绝需要时间较长，是否确认继续？")){
              		  return;
              	  }
              }
              if(action_id == "order_split") {
//            	  window.location.href="order_split.php?order_id="+$order_id; 
            	  window.open("order_split.php?order_id="+$order_id,"_blank")
            	  return;
              }
              $(".noteText").val("");
              $("#datepicker").val("");
              $("#actListTab").show().attr("class",action_id).animate({"left":left},"fast"); 
        });

        $(".subActBtn").click(function(){//提交头部操作内容
             header_action_ajax($(this),$(this).parent().attr("class"));
        });
        
        // 选择支付方式时 
        $("#pay_id").change(function(){
          $("#order_pay_id_select").val($(this).find("option:selected").text()); 
        }); 

        // 当选择快递时判断 该仓库是否支持该快递 
        $("#shipping_id").change(function(){
          $("#order_shipping_id_select").val($(this).find("option:selected").text()); 
           check_facility_shipping(); 
        }); 
           
        // 当选择仓库时 判断该仓库是否支持下面的快递 
        $("#facility_id").change(function(){
           check_facility_shipping(); 
        }); 

        //检查快递合法性 SINRI 真是大坑
        function check_facility_shipping(){
          var fid=$('#facility_id').val();
          var sid=$('#shipping_id').val();
               //alert("F="+fid+" S="+sid);
              $('#sinri_facility_express_check_result').val(0);
             $.ajax({
               type:'get',
               async : false,
               url:"includes/lib_sinri_facility_express.php?act=ajax&facility_id="+fid+"&shipping_id="+sid,
               data:'',
               success:function(data){
                 //alert(data+" "+data.indexOf('FS-YES'));
                if(data.indexOf('FS-YES')>-1){
                  $('#sinri_facility_express_check_result').val(1);
                }else{
                  $('#sinri_facility_express_check_result').val(-1);
                }
                
               },
               error:function(){
                 alert("查询【仓库X快递】合法性的ajax挂了，快去哭诉。");
                 $('#sinri_facility_express_check_result').val(-1);
               }
             });
            if($('#sinri_facility_express_check_result').val()==1){
              return true;
             }else{
              alert('当前仓库不支持该运送方式！\n如果需要为该仓库添加此运送方式，请向ERP组递交申请。');
              return false;
              }
        }

        function check_bwshop_tax_limit_beyond(){
          if($warning_for_bwshop_order){
            return $warning_for_bwshop_order;
          }else{
            return false;
          }
        }
        
        //检查退款
        function check_refund_status(order_id){
           if(!order_id){
             alert("check_refund_status订单号为空！");
             return false;
           }

           var result = false;
           $.ajax({
                 mode: 'abort',
                 async : false,
                 type: 'POST',
                 dataType: 'json',
                 url : 'ajax.php?act=check_refund_status', 
                 data: 'order_id=' + order_id,
                 success: function(data) {
                   result = true;
                   if(!data.success) {
                     if(!confirm(data.error_info)) {
                      result = false;
                     }
                   }
                 },
                 error: function() {
                     result = false;
                   alert('check_refund_status时ajax请求错误order_id:' + order_id); 
                 }
             });
           return result;
        }
        
        //检查可预订库存
        function get_order_goods_atp_info(order_id){
           if(!order_id){
             alert("get_order_goods_atp_info订单号为空！");
             return false;
           }
           var result = false;
           $.ajax({
                 mode: 'abort',
                 async : false,
                 type: 'POST',
                 dataType: 'json',
                 url : 'ajax.php?act=get_order_goods_atp_info', 
                 data: 'order_id=' + order_id,
                 success: function(data) {
                   result = true;
                   if(!data.success) {
                   if(!confirm(data.error_info)) {
                     result = false;
                   }
                   }
                 },
                 error: function() {
                     result = false;
                   alert('get_order_goods_atp_info时ajax请求错误order_id:' + order_id); 
                 }
             });
           return result;
        }
        
        //检查订单金额
        function check_pay_info(order_id){
           if(!order_id){
             alert("check_pay_info订单号为空！");
             return false;
           }

           var result = false;
           $.ajax({
                 mode: 'abort',
                 async : false,
                 type: 'POST',
                 dataType: 'json',
                 url : 'ajax.php?act=check_pay_info', 
                 data: 'order_id=' + order_id,
                 success: function(data) {
                   result = true;
                   if(!data.success) {
                     alert(data.error_info);
                     result = false;
                   }
                   if(data.success_info && confirm(data.success_info)!= true){
                    result = false;
                   }
                 },
                 error: function() {
                     result = false;
                   alert('check_pay_info时ajax请求错误order_id:' + order_id); 
                 }
             });
           return result;
        }
        
        //检查预存款
        function check_prepayment(order_id,party_id){
           if(!order_id || !party_id){
             alert("check_prepayment订单号为空或者组织号为空！");
             return false;
           }

           var result = false;
           $.ajax({
                 mode: 'abort',
                 async : false,
                 type: 'POST',
                 dataType: 'json',
                 url : 'ajax.php?act=check_prepayment', 
                 data: 'order_id=' + order_id + '&party_id=' + party_id,
                 success: function(data) {
                   result = true;
                   if(!data.success) {
                   if(!confirm(data.error_info)) {
                     result = false;
                   }
                   }
                 },
                 error: function() {
                     result = false;
                   alert('check_prepayment时ajax请求错误order_id:' + order_id); 
                 }
             });
           return result;
        }

        //通过订单号查询原订单号来寻找是否已存在对应的淘宝订单号
        function check_taobao_order_sn(order_sn){
        	var isSubmit = false;
            $.ajax({
           	 async : false,
           	 type: 'POST',
           	 data : {order_sn : order_sn},
           	 dataType : "json",
           	 url : "ajax.php?act=search_taobao_sn_by_order",
           	 success : function(data){
           		 if(data["isSubmit"]){
           			isSubmit = true;
           		 }
           		 	
           	 },
           	 error : function(data){
           		 alert("加载超时，请重试");
           	 }
            });
            
            return isSubmit || confirm("此单已存在录单或换货，继续操作可能导致重复发货，确认换货么？");
        }
        
       //通过订单号查询该订单是否已上传给跨境购平台审核，并根据审核状态来判断该订单是否能取消,不能取消则返回false
        function check_kjg_order_cancel(order_sn){
        	var isSubmit = true;
            $.ajax({
	           	 async : false,
	           	 type: 'POST',
	           	 data : {order_sn : order_sn},
	           	 dataType : "json",
	           	 url : "ajax.php?act=search_kgj_by_orderSn",          	
           	 
	           	 success : function(data){
	          		 if(data["isSubmit"]==1){
	          			isSubmit = true;
	          		 }else{
	          			isSubmit = false;
	          		 }
	          	 },
	          	 error : function(data){
	          		 alert("加载超时，请重试");
	          	 }
	           });
           
           return isSubmit;
        }
        
        
        
        // 头部提交前检查
        function check_header_sub(order_action_id) {
          // 确认订单
          if(order_action_id == 'order_confirm') {
                //SINRI 判断快递和仓库的符合性
                if(!check_facility_shipping()){
                   //alert('F');
                   return false;
                }else{
                  //alert('T');
                }

                bwshop_warning=check_bwshop_tax_limit_beyond();
                if(bwshop_warning){
                  if(!confirm(bwshop_warning+'你要一意孤行继续确认订单么？')){
                    return false;
                  }
                }

              // 判断退款
                 if( !check_refund_status($order_id) ) {
                  return false;
                 }
             
             // 判断可预订量
             if( !get_order_goods_atp_info($order_id) ) {
              return false;
             }
             
             
             // 判断金额不等
             if( !check_pay_info($order_id) ) {
              return false;
             }
             
             // 判断预存款不足
             if( !check_prepayment($order_id,$party_id) ) {
              return false;
             }
             
             //判断是否有重复录单或者换货
             if( !check_taobao_order_sn($("#order_sn").val()) ){
     	  		return false;
     	  	}
          }
          
          //取消订单   
//          if(order_action_id == 'order_cancel') {
//                //判断是否能取消订单
//                if(!check_kjg_order_cancel($("#order_sn").val())){
//                   alert('该订单已经提交到跨境购平台进行审核，不能再取消！');
//                   return false;
//                }
//          }
          
          return true;
          
        }
        
        function header_action_ajax(that,order_action_id) {
           var is_shipping_note = 0;     
           if(document.getElementById("isShippingNote").checked) {
             is_shipping_note = 1;
           }
           if(!check_header_sub(order_action_id)) {
             return false;
           }
            var data="order_action_id="+order_action_id+"&content_type=order_header&action_type=update&note_content="+$(".noteText").val()+"&is_shipping_note="+is_shipping_note+"&add_time="+$("#datepicker").val()+"&order_id="+$order_id;
            var today = new Date();
            var selectedTime = new Date($("#datepicker").val());
            var can_cancel = false;
  /*
            var flag = true;
            if(order_action_id=='order_cancel'){
            	$.ajax({
                    type:'POST',
                    dataType:'json',
                    async : false,
                    url:"ajax.php?act=is_bind_mt_order",
                    data:{
                    	order_id:$order_id,
                    },
                    success:function(data){
                    	console.log(data);
                    	if(data.result == 'failure'){
                    		alert(data.note);
                    		$("#actListTab").hide();
                    		flag = false;
                    	}
                    },
                    error:function(){
                    	alert("失败");
                    	flag = false;
                    }                        
            	});
            }

            if(!flag){
            	return false;
            }
*/
            
            if(selectedTime.getTime()!= "" && (selectedTime.getTime() < today.getTime())){
              alert("输入的日期必须大于等于今天！");
              //金宝贝。。。2016.6.7注释
//            }else if((order_action_id=='order_cancel' || order_action_id=='order_abandon') && $party_id=='65574'){
//           	 	//先通过giessen取消订单成功后再erp取消，并将brand_gymboree_sales_order_info
//          		$.ajax({
//                        type:'POST',
//                        dataType:'json',
//                        async : false,
//                        url:"order_cancel_gymboree.php?act=order_cancel&order_id="+$order_id,
//                        data:'',
//                        beforeSend:function(){
//                            that.addClass('disableBtn').text("正在提交").attr("disabled",true);
//                         },
//                        success:function(data){
//                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
//                        	if(data.flag =='WMS_SUCCESS'){
//                        		//可以操作erp取消了
//                        		//记得一定要修改传送状态，与订单action
//                        		can_cancel = true;
//                        	}else if(data.flag =='FALSE' || data.flag =='SHIP' || data.flag =='ERROR'){
//                        		alert("取消订单失败！"+data.message);
//                        		$("#closeActTab").trigger("click");
//                        	}
//                        	$("#actListTab").hide();
//                        },
//                        error:function(){
//                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
//                        	alert("金宝贝订单已经被仓库扣下了，请重试！");
//                        }                        
//                 });
          		//bwshop....2016.6.7注释
//            }else if((order_action_id=='order_cancel' || order_action_id=='order_abandon') && $party_id=='65638'){
//           	 	//乐其跨境业务下面
//          		$.ajax({
//                        type:'POST',
//                        dataType:'json',
//                        async : false,
//                        url:"order_split_haiwai.php?act=order_cancel&order_id="+$order_id,
//                        data:'',
//                        beforeSend:function(){
//                            that.addClass('disableBtn').text("正在提交").attr("disabled",true);
//                         },
//                        success:function(data){
//                        	console.log(data);
//                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
//                        	if(data.flag =='SUCCESS'){
//                        		can_cancel = true;
//                        	}else if(data.flag =='FALSE' || data.flag =='SHIP' || data.flag =='ERROR'){
//                        		alert("取消订单失败！"+data.message);
//                        		$("#closeActTab").trigger("click");
//                        	}
//                        	$("#actListTab").hide();
//                        },
//                        error:function(){
//                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
//                        	$("#actListTab").hide();
//                        	//alert("乐其跨境订单不能取消，请重试！");
//                        }                        
//                 });
            }else if((order_action_id=='order_cancel' || order_action_id=='order_abandon') && ($party_id=='65614' || $party_id=='65558' || $party_id=='65632' || $party_id=='65553') ){
           	 	//先通过物流宝取消订单接口取消订单成功后再erp取消 
            	$.ajax({
                        type:'POST',
                        dataType:'json',
                        async : false,
                        url:"order_cancel_birdexpress.php?act=order_cancel&order_id="+$order_id,
                        data:'',
                        beforeSend:function(){
                            that.addClass('disableBtn').text("正在提交").attr("disabled",true);
                         },                       
                        success:function(data){
                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
                        	if(data.flag =='SUCCESS'){
                        		//可以操作erp取消了
                        		can_cancel = true;
                        		alert('取消成功');
                        	}else if(data.flag =='ERROR'){
                        		alert("取消订单失败！"+data.message);
                        		$("#closeActTab").trigger("click");
                        	}
                        	$("#actListTab").hide();
                        },
                        error:function(){
                        	that.removeClass('disableBtn').text("提交").attr("disabled",false);
                        	alert("订单已经被仓库扣下了，请重试！");
                        }                        
                 });
            }else if ((order_action_id=='order_cancel' || order_action_id=='order_abandon') && is_baoda) {
            	//跨境业务组下面推送申报系统订单
            	$.ajax({
            		type:'POST',
            		dataType:'json',
            		async:false,
            		url:"ajax.php?act=kjg_order_cancel",
            		data:"order_id="+$order_id,
            		beforeSend:function() {
            			that.addClass('disableBtn').text("正在提交").	attr("disabled",true);
            		},
            		success:function(data){
                    	that.removeClass('disableBtn').text("提交").attr("disabled",false);
                    	if(data.flag =='SUCCESS'){
                    		//可以操作erp取消了
                    		can_cancel = true;
                    		alert('作废订单成功');
                    	}else if(data.flag =='ERROR'){
                    		alert("作废订单失败！"+data.message);
                    		$("#closeActTab").trigger("click");
                    	}
                    	$("#actListTab").hide();
                    },
                    error:function(){
                    	that.removeClass('disableBtn').text("提交").attr("disabled",false);
                    	alert("ajax error！");
                    }
            	});
            }
            else{
            	can_cancel = true;
            }
            if(can_cancel){
                $.ajax({
                   type:'get',
                   dataType:'json',
                   url:'orderV2/sales_order_edit_ajax.php',
                   data:data,
                   beforeSend:function(){
                     that.addClass('disableBtn').text("正在提交").attr("disabled",true);
                   },
                   success:function(data){
                     that.removeClass('disableBtn').text("提交").attr("disabled",false);
                     if(data.error_info_.err_no == 0){
                     //  alert("操作成功");
                       $("#stateList").html("<li>订单状态:</li>");
                       for(var key in data.status_list_){
                         $("#stateList").append("<li><span>"+data.status_list_[key]+"</span></li>");
                       }
                       if (data.refund_apply_enabled_) {
                           $("#stateList").append("<li><a href='refund_apply_unshipping.php?order_id="+data.order_id_+"' target='_blank'>退款申请</a></li>");
                         }
                       var $actList = $("#actList");
                       $actList.html("");
                       for(var key in data.allowed_action_list_){
                         $actList.append("<li><span id='"+key+"'>"+data.allowed_action_list_[key]+"</span></li>");
                       }
                       
                       // 刷新操作记录
                       refresh_act_history();
                       if((order_action_id=='order_cancel' || order_action_id=='order_abandon') && $party_id==65574){
                    	   $.ajax({
                               type:'POST',
                               dataType:'json',
                               async : false,
                               url:"order_cancel_gymboree.php?act=update_gymboree_sale_order&order_id="+$order_id,
                               data:'',
                               success:function(data){
                            	   if(!data){
                            		   alert("该订单状态未能正确反馈，请告知ERP");
                            	   }
                               },
                               error:function(){
                               	alert("该订单状态未能正确反馈，请告知ERP");
                               }                        
                             });
                       }
                     } else {
                       alert(data.error_info_.message);
                     }
                     $("#actListTab").hide();
                     if(order_action_id=='mark_taobao_order_sn_with_tail_x'){
                       location.reload(true);
                     }
                   },
                   error:function(){
                     that.removeClass('disableBtn').text("提交").attr("disabled",false);
                     alert("AJAX加载失败");
                   }
               });
            }
           
        }

        $("#closeActTab").click(function(){//关闭头部操作菜单
              $(this).parent().hide();
        });

        $(".addUniOrder").click(function(){
          var that=$(this);
          $(".inputUniOrder").slideToggle(50);
        });

        $(".subUniOrder").click(function(){//合并订单
            var that = $(this);
            var data="merge_type=merge&content_type=merge_info&action_type=update&order_id="+$order_id+"&merge_shipment_external_type="+that.siblings("select").val()+"&merge_shipment_order_sn="+that.siblings("input[type='text']").val();
            // alert(data);
            $.ajax({
              type:'get',
              dataType:'json',
              url:'orderV2/sales_order_edit_ajax.php',
              data:data,
              beforeSend:function(){
                that.attr("disabled",true);
              },
              success:function(data){
                that.attr("disabled",false);
                if(data.error_info_.err_no == 0){
                  alert("合并成功");
                  $(".inputUniOrder").slideUp(200);
                  $(".existUniOrderId").html("");
                  var merged_order_ids_wrap = $(".merged_order_ids_wrap");
                  merged_order_ids_wrap.fadeIn();
                  for(var key in data.merged_order_ids_){
                    merged_order_ids_wrap.append("<span class='existUniOrderId'><a href='order_edit.php?order_id="+data.merged_order_ids_[key].order_id+"' target='_blank'>"+data.merged_order_ids_[key].order_sn+"</a>,<span>");
                  }
                  
                  // 刷新操作记录
                  refresh_act_history();

                }else{
                  that.attr("disabled",false);
                  alert(data.error_info_.message);
                }
              },
              error:function(){
                alert("AJAX加载失败");
              }
            });
        });

        $(".disUniOrder").click(function(){//拆分订单
        	//检查该订单的shipping_status
        	
        	if(is_picked($order_id)) {
        		if(confirm('该合并订单已批拣出库，取消合并后订单必须取消，需要发货的订单需要重新录单，请选择确认或取消？') == false) {
        			return;
        		}
        	}
        	
            var that = $(this);
            var data="merge_type=split&content_type=merge_info&action_type=update&order_id="+$order_id;
            var existUniOrderId=$(".existUniOrderId");
            // alert(data);
            $.ajax({
              type:'get',
              dataType:'json',
              url:'orderV2/sales_order_edit_ajax.php',
              data:data,
              beforeSend:function(){
              },
              success:function(data){
                if(data.error_info_.err_no == 0){
                  if(data.can_edit_){
                    alert("拆分成功");
                    $(".merged_order_ids_wrap").fadeOut(200);
                    
                    // 刷新操作记录
                    refresh_act_history();
                  }
                }else{
                  alert(data.error_info_.message);
                }
              },
              error:function(){
                alert("AJAX加载失败");
              }
            });
    	
        });
  
        //sinri 强行拆分订单
        $(".disUniOrder_force").click(function(){
    	  if(is_picked($order_id)) {
    		 if(confirm('该合并订单已批拣出库，取消合并后订单必须取消，需要发货的订单需要重新录单!') == false) {
    			return;
    		 }
    	  }
          $.ajax({
              type:'post',
              dataType:'json',
              url:'includes/lib_force_orders_divorce.php',
              data:"order_id="+$order_id+"&act=force_order_divorce",
              beforeSend:function(){

              },
              success:function(data){
                // alert(data);
                if(data.result=='done'){
                    alert("强行拆分成功");
                    $(".merged_order_ids_wrap").fadeOut(200);
                    
                    // 刷新操作记录
                    refresh_act_history();
                }else{
                  alert("强行拆分失败:"+data.result);
                }
              },
              error:function(){
                alert("AJAX加载失败");
              }
            });
        });

        $("#useBonusBtn").click(function(){
              var that=$(this);
              that.next().slideToggle(100);
        });
        $("#testBonus").click(function(){//检测红包代码可用性 
              var that=$(this);
              // if(!regNumber.test(that.prev().val())){
              //     alert("请输入数字!");
              // }else{
                  var data="order_id="+$order_id+"&code="+that.prev().val();
                  // alert(data);
                  $.ajax({
                      type:'post',
                      dataType:'json',
                      url:'ajax.php?act=test_bonus_id',
                      data:data,
                      beforeSend:function(){
                        that.attr("disabled",true);
                      },
                      success:function(data){
                        that.attr("disabled",false);
                        alert(data.info);
                      },
                      error:function(){
                        that.attr("disabled",false);
                        alert("AJAX加载失败");
                      }
                  });
              // }
        });
        $("#subBonus").click(function(){//使用红包
          var that = $(this);
          // if(!regNumber.test($("#bonus_id").val())){
          //       alert("请输入正确格式的红包代码!");
          // }else{
                var data= "order_id="+$order_id+"&bonus_id="+$("#bonus_id").val()+"&note_content="+$(this).prev().prev().val()+"&content_type=pay_info&action_type=update";
                // alert(data);
                $.ajax({
                  type:'get',
                  dataType:'json',
                  url:'orderV2/sales_order_edit_ajax.php',
                  data:data,
                  beforeSend:function(){
                    that.attr("disabled",true);
                  },
                  success:function(data){
                    that.attr("disabled",false);
                    if(data.error_info_.err_no == 0){
                      alert("success");
                    }else{
                      alert(data.error_info_.message);
                    }
                  },
                  error:function(){
                    that.attr("disabled",false);
                    alert("AJAX加载失败");
                  }
                });
        // }
        });
        function search_goods(that,data) {
            $.ajax({
                type:'get',
                dataType:'json',
                url:"order.php?act=search_goods",
                data:data,
                beforeSend:function(){
                  that.addClass("searchNameLoad");
                },
                success:function(data){
                    that.removeClass("searchNameLoad");
                    var $nameNumber = data.goodslist.length;
                    if($nameNumber > 0){
                          $(".goodsNameList").html("");
                          for(var i=0;i<$nameNumber;i++){
                            $(".goodsNameList").append("<p>"+data.goodslist[i].name+"</p>");
                          }
                          $(".goodsNameList").fadeIn();
                          $(".goodsNameList p").click(function(){
                            var goodsInd = $(".goodsNameList p").index(this);
                            $(this).parent().prev().val($(this).text());
                            $("#searchGoodsId").val(data.goodslist[goodsInd].goods_id);
                            $(this).parent().fadeOut();
                            var $searchStyleList = $("#searchStyleList");
                            $("#searchGoodsPrice").val(data.goodslist[goodsInd].shop_price); 
                            if(data.goodslist[goodsInd].style_list){
                              $searchStyleList.html("");
                              for(var key in data.goodslist[goodsInd].style_list){
                                $searchStyleList.append("<option  price='"+data.goodslist[goodsInd].style_list[key].style_price+"'  value='"+data.goodslist[goodsInd].style_list[key].style_id+"'>"+data.goodslist[goodsInd].style_list[key].color+"</option>");
                              }

                            }else{
                              $searchStyleList.html("<option value='0'>无样式</option>");
                            }
                            
                            $("#searchGoodsNumber").val("1");
                          });
                    }else{
                      $(".goodsNameList").html("");
                      $(".goodsNameList").append("<p>没搜到呀，换个关键字再试试~</p>").fadeIn();
                    }
                },
                error:function(error){
                  that.removeClass("searchNameLoad");
                    alert("关键字搜索请求失败!");
                }
            });
        }

         // 订单中增加商品时 搜索商品 样式选择
          $("body").on("change","#searchStyleList",function(){
              var style_price = $(this).find("option:selected").attr("price");
              if(!isNaN(style_price)){
                 $("#searchGoodsPrice").val(style_price); 
              }
          });                  
        
        $('#searchNameBtn').click(function() {
            var that = $('#searchName');
            var data = "keyword=" + $('#searchName').val();
            search_goods(that,data);
        });
        $("#searchName").keyup(function(e){//根据关键字搜索商品id&name&style
            if(e.which == 13){
                  var that = $(this);
                  var data = "keyword=" + $(this).val();
                  search_goods(that,data);
          }
        }).blur(function(){
          $(".goodsNameList").fadeOut();
        });
        
        
        // 搜索套餐
        function search_group_goods(that,data) {
            $.ajax({
                type:'get',
                dataType:'json',
                url:"order.php?act=search_group_goods",
                data:data,
                beforeSend:function(){
                  that.addClass("searchNameLoad");
                },
                success:function(data){
                    that.removeClass("searchNameLoad");
                    var $nameNumber = data.groupGoodsList.length;
                    if($nameNumber > 0){
                          $(".groupGoodsNameList").html("");
                          for(var i=0;i<$nameNumber;i++){
                            $(".groupGoodsNameList").append("<p>"+data.groupGoodsList[i].name+"</p>");
                          }
                          $(".groupGoodsNameList").fadeIn();
                          $(".groupGoodsNameList p").click(function(){
                            var groupGoodsInd = $(".groupGoodsNameList p").index(this);
                            $(this).parent().prev().val($(this).text());
                            $("#searchGroupGoodsId").val(data.groupGoodsList[groupGoodsInd].group_goods_id);
                            $(this).parent().fadeOut();
                          });
                    }else{
                      $(".groupGoodsNameList").html("");
                      $(".groupGoodsNameList").append("<p>没搜到呀，换个关键字再试试~</p>").fadeIn();
                    }
                },
                error:function(error){
                  that.removeClass("searchNameLoad");
                    alert("关键字搜索请求失败!");
                }
            });
        }
        
        $('#searchGroupGoodsNameBtn').click(function() {
            var that = $('#searchGroupGoodsName');
            var data = "keyword=" + $('#searchGroupGoodsName').val();
            search_group_goods(that,data);
        });
        $("#searchGroupGoodsName").keyup(function(e){//根据关键字搜索套餐商品
            if(e.which == 13){
                  var that = $(this);
                  var data = "keyword=" + $(this).val();
                  search_group_goods(that,data);
          }
        }).blur(function(){
          $(".groupGoodsNameList").fadeOut();
        });

        var $submitPlate = $(".submitPlate");
        $("#plateForm .editBtn").click(function(){//点击平台信息下的修改按钮，获取平台信息
                  if($(this).text() == '修改'){
                        var data='act=search_distributor&party_id='+ $party_id;
                        $.ajax({
                            type:'post',
                            dataType:'json',
                            url:'ajax.php',
                            
                            data:data,
                            success:function(data){
                              var disLen = data.length;
                              var $distributor_id = $("#plateForm #distributor_id");
                              $distributor_id.html("");
                              for(var i=0;i<disLen;i++){
                                $distributor_id.append("<option value='"+data[i].distributor_id+"'>"+data[i].name+"</option>");
                              }
                            },
                            error:function(){
                              alert("ajax error");
                            }
                        });
                        $(this).text('取消').addClass('cancelEditBtn');
                  }else{
                    $(this).text('修改').removeClass('cancelEditBtn');
                  }
                  $submitPlate.slideToggle(100);
                  var $inputs = $("#plateForm input[type='text']");
                  var inputLen = $inputs.length;
                  for(var i=0;i<inputLen;i++){
                    if($inputs.eq(i).attr('readonly') == 'readonly'){
                      $inputs.eq(i).removeAttr('readonly');
                    }else{
                      $inputs.eq(i).attr('readonly','readonly');
                    }
                  }
        });

        $submitPlate.click(function(){//提交修改的平台信息
          if(! regNumber.test($("#TAOBAO_POINT_FEE").val())){
            alert("积分请输入数字");
          }
//          else if(! regTid.test($("#taobao_order_sn").val())){
//              alert("订单号以8位数字开始");
//          }
          else{
              var data="OUTER_TYPE="+ $("#OUTER_TYPE").val()+"&taobao_order_sn="+ $("#taobao_order_sn").val()+"&TAOBAO_USER_ID="+ $("#TAOBAO_USER_ID").val()+"&TAOBAO_POINT_FEE="+ $("#TAOBAO_POINT_FEE").val()+"&distributor_id="+ $("#distributor_id").val()+"&content_type=platform_info&action_type=update&order_id="+ $order_id;
              $.ajax({
                type:'get',
                dataType:'json',
                data:data,
                url:'orderV2/sales_order_edit_ajax.php',
                beforeSend:function(){
                  $submitPlate.attr("disabled",true).val("正在提交").addClass("disableBtn");
                },
                success:function(data){
                  if(data.error_info_.err_no == 0){
                        // $submitPlate.attr("disabled",false).val("提交修改").removeClass("disableBtn");
                        $("#taobao_order_sn").val(data.order_sn_);
                        $("#TAOBAO_USER_ID").val(data.user_id_);
                        $("#TAOBAO_POINT_FEE").val(data.point_fee_);
                        $("#distributor_id").html("<option>"+data.distributor_+"</option>");
                        $submitPlate.slideUp(100);
                        $("#plateForm .editBtn").text("修改").removeClass('cancelEditBtn');                       
                       
                        alert("修改成功");    
                        //刷新订单支付信息
                        refresh_pay_info();                    
                        // 刷新操作记录
                        refresh_act_history();
                        
                  }else{
                    alert(data.error_info_.message);
                    console.log('A');
                  }
                  $submitPlate.attr("disabled",false).val("提交修改").removeClass("disableBtn");
                },
                error:function(){
                  $submitPlate.attr("disabled",false).val("提交修改").removeClass("disableBtn");
                  alert("ajax error");
                }
              });
          }
        });

        var $subPaySubmit = $(".subPaySubmit");
        $("#payInfoForm .editBtn").click(function(){//点击付款信息下的修改按钮，获取抵用券
                  if($(this).text() == '修改'){
                      $(this).text('取消').addClass('cancelEditBtn');
                      $("#bonus input").css({"border":"1px solid #A0B2C7"});
                      $("#order_discount input").css({"border":"1px solid #A0B2C7"});
                  }else{
                      $(this).text('修改').removeClass('cancelEditBtn');
                      $("#bonus input").css({"border":"1px solid #fff"});
                      $("#order_discount input").css({"border":"1px solid #fff"});
                  }
                  $subPaySubmit.slideToggle(100);
        });
        $subPaySubmit.click(function(){//提交修改的付款信息   
        	var data= "order_id="+$order_id+"&bonus="+$("#bonus_").val()+"&order_discount="+$("#order_discount_").val()+"&note_content="+$(this).prev().prev().val()+"&content_type=pay_info&action_type=update";
               
               $.ajax({
                 type:'get',
                 dataType:'json',
                 url:'orderV2/sales_order_edit_ajax.php',
                 data:data,
                 beforeSend:function(){
                     $("#disable-mask").show();
                     $subPaySubmit.attr("disabled",true).val("正在提交").addClass("disableBtn");
                 },
                 success:function(data){
                  $("#disable-mask").hide();
                   if(data.error_info_.err_no == 0){
                   // 刷新订单支付信息
                     refresh_pay_info();
                     alert("修改成功！");
                     //刷新订单的状态信息
                     refresh_orderStatus_info();
                     
                     // 刷新操作记录
                     refresh_act_history();
                     
                   }else{
                     alert(data.error_info_.message);
                     console.log('B');
                   }
                   $subPaySubmit.attr("disabled",false).val("提交修改").removeClass("disableBtn").hide();
                 $("#payInfoForm .editBtn").text('修改').removeClass('cancelEditBtn');
                 },
                 error:function(){
                  $("#disable-mask").hide();
                   $subPaySubmit.attr("disabled",false).val("提交修改").removeClass("disableBtn").hide();
                   $("#payInfoForm .editBtn").text('修改').removeClass('cancelEditBtn');
                   alert("AJAX加载失败");
                 }
               });
        });
        
        // 得到地址的下拉框列表
        function get_address_list($type,$parent) {
            var data='act=get_regions&type='+$type+'&parent='+$parent;
            var province_origin = $("#province").val();
            var city_origin = $("#city").val();
            var district_origin = $("#district").val();
            $.ajax({
              async:false,
              type:'post',
              dataType:'json',
              url:'ajax.php',
              data:data,
              success:function(data){
             var address = null;
             if($type == 1) {
                    address = $("#province");
             } else if($type == 2) {
              address = $("#city");
             } else if($type == 3) {
              address = $("#district");
             } else {
              alert('获取地址信息type数据异常');
              return false;
             }
             address.html("");
                for(var key in data.regions){
                    address.append("<option value='"+data.regions[key].region_id+"'>"+data.regions[key].region_name+"</option>");
                }
              },
              error:function(){
                alert("AJAX加载失败");
              }
            });
        }
        $("#custDetail .editBtn").click(function(){//点击订单详细信息中的修改按钮，以获取相应格子的信息进行修改
            var $inputs = $(this).parent().find("input[type='text']");
            var inputLen = $inputs.length;
            var editType = $(this).parent().attr("id");
            if($(this).text() == '修改'){
              for(var i=0;i<inputLen;i++){
                $inputs.eq(i).removeAttr('readonly').addClass('editableInput');
              }
              $("#province").attr("disabled",false);
              $("#city").attr("disabled",false);
              $("#district").attr("disabled",false);
              $(this).text('取消').addClass('cancelEditBtn');
              if(editType == "addressForm"){

                    $("#addressInfoBox select").change(function(){
                      if($(this).attr("id") == 'province'){
                        get_address_list(2,$("#province").val());
                        get_address_list(3,$("#city").val());
                      }else if($(this).attr("id") == 'city'){
                       
                        get_address_list(3,$("#city").val());
                      }  
                    });
              }else if(editType == "facilityForm"){
                $original_facility_id = $("#facility_id").val();
                //alert("original_facility_id :"+$original_facility_id);
                var data = "order_id="+$order_id+"&content_type=facility";
                $.ajax({
                  type:'get',
                  dataType:'json',
                  url:'orderV2/sales_order_edit_ajax.php',
                  data:data,
                  success:function(data){
                    if(data.error_info_.err_no == 0){
                      $facility_id = $("#facility_id");
                      $facility_id.html("");
                      for(var key in data.available_facility_list_){
                        $facility_id.append("<option value='"+key+"'>"+data.available_facility_list_[key]+"</option>");
                      }
                    }else{
                      alert(data.error_info_.message);
                      console.log('c');
                    }
                  },
                  error:function(){
                    alert("AJAX加载失败");
                  }
                });

              }
            }else{
              for(var i=0;i<inputLen;i++){
                $inputs.eq(i).attr('readonly','readonly').removeClass('editableInput');
              }
              $(this).text('修改').removeClass('cancelEditBtn');
              var $province = $("#province optgroup");
              var $city = $("#city optgroup");
              var $district = $("#district optgroup");
              $("#province").attr("disabled","disabled");
              $("#city").attr("disabled","disabled");
              $("#district").attr("disabled","disabled");
            }
            $(this).parent().find("input[type='button']").slideToggle(100);
          
        });

        var $subCustgridBtn = $(".custgrid input[type='button']");
        $subCustgridBtn.click(function(){//提交订单详细信息的修改信息
          var mainClass= $(this).attr('class').split(" ")[0];
          //alert(mainClass);         
          var formType = $(this).parent().attr("id");
          if(formType == "plateForm"){
        	  if(!regTid.test($('#taobao_order_sn').val())){
        		  alert("订单号以14位数字开始");
        		  return false;
        	  }
          }
          if(formType == "contactForm"){
            if($("#mobile").val() && !regMobile.test($("#mobile").val())){
              alert("请输入正确格式的手机号");
              return false;
            }
          }
          if(formType == "custInfoForm"){
              if(! regDate.test($("#birthday").val())){
                alert("请输入正确格式的出生日期");
                return false;
              }
          }
          
          if(formType == "addressForm"){
            if(! regZipcode.test($("#zipcode").val())){
              alert("请输入正确格式的邮政编码");
              return false;
            }
            if($("#province").val() == 0){
              alert("请输入省份信息");
              return false;
            }
            if($("#city").val() == 0){
              alert("请输入城市信息");
              return false;
            }
          }
          var data = $(this).parent().serialize();
          var index = $subCustgridBtn.index(this);
          data = decodeURIComponent(data,true);
          data = "content_type="+mainClass+"&action_type=update&order_id="+$order_id + "&"+ data;
          // alert("该弹出框用于开发人员进行调试，观测传递过去的数据啥的："+data);
          $.ajax({
            type:'get',
            dataType:'json',
            url:'orderV2/sales_order_edit_ajax.php',
            data:data,
            beforeSend:function(){
              $subCustgridBtn.eq(index).attr("disabled",true).val("正在提交").addClass("disableBtn");
            },
            success:function(data){
              
              if(data.error_info_.err_no == 0){
                $subCustgridBtn.eq(index).attr("disabled",false).val("提交修改").removeClass("disableBtn").hide();
                $subCustgridBtn.eq(index).parent().find(".editBtn").text("修改").removeClass('cancelEditBtn');
                $subCustgridBtn.eq(index).parent().find("input[type='text']").removeClass('editableInput');
                if(index == 0){
                  $("#consignee").val(data.consignee_);
                  $("#sex").val(data.sex_);
                  if(data.sex_ == 'unknown'){
                      $("#sex option").eq(0).attr("select","selected");  
                  }else if(data.sex_ == 'male'){
                      $("#sex option").eq(1).attr("select","selected");  
                  }else if(data.sex_ == 'female'){
                      $("#sex option").eq(2).attr("select","selected");  
                  }
                  if(data.is_maintain_birthday_) {
                      $("#birthday").val(data.birthday_);
                  }
                  $("#buyer_detail_info").html(data.buyer_detail_info_);

                }else if(index == 1){
                  $("#zipcode").val(data.zipcode_);
                  $("#address").val(data.address_);
                  $("#province").html("<option value='"+data.province_id_+"'>"+data.province_+"</option><optgroup></optgroup>"); 
                  for(var i=0;i<data.province_list_.length;i++) {
                   $("#province").append("<option value='"+data.province_list_[i].region_id+"'>"+data.province_list_[i].region_name+"</option><optgroup></optgroup>");
                  }
                  $("#city").html("<option value='"+data.city_id_+"'>"+data.city_+"</option><optgroup></optgroup>");
                  for(var i=0;i<data.city_list_.length;i++) {
                   $("#city").append("<option value='"+data.city_list_[i].region_id+"'>"+data.city_list_[i].region_name+"</option><optgroup></optgroup>");
                  }
                  $("#district").html("<option value='"+data.district_id_+"'>"+data.district_+"<option><optgroup></optgroup>");
                  for(var i=0;i<data.district_list_.length;i++) {
                   $("#district").append("<option value='"+data.district_list_[i].region_id+"'>"+data.district_list_[i].region_name+"</option><optgroup></optgroup>");
                  }
                  $("#buyer_detail_info").html(data.buyer_detail_info_);

                }else if(index == 2){
                  $("#tel").val(data.tel_);
                  $("#mobile").val(data.mobile_);
                }else if(index == 3){
                  $("#pay_id").find("option[value='"+data.pay_id_+"']").attr("select","selected");
                }else if(index == 4){
                  $("#shipping_id").find("option[value='"+data.shipping_id_+"']").attr("select","selected");
                  $("#facility_id").html("<option value='"+data.facility_id_+"'>"+data.facility_name_+"</option><optgroup></optgroup>");
                }
                alert("修改成功");
              }else{
                if (data.error_info_.message=="已推送给菜鸟的订单不能转仓") {
                   // 此處添加AJAX
                   $('#pop').show();
                   var facility_id = $("#facility_id option:selected").val();
                   console.log(facility_id+'facility_id');
                   $('#get_facility_id').val(facility_id);
                   //console.log(888);
                   $subCustgridBtn.eq(index).attr("disabled",false).val("提交修改").removeClass("disableBtn");
                }else{
                  alert(data.error_info_.message); 
                  $subCustgridBtn.eq(index).attr("disabled",false).val("提交修改").removeClass("disableBtn");
                }
              }

              
              // 刷新订单支付信息
              refresh_pay_info();
              //自动刷新订单状态信息
              refresh_orderStatus_info();
              // 刷新操作记录
              refresh_act_history();
              
            },
            error:function(){
              $subCustgridBtn.eq(index).attr("disabled",false).val("提交修改").removeClass("disableBtn");
              alert('AJAX加载失败');
            }
          });
        });

        $("#customRecord").delegate(".openBtn","click",function(){//售后记录界面，打开售后详细信息按钮
                    var thisServiceId = $(this).parent().parent().find(".serviceId").text();
                    var odt = $(this).attr("id");
                    var data = "content_type="+odt+"&service_id="+thisServiceId+"&action_type=query&order_id="+$order_id;
                    var top = $(this).position().top +30;
                    var left = $(this).position().left -687;
                    // $(".recordDetailBox").animate({"top":top,"left":left},"fast");
                    $.ajax({
                      type:'get',
                      dataType:'json',
                      data:data,
                      url:'orderV2/sales_order_edit_ajax.php',
                      beforeSend:function(){
                        
                        $(".recordDetailBox").html("<img src='images/close.png' id='close'><img src='images/topArrow.png' id='topArrow'><p>Loading ...</p>").fadeIn(200);
                      },
                      success:function(data){
                        if(data.error_info_.err_no == 0){
                          $(".recordDetailBox").animate({"top":top,"left":left},"fast");
                          $(".recordDetailBox p").remove();
                          if(odt == 'service_record_logs'){
                              $(".recordDetailBox").append("<table><th>记录类型</th><th>订单状态</th><th>记录人</th><th style='width:80px'>记录时间</th><th>备注</th></table>");
                              for(var key in data.service_logs_){
                                $(".recordDetailBox table").append("<tr><td>"+data.service_logs_[key].logger_type_+"</td><td>"+data.service_logs_[key].status_name_+"</td><td>"+data.service_logs_[key].logger_+"</td><td>"+data.service_logs_[key].datetime_+"</td><td>"+data.service_logs_[key].note_+"</td></tr>");
                              }
                          }
                          if(odt == 'service_record_comments'){
                            $(".recordDetailBox").append("<table><th>评论人</th><th>评论内容</th><th style='width:80px'>评论时间</th><th>回应人</th><th>回应内容</th><th style='width:80px'>回应时间</th></table>");
                            for(var key in data.service_comments_){
                              $(".recordDetailBox table").append("<tr><td>"+data.service_comments_[key].post_username_+"</td><td>"+data.service_comments_[key].post_comment_+"</td><td>"+data.service_comments_[key].post_datetime_+"</td><td>"+data.service_comments_[key].replied_username_+"</td><td>"+data.service_comments_[key].reply_+"</td><td>"+data.service_comments_[key].replied_datetime_+"</td></tr>");
                            }

                          }

                        }else{
                          alert(data.error_info_.message);
                        } 
                      },
                      error:function(){
                        $(".recordDetailBox").html("<img src='images/close.png' id='close'><img src='images/topArrow.png' id='topArrow'><p>ajax error...</p>");
                      }
                    });
                    $(".recordDetailBox #close").click(function(){
                        $(this).parent().fadeOut(100);
                    });
        });
        $("#customRecord").delegate("#service_record_messages","click",function(){//售后记录界面，售后沟通记录详情
                    var data = "content_type="+$(this).attr("id")+"&order_id="+$order_id+"&action_type=query";
                    var top = $(this).position().top +30;
                    var left = $(this).position().left;
                    $(".recordDetailBox").animate({"top":top,"left":left},"fast");
                    $.ajax({
                      type:'get',
                      dataType:'json',
                      data:data,
                      url:'orderV2/sales_order_edit_ajax.php',
                      beforeSend:function(){
                        $(".recordDetailBox").html("<img src='images/close.png' id='close'><img src='images/topArrow.png' id='topArrow'><p>Loading ...</p>").fadeIn(200);
                        left = left+140;
                        $("#topArrow").animate({"left":left},"fast");

                      },
                      success:function(data){
                          if(data.error_info_.err_no == 0){
                            $(".recordDetailBox p").remove();                  
                            $(".recordDetailBox").append("<table><th>发送时间</th><th>咨询类型</th><th>咨询详情</th><th>发送人</th><th>操作状态</th></table>");
                              for(var key in data.service_message_list_){
                                $(".recordDetailBox table").append("<tr><td>"+data.service_message_list_[key].created_stamp_+"</td><td>"+data.service_message_list_[key].support_type_+"</td><td>"+data.service_message_list_[key].message_+"</td><td>"+data.service_message_list_[key].send_by_+"</td><td>"+data.service_message_list_[key].status_+"</td></tr>");
                              }
                          }
                          
                      },
                      error:function(){
                        $(".recordDetailBox").html("<img src='images/close.png' id='close'><img src='images/topArrow.png' id='topArrow'><p>ajax error...</p>");
                      }

                    });
                    $(".recordDetailBox #close").click(function(){
                        $(this).parent().fadeOut(100);
                    });
        });
        //li操作
         var $detailLi = $("#detailWrap ul li");
       
        $detailLi.click(function(){//标签页ajax控制代码，用于全站式ajax
          var detailLiInd = $("#detailWrap ul li").index(this);
          var nav_action_id = $(this).attr('id');
            if(detailLiInd == 4){
              nav_action_id = $(this).attr('class');
              nav_action_id = 'status_history_records';
            }
          if( ! $(this).hasClass("detailShown")){
            $(this).addClass("detailShown").siblings().removeClass("detailShown");
          }
      //ajax
      navList_action_ajax(detailLiInd,nav_action_id);
     
      
      });
      

       

       // 显示 组织 店铺 默认的 最优仓库 最优快递  
      function showBestFacilityShippingInfo(best){
        var facility_name = ""; 
        var shipping_name = ""; 
        console.log(best); 
        if(facility_name =="" || shipping_name ==""){
          if(best.drf_facility_name != null && best.drs_shipping_name != null) {
            facility_name = best.drf_facility_name; 
            shipping_name = best.drs_shipping_name; 
          }
        }
        
        if(facility_name =="" || shipping_name ==""){
          if(best.df_facility_name != null && best.ds_shipping_name != null){ 
            facility_name = best.df_facility_name;
            shipping_name = best.ds_shipping_name; 
          }  
        }

        if(facility_name =="" || shipping_name ==""){
          if(best.rf_facility_name != null && best.rs_shipping_name != null){ 
            facility_name = best.rf_facility_name; 
            shipping_name = best.rs_shipping_name; 
          }
        }
        if(facility_name =="" || shipping_name ==""){
          if(best.pf_facility_name != null && best.ps_shipping_name != null){ 
            facility_name = best.pf_facility_name; 
            shipping_name = best.ps_shipping_name;  
          } 
        }
        if(facility_name =="" || shipping_name ==""){
          if(best.distributor_facility_name != null && best.distributor_shipping_name != null){ 
            facility_name = best.distributor_facility_name; 
            shipping_name = best.distributor_shipping_name;  
          } 
        }
        if(facility_name =="" || shipping_name ==""){
          if(best.party_facility_name != null && best.party_shipping_name != null){ 
            facility_name = best.party_facility_name; 
            shipping_name = best.party_shipping_name; 
          } 
        }
      
        var html =""; 
        if(facility_name !="" && shipping_name !=""){
           html += "推荐:"+facility_name+"&nbsp;"+shipping_name; 
        }else{
          if(facility_name !=""){
            html += " 推荐仓库:"+facility_name+" "; 
          }
          if(shipping_name !=""){
            html += " 推荐快递:"+shipping_name+" "; 
          }
        }
        $("#facility_best_info").html(html);
      }
        

        //ajax加载函数
      function navList_action_ajax(detailLiInd,nav_action_id) {
         var $info = $(".info");
         var data ='order_id=' + $order_id + '&content_type=' + nav_action_id +"&action_type=query";
       $.ajax({
      //async:false,
            type:'get',
            dataType:'json',
            url:"orderV2/sales_order_edit_ajax.php",
            data:data,
            beforeSend:function(){
              $("#loadimg").fadeIn(200);
              $info.eq(detailLiInd).siblings().hide();
              
            },
            success:function(data){
              if(data.error_info_.err_no == 0){
                $info.eq(detailLiInd).fadeIn(260);
                $info.eq(detailLiInd).siblings().hide(); 
                $("#loadimg").fadeOut(100);
                if(detailLiInd ==0){ 
                          $(".custReq").html("").append(data.consigne_info_.remark_list_['48小时无货']);
                          $(".custPost").html("").append(data.consigne_info_.remark_list_['客户留言']);
                          $(".custSellerPost").html("").append(data.consigne_info_.remark_list_['小二留言']);
                          $("#user_name").val(data.consigne_info_.user_name_);
                          $("#consignee").val(data.consigne_info_.consignee_);
                          $("#sex").val(data.consigne_info_.sex_);
                          if(data.consigne_info_.sex_ == 'unknown'){
                              $("#sex option").eq(0).attr("select","selected");
                          }else if(data.consigne_info_.sex_ == 'male'){
                              $("#sex option").eq(1).attr("select","selected");
                          }else if(data.consigne_info_.sex_ == 'female'){
                              $("#sex option").eq(2).attr("select","selected");
                          }
                          if(data.consigne_info_.is_maintain_birthday_) {
                              $("#birthday").val(data.consigne_info_.birthday_);
                          }
                          $("#province").html("<option value='"+data.consigne_info_.province_id_+"'>"+data.consigne_info_.province_+"</option><optgroup></optgroup>");
                          for(var i=0;i<data.consigne_info_.province_list_.length;i++) {
                              $("#province").append("<option value='"+data.consigne_info_.province_list_[i].region_id+"'>"+data.consigne_info_.province_list_[i].region_name+"</option><optgroup></optgroup>");
                          }
                          $("#city").html("<option value='"+data.consigne_info_.city_id_+"'>"+data.consigne_info_.city_+"</option><optgroup></optgroup>");
                          for(var i=0;i<data.consigne_info_.city_list_.length;i++) {
                              $("#city").append("<option value='"+data.consigne_info_.city_list_[i].region_id+"'>"+data.consigne_info_.city_list_[i].region_name+"</option><optgroup></optgroup>");
                          }
                          $("#district").html("<option value='"+data.consigne_info_.district_id_+"'>"+data.consigne_info_.district_+"<option><optgroup></optgroup>");
                          for(var i=0;i<data.consigne_info_.district_list_.length;i++) {
                              $("#district").append("<option value='"+data.consigne_info_.district_list_[i].region_id+"'>"+data.consigne_info_.district_list_[i].region_name+"</option><optgroup></optgroup>");
                          }
                          $("#address").val(data.consigne_info_.address_);
                          $("#buyer_detail_info").html(data.consigne_info_.buyer_detail_info_);
                          $("#zipcode").val(data.consigne_info_.zipcode_);
                          $("#tel").val(data.consigne_info_.tel_);
                          $("#mobile").val(data.consigne_info_.mobile_);
                          $("#pay_id").html("<option value='"+data.pay_info_.pay_id_+"'>"+data.pay_info_.pay_name_+"</option>");
                          $("#order_pay_id_select").val(data.pay_info_.pay_name_);
                          $("#shipping_id").html("<option value='"+data.shipping_info_.shipping_id_+"'>"+data.shipping_info_.shipping_name_+"</option>");
                          $("#order_shipping_id_select").val(data.shipping_info_.shipping_name_); 
                          $("#kedaxing").html(data.arrived_type_);
                          $("#shipping_basic_fee").val(data.shipping_info_.shipping_basic_fee_);
                          $("#shipping_proxy_fee").val(data.shipping_info_.shipping_proxy_fee_);
                          
                          //配送仓
                          $("#facility_id").html("<option value='"+data.facility_info_.facility_id_+"'>"+data.facility_info_.facility_name_+"</option>");
                          //判断配送仓是否可编辑
                          if(!data.facility_info_.can_edit_){
                            $("#facilityForm .editBtn").hide();
                          }else{
                            $("#facilityForm .editBtn").show();
                          }
                          showBestFacilityShippingInfo(data.best_facility_shipping_info_); 
                          //判断收货人信息，地址信息，联系信息是否可以编辑
                          if(!data.consigne_info_.can_edit_){
                            $("#custInfoForm .editBtn").hide();
                            $("#addressForm .editBtn").hide();
                            $("#contactForm .editBtn").hide();
                          }else{
                          
                            $("#custInfoForm .editBtn").show();
                            $("#addressForm .editBtn").show();
                            $("#contactForm .editBtn").show();
                          }
                          //支付方式加载
                          for(var pkey in data.payment_group_data_){
                              $("#pay_id").append("<optgroup label='"+pkey+"'>");
                              for(var pkey1 in data.payment_group_data_[pkey]){
                                $("#pay_id").append("<option value='"+data.payment_group_data_[pkey][pkey1].pay_id+"'>"+data.payment_group_data_[pkey][pkey1].pay_name+"</option>");
                              }
                              $("#pay_id").append("</optgroup>");
                          }
                         //发票类型
                          var invoiceInfo = $("#invoiceInfo");
                          invoiceInfo.html("").append("<span>"+data.inv_info_.invoice_type_+"</span>");
                          for(var key in data.inv_info_.invoice_attr_list_){
                           if(key =='发票抬头') {
                                 invoiceInfo.append("<p style='display:inline-block;padding-left:20px;'>"+key+"："+" <input type='text' style='width:250px;' id='inv_payee_' name='inv_payee_' class='value_' value='"+ data.inv_info_.invoice_attr_list_[key] +"'></input></p>");
                           } else {
                                 invoiceInfo.append("<p>"+key+"："+data.inv_info_.invoice_attr_list_[key]+"</p>");
                           }
                          }
                          //支付单号
                          $("#pay_number").val(data.pay_info_.pay_number_);
                          
                          //申报系统支付方式加载
                          $("#kjg_pay_id").append("<option value='-1' selected></option>");
                          for(var pkey in data.kjg_payment_){	 
                        	  if(data.pay_info_.kjg_pay_id_==pkey) {
                        		  $("#kjg_pay_id").append("<option value='"+pkey+"' selected>"+data.kjg_payment_[pkey]+"</option>");
                        	  }else{
                        		  $("#kjg_pay_id").append("<option value='"+pkey+"' >"+data.kjg_payment_[pkey]+"</option>");
                        	  }

                              

                          }                          

                          //判断支付方式是否可编辑
                          if(!data.pay_info_.can_edit_){
                            $("#payForm .editBtn").hide();
                          }else{
                            $("#payForm .editBtn").show();
                          }
                          // TODO：发票权限特殊处理：已付款都可以修改
                          if(data.pay_info_.pay_status_ == 2 || data.pay_info_.is_cod_ == 1){
                              $("#payForm .editBtn").show();
                              $('#force_update').val(1);
                              // 支付方式不能改的
                              if(!data.pay_info_.can_edit_) {
                                  $("#pay_id").html("<option value='"+data.pay_info_.pay_id_+"'>"+data.pay_info_.pay_name_+"</option>");
                              }
                          }

                            //快递方式
                          for(var skey in data.shipping_group_data_){
                            $("#shipping_id").append("<optgroup label='"+skey+"'>");
                            for(var skey1 in data.shipping_group_data_[skey]){
                              $("#shipping_id").append("<option value='"+data.shipping_group_data_[skey][skey1].shipping_id+"'>"+data.shipping_group_data_[skey][skey1].shipping_name+"</option>");
                            }
                            $("#shipping_id").append("</optgroup>");
                          }
                          
                          $("#orderDetail input[type='text']").attr("readonly",true);
                          //判断快递方式是否可以编辑
                          if(!data.shipping_info_.can_edit_){
                              $("#shipForm .editBtn").hide();
                            }else{
                              $("#shipForm .editBtn").show();
                            }

                          //商品列表加载
                          // 赠品活动提醒
                          if(data.goods_list_.gift_reminds_) {
                            $("#giftReminds p").html('赠品活动提醒：'+data.goods_list_.gift_reminds_);
                          } else {
                            $("#giftReminds p").html('无赠品活动提醒');
                          }
                          //显示商品列表
                          showGoodsList(data);
                         //判断商品是否可编辑
                          if(!data.goods_list_.can_edit_){
                            $(".goodsEditBtn").hide();
                            $(".goodsDeleteBtn").hide();
                            $(".addGoodsBtn").hide();
                            $(".addGroupGoodsBtn").hide();
                          }else{
                            $(".goodsEditBtn").show();
                            $(".goodsDeleteBtn").show();
                            $(".addGoodsBtn").show();
                            $(".addGroupGoodsBtn").show();
                          }
                          $(".reservedTime span").text(data.reserved_time_);
                          $("#goods").append("<div class='clear'></div>");

                          var $styleList = $(".styleList");
                          var $styleListOptgroup = $(".styleList optgroup");
                     var $goodsEditBtn = $(".goodsEditBtn"); 
                     $("#goods").delegate(".goodsEditBtn","click",function(){
                                var $oldGoodsNumber = $(this).parent().parent().find(".goods_number").val();
                                var $oldGoodsPrice = $(this).parent().parent().find(".goods_price").val();
                                var $oldGoodsDiscount = $(this).parent().parent().find(".goods_discount").val()
                                //alert("原始数量为："+$oldGoodsNumber+"，原始单价为："+$oldGoodsPrice);
                                var $inputs = $(this).parent().parent().find("input[type='text']");
                                var inputLen = $inputs.length;
                                var goodsEditBtnIndex = $goodsEditBtn.index(this);
                                if($(this).text() == '修改'){
                                  $styleList.eq(goodsEditBtnIndex).attr("disabled",false);
                                  $(this).next().after("<p class='goodsSubBtn'>提交</p>");
                                  var $goodsSubBtn = $(".goodsSubBtn");
                                  $goodsSubBtn.click(function(){
                                    //alert("原始数量为："+$oldGoodsNumber+"，原始单价为："+$oldGoodsPrice);
                                    var that = $(this);
                                    var parentNode = that.parent().parent();
                                   // alert("当前数量为："+$nowGoodsNumber + ",当前单价为："+$nowGoodsPrice);
                                   
                                    var good_price = parentNode.find(".goods_price").val();//这里限制输入的数据不能超过6位
                                    var price_strs = good_price.split('.');
                                      if(price_strs.length == 2 && price_strs[1].length > 6){
                                        alert('商品单价只能保存六位小数');
                                        return;
                                      }
                                    if(parentNode.find(".goods_price").val() >= 0 && parentNode.find(".goods_number").val()>0 && parentNode.find(".goods_discount").val()>=0 ){
                                      that.text("正在提交");
                                        var data="party_id="+$party_id+"&order_goods_id="+parentNode.find(".order_goods_id").text()+"&order_id="+$order_id+"&style_id="+parentNode.find(".styleList").val()+"&goods_price="+parentNode.find(".goods_price").val()+"&goods_number="+parentNode.find(".goods_number").val()+"&goods_discount="+parentNode.find(".goods_discount").val()+"&content_type=goods_list&action_type=update";
                                        var $inputs = that.parent().parent().find("input[type='text']");
                                        var inputLen = $inputs.length;
                                        
                                        $.ajax({
                                          type:'get',
                                          dataType:'json',
                                          url:'orderV2/sales_order_edit_ajax.php',
                                          data:data,
                                          beforeSend:function(){
                                            $("#disable-mask").show();
                                          },
                                          success:function(data){
                                            $("#disable-mask").hide();
                                            //alert(data.error_info_.err_no);
                                            if(data.error_info_.err_no == 0){
                                              that.parent().parent().find(".goods_price").val(data.p_goods_price_);
                                              that.parent().parent().find(".goods_number").val(data.p_goods_number_);
                                              that.parent().parent().find(".styleList").html("<option value='"+data.style_id_+"'>"+data.style_name_+"</option>");
                                              that.parent().prev().text(data.total_price_);
                                              that.text("提交").hide();
                                              
                                              for(var i=0;i<inputLen;i++){
                                                $inputs.eq(i).attr('readonly','readonly').removeClass('editableInput');
                                              }
                                              that.prev().prev().text('修改').removeClass('cancelEditBtn');
                                              $styleList.eq(goodsEditBtnIndex).attr("disabled","disabled");
                                              
                                              // 刷新订单支付信息
                                              refresh_pay_info();
                                              alert("修改成功");
                                              //刷新订单的状态信息
                                              refresh_orderStatus_info();

                                              // 刷新操作记录
                                              refresh_act_history();

                                            }else{
                                              that.parent().parent().find(".goods_number").val($oldGoodsNumber);
                                              that.parent().parent().find(".goods_price").val($oldGoodsPrice);
                                              that.parent().parent().find(".goods_discount").val($oldGoodsDiscount);
                                              that.text("提交").hide();
                                              for(var i=0;i<inputLen;i++){
                                                $inputs.eq(i).attr('readonly','readonly').removeClass('editableInput');
                                              }
                                              that.prev().prev().text('修改').removeClass('cancelEditBtn');
                                              $styleList.eq(goodsEditBtnIndex).attr("disabled","disabled");
                                              alert(data.error_info_.message);
                                              
                                            }
                                            
                                            

                                          },
                                          error:function(){
                                            $("#disable-mask").hide();
                                            that.parent().parent().find(".goods_number").val($oldGoodsNumber);
                                            that.parent().parent().find(".goods_price").val($oldGoodsPrice);
                                            that.parent().parent().find(".goods_discount").val($oldGoodsDiscount);
                                            that.text("提交").hide();
                                            for(var i=0;i<inputLen;i++){
                                              $inputs.eq(i).attr('readonly','readonly').removeClass('editableInput');
                                            }
                                            that.prev().prev().text('修改').removeClass('cancelEditBtn');
                                            $styleList.eq(goodsEditBtnIndex).attr("disabled","disabled");
                                            alert("AJAX加载失败");
                                            
                                          }

                                        });
                                    }else{
                                      alert("商品数量以及商品单价必须大于零");
                                      that.parent().parent().find(".goods_number").val($oldGoodsNumber);
                                      that.parent().parent().find(".goods_price").val($oldGoodsPrice);
                                      that.parent().parent().find(".goods_discount").val($oldGoodsDiscount);
                                    }

                                  });
                                
                                  for(var i=0;i<inputLen;i++){
                                    $inputs.eq(i).removeAttr('readonly').addClass('editableInput');
                                  }
                                  $(this).text('取消').addClass('cancelEditBtn');
                                  var data='goods_id='+$(this).parent().parent().attr("id")+"&order_id="+$order_id;
                                  $.ajax({
                                    type:'get',
                                    dataType:'json',
                                    url:'order.php?act=search_goods_style&no_storage_info=1',
                                    data:data,
                                    success:function(data){
                                      $styleListOptgroup.eq(goodsEditBtnIndex).html("<option value='0'>请选择</option>");
                                      for(var key in data.goods_style_list){
                                        $styleListOptgroup.eq(goodsEditBtnIndex).append("<option value='"+data.goods_style_list[key].style_id+"'>"+data.goods_style_list[key].color+"</option>");
                                      }
                                    },
                                    error:function(){
                                      alert("搜索商品样式失败");
                                    }
                                  });
                            }else{
                              $(this).text("提交").next().next().hide();
                              for(var i=0;i<inputLen;i++){
                                $inputs.eq(i).attr('readonly','readonly').removeClass('editableInput');
                              }
                              $styleList.eq(goodsEditBtnIndex).attr("disabled","disabled");
                              $(this).text('修改').removeClass('cancelEditBtn');
                            }
                          });
                  
                     // alert(data.can_edit_);
                  
                    //最优快递
                    var bestExpNum = data.best_express_list_.best_express_list_.length;
                    //$("#bestExp").html("<table><th>配送仓</th><th>快递方式</th><th>预计费用</th><th>可达情况</th><th>操作</th></table>");
                    $("#bestExp").html("<table><th>配送仓</th><th>快递方式</th><th>可达情况</th><th>操作</th></table>");
                    if(bestExpNum!=0){
                      for(var i=0;i<bestExpNum;i++){
                        //$("#bestExp table").append("<tr><td>"+data.best_express_list_.best_express_list_[i].facility_name_+"</td><td>"+data.best_express_list_.best_express_list_[i].shipping_name_+"</td><td>"+data.best_express_list_.best_express_list_[i].shipping_fee_+"元</td><td>"+data.best_express_list_.best_express_list_[i].arrive_type_+"</td><td><input style='width:120px' type='button' class='changeTrackBtn' value='修改快递和仓库'><p style='display:none'>"+data.best_express_list_.best_express_list_[i].shipping_id_+"</p><p style='display:none'>"+data.best_express_list_.best_express_list_[i].facility_id_+"</p></td></tr>");
                        $("#bestExp table").append("<tr><td>"+data.best_express_list_.best_express_list_[i].facility_name_+"</td><td>"+data.best_express_list_.best_express_list_[i].shipping_name_+"</td><td>"+data.best_express_list_.best_express_list_[i].arrive_type_+"</td><td><input style='width:120px' type='button' class='changeTrackBtn' value='修改快递和仓库'><p style='display:none'>"+data.best_express_list_.best_express_list_[i].shipping_id_+"</p><p style='display:none'>"+data.best_express_list_.best_express_list_[i].facility_id_+"</p></td></tr>");
                      }
                    }else{
                      $("#bestExp").html("<h2>暂时没有最优快递呀~</h2>");
                    }
                  
                   
                    if(!data.best_express_list_.can_edit_){
                        $(".changeTrackBtn").hide();
                      }else{
                        $(".changeTrackBtn").show();
                      }
                    //
                    //付款信息加载
                   // $("#loadimg").fadeOut(100);
                     show_order_pay(data.fee_list_);

                        $("#useBonusBtn").show();
                       if(!data.fee_list_.can_edit_){
                       
                           $("#payInfoForm .editBtn").hide();
                         }else{
                           $("#payInfoForm .editBtn").show();
                         }
                      
              //
              
            //操作信息记录
              $("#actHistoryBox table").html("<tr><th>订单状态</th><th>操作人</th><th>操作时间</th><th>操作备注</th></tr>");
                        var ahTable = "";
                        for(var key in data.action_list_){
                          ahTable = ahTable + "<tr><td rowspan='"+data.action_list_[key].action_count+"'>"+data.action_list_[key].order_status+"</td>";
                          for(var key1 in data.action_list_[key].action_list){
                            if(data.action_list_[key].action_list[key1].note_type_ == 'SHIPPING') {
                                  ahTable = ahTable + "<td>"+data.action_list_[key].action_list[key1].action_user_+"</td><td>"+data.action_list_[key].action_list[key1].action_time_+"</td><td style='background:yellow'>"+data.action_list_[key].action_list[key1].note_+"</td></tr>";
                            } else {
                                  ahTable = ahTable + "<td>"+data.action_list_[key].action_list[key1].action_user_+"</td><td>"+data.action_list_[key].action_list[key1].action_time_+"</td><td>"+data.action_list_[key].action_list[key1].note_+"</td></tr>";
                            }
                          }
                        }
                        $("#actHistoryBox table").append(ahTable);

            //
              

                }else if(detailLiInd ==1){
                  $(".egw").html(data.estimate_goods_weight_);
                  $(".pew").html(data.package_estimate_weight_);
                  $(".tew").html(data.total_estimate_weight_);
                  $(".midAddr").html(data.midway_address_);
                  $(".facility").html(data.facility_name_);
                  var shipNumber = data.shipping_list_.length;
                  if(shipNumber!=0){
                    $("#shipList").html("<h2>发货信息</h2>");
                    for(var i=0;i<shipNumber;i++){
                      $("#shipList").append("<p><span class='shipmentId'>发货单号："+data.shipping_list_[i].shipment_id_+"</span><span class='trackId'>快递单号："+data.shipping_list_[i].tracking_number_+"</span><span class='shippingName'>快递公司："+data.shipping_list_[i].shipping_name_+"</span><span class='shippingLeqeeWeight'>称重："+data.shipping_list_[i].shipping_leqee_weight_+"kg</span><span class='shipStatus'>包裹状态："+data.shipping_list_[i].shipping_status_+"</span><span class='shipCost'>快递实际费用："+data.shipping_list_[i].shipping_cost_+"</span></p>");
                    }
                  }else{
                    $("#shipList").html("<h2>该订单还没有发货单呀~</h2>");
                  }
                
                 // var transitStepNum = data.transit_step_.length;
                 // $("#transitList").html("<h2>快递走件</h2>");
                 // if(transitStepNum!=0){
                //    for(var i=0;i<transitStepNum-1;i++){
                //      $("#transitList").append("<p><span>"+data.transit_step_[i].status_time_+"</span><span>"+data.transit_step_[i].status_desc_+"</span></p>");
                //      }
                //    $("#transitList").append("<p><span style='color:green;'>"+data.transit_step_[i].status_time_+"</span><span style='color:green;'>"+data.transit_step_[i].status_desc_+"</span></p>");
                //  }else{
                //    $("#transitList").html("<h2>该订单暂无走件信息~ </h2>");
                //  }
                
                  var otherExpNum = data.other_express_list_.length;
                  $("#otherExp").html("<h2>其他快递</h2><table><th>配送仓</th><th>快递方式</th><th>预计费用</th><th>可达情况</th></table>");
                  if(otherExpNum!=0){
                    for(var i=0;i<otherExpNum;i++){
                      $("#otherExp table").append("<tr><td>"+data.other_express_list_[i].facility_name_+"</td><td>"+data.other_express_list_[i].shipping_name_+"</td><td>"+data.other_express_list_[i].shipping_fee_+"元</td><td>"+data.other_express_list_[i].arrive_type_+"</td></tr>");
                    }
                  }else{
                    $("#otherExp").html("<h2>暂时没有其他快递呀~</h2>");
                  }
                 
                  // alert(data.can_edit_);
                  if(!data.can_edit_){
                    $(".changeTrackBtn").hide();
                  }else{
                    $(".changeTrackBtn").show();
                  }

                }else if(detailLiInd == 2){
                  $("#customRecord table").html("<th>售后类型</th><th>售后状态</th><th>申请信息</th><th>审核信息</th><th style='width:80px'>售后评论</th><th style='width:80px'>售后记录</th>");
                  var recordNum = data.record_list_.length;
                  for(var i=0;i<recordNum;i++){
                    var oneRecord = "<tr><td class='serviceId'>"+data.record_list_[i].service_id_+"<td>"+data.record_list_[i].type_name_+"</td><td>"+data.record_list_[i].status_name_+"</td><td>"+data.record_list_[i].apply_username_+"</br>"+data.record_list_[i].apply_reason_+"</br>"+data.record_list_[i].apply_datetime_+"</td><td>"+data.record_list_[i].review_username_+"</br>"+data.record_list_[i].review_remark_+"</br>"+data.record_list_[i].review_datetime_+"</td><td><p class='commentsBtn openBtn' id='service_record_comments'>"+data.record_list_[i].service_comment_count_+"条</p></td><td><p class='logBtn openBtn' id='service_record_logs' >"+data.record_list_[i].service_log_count_+"条</p></td></tr>";
                    $("#customRecord table").append(oneRecord); 
                  }
                  $("#customRecord table").append("<tr><td class='orderId'>"+data.order_id_+"</td><td class='messageTd'><p class='messageBtn' id='service_record_messages' >"+data.service_message_count_+"条售后沟通记录</p></td></tr>");
                  
                }/*else if(detailLiInd == 3){
                    $("#statusHistoryBox table").html("<th>订单状态</th><th>支付状态</th><th>仓库物流</th><th>配货单，发票，快递面单</th><th>操作</th><th>备注</th>");
                    var shTable = "";
                    for(var key in data.hostory_list_){
                      shTable = shTable+"<tr><td rowspan='"+data.hostory_list_[key].note_count_+"'>"+data.hostory_list_[key].order_status_+"</td><td rowspan='"+data.hostory_list_[key].note_count_+"'>"+data.hostory_list_[key].pay_status_+"</td><td rowspan='"+data.hostory_list_[key].note_count_+"'>"+data.hostory_list_[key].logistic_status_+"</td><td rowspan='"+data.hostory_list_[key].note_count_+"'>"+data.hostory_list_[key].shipment_print_status_+"，"+data.hostory_list_[key].invoice_print_status_+"，"+data.hostory_list_[key].waybill_print_status_+"</td>";
                      if(data.hostory_list_[key].notes_ != null){
                        for(var key1 in data.hostory_list_[key].notes_){
                        shTable = shTable+"<td>"+data.hostory_list_[key].notes_[key1].created_by_user_login+"</br>"+data.hostory_list_[key].notes_[key1].created_stamp+"</td><td>"+data.hostory_list_[key].notes_[key1].note+"</td></tr>";
                        }
                      }else{
                        shTable = shTable+"<td>无</td><td>无</td></tr>";
                      }
                      
                    }
                    $("#statusHistoryBox table").append(shTable);
                }*/else if(detailLiInd == 3){
                  // $("#saleSupportMessageBox").html("先用外面的链接！");
                  // location.href = "sale_support/sale_support.php?order_id=3992768";
                    var shTable = "<iframe style='width:100%;height=100%;' src='sale_support/sale_support.php?order_id=3992768'></iframe>";
                    $("#saleSupportMessageBox").html(shTable);
                }/*else if(detailLiInd == 5){
                  // $("#trans_status_show").html("<th>流转状态</th><th>操作时间</th>");
                }*/

             } else{
                $("#loadimg").fadeOut(100);
                alert(data.error_info_.err_no);
              }
            },
            error:function(){
              $("#loadimg").fadeOut(100);
              alert("AJAX加载失败");
            }
          });
      }
        function in_array(value,array){
        	for(key in array){
        		if(array[key]==value)
        			return true;
        	}
        	return false;
        }
     //end ajax加载函数
  
         /**
       * 支付方式搜索
       */
      $('#order_pay_id_select').autocomplete('distribution_order.php?request=ajax&act=search_payment', {
        dataType : 'json',
        minChars: 0,
        mustMatch: false,
        formatItem : function(row, i, max, value, term) {
          return(row.pay_name);
        },
        formatResult : function(row) {
          return(row.pay_name);
        }
      }).result(function(event, row, formatted) {
        $('#order_pay_id_select').val(row.pay_name);
        $("#pay_id option").each(function(){
          if($(this).val() == row.pay_id){
            $(this).attr("selected",true); 
          }else{
             $(this).attr("selected",false);
          }
        }); 
      });

      /**
      *配送方式关键字搜索
      */
      $('#order_shipping_id_select').autocomplete('distribution_order.php?request=ajax&act=get_select_shipping', {
        dataType : 'json',
        minChars: 0,
        mustMatch: false,
        formatItem : function(row, i, max, value, term) {
          return(row.shipping_name);
        },
        formatResult : function(row) {
          return(row.shipping_name);
        }
      }).result(function(event, row, formatted) { 
        $('#order_shipping_id_select').val(row.shipping_name); 
        var shipping_id = row.shipping_id; 
        $('#shipping_id option').each(function(){
            var this_shipping_id = $(this).val();
            if(this_shipping_id == shipping_id){
              $(this).attr("selected",true);
              $("#shipping_id").trigger("change"); 
            }else{
              $(this).attr("selected",false); 
            }
        }); 
      });


});