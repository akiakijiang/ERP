<?php
/**
 * TOP API: taobao.taohua.audioreader.album.get request
 * 
 * @author auto create
 * @since 1.0, 2011-05-30 13:12:25.0
 */
class TaohuaAudioreaderAlbumGetRequest
{
	/** 
	 * 有声读物商品ID
	 **/
	private $itemId;
	
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

	public function getApiMethodName()
	{
		return "taobao.taohua.audioreader.album.get";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
