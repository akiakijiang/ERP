<?php
/**
 * TOP API: taobao.taohua.audioreader.tracks.get request
 * 
 * @author auto create
 * @since 1.0, 2011-07-01 10:00:10.0
 */
class TaohuaAudioreaderTracksGetRequest
{
	/** 
	 * 有声读物专辑ID
	 **/
	private $itemId;
	
	/** 
	 * 当前页码
	 **/
	private $pageNo;
	
	/** 
	 * 每页个数
	 **/
	private $pageSize;
	
	private $apiParas = array();
	
	public function setItemId($itemId)
	{
		$this->itemId = $itemId;
		$this->apiParas["item_id"] = $itemId;
	}

	public function getItemId()
	{
		return $this->itemId;
	}

	public function setPageNo($pageNo)
	{
		$this->pageNo = $pageNo;
		$this->apiParas["page_no"] = $pageNo;
	}

	public function getPageNo()
	{
		return $this->pageNo;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		$this->apiParas["page_size"] = $pageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function getApiMethodName()
	{
		return "taobao.taohua.audioreader.tracks.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
