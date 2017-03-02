/**
 *  快递面单递增规则
 *  ljzhou 2013-10-21
 */


//由于快递规则不一样，递增时需要特殊判断 ljzhou 2013-10-16
function auto_add_tracking_number(start_num,offset){
	var shipping_id=document.getElementById("shipping_id_"+offset).value;
//	shipping_id=4;
	// 宅急送
	if(shipping_id == 12) {
		return zjs_add_tracking_number(start_num,offset);
	}
	// 顺丰空运和顺丰陆运
	else if(shipping_id == 44 || shipping_id == 117 || shipping_id == 127 || shipping_id == 136 || shipping_id == 135) {
		return sf_add_tracking_number(start_num,offset);
	}  
	// 其他默认递增+1
	else {
		start_num = ~~start_num; // 去掉前面的0
		start_num = parseInt(start_num)+offset;
		start_num = add_zero_before_number(start_num,9);//把位数变回9位，前面有0的情况
		return start_num;
	}
}

// 宅急送，末尾遇6加4,其他加11
function zjs_add_tracking_number(start_num,offset) {
    if(offset == 0) {
    	start_num = add_zero_before_number(start_num,9);// 补齐9位
    	return start_num;
    }
	var last_num;
	start_num = ~~start_num; // 去掉前面的0
	start_num = start_num.toString();
	last_num = start_num.substr(-1,1);
	start_num = parseInt(start_num);
	if(last_num == 6) {
		return zjs_add_tracking_number(start_num+4,offset-1);
	} else {
		return zjs_add_tracking_number(start_num+11,offset-1);
	}
}

