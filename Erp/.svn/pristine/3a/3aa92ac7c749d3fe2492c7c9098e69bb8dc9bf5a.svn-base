<div class="form">

<?php 
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
");

echo CHtml::beginForm();
?>
	<div class="row">
		<?php 
		echo 
		CHtml::label("旧密码", "MpsUser[oldpassword]"). 
		CHtml::passwordField("MpsUser[oldpassword]", '', array('size'=>15,'maxlength'=>32)); 
		?>
	</div>
    <div class="row">
        <?php 
		echo 
		CHtml::label("新密码", "MpsUser[newpassword]"). 
		CHtml::passwordField("MpsUser[newpassword]", '', array('size'=>15,'maxlength'=>32)); 
		?>
	</div>
	<div class="row">
	    <?php 
		echo 
		CHtml::label("确认新密码", "MpsUser[reenterpassword]"). 
		CHtml::passwordField("MpsUser[reenterpassword]", '', array('size'=>15,'maxlength'=>32)); 
		?>
	</div>
	<div class="row buttons">
		<?php echo CHtml::submitButton('修改密码'); ?>
	</div>

<?php echo CHtml::endForm(); ?>

</div><!-- form -->
