<?php
$this->pageTitle=Yii::app()->name . ' - Login';
$this->breadcrumbs=array(
	'Login',
);
?>

<table style="width:200px;margin:20px auto;">
<?php $form=$this->beginWidget('CActiveForm', array(
	'id'=>'login-form',
	'enableAjaxValidation'=>true,
)); ?>

	<tr>
	<td align="right">
		<?php echo $form->labelEx($model,'username'); ?>
	</td>
	<td>
		<?php echo $form->textField($model,'username'); ?>
		<?php echo $form->error($model,'username'); ?>
	</td>
	</tr>

	<tr>
	<td align="right">
		<?php echo $form->labelEx($model,'password'); ?>
    </td>
	<td>
		<?php echo $form->passwordField($model,'password'); ?>
		<?php echo $form->error($model,'password'); ?>
	</td>
	</tr>

	<tr>
	<td>
	</td>
	<td><?php echo $form->checkBox($model,'rememberMe'); ?>
		<?php echo $form->label($model,'rememberMe'); ?>
		<?php echo $form->error($model,'rememberMe'); ?>
	</td>
	</tr>

	<tr>
	<td>
	</td>
	<td>
		<?php echo CHtml::submitButton('登录'); ?>
	</td>
	</tr>

<?php $this->endWidget(); ?>
</table><!-- form -->
