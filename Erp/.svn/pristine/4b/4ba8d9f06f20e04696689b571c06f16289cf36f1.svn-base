<?php 

require_once(Yii::getPathOfAlias('application').'/vendors/PHPMailer/class.phpmailer.php');

/**
 * PHPMailer Component for Yii
 * 
 * @author Yang Xiang <swaygently@gmail.com>
 * @example
 * 
 * // 配置文件
 * @code
 * array(
 *     'class'=>'application.component.CMail',
 *     'Mailer'=>'smtp',  
 *     'Host'=>'mail.yourdomain.com',
 *     'Port'=>26,
 *     'SMTPAuth'=>true,
 *     'SMTPKeepAlive'=>true,
 *     'Username'=>'yourname@yourdomain',
 *     'Password'=>'yourpassword',
 * )
 * 
 * // 使用
 * try
 * {
 *     $mail=Yii::app()->mail;
 *     $mail->SetFrom('list@mydomain.com', 'List manager');
 *     $mail->AddReplyTo('list@mydomain.com', 'List manager');
 *     $mail->Subject='Mail Subject';
 *     $mail->AddAddress('haimeimei@gmail.com', 'Hai MeiMei');
 *     $mail->AddStringAttachment('My Photo', 'YourPhoto.jpg');
 *     $mail->AltBody="To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
 *     $mail->MsgHTML($body);
 *     $mail->send();
 * }
 * catch (Exception $e) 
 * {
 *     Yii::log($e->getMessage(),CLogger::LEVEL_ERROR);
 * }
 * 
 * // Clear all addresses and attachments for next
 * $mail->ClearAddresses();
 * $mail->ClearAttachments();
 * @endcode
 * 
 */
class CMail extends PHPMailer implements IApplicationComponent
{
    private $_initialized=false;
    
    /**
     * 允许抛出异常
     */
    public function __construct() 
    {
        parent::__construct(true);
    } 
    
    public function init()
    {
        $this->_initialized=true;
        
        // 设置语言和编码
        $this->CharSet=Yii::app()->charset;
        $this->SetLanguage(strtolower(Yii::app()->getLanguage()));
    }
    
    public function getIsInitialized()
    {
        return $this->_initialized;
    }
}