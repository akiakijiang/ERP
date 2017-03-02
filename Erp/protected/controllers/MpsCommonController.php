<?php

/**
 * mps 公用的controller，继承 RController 主要有以下公用的部分：
 * - layout
 * - filters
 *
 *
 * @author zwsun <zwsun@i9i8.com>
 * @date 2011-6-10 15:03:23
 */
class MpsCommonController extends RController
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/mps';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
            'rights',
		);
	}
    
}
