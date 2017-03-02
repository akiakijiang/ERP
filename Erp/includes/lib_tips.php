<?
/**
 * oukoo[欧酷网]
 * 提示信息和其他字符串服务
 * @author :Tao Fei<ftao@oukoo.com>
 * @copyright oukoo<0.5>
*/

//目前实现是基于lib_help 的， 将这些信息作为一类特殊的帮助信息

$tip_help_cat_id = 9;

function get_tip_by_id($tip_id)
{
    return get_help_by_id($tip_id);
}

$tip_ziti = <<<EOT
<ul id="zitiTip" style="text-align:left;"><li>上门提货用户须知</li><li>请选择上门自提的顾客，务必在与我们客服确认商品是否到达自提点后，再前来领取。自提点联系电话只用于查询如何到达自提点和自提手续，如果您有其他方面的疑问，如产品咨询，请拨打我们的客服电话：4008-206-206</li><li>请您在个人信息中留下手机号码，自提点在收到货物后会打电话通知您前来提货。</li><li>客户上门自提流程：</li><li>1. 您订购的商品到达欧酷自提点后，我们会在第一时间与您确认上门自提的时间。</li><li>2. 在您上门自提前我们会准备好商品、发票和发货单。</li><li>3. 目前仅上海张江自提点支持刷卡付款，其他自提点请使用现金付款。</li></ul>
EOT;

/**
 *
 */
function insert_tip($tip_id)
{

}

?>
