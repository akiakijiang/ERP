<?php
/**
 * Helper_Mail 邮件助手
 * 
 * @package helper
 * 
 * 
 * 用法
 * 
 * $mail = Helper_Mail::smtp();
 * try {
 *     $mail->AddReplyTo('erp@oukoo.com', '欧酷网ERP组');
 *     $mail->SetFrom('erp@oukoo.com', '欧酷网ERP组');
 *     $mail->Subject = 'email title';
 *     $mail->AddAddress('yxiang@oukoo.com', '收件人');
 *     $mail->Body = '邮件内容!';
 *     $mail->IsHtml();  // 如果邮件是html格式的话
 *     $mail->AddAttachment('images/phpmailer.gif');  // 附件
 *     $mail->Send(); 
 * } catch (phpmailerException $e) {
 *     echo $e->errorMessage();
 * }
 *
 */


/**
 * 邮件助手
 */
abstract class Helper_Mail 
{
    /**
     * 返回一个配置好的smtp连接的邮件句柄
     *
     * @return object
     */
    static function smtp()
    {
        require_once(ROOT_PATH . 'includes/phpmailer/class.phpmailer.php');
        $mail = new PHPMailer(true);
        $mail->IsSendmail();
        /*
        $mail->SMTPDebug = false;
        // SMTP服务器
        $mail->IsSMTP();
        $mail->Host = $GLOBALS['_CFG']['smtp_host'];
        $mail->Port = $GLOBALS['_CFG']['smtp_port'];
        // SMTP认证
        $mail->SMTPAuth = true;
        $mail->SMTPSecure='ssl';
        $mail->Username = $GLOBALS['_CFG']['smtp_user'];
        $mail->Password = $GLOBALS['_CFG']['smtp_pass'];
        */
        return $mail;
    }
    
    /**
     * 发送邮件
     * 类似目前的send_mail方法
     * 
     * TODO 该函数现要调整测试 
     * 
     * @return boolean
     */
    static function send($name, $email, $subject, $content, $type = 0, $party_id = NULL)
    {
		// 如果邮件编码不是utf8，创建字符集转换对象，转换编码 
        if ($GLOBALS['_CFG']['mail_charset'] != 'UTF8') {
            include_once (ROOT_PATH . 'includes/iconv/cls_iconv.php');
            $iconv = new Chinese(ROOT_PATH);
            $name = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $name);
            $subject = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $subject);
            $content = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $content);
            $GLOBALS['_CFG']['shop_name'] = $iconv->convert('UTF8', $GLOBALS['_CFG']['mail_charset'], $GLOBALS['_CFG']['shop_name']);
            $charset = $GLOBALS['_CFG']['mail_charset'];
        }
        else {
            $charset = 'UTF-8';
        }
    
        // 如果是测试模式，则保存邮件到本地目录
        if ($GLOBALS['_CFG']['smtp_debug'] == 'debug') {
            // 保存测试邮件到本地
            if (stripos(PHP_OS, 'win') !== false) {
                $smtp_path = "F:/out/";
            } else {
                $smtp_path = $GLOBALS['_CFG']['smtp_debug_path'] != '' ? $GLOBALS['_CFG']['smtp_debug_path'] : "/tmp/mail_debug/";
            }
            
            return file_put_contents($smtp_path . strip_tags("{$email}_") . date("Y-m-d_H-i-s") .".htm", $content);
        }
        else {
            $shop_name = isset($GLOBALS['_CFG']['shop_name_'. $party_id]) ? $GLOBALS['_CFG']['shop_name_'. $party_id] : $GLOBALS['_CFG']['shop_name'] ;
            $smtp_mail = isset($GLOBALS['_CFG']['smtp_mail_'. $party_id]) ? $GLOBALS['_CFG']['smtp_mail_'. $party_id] : $GLOBALS['_CFG']['smtp_mail'] ;
    
            require_once(ROOT_PATH . 'includes/phpmailer.php');
            $mail = new PHPMailer(true);
            $mail->IsSMTP();

            try {
                $mail->SMTPDebug  = false;  // 2为测试模式
                $mail->SMTPAuth   = true;   // 开启SMTP验证
                $mail->SMTPSecure = "";     // 服务前缀
                $mail->Host       = $GLOBALS['_CFG']['smtp_host'];  // sets GMAIL as the SMTP server
                $mail->Port       = $GLOBALS['_CFG']['smtp_port'];  // set the SMTP port for the GMAIL server
                $mail->Username   = $GLOBALS['_CFG']['smtp_user'];  // GMAIL username
                $mail->Password   = $GLOBALS['_CFG']['smtp_pass'];  // GMAIL password
                #$mail->AddReplyTo('erp@oukoo.com', '欧酷网ERP组');
                $mail->SetFrom($smtp_mail, $shop_name);
                $mail->AddAddress($email, $name);
                $mail->Subject = $subject;
                #$mail->AddReplyTo('name@yourdomain.com', 'First Last');
                #$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!'; // optional - MsgHTML will create an alternate automatically
                if ($type == 1) {
                    $mail->IsHTML();
                }
                $mail->Body = $content;    
                #$mail->AddAttachment('images/phpmailer.gif');      // attachment
                #$mail->AddAttachment('images/phpmailer_mini.gif'); // attachment
                $result = $mail->Send();
                if ($result) {
                    mail_log($name, $email, $subject, $content, $type);
                    return true;
                }
                return false;
            } catch (phpmailerException $e) {
                return false;
                #echo $e->errorMessage(); //Pretty error messages from PHPMailer
            }
        }
    }
}


?>