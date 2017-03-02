<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 支持带模板功能的邮件发送
 * 
 * @param string $tpl 
 *   要使用的模板名
 * @param array  $vars 
 *   要赋值到模板中的变量
 * @param int $party_id
 *   组织
 * @param int $distributor_id
 *   分销商
 * @param string $recipient
 *   收件人姓名
 * @param string $email
 *   收件人email地址
 * @param string $subject
 *   邮件标题， 如果不指定，则默认用模板的标题
 * @return mixed
 *   模板不存在返回 -1 成功返回true, 发送失败返回false 
 */
function erp_send_mail($tpl, $vars = array(), $party_id = NULL, 
            $distributor_id = NULL, $recipient, $email, $subject = NULL)
{
    global $smarty, $db;

    // 取得短信模版， 如果 模板不存在或者该模板不发送短信，则返回-1
    $tpl = get_mail_template($tpl, $party_id);
    if (!$tpl || !$tpl['is_send']) { return -1; }
    
    // 邮件标题
    if (is_null($subject)) { $subject = $tpl['template_subject']; }

    // 邮件内容
    if (!empty($vars)) { $smarty->assign($vars); }
    $smarty->assign('_MAIL_TPL_PARTY_ID_', $party_id);  // 模板的组织ID
    $compile_id = "db:ecs_mail_templates:{$tpl['template_code']}_".$party_id; 
    $content = $smarty->fetch("db:{$tpl['template_code']}", null, $compile_id); 

    // 决定发给用户还是分销商
    if ($distributor_id > 0) {
        $sql = "SELECT alipay_account, abt_send_mail FROM distributor WHERE distributor_id = {$distributor_id}";
        $distributor = $db->getRow($sql, true);
        if ($distributor['abt_send_mail'] == 'NONE' ||         // 不发送
            $distributor['abt_send_mail'] == 'DISTRIBUTOR') {  // 发给分销商的邮件在cron job里处理
            return -1;
        }
    }

    return send_mail($recipient, $email, $subject, $content, $tpl['is_html'], $party_id);
}

/**
 * 支持带模板功能的短信发送
 * 
 * @param string $tpl 
 *   要使用的模板名
 * @param array  $vars 
 *   要赋值到模板中的变量
 * @param int $party_id
 *   组织
 * @param int $distributor_id
 *   分销商
 * @param string $recipients
 *   收件人姓名
 * @param string $email
 *   收件人email地址
 * @param string $subject
 *   邮件标题， 如果不指定，则默认用模板的标题
 * @return mixed
 *   模板不存在返回 -1 成功返回true, 发送失败返回false 
 */
function erp_send_message($tpl, $vars = array(), $party_id = NULL, $distributor_id = NULL, $mobile)
{ 
    global $smarty, $db;

    // 取得短信模版， 如果 模板不存在或者该模板不发送短信，则返回-1
    $tpl = get_msg_template($tpl, $party_id);
    if (!$tpl || !$tpl['is_send']) { return -1; }
    
    print date("Y-m-d H:i:s") . " " . "begin get_msg" . "\r\n";
    // 短信内容
    if (!empty($vars)) { $smarty->assign($vars); }
    $smarty->assign('_MSG_TPL_PARTY_ID_', $party_id);  // 短信模板的组织ID 
    $compile_id = "db:ecs_msg_templates:{$tpl['template_code']}_".$party_id;
    $msg = $smarty->fetch("db_msg:{$tpl['template_code']}", null, $compile_id);
    
    print date("Y-m-d H:i:s") . " " . "begin abt_send_message" . "\r\n";
    // 分销商配置是否发送短信
    if ($distributor_id > 0) {
        $sql = "SELECT tel, abt_send_message FROM distributor WHERE distributor_id = {$distributor_id}";
        $distributor = $db->getRow($sql, true);
        if ($distributor['abt_send_message'] == 'NONE') {                // 不发送
            return -1;
        }
    }

    print date("Y-m-d H:i:s") . " " . "begin send_message" . "\r\n";
    return send_message($msg, $mobile, $party_id, $tpl['server_name']);
}

?>