// 顺丰
//前11位数字是按照顺序排列，最后一位数字是根据一定原理推导出来的：
//1、当第11位数不等于9时，第二张运单的最后一位数字=上一张运单最后一位数字+9，取结果的个位数
//2、当第11位数字=9时，看第10位数字的规律
//? 当第10位数字=0，1，2，4，5，7，8时，第二张运单的最后一位数字=上一张运单最后一位数字+6，取结果的个位数
//? 当第10位数字=3，6时，第二张运单的最后一位数字=上一张运单最后一位数字+5，取结果的个位数
//3、当第11位数字、第10位数字都等于9时，看第9位数字的规律
//? 当第9位数字=0，2，4，6，8时，第二张运单最后一位数字=上一张运单最后一位数字+3，取结果的个位数
//? 当第9位数字=1，3，5，7时，第二张运单最后一位数字=上一张运单最后一位数字+2，取结果的个位数
//4、当第11位数字、第10位数字、第9位数字都等于9时，看第8位数字的规律
//? 当第8位数字=0，3，6时，第二张运单的最后一位数字=上一张运单最后一位数字+0，取结果的个位数
//? 当第8位数字=1，2，4，5，7，8时，第二张运单的最后一位数字=上一张运单最后一位数字+9，取结果的个位数
//5、当第11位数字、第10位数字、第9位数字、第8位数字都等于9时，看第7位数字的规律
//? 当第7位数字=0时，第二张运单的最后一位数字=上一张运单最后一位数字+7，取结果的个位数
//? 当第7位数字=1，2，3，4，5，6，7，8时，第二张运单的最后一位数字=上一张运单最后一位数字+6，取结果的个位数
//6、当第11位数字、第10位数字、第9位数字、第8位数字、第7位数字都等于9时，看第6位数字的规律
//? 当第6位数字=0，1，2，3，4，5，6，7，8时，第二张运单的最后一位数字=上一张运单最后一位数字+3，取结果的个位数
//7、当第11位数字、第10位数字、第9位数字、第8位数字、第7位数字、第6位数字都等于9时，根据第5位数字判断
//? 当第5位数字=0，1，2，4，5，7，8时，第二张运单的最后一位数字=上一张运单最后一位数字+9，取结果的个位数
//? 当第5位数字=3，6时，第二张运单的最后一位数字=上一张运单最后一位数字+8，取结果的个位数
//8、当第11位数字、第10位数字、第9位数字、第8位数字、第7位数字、第6位数字、第5位数字都等于9时，根据第4位数字判断
//? 当第4位数字=0，2，4，6，8时，，第二张运单的最后一位数字=上一张运单最后一位数字+5，取结果的个位数
//? 当第4位数字=1，3，5，7时，第二张运单的最后一位数字=上一张运单最后一位数字+4,取结果的个位数
// ljzhou 2013-10-19	
function sf_add_tracking_number(start_num,offset) {
    if(offset == 0) {
    	return start_num;
    }

	start_num = start_num.toString();
    begin_num = start_num.substr(0,8);
	begin_num = ~~begin_num; // 去掉前面的0
	begin_num = parseInt(begin_num)+1;
	begin_num = add_zero_before_number(begin_num,8);//把位数变回8位，前面有0的情况
	
	var nums = Array();
	// 顺丰取最后9个数字作运算，所以上面注释的第11个数字相当于第8个数字
	for(var i=0;i<9;i++) {
		nums[i+4] = parseInt(start_num.substr(i,1));
		//alert(nums[i+4]);
	}

	if(nums[11] == 9) {
		if ($.inArray(nums[10],Array(0,1,2,4,5,7,8)) != -1) {
           nums[12] = (nums[12]+6)%10;
		}
		else if($.inArray(nums[10],Array(3,6)) != -1) {
			nums[12] = (nums[12]+5)%10;
		} 
		else 
		{
			if ($.inArray(nums[9],Array(0,2,4,6,8)) != -1) {
               nums[12] = (nums[12]+3)%10;
		    }
		    else if($.inArray(nums[9],Array(1,3,5,7)) != -1) {
				nums[12] = (nums[12]+2)%10;
			} 
			else 
			{
				if ($.inArray(nums[8],Array(0,3,6)) != -1) {
                   nums[12] = (nums[12]+0)%10;
			    }
			    else if($.inArray(nums[8],Array(1,2,4,5,7,8)) != -1) {
					nums[12] = (nums[12]+9)%10;
				} 
				else 
				{
			        // 只写1个0会认为进不去?
				   	if ($.inArray(nums[7],Array(0,0)) != -1) {
	                   nums[12] = (nums[12]+7)%10;
				    }
				    else if($.inArray(nums[7],Array(1,2,3,4,5,6,7,8)) != -1) {
						nums[12] = (nums[12]+6)%10;
					} 
					else 
					{
						if ($.inArray(nums[6],Array(0,1,2,3,4,5,6,7,8)) != -1) {
		                   nums[12] = (nums[12]+3)%10;
					    }
						else 
						{
							if ($.inArray(nums[5],Array(0,1,2,4,5,7,8)) != -1) {
			                   nums[12] = (nums[12]+9)%10;
						    }
							else if ($.inArray(nums[5],Array(3,6)) != -1) {
							   nums[12] = (nums[12]+8)%10;
							}
							else
							{
								if ($.inArray(nums[4],Array(0,2,4,6,8)) != -1) {
				                   nums[12] = (nums[12]+5)%10;
							    }
								else if ($.inArray(nums[4],Array(1,3,5,7)) != -1) {
								   nums[12] = (nums[12]+4)%10;
								}
								else
								{
								   // 没定义，默认加1
								   nums[12] = (nums[12]+1)%10;
								}
							}
						}
						
					}
					
				}
				
			}
		}
	} 
	else 
	{
		nums[12] = (nums[12]+9)%10;
	}

	start_num = begin_num + nums[12].toString();
	
	return sf_add_tracking_number(start_num,offset-1);

}

// 数字前面加上0，number为穿进去的数字，size为数字返回的位数
function add_zero_before_number(number,size) {
	var num = number.toString();
	var zero_number = parseInt(size) - num.length;
	if(zero_number > 0) {
		var zero_before = '';
		for(var i=0;i<zero_number;i++) {
			zero_before +='0';
		}
		num = zero_before + num;
	}
	
	return num;
}
