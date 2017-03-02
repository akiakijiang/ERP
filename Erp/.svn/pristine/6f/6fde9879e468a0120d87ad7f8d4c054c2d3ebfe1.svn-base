<?php
/**
 * 创瑞短信接口
 */
function send_message_with_crsms($msg, $dest_mobile, $sign = '乐其', &$response=null){
    global $db;

    //尊敬的乐其金佰利分销商：目前您的预存款账户只剩@，请在24小时内及时续款，避免停发货【乐其】
    //温馨提示：由于您订购的商品较多，我们将分为（@）个包裹为您发货，请亲注意签收。快递：@ 【XX】
    $url="http://web.cr6868.com/asmx/smsservice.aspx?name=17770800926&pwd=1343363AF6E59CF9787771D8CFCB&content=".urlencode($msg)."&mobile=".urlencode($dest_mobile)."&stime=&sign=".urlencode($sign)."&type=pt&extno=";
    $response=file_get_contents($url);

    // 正常返回值形如 0,2016051110135509673806185,0,1,0,提交成功

    //获取信息发送后的状态
    $res_list=explode(',', $response);

    $done=0;
    
    if($res_list[0] == '0'){
        //SUCCESS
        $sql="INSERT INTO ecshop.cr_sms(
            `sms_id`,
            `sms_send_id`,
            `status`,
            `send_time`,
            `response_time`,
            `mobile`,
            `content`,
            `sign`
        )VALUES(
            NULL,
            '{$res_list[1]}',
            'APPLIED',
            NOW(),
            NULL,
            '{$dest_mobile}',
            '{$msg}',
            '{$sign}'
        )";
        $done=1;
    }else{
        //FAIL
        $sql="INSERT INTO ecshop.cr_sms(
            `sms_id`,
            `sms_send_id`,
            `status`,
            `send_time`,
            `response_time`,
            `mobile`,
            `content`,
            `sign`
        )VALUES(
            NULL,
            'NA',
            'THROWN',
            NOW(),
            NULL,
            '{$dest_mobile}',
            '{$msg}',
            '{$sign}'
        )";
        $done=0;
    }
    $db->exec($sql);

    return $done;
}
