<?php

class SiteController extends Controller
{
    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow',  // allow all users to access 'login' actions.
                'actions'=>array('login','error'),
                'users'=>array('*'),
            ),
            array('allow', // allow authenticated users to access all actions
                'users'=>array('@'),
            ),
            array('deny',  // deny all users
                'users'=>array('*'),
            ),
        );
    }
    
	public function actionIndex()
	{
		$this->layout='//layouts/main';
		$this->render('index');
	}
	
	public function actionLogin() 
	{	
		$model=new LoginForm;

		// 登录
        if(isset($_POST['LoginForm']))
        {
            $model->attributes=$_POST['LoginForm'];
            if($model->validate() && $model->login()){
                if(Yii::app()->request->getIsAjaxRequest()){
                    echo CJSON::encode(array('success'=>true));
                	Yii::app()->end();
                }
                else
                    $this->redirect(Yii::app()->user->returnUrl);
            }else{
                if(Yii::app()->request->getIsAjaxRequest()) {
                    echo CJSON::encode(array('success'=>false,'msg'=>reset($model->getErrors())));            	
                    Yii::app()->end();                	
                }
            }
        }
        
		$this->render('login',array('model'=>$model));
	}
	
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
	
    public function actionError()
    {
    	$this->layout=false;
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }
    
    public function actionTest()
    {
        /*
        $mail=Yii::app()->mail;
        $mail->Subject='Mail Subject';
        $mail->AddAddress('swaygently@gmail.com', 'Hai MeiMei');
        $mail->AltBody="To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
        $mail->MsgHTML("just a test!");
        $mail->send();
        */
    }
}