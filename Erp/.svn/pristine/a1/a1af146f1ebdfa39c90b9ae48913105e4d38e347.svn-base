<?php
header('Content-type: text/html; charset=utf-8');
?>
<a href="generate_sale_order.php">
	生成先款后货订单(需要在testshop上登陆)
</a>
<br />
<a href="generate_sale_order.php?type=cod">
	生成货到付款订单(需要在testshop上登陆)
</a>
<br />
<form action="order_flow.php">
	订单号 <input name="order_sn" />
	<input type="hidden"  name="action" value="order_confirm" />
	<input type="submit" name="go" value="将订单跳到客服确认" /> （先款后货订单跳到已付款，订单自动确认）
</form>
<br />
<form action="order_flow.php">
	订单号 <input name="order_sn" />
	<input type="hidden"  name="action" value="dph" />
	<input  type="submit" name="go" value="将订单跳到物流配货" />
</form>
<br />
<form action="order_flow.php">
	订单号 <input name="order_sn" />
	<input type="hidden"  name="action" value="dc" />
	<input  type="submit" name="go" value="将订单跳到物流待发货" />
</form>
<br />
<form action="order_flow.php">
	订单号 <input name="order_sn" />
	<input type="hidden"  name="action" value="shipping" />
	<input  type="submit" name="go" value="将订单跳到物流发货" />
</form>
<br />
<form action="order_flow.php">
	订单号 <input name="order_sn" />
	<input type="hidden" name="action"  value="user_confirm" />
	<input  type="submit" name="go" value="将订单跳到用户确认收货" />
</form>