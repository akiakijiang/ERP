<?php
/**
 * TOP API: taobao.marketing.tag.delete request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 15:35:36.0
 */
class MarketingTagDeleteRequest
{
	/** 
	 * 标签ID
	 **/
	private $tagId;
	
	private $apiParas = array();
	
	public function setTagId($tagId)
	{
		$this->tagId = $tagId;
		$this->apiParas["tag_id"] = $tagId;
	}

	public function getTagId()
	{
		return $this->tagId;
	}

	public function getApiMethodName()
	{
		return "taobao.marketing.tag.delete";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
