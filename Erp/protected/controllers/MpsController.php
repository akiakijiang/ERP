<?php

class MpsController extends MpsCommonController
{
    public function allowedActions()
    {
        return 'login, Login, logout, Logout, error, Error, Barcode, barcode'; // 这些是所有的都能访问的
    }
    
	public function actionIndex()
	{
        $this->redirect(array('SupplierDispatchList/index', 'alert' => 1));
		//$this->render('index');
	}
    
    public function actionBarcode($barcode, $width = 230, $height = 30)
    {
        require_once(Yii::app()->basePath . '/../includes/lib_barcode.php');

        // download a ttf font here for example : http://www.dafont.com/fr/nottke.font
        // $font     = './NOTTB___.TTF';
        // - -

        $fontSize = 10;   // GD1 in px ; GD2 in point
        $marge    = 10;   // between barcode and hri in pixel
        
        $scale    = 2;    // barcode height in 1D ; not use in 2D
        $angle    = 0;    // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
        $type     = 'code128';

        // -------------------------------------------------- //
        //            ALLOCATE GD RESSOURCE
        // -------------------------------------------------- //
        $im     = imagecreatetruecolor($width, $height);
        $black  = ImageColorAllocate($im,0x00,0x00,0x00);
        $white  = ImageColorAllocate($im,0xff,0xff,0xff);
        $red    = ImageColorAllocate($im,0xff,0x00,0x00);
        $blue   = ImageColorAllocate($im,0x00,0x00,0xff);
        imagefilledrectangle($im, 0, 0, $width, $height, $white);

        // -------------------------------------------------- //
        //                      BARCODE
        // -------------------------------------------------- //
        $x = $width/2;   // barcode center
        $y = $height/2;  // barcode center
        $data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$barcode), $scale, $height);

        // -------------------------------------------------- //
        //                        HRI
        // -------------------------------------------------- //
        if (isset($font)){
            $box = imagettfbbox($fontSize, 0, $font, $data['hri']);
            $len = $box[2] - $box[0];
            Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
            imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
        }

        // -------------------------------------------------- //
        //                    GENERATE
        // -------------------------------------------------- //
        header('Content-type: image/gif');
        header('Cache-Control: public');

        imagegif($im);
        imagedestroy($im);

    }
	
	public function actionLogin() 
	{
		$model=new MpsLoginForm;
        
        // if it is ajax validation request
		if(isset($_POST['ajax']) && $_POST['ajax']==='login-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}

		// collect user input data
		if(isset($_POST['MpsLoginForm']))
		{
			$model->attributes=$_POST['MpsLoginForm'];
			// validate user input and redirect to the previous page if valid
			if($model->validate() && $model->login()) 
            {
				$this->redirect($this->createUrl('mps/index'));
            }
		}
        
		// display the login form
		$this->render('login',array('model'=>$model));
	}
	
	public function actionLogout()
	{
		Yii::app()->user->logout();
		$this->redirect(Yii::app()->homeUrl);
	}
	
    public function actionError()
    {
        if($error=Yii::app()->errorHandler->error)
        {
            if(Yii::app()->request->isAjaxRequest) 
            {
                $this->layout=false;
                echo $error['message'];
            }
            else
            {
                $this->render('error', $error);
            }
        }
    }
    
}