<?php
/**
 * TOP API: taobao.sellercenter.subusers.get request
 * 
 * @author auto create
 * @since 1.0, 2011-06-14 13:16:32.0
 */
class SellercenterSubusersGetRequest
{
	/** 
	 * 表示卖家昵称
	 **/
	private $nick;
	
	private $apiParas = array();
	
	public function setNick($nick)
	{
		$this->nick = $nick;
		$this->apiParas["nick"] = $nick;
	}

	public function getNick()
	{
		return $this->nick;
	}

	public function getApiMethodName()
	{
		return "taobao.sellercenter.subusers.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
