<?php
/**
 * TOP API: taobao.marketing.tags.get request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 15:34:13.0
 */
class MarketingTagsGetRequest
{
	/** 
	 * 需要的返回字段，可选值为UserTag中所有字段
	 **/
	private $fields;
	
	private $apiParas = array();
	
	public function setFields($fields)
	{
		$this->fields = $fields;
		$this->apiParas["fields"] = $fields;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getApiMethodName()
	{
		return "taobao.marketing.tags.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
