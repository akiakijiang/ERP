<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string 
	 */
	public $layout=false;
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs=array();
	/**
	 * 图片目录
	 *
	 * @return string
	 */
    public function getImgUrl()
    {
    	return Yii::app()->getRequest()->getBaseUrl().'/images'; 
    }
    /**
     * Css目录
     * 
     * @return string
     */
    public function getCssUrl()
    {
    	return Yii::app()->getRequest()->getBaseUrl().'/css';
    }
    /**
     * Asset目录
     */
    public function getAssetUrl()
    {
        return Yii::app()->getAssetManager()->getBaseUrl();
    }
}