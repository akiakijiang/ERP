<?php
/**
 * oukoo[欧酷网]
 * 通过JS方式输出大区域的配送方式列表
 * @author :Tao Fei<ftao@oukoo.com>
 * @copyright oukoo<0.5>
*/

/*
输出结果的格式是
array( 
    shipping_name  => array(shipping_desc,....)
)
*/

define('IN_ECS', true);
require_once('../includes/master_init.php');
require_once('../includes/lib_order.php');
require_once('../includes/lib_common.php');
require_once('includes/cls_json.php');
$ret = array();
$provinces = get_regions(1, $_CFG['shop_country']);
$provinces[] = array('region_id' => 233, 'region_name' => "广州", 'parent_id' => 20);
$provinces[] = array('region_id' => 234, 'region_name' => "深圳", 'parent_id' => 20);
$all_area = array();
foreach($provinces as $v)
{
    //$ret[$v['region_id']]= array('name' => $v['region_name'], 'data'=> array());
    $all_area[] = '"' . $v['region_name'] . '"';
    $ret[$v['region_name']]= array('id' => $v['region_id'], 'data'=> array());
    $region = array($_CFG['shop_country']);
    if($v['parent_id'])
    {
        $region[] = $v['parent_id'];
        $region[] = $v['region_id'];
        $region[] = 0;
    }
    else
    {
        $region[] = $v['region_id'];
        $region[] = 0;
        $region[] = 0;
    }
    $asl = available_shipping_list2($region);
    foreach($asl as $shipping)
    {
        $t = $shipping;
        $config = unserialize_config($t['configure']);
        $desc = sprintf("%s%s天%s元", $t['shipping_name'], $config['delivery_time'], $config['basic_fee']);
        if($config['percent'])
        {
            $desc .= sprintf("+%s%%总额", $config['percent']);
        }
        $t['desc_to_show'] = $desc;
        $ret[$v['region_name']]['data'][] = $t;
    }
}
$json = new JSON;
$text = sprintf("var all_area = [%s];\n", join(",", $all_area)); 
$text .= sprintf("var ouku_shipping_data = %s.parseJSON();\n" , $json->encode($json->encode($ret)));
?>
/*
	* 商品详细页显示快递到达时间
	* area_all 展示区域
	* area_select 显示选定的城市
    * shipping_cod
    * shipping_nocod
	
*/

    <?php echo $text; ?>


function showShippingList(area)
{
    var shipping_cod_ele = document.getElementById('shipping_cod'); 
    var shipping_nocod_ele = document.getElementById('shipping_nocod'); 
    var cod_html = "";
    var nocod_html = "";
    for (var j = 0; j < ouku_shipping_data[area]['data'].length; j++)
    {
    	var m=0;
    	var n=0;
        var cod_html = "";
        var nocod_html = "";
        for (var j = 0; j < ouku_shipping_data[area]['data'].length; j++)
        {
            var item = ouku_shipping_data[area]['data'][j];
            if (item.support_no_cod == '1' && item.support_cod == '0')
            {	
            	m += 1;
            	if(m==2){
                	nocod_html += item.desc_to_show + '&nbsp;&nbsp;&nbsp;&nbsp;';
            	}else{
            		nocod_html += item.desc_to_show + '<br/>';
            	}
            }
            if (item.support_no_cod == '0' && item.support_cod == '1')
            {
            	n += 1;
            	if(n==1){
            		cod_html += item.desc_to_show + '<br/>';
            	}else{
            		cod_html += item.desc_to_show + '&nbsp;&nbsp;&nbsp;&nbsp;';
            	}	
            }
        }
        shipping_cod_ele.innerHTML = cod_html;
        shipping_nocod_ele.innerHTML = nocod_html;
    }
    shipping_cod_ele.innerHTML = cod_html;
    shipping_nocod_ele.innerHTML = nocod_html;
}

function showArea(){
    document.getElementById('area_all').style.display = '';
}

function closeArea(){
    document.getElementById('area_all').style.display = 'none';
}

function init_shipping_info()
{
    var area_all_ele = document.getElementById('area_all');
    var area_select_ele = document.getElementById('area_select');

    var html = '';
    var len = all_area.length;
    for(var i=0; i<len; i++){		
        if(i == 0){
            html += '<tr>';
        }
        html += '<td><a href="#" id="area_'+ i + '">' + all_area[i] +  '</a></td>';
        if(i == 10) {
            html += '</tr><tr>';
            continue;
        }else if(i == 21){
            html += '</tr><tr>';
            continue;			
        }else if(i == len-1){
            html += '</tr><tr><td colspan=11" style="text-align:left;">提示：由于民航总局对含有电池商品的空运限制，货运方式均改为陆运，导致配送时间延迟请用户谅解。</td></tr>';
        }
    }
    area_all_ele.innerHTML = '<table cellpadding="0" cellspacing="0">'+html+'</table>';

    for(var i = 0; i < len; i++){
        document.getElementById('area_'+i).onclick = function(){			
            area_select_ele.innerHTML =  this.innerHTML;
            area_all_ele.style.display = 'none';
            showShippingList(this.innerHTML);
            return false;
        }
    }
    if(document.all){
        area_select_ele.attachEvent('onmouseover',showArea);
        area_select_ele.attachEvent('onmouseleave',closeArea);
        area_all_ele.attachEvent('onmouseover',showArea);
        area_all_ele.attachEvent('onmouseleave',closeArea);
        
    }else{
        area_select_ele.addEventListener('mouseover',showArea,false);
        area_select_ele.addEventListener('mouseout',closeArea,false);
        area_all_ele.addEventListener('mouseover',showArea,false);
        area_all_ele.addEventListener('mouseout',closeArea,false);
    }
    area_select_ele.onclick = function(){return false;};
}

