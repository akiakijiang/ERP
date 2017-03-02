<?php
/**
 * TOP API: taobao.marketing.tag.add request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 15:33:34.0
 */
class MarketingTagAddRequest
{
	/** 
	 * 标签用途描述
	 **/
	private $description;
	
	/** 
	 * 标签名称
	 **/
	private $tagName;
	
	private $apiParas = array();
	
	public function setDescription($description)
	{
		$this->description = $description;
		$this->apiParas["description"] = $description;
	}

	public function getDescription()
	{
		return $this->description;
	}

	public function setTagName($tagName)
	{
		$this->tagName = $tagName;
		$this->apiParas["tag_name"] = $tagName;
	}

	public function getTagName()
	{
		return $this->tagName;
	}

	public function getApiMethodName()
	{
		return "taobao.marketing.tag.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
