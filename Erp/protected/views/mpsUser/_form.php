<div class="form">

<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'mps-user-form',
	'enableAjaxValidation'=>false,
)); 

Yii::app()->clientScript->registerCss('row', ".row {margin: 5px 0;clear: left;}
label
{
float: left;
margin-right: 10px;
position: relative;
text-align: right;
width: 100px;
}
.buttons{padding-left: 110px;}
")
?>

	<?php echo $form->errorSummary($model); ?>

	<div class="row">
		<?php echo $form->labelEx($model,'user_name'); ?>
		<?php
		$user_name_html_options = array('size'=>15,'maxlength'=>30,);
		if (!$model->isNewRecord) 
		{
		    $user_name_html_options['readonly'] = true;
		}
		
		echo $form->textField($model,'user_name', $user_name_html_options); 
		?>
		<?php echo $form->error($model,'user_name'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'password'); ?>
		<?php echo $form->passwordField($model,'password',array('size'=>15,'maxlength'=>32)); ?>
		<?php echo $form->error($model,'password'); ?>
		<?php if (!$model->isNewRecord) echo '密码为空，则不修改原有密码'; ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'supplier_id'); ?>
        <?php 
        $_suppliers = Supplier::model()->findAll();
        $suppliers = array();
        foreach ($_suppliers as $supplier) {
            $suppliers[$supplier->provider_id] = $supplier->provider_name;
        }
        echo $form->dropDownList($model, 'supplier_id', $suppliers);
        ?>
		<?php echo $form->error($model,'supplier_id'); ?>
	</div>

	<div class="row buttons">
		<?php echo CHtml::submitButton($model->isNewRecord ? '创 建' : '修 改'); ?>
	</div>

<?php $this->endWidget(); ?>

</div><!-- form -->