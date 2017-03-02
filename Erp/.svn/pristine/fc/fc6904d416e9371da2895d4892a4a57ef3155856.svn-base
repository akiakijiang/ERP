<?php
/**
 * TOP API: taobao.traderates.search request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 16:20:38.0
 */
class TraderatesSearchRequest
{
	/** 
	 * 商品的数字id
	 **/
	private $numIid;
	
	/** 
	 * 当前页
	 **/
	private $pageNo;
	
	/** 
	 * 每页显示的条数，允许值：5、10、20、40
	 **/
	private $pageSize;
	
	/** 
	 * 商品所属的卖家nick
	 **/
	private $sellerNick;
	
	private $apiParas = array();
	
	public function setNumIid($numIid)
	{
		$this->numIid = $numIid;
		$this->apiParas["num_iid"] = $numIid;
	}

	public function getNumIid()
	{
		return $this->numIid;
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

	public function setSellerNick($sellerNick)
	{
		$this->sellerNick = $sellerNick;
		$this->apiParas["seller_nick"] = $sellerNick;
	}

	public function getSellerNick()
	{
		return $this->sellerNick;
	}

	public function getApiMethodName()
	{
		return "taobao.traderates.search";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
