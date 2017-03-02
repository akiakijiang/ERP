<?php

/**
 * 短信发送类
 * @author yfhu
 *
 */
abstract class MessageClientBase {
	/**
	 * 发端短信
	 * @param  $mobiles
	 * @param  $content
	 * @param  $sendTime
	 * @return int
	 */
	abstract function sendSMS($mobiles=array(),$content,$sendTime='');
	
	/**
	 * 获取剩余条数
	 * @return int
	 */
	abstract function getBalance();
}