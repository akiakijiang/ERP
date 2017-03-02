<?php

class MpsUserController extends MpsCommonController
{

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new MpsUser;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['MpsUser']))
		{
			$model->attributes=$_POST['MpsUser'];
            $model->password = md5($model->password);
			if($model->save())
				$this->redirect(array('view','id'=>$model->user_id));
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
        $oldpassword = $model->password;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['MpsUser']))
		{
			$model->attributes=$_POST['MpsUser'];
            
            if (!$model->password) 
            {
                $model->password = $oldpassword;
            }
            else
            {
                $model->password = md5($model->password);
            }
            
			if($model->save())
				$this->redirect(array('view','id'=>$model->user_id));
		}
        
        $model->password = null;
		$this->render('update',array(
			'model'=>$model,
		));
	}
	
	
	public function actionUpdatepassword()
	{
	    
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['MpsUser']))
		{
		    if (empty($_POST['MpsUser']['oldpassword']) 
		        || empty($_POST['MpsUser']['newpassword'])
		        || empty($_POST['MpsUser']['reenterpassword'])
		        ) 
		    {
		        throw new CHttpException(200,'请输入旧密码，新密码以及再次确认的新密码');
		    }
		    
		    if ($_POST['MpsUser']['newpassword'] != $_POST['MpsUser']['reenterpassword']) 
		    {
		        throw new CHttpException(200,'两次输入的新密码不一致');
		    }
		    
		    $id = Yii::app()->user->id;
            $model=$this->loadModel($id);
            $oldpassword = $model->password;
		    
		    if (md5($_POST['MpsUser']['oldpassword']) != $oldpassword) 
		    {
		        throw new CHttpException(200,'旧密码错误');
		    }
		    
            $model->password = md5($_POST['MpsUser']['newpassword']);
            
			if($model->save())
				$this->redirect(array('mps/index'));
		}
        
		$this->render('updatepassword');
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
		$dataProvider=new CActiveDataProvider('MpsUser');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new MpsUser('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['MpsUser']))
			$model->attributes=$_GET['MpsUser'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer the ID of the model to be loaded
	 */
	public function loadModel($id)
	{
		$model=MpsUser::model()->findByPk((int)$id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param CModel the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='mps-user-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
