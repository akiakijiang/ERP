<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>乐其ERP - 登录</title>
    <?php 
        Yii::app()->getClientScript()->registerCoreScript('jquery');
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" type="text/css" href="<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/resources/css/ext-all.css"/>
    <style type="text/css">
        #msg-div {
            position:absolute;
            left:35%;
            top:10px;
            width:250px;
            z-index:20000;
        }
    </style>
    <script type="text/javascript" src="<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/adapter/jquery/ext-jquery-adapter.js"></script>
    <?php if(YII_DEBUG):?>
    <script type="text/javascript" src="<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/ext-all-debug.js"></script>
    <?php ;else:?>
    <script type="text/javascript" src="<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/ext-all.js"></script>
    <?php endif;?>
    <script type="text/javascript" src="<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/src/locale/ext-lang-zh_CN.js"></script>
	<script type="text/javascript">
	Ext.BLANK_IMAGE_URL = '<?php echo Yii::app()->getAssetManager()->getBaseUrl();?>/ext-3.2.1/resources/images/default/s.gif';
	
	Ext.example = function(){
	    var msgCt;

	    function createBox(t, s){
	        return ['<div class="msg">',
	                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
	                '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
	                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
	                '</div>'].join('');
	    }
	    return {
	        msg : function(title, format){
	            if(!msgCt){
	                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
	            }
	            msgCt.alignTo(document, 't-t');
	            var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
	            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);
	            m.slideIn('t').pause(2).ghost("t", {remove:true});
	        }
	    };
	}();
	
	Ext.onReady(function(){
	    var loginForm = new Ext.form.FormPanel({ 
	        id:'login-form',
	        frame:true,
	        width:300,
	        title:'乐其ERP用户登录',
	        labelAlign:'top',
	        defaults:{width:285}
	    });
	
	    var onClick = function(button,e){
	        if (loginForm.getForm().isValid()) {
	        	loginForm.getEl().mask('验证中...');
	        	loginForm.getForm().submit({
		            url:'<?php echo $this->createUrl('login') ?>',
		            method:'POST',
		            success:function(form,action){
		        	    loginForm.getEl().unmask();
		                if (action.result.success){
		                    window.location = '<?php echo Yii::app()->user->returnUrl?>'
		                } else{
		                    Ext.example.msg('登录失败', Ext.isArray(action.result.msg)?action.result.msg[0]:action.result.msg);
		                }
		            },
		            failure:function(form,action){
		            	loginForm.getEl().unmask();
		                switch(action.failureType) {
		                case Ext.form.Action.CLIENT_INVALID:
		                    Ext.example.msg('登录失败', '请填写完整的信息');
		                    break;
		                case Ext.form.Action.CONNECT_FAILURE:
		                    Ext.example.msg('登录失败', '与服务器通讯失败');
		                    break;
		                case Ext.form.Action.SERVER_INVALID:
		                	Ext.example.msg('登录失败', Ext.isArray(action.result.msg)?action.result.msg[0]:action.result.msg);
		                    break;
		                }
		            }
		        });
	        }
	    };
	    loginForm.add({
	        xtype:'textfield',
	        fieldLabel:'<?php echo $model->getAttributeLabel('username')?>',
	        name:'LoginForm[username]',
	        allowBlank:false
	    });
	    loginForm.add({
	        xtype:'textfield',
	        fieldLabel:'<?php echo $model->getAttributeLabel('password')?>',
	        name:'LoginForm[password]',
	        inputType:'password',
	        allowBlank:false,
	        enableKeyEvents:true,
	        listeners:{
	            'keyup':{
	                fn:function(textfield,e){
	                    if (e.getKey()==Ext.EventObject.ENTER){
	                    	onClick();
	                    }
	                },
	                buffer:200
	            }
	        }
	    });
	    loginForm.addButton({
	        text:'登录',
	        handler:onClick
	    });
	
	    new Ext.Viewport({
	        items:[loginForm]
	    });
	
	    // 居中
	    Ext.getCmp('login-form').getEl().center();
	    Ext.getCmp('login-form').getEl().setY(150);
	});

	</script>
</head>
<body>

</body>
</html>