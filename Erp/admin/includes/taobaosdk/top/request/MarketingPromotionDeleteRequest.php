<?php
/**
 * TOP API: taobao.marketing.promotion.delete request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 15:32:26.0
 */
class MarketingPromotionDeleteRequest
{
	/** 
	 * 已设置的优惠策略ID
	 **/
	private $promotionId;
	
	private $apiParas = array();
	
	public function setPromotionId($promotionId)
	{
		$this->promotionId = $promotionId;
		$this->apiParas["promotion_id"] = $promotionId;
	}

	public function getPromotionId()
	{
		return $this->promotionId;
	}

	public function getApiMethodName()
	{
		return "taobao.marketing.promotion.delete";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
