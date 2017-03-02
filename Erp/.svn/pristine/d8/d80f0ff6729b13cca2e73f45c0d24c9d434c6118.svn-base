<html>
<head>
<meta name=Title content="">
<meta name=Keywords content="">
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<meta name=ProgId content=Word.Document>
<meta name=Generator content="Microsoft Word 14">
<meta name=Originator content="Microsoft Word 14">
<style>
    body {
        font-size: 14px;
    }
    
    table td {
        padding: 5px;
    }
</style>
</head>

<body lang=ZH-CN style=''>

<div class=WordSection1 style='layout-grid: 15.6pt'>

<table class=MsoTableGrid border=1 cellspacing=0 cellpadding=0
	style='border-collapse: collapse; width: 600px'>
	<tr>
		<td width=212 colspan=2>
		<p class=MsoNormal align=center style='text-align: center'>
            <?php 
            echo 
            strtoupper($dispatchList['dispatch_sn']) . "-" . 
            $dispatchList['price'] . "-" .  
            $dispatchList['order_sn'] . "-" .
            $dispatchList['goods_id'];
            ?> 
			<br/>
            <img src="<?php echo $this->createUrl('mps/barcode', array('barcode'=> $dispatchList['dispatch_sn'], 'height'=>60)); ?>" />
		</p>
		</td>
		<td width=214 valign=top>
		<p class=MsoNormal>下单日期：<?php echo date("Y-m-d", strtotime($dispatchList['order_time'])); ?></p>
		<p class=MsoNormal>交货日期：<?php echo date("Y-m-d", strtotime($dispatchList['due_date'])); ?></p>
		<?php if ($dispatchList['important_day']) { ?><p class=MsoNormal>婚期：<?php echo $dispatchList['important_day']; ?></p><?php } ?>
		<p class=MsoNormal>发货索引：<?php echo $dispatchList['external_order_sn']; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>size：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset( $attributes['goodsStyle_size']) ? $attributes['goodsStyle_size'] : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>胸围：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_bust']) ? $attributes['goodsStyle_bust'] : ''; ?></p>
		</td>
		<td width=214 rowspan=11 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['note']) ? str_replace("\n", '<br />',CHtml::encode($attributes['note'])) : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>腰围：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_waist']) ? $attributes['goodsStyle_waist'] : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>臀围：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_hips']) ? $attributes['goodsStyle_hips'] : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>肩到胸：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>肩到腰：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>喉咙到下摆：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_hollow_to_hem']) ? $attributes['goodsStyle_hollow_to_hem'] : '';?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>喉咙到地：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_hollow_to_floor']) ? $attributes['goodsStyle_hollow_to_floor'] : '' ; ?>（不含鞋高）</p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>身高：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>颜色：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_color']) ? $attributes['goodsStyle_color'] : '' ; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>腰带颜色：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_sash_color']) ? $attributes['goodsStyle_sash_color'] : '' ; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>腰带长：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_sash_size']) ? $attributes['goodsStyle_sash_size'] : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=95 valign=top>
		<p class=MsoNormal>面料：</p>
		</td>
		<td width=117 valign=top>
		<p class=MsoNormal><?php echo isset($attributes['goodsStyle_textures']) ? $attributes['goodsStyle_textures'] : ''; ?></p>
		</td>
	</tr>
	<tr>
		<td width=426 colspan=3 valign=top>
		<p class=MsoNormal>备注：尺寸为英寸。</p>
		</td>
	</tr>
</table>

<p class=MsoNormal>
<?php 
foreach($imgAttributes as $imgSrc) 
{
    $imgSrc = str_replace('imgerp.leqee.com', 'imgerp.aaasea.com', $imgSrc);
    echo "<img src=\"{$imgSrc}\" border=\"0\"	style=\"max-width: 600px;\" /> ";
} 
?>
</p>

</div>

<p style="text-align: center; left: 620px; top:20px; position: fixed; z-index: 100000" id="xxx"><input
	class="btn" type="button" value=" 返回 " onClick="history.back();">
	<input type="button" class="btn" value="打印" onclick="document.getElementById('xxx').style.display='none';window.print();">
</p>

</body>
</html>
