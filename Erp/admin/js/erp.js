function E(id) {return document.getElementById(id);}
function ES(name) {return document.getElementsByName(name);}
function submitData(submit_button, form_id) {
	submit_button.disabled = "disabeld";
	E(form_id).submit();
}

function submitEnabled(name) {
	var submit_button = ES(name);
	for (var i = 0; i < submit_button.length; i++) {
		submit_button[i].disabled = '';
	}
}

function click_button(name) {
	var objs = ES(name);
	for (var i = 0; i < objs.length; i++) {
		if (objs[i] != null) {
			objs[i].click();
		}
	}
}

function setEditable(id, order_sn, value) {
	var order_type = ES("order_type_" + id + "[]");
	var is_new = ES("is_new_" + id + "[]");
	var erp_id = ES("erp_id_" + id + "[]");
	var last_update_time = ES("last_update_time_" + id + "[]");
	
	var provider_id = ES("provider_id_" + id + "[]");
	var in_sn = ES("in_sn_" + id + "[]");
	var in_sn_button = ES("in_sn_button_" + id + "[]");
	var in_sn_cancel_button = ES("in_sn_cancel_button_" + id + "[]");
	var out_sn = ES("out_sn_" + id + "[]");
	var out_sn_button = ES("out_sn_button_" + id + "[]");
	var out_sn_cancel_button = ES("out_sn_cancel_button_" + id + "[]");	
	var is_purchase_paid = ES("is_purchase_paid_" + id + "[]");
	var purchase_paid_type = ES("purchase_paid_type_" + id + "[]");
	var purchase_paid_amount = ES("purchase_paid_amount_" + id + "[]");
	var purchase_paid_time = ES("purchase_paid_time_" + id + "[]");
	var cheque = ES("cheque_" + id + "[]");
	var purchase_invoice = ES("purchase_invoice_" + id + "[]");
	var action_user = ES("action_user_" + id + "[]");
	var in_reason = ES("in_reason_" + id + "[]");
	
	var erp_goods_sn = ES("erp_goods_sn_" + id + "[]");
	var shipping_invoice = ES("shipping_invoice_" + id + "[]");
	var shipping_invoice_status = ES("shipping_invoice_status_" + id + "[]");
		
	var is_finance_paid = ES("is_finance_paid_" + id + "[]");	
	
	for (i = 0; i < last_update_time.length; i++) {
		if (order_type.length > 0) order_type[i].disabled = value;
		if (is_new.length > 0) is_new[i].disabled = value;
		if (erp_id.length > 0) erp_id[i].disabled = value;
		if (last_update_time.length > 0) last_update_time[i].disabled = value;
		
		if (provider_id.length > 0) provider_id[i].disabled = value;
		if (in_sn.length > 0) in_sn[i].disabled = value;
		if (in_sn_button.length > 0) in_sn_button[i].disabled = value;
		if (in_sn_cancel_button.length > 0) in_sn_cancel_button[i].disabled = value;
		if (out_sn.length > 0) out_sn[i].disabled = value;
		if (out_sn_button.length > 0) out_sn_button[i].disabled = value;
		if (out_sn_cancel_button.length > 0) out_sn_cancel_button[i].disabled = value;
		if (is_purchase_paid.length > 0) is_purchase_paid[i].disabled = value;
		if (purchase_paid_type.length > 0) purchase_paid_type[i].disabled = value;
		if (purchase_paid_amount.length > 0) purchase_paid_amount[i].disabled = value;
		if (purchase_paid_time.length > 0) purchase_paid_time[i].disabled = value;
		if (cheque.length > 0) cheque[i].disabled = value;
		if (purchase_invoice.length > 0) purchase_invoice[i].disabled = value;
		if (action_user.length > 0) action_user[i].disabled = value;
		if (in_reason.length > 0) in_reason[i].disabled = value;
		
//		if (erp_goods_sn.length > 0) erp_goods_sn[i].disabled = value;
		if (shipping_invoice.length > 0) shipping_invoice[i].disabled = value;		
		if (shipping_invoice_status.length > 0) shipping_invoice_status[i].disabled = value;		
		
		if (is_finance_paid > 0) is_finance_paid[i].disabled = value;		
	}
	
	if (E("order_goods_id_" + id) != null) E("order_goods_id_" + id).disabled = value;
	if (E("order_sn_" + id) != null) E("order_sn_" + id).disabled = value;
		
	if (E("pay_id_" + order_sn) != null) E("pay_id_" + order_sn).disabled = value;
	if (E("pay_status_" + order_sn) != null) E("pay_status_" + order_sn).disabled = value;
	if (E("pay_method_" + order_sn) != null) E("pay_method_" + order_sn).disabled = value;
	if (E("real_paid_set_button_" + order_sn) != null) E("real_paid_set_button_" + order_sn).disabled = value;
	if (E("real_paid_" + order_sn) != null) E("real_paid_" + order_sn).disabled = value;
	
	if (E("shipping_status_" + order_sn) != null) E("shipping_status_" + order_sn).disabled = value;
	
	if (E("carrier_id_" + order_sn) != null) E("carrier_id_" + order_sn).disabled = value;
	if (ES("bill_no_" + order_sn).length > 0) ES("bill_no_" + order_sn)[0].disabled = value;
	if (E("real_shipping_fee_" + order_sn) != null) E("real_shipping_fee_" + order_sn).disabled = value;
	if (E("proxy_amount_" + order_sn) != null) E("proxy_amount_" + order_sn).disabled = value;
	if (E("order_amount_" + order_sn) != null) E("order_amount_" + order_sn).disabled = value;
	
	if (E("note_" + order_sn) != null) E("note_" + order_sn).disabled = value;
}

function setInSn(id, value) {
	E("in_sn_" + id).value = value;
}
function setOutSn(id, value) {
	E("out_sn_" + id).value = value;
}