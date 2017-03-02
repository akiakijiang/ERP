<?php
/**
 * TOP API: taobao.picture.category.add request
 * 
 * @author auto create
 * @since 1.0, 2011-05-17 16:24:58.0
 */
class PictureCategoryAddRequest
{
	/** 
	 * 需要返回的字段,根据PictureCategory中的以下字段：picture_category_id,picture_category_name,position,type,total,created,modified ,多个字段用“,”分隔。如：type,total,created,modified
	 **/
	private $fields;
	
	/** 
	 * 图片分类的父分类,一级分类的parent_id为0,二级分类的则为其父分类的picture_category_id
	 **/
	private $parentId;
	
	/** 
	 * 图片分类名称，最大长度20字符，中英文都算一字符，不能为空
	 **/
	private $pictureCategoryName;
	
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

	public function setParentId($parentId)
	{
		$this->parentId = $parentId;
		$this->apiParas["parent_id"] = $parentId;
	}

	public function getParentId()
	{
		return $this->parentId;
	}

	public function setPictureCategoryName($pictureCategoryName)
	{
		$this->pictureCategoryName = $pictureCategoryName;
		$this->apiParas["picture_category_name"] = $pictureCategoryName;
	}

	public function getPictureCategoryName()
	{
		return $this->pictureCategoryName;
	}

	public function getApiMethodName()
	{
		return "taobao.picture.category.add";
	}
	
	public function getApiParas()
	{
		return $this->apiParas;
	}
}
