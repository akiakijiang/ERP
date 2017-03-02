<?php

/**
 *
 */
class SupplierDispatchListController extends MpsCommonController
{

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($sn)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($sn),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new SupplierDispatchList;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['SupplierDispatchList']))
		{
			$model->attributes=$_POST['SupplierDispatchList'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->supplier_dispatch_list_id));
		}

		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['SupplierDispatchList']))
		{
			$model->attributes=$_POST['SupplierDispatchList'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->supplier_dispatch_list_id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}
    
    
    public function actionUpdatestatus($sn, $status) 
    {
        $model = $this->loadModel($sn);
        
        $model->status = $status;
        if($model->save()) 
        {
            $this->redirect(array('/SupplierDispatchList/index'));
        }
    }
    
    
    public function actionPrint($sn) 
    {
        $this->layout = null;
        $model = new SupplierDispatchList();
        $dispatchList = $model->findBySn($sn);
        
        $attributes = $model->findAttributesBySn($sn);
        
        $imgAttributes = array();
        foreach ($attributes as $name => $value) {
            if (preg_match('/.*\d_original$/', $name)) {
                $imgAttributes[] = $value;
            }
        }

        // 以前存的工单，图片的属性不太一样
        if (!$imgAttributes) {
            foreach ($attributes as $name => $value) {
                if (preg_match('/.*\d_o$/', $name)) {
                    $imgAttributes[] = $value;
                }
            }
        }
        
        //var_dump($attributes);die();
        $this->render('print', array(
            'dispatchList' => $dispatchList,
            'attributes' => $attributes,
            'imgAttributes' => $imgAttributes,
        ));
    }
    

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'index' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel($id)->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
        $model = new SupplierDispatchList();
        
        $isAlert = false;
        if (!empty($_GET['alert'])) 
        {
            $condition = array('type' => 'overtime', 'status' => 'CONFIRMED');
            $isAlert = true;
        }
        else
        {
            $condition = isset($_GET['condition']) ? $_GET['condition'] : array();
        }
        
        list($supplierDispatchLists, $pagination) = $model->findBySupplierId($condition);

		$this->render('index',array(
			'supplierDispatchLists'=>$supplierDispatchLists,
            'pagination' => $pagination,
            'condition' => $condition,
            'isAlert' => $isAlert,
		));
	}
	

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new SupplierDispatchList('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['SupplierDispatchList']))
			$model->attributes=$_GET['SupplierDispatchList'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($sn)
	{
		$model=SupplierDispatchList::model()->find(
            array(
            'condition' => 'dispatch_sn = :dispatch_sn',
            'params' => array(':dispatch_sn' => $sn),
            )
        );
		if($model===null)
			throw new CHttpException(404,'对应的工单没有找到或者被删除');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='supplier-dispatch-list-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
    
}
