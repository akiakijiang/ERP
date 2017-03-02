// RF扫描枪键盘监听事件，页面跳转事件
// ljzhou 2013.08.05


var RF_SCAN_KEY = 'F4'; // 扫描枪触发的按键

var KEY = new Array(8,13,16,17,37,38,39,40,112,113,114,115,173,190);
// F1,F2,F3,F4,上下左右 特殊处理
var KEY_F = new Array(37,38,39,40,112,113,114,115);

var KEY_MAP = {
	"8": "BACKSPACE",  // Backspace
    "13": "ENTER",  // ENTER
    "16": "SHIFT",     // SHIFT
    "17": "CTRL",   // CTRL
    "37": "LEFT",   // LEFT
    "38": "UP",     // UP
    "39": "RIGHT",  // RIGHT
    "40": "DOWN",   // DOWN
    "112": "F1",    // F1
    "113": "F2",    // F2
    "114": "F3",    // F3
    "115": "F4",    // F3
    "190": ".",     // .
};


var EVENT_MAP = {
    "F1": "FROM_LOCATION_OVER",        // ENTER
    //"CTRL": "GROUNDING_OVER",    // CTRL
    //".": "TO_LOCATION_OVER",    // F2
    "F2": "GOODS_OVER",        // F2
    "F3": "TO_LOCATION_OVER",    // F3
    "LEFT": "PAGE_LEFT",   // LEFT
    "RIGHT": "PAGE_RIGHT",  // RIGHT
    "UP": "MOUSE_UP",     // UP
    "DOWN": "MOUSE_DOWN",   // DOWN
 };

var RECEIVE_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"to_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"validity"},
	"validity":      {"mouse_up":"goods_number","mouse_down":"validity"},
};

var RECEIVE_PAGE_FOCUS = {
	"from_location":"from_location",
	"to_location":"to_location",
	"goods_barcode":"goods_barcode",
};

// to_location,goods_barcode只是为了完结时鼠标定位
var RECEIVE_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"to_location":   {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};

var GROUDING_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"from_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"goods_number","mouse_down":"to_location"},
};

var GROUDING_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
	"to_location":"to_location",
};

var GROUDING_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"to_location":   {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};


// 通用上架RF枪扫描(-t,-h,-gh)
var COMMON_GROUDING_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"from_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"goods_number","mouse_down":"to_location"},
};

var COMMON_GROUDING_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
	"to_location":"to_location",
};

// to_location,goods_barcode只是为了完结时鼠标定位
var COMMON_GROUDING_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"to_location":   {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};

//通用下架RF枪扫描(-gt,-gh)
var COMMON_UNDERCARRIAGE_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"to_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"goods_number"},
};

var COMMON_UNDERCARRIAGE_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
	"to_location":"to_location",
};

// to_location,goods_barcode只是为了完结时鼠标定位
var COMMON_UNDERCARRIAGE_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"to_location":   {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};


var MOVING_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"from_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"goods_number","mouse_down":"to_location"},
};

var MOVING_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
	"to_location":"to_location",
};

var MOVING_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"to_location":   {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};

var BATCH_PICK_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"to_location"},
	"to_location":   {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"to_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"goods_number"},
};

var BATCH_PICK_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"to_location",
};

var BATCH_PICK_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"goods_barcode","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"goods_barcode","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"goods_barcode","current_page":"goods_barcode"},
	"end_page":      {"left_page":"from_location","right_page":"goods_barcode","current_page":"goods_barcode"},
};


var TAKE_STOCK_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"from_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"validity"},
	"validity":      {"mouse_up":"goods_number","mouse_down":"validity"},
};

var TAKE_STOCK_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
};

var TAKE_STOCK_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"goods_barcode","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"goods_barcode","current_page":"from_location"},
	"goods_barcode": {"left_page":"from_location","right_page":"goods_barcode","current_page":"goods_barcode"},
	"end_page":      {"left_page":"from_location","right_page":"goods_barcode","current_page":"goods_barcode"},
};

// 简单盘点界面
var TAKE_STOCK_SIMPLE_STEP_MAP = {
	"from_location": {"mouse_up":"from_location","mouse_down":"goods_barcode"},
	"goods_barcode": {"mouse_up":"from_location","mouse_down":"goods_number"},
	"goods_number":  {"mouse_up":"goods_barcode","mouse_down":"goods_number"},
};

var TAKE_STOCK_SIMPLE_PAGE_FOCUS = {
	"from_location":"from_location",
	"goods_barcode":"goods_barcode",
};

var TAKE_STOCK_SIMPLE_PAGE_CHANGE = {
	"start_page":    {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"from_location": {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
	"end_page":      {"left_page":"from_location","right_page":"from_location","current_page":"from_location"},
};
	
	
// 需要动态显示的一些id
var SHOW_ALL_IDS = new Array("tr_from_location","tr_from_location_note","tr_serial_number","tr_serial_number_note","tr_to_location","tr_to_location_note","tr_cur_position","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note","tr_validity","tr_validity_note","tr_current_page","tr_current_func");

//收货入库所有输入框的id
var SHOW_RECEIVE_ALL_IDS = new Array("from_location","from_location_note","to_location","to_location_note","cur_position","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");
var RECEIVE_SHOW_IDS = new Array();
RECEIVE_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_to_location","tr_to_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note");


//收货上架所有输入框的id
var SHOW_GROUDING_ALL_IDS = new Array("from_location","from_location_note","cur_position","goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
var GROUDING_SHOW_IDS = new Array();
GROUDING_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note","tr_to_location","tr_to_location_note");

//通用上架所有输入框的id
var SHOW_COMMON_GROUDING_ALL_IDS = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note","to_location","to_location_note");
var COMMON_GROUDING_SHOW_IDS = new Array();
COMMON_GROUDING_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note","tr_to_location","tr_to_location_note");

//通用下架所有输入框的id
var SHOW_COMMON_UNDERCARRIAGE_ALL_IDS = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
var COMMON_UNDERCARRIAGE_SHOW_IDS = new Array();
COMMON_UNDERCARRIAGE_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_to_location","tr_to_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note");

var SHOW_MOVING_ALL_IDS = new Array("from_location","goods_barcode","to_location");
var MOVING_SHOW_IDS = new Array();
MOVING_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note","tr_to_location","tr_to_location_note");
//MOVING_SHOW_IDS['goods_barcode'] = new Array("tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note");
//MOVING_SHOW_IDS['to_location'] = new Array("tr_to_location","tr_to_location_note");

var BATCH_PICK_SHOW_IDS = new Array();
BATCH_PICK_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note");
BATCH_PICK_SHOW_IDS['goods_barcode'] = new Array("tr_to_location","tr_to_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note");

var TAKE_STOCK_SHOW_IDS = new Array();
TAKE_STOCK_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note");
TAKE_STOCK_SHOW_IDS['goods_barcode'] = new Array("tr_to_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note","tr_validity","tr_validity_note");

// 简单盘点界面
//所有输入框的id
var SHOW_TAKE_STOCK_SIMPLE_ALL_IDS = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
var TAKE_STOCK_SIMPLE_SHOW_IDS = new Array();
TAKE_STOCK_SIMPLE_SHOW_IDS['from_location'] = new Array("tr_from_location","tr_from_location_note","tr_goods_barcode","tr_goods_barcode_note","tr_goods_number","tr_goods_number_note");


// 收货数据初始化数组
// 1、采购订单级别清空，会把上架容器级别，条码级别清空 2、上架容器级别清空，会把上架容器级别，条码级别清空 3、条码级别只清空条码级别
var RECEIVE_DATA_CLEAR_IDS = new Array();
RECEIVE_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");
RECEIVE_DATA_CLEAR_IDS['to_location'] = new Array("to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");
RECEIVE_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");

//上架数据初始化数组
//1、上架界面清空，会把条码界面，储位容器界面清空 2、条码界面清空，会把储位界面清空 3、储位界面只清空储位界面
var GROUDING_DATA_CLEAR_IDS = new Array();
GROUDING_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
GROUDING_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
GROUDING_DATA_CLEAR_IDS['to_location'] = new Array("to_location","to_location_note");

//通用上架数据初始化数组
//1、上架界面清空，会把条码界面，储位容器界面清空 2、条码界面清空，会把储位界面清空 3、储位界面只清空储位界面
var COMMON_GROUDING_DATA_CLEAR_IDS = new Array();
COMMON_GROUDING_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");
COMMON_GROUDING_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note","to_location","to_location_note");
COMMON_GROUDING_DATA_CLEAR_IDS['to_location'] = new Array("to_location","to_location_note");

//通用下架数据初始化数组
//1、上架界面清空，会把条码界面，储位容器界面清空 2、条码界面清空，会把储位界面清空 3、储位界面只清空储位界面
var COMMON_UNDERCARRIAGE_DATA_CLEAR_IDS = new Array();
COMMON_UNDERCARRIAGE_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","serial_number","serial_number_note","validity","validity_note");
COMMON_UNDERCARRIAGE_DATA_CLEAR_IDS['to_location'] = new Array("to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note");
COMMON_UNDERCARRIAGE_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note");


//移库数据初始化数组
//1、起始容器界面清空，会把条码界面，目的容器界面清空 2、条码界面清空，会把目的容器界面清空 3、目的容器界面清空，只清空目的容器界面
var MOVING_DATA_CLEAR_IDS = new Array();
MOVING_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
MOVING_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note","to_location","to_location_note");
MOVING_DATA_CLEAR_IDS['to_location'] = new Array("to_location","to_location_note");

// 盘点界面
var BATCH_PICK_DATA_IDS = new Array();
BATCH_PICK_DATA_IDS['from_location'] = new Array("from_location","from_location_note");
BATCH_PICK_DATA_IDS['goods_barcode'] = new Array("to_location","to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note");

var TAKE_STOCK_DATA_IDS = new Array();
TAKE_STOCK_DATA_IDS['from_location'] = new Array("from_location","from_location_note");
TAKE_STOCK_DATA_IDS['goods_barcode'] = new Array("to_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note","validity","validity_note");

//简单盘点数据初始化数组
//1、起始容器界面清空，会把所有数据清空 2、商品结束：清除商品条码，数量
var TAKE_STOCK_SIMPLE_DATA_CLEAR_IDS = new Array();
TAKE_STOCK_SIMPLE_DATA_CLEAR_IDS['from_location'] = new Array("from_location","from_location_note","goods_barcode","goods_barcode_note","goods_number","goods_number_note");
TAKE_STOCK_SIMPLE_DATA_CLEAR_IDS['goods_barcode'] = new Array("goods_barcode","goods_barcode_note","goods_number","goods_number_note");


// 批拣的一些参数
var BATCH_PICK_PATH = new Array(); // 批拣路线保存数组
var PICK_LOC_POS = 0; // 当前的容器在循环中的位置
var PICK_GOODS_POS = 0; // 当前的商品在容器中循环的位置
var PAGE_INDICATE = 'current_page'; // 页面跳转指令

//////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////// 功能函数部分/////////////////////////////////////////
//数据初始化
function data_init() {

    var current_func = $('#current_func').val();//得到当前页面
    
    if(current_func == 'receive') {
    	STEP_MAP = RECEIVE_STEP_MAP;
    	PAGE_FOCUS = RECEIVE_PAGE_FOCUS;
    	PAGE_CHANGE = RECEIVE_PAGE_CHANGE;
    	SHOW_IDS = RECEIVE_SHOW_IDS;
    	DATA_CLEAR_IDS = RECEIVE_DATA_CLEAR_IDS;
        set_up_calendar("validity","validity_trigger");

    } else if(current_func == 'grouding') {
    	STEP_MAP = GROUDING_STEP_MAP;
    	PAGE_FOCUS = GROUDING_PAGE_FOCUS;
    	PAGE_CHANGE = GROUDING_PAGE_CHANGE;
    	SHOW_IDS = GROUDING_SHOW_IDS;
    	DATA_CLEAR_IDS = GROUDING_DATA_CLEAR_IDS;
    	
    	// 去掉储位容器完结的按钮
    	$('#td_btn_to_location_over').hide();
    } else if(current_func == 'common_grouding') {
    	STEP_MAP = COMMON_GROUDING_STEP_MAP;
    	PAGE_FOCUS = COMMON_GROUDING_PAGE_FOCUS;
    	PAGE_CHANGE = COMMON_GROUDING_PAGE_CHANGE;
    	SHOW_IDS = COMMON_GROUDING_SHOW_IDS;
    	DATA_CLEAR_IDS = COMMON_GROUDING_DATA_CLEAR_IDS;
    	set_up_calendar("validity","validity_trigger");

    } else if(current_func == 'common_undercarriage') {
    	STEP_MAP = COMMON_UNDERCARRIAGE_STEP_MAP;
    	PAGE_FOCUS = COMMON_UNDERCARRIAGE_PAGE_FOCUS;
    	PAGE_CHANGE = COMMON_UNDERCARRIAGE_PAGE_CHANGE;
    	SHOW_IDS = COMMON_UNDERCARRIAGE_SHOW_IDS;
    	DATA_CLEAR_IDS = COMMON_UNDERCARRIAGE_DATA_CLEAR_IDS;
    	set_up_calendar("validity","validity_trigger");

    } else if(current_func == 'moving') {
    	STEP_MAP = MOVING_STEP_MAP;
    	PAGE_FOCUS = MOVING_PAGE_FOCUS;
    	PAGE_CHANGE = MOVING_PAGE_CHANGE;
    	SHOW_IDS = MOVING_SHOW_IDS;
    	DATA_CLEAR_IDS = MOVING_DATA_CLEAR_IDS;

    } else if(current_func == 'batch_pick') {
    	STEP_MAP = BATCH_PICK_STEP_MAP;
    	PAGE_FOCUS = BATCH_PICK_PAGE_FOCUS;
    	PAGE_CHANGE = BATCH_PICK_PAGE_CHANGE;	
    	SHOW_IDS = BATCH_PICK_SHOW_IDS;

    } else if(current_func == 'take_stock') {
    	STEP_MAP = TAKE_STOCK_STEP_MAP;
    	PAGE_FOCUS = TAKE_STOCK_PAGE_FOCUS;
    	PAGE_CHANGE = TAKE_STOCK_PAGE_CHANGE;
    	SHOW_IDS = TAKE_STOCK_SHOW_IDS;

        set_up_calendar("validity","validity_trigger");

    } else if(current_func == 'take_stock_simple') {
    	STEP_MAP = TAKE_STOCK_SIMPLE_STEP_MAP;
    	PAGE_FOCUS = TAKE_STOCK_SIMPLE_PAGE_FOCUS;
    	PAGE_CHANGE = TAKE_STOCK_SIMPLE_PAGE_CHANGE;
    	SHOW_IDS = TAKE_STOCK_SIMPLE_SHOW_IDS;
    	DATA_CLEAR_IDS = TAKE_STOCK_SIMPLE_DATA_CLEAR_IDS;

    }
    
	$('#current_page').val('from_location');
    show_hide_ids(SHOW_IDS['from_location']);
    //$('#action_button').hide();
    
}


// 表单元素的显示与隐藏
function show_hide_ids(PAGE_SHOW_IDS) {
	for(var i=0;i<SHOW_ALL_IDS.length;i++) {
		var flag = false;
		for(var j=0;j<PAGE_SHOW_IDS.length;j++) {
			if(SHOW_ALL_IDS[i]==PAGE_SHOW_IDS[j]) {
				$('#'+SHOW_ALL_IDS[i]).show();
				flag=true;
				break;
			}
		}
		if(!flag) {
			$('#'+SHOW_ALL_IDS[i]).hide();
		}
	}
}

//新页面表单数据的初始化
function page_data_init(PAGE_DATA_IDS) {
	for(var i=0;i<PAGE_DATA_IDS.length;i++) {
		$('#'+PAGE_DATA_IDS[i]).val('');
	}
}

//新页面数据清空导致连带的其他页面数据清空
function page_data_clear(page_indicate) {
	page_data_init(DATA_CLEAR_IDS[page_indicate]);
}

function action_event(event,location_id) {
	 var action = EVENT_MAP[KEY_MAP[event.keyCode]];
	 action_event_target(action,location_id);
}

function action_event_target(action,location_id) {
	 if(action == "GOODS_OVER") {
		 sub_goods_over();
	 } else if(action == "TO_LOCATION_OVER") {
		 sub_to_location_over();
	 } else if(action == "FROM_LOCATION_OVER") {
		 sub_from_location_over();
	 } else if(action == "PAGE_LEFT") {
		 sub_page_left();
	 } else if(action == "PAGE_RIGHT") {
		 sub_page_right();
	 } else if(action == "MOUSE_UP") {
		 sub_mouse_up(location_id);
	 } else if(action == "MOUSE_DOWN") {
		 sub_mouse_down(location_id);
	 }
}

/**
 * 扫描动作
 * 把扫描的串号自动匹配到订单的商品，没有匹配则报错
 */
function scan(event)
{   
	if ($.inArray(event.keyCode,KEY_F) == -1) {
		return;
	}

    var location = $(this);
    var barcode = $.trim(location.val());
    var location_id = location.attr('id');
    if (barcode != '') {
    	if(location_id == 'from_location') {
        	from_location_scan(event,barcode,location_id);
    	} else if(location_id == 'to_location') {
        	to_location_scan(event,barcode,location_id);
    	} else if(location_id == 'goods_barcode') {
        	goods_barcode_scan(event,barcode,location_id);
    	} else if(location_id == 'serial_number') {
    		serial_number_scan(event,barcode,location_id);
    	} else if(location_id == 'goods_number') {
        	goods_number_scan(event,barcode,location_id);
    	} else if(location_id == 'validity') {
        	validity_scan(event,barcode,location_id);
    	}
    } 
    else {
    	var note = '#'+location_id+'_note';
    	$(note).val("条码不能为空");
    }
}


/**
 * 检测是否串号控制
 */
 function check_goods_is_serial(party_id,goods_barcode)
 {
	 if(!party_id) {
		 alert('检测是否串号控制组织号为空');
		 return false;
	 }
	 if(!goods_barcode) {
		 alert('检测是否串号商品条码为空');
		 return false;
	 }
	 //alert('batch_order_sn:'+batch_order_sn+' goods_barcode:'+goods_barcode);
     var result = new Array();
     $.ajax({
         async : false,
         type: 'POST',
         dataType: 'json',
         url : 'ajax.php?act=check_goods_is_serial', 
         data: 'party_id=' + party_id +'&goods_barcode=' + goods_barcode,
         error: function() { alert("检测是否串号控制ajax请求错误goods_barcode:"+goods_barcode); result['success'] =  false; },
         success: function(data) {
	       if(data['success']) {
	       	  if(data['res']) {
	       		  result['res'] = true;
	       	  } else {
	       		  result['res'] = false;
	       	  }
	       	  result['success'] = true;
	       } else {
	    	   alert(data['error']);
	    	   result['success'] =  false;
	       }
         }
     }); 
     return result;
     
 }
 
 //获取串号商品容器上剩余的数量(针对串号控制的情况)
 function get_location_serail_goods_number(location_barcode,goods_barcode,serial_number)
 {
	  if(!location_barcode || !goods_barcode || !serial_number) {
	   	   alert('获取串号商品容器上剩余的数量时容器号或商品条码或串号为空');
	   	   return;
	  }
      var result = Array();
      $.ajax({
         async : false,
         type: 'POST',
         dataType: 'json',
         url : 'ajax.php?act=get_location_serail_goods_number', 
         data: 'location_barcode=' + location_barcode +'&goods_barcode=' + goods_barcode+'&serial_number=' + serial_number,
         error: function() { alert("获取串号商品未上架数ajax请求错误goods_barcode:"+goods_barcode); result['success'] = false; },
         success: function(data) {
	   	       if(data.success) {
	   	       	  result['goods_number'] = data.goods_number;
	   	       	  result['success'] = true;
	   	       } else {
	   	    	   result['error'] = data.error;
	   	    	   result['success'] =  false;
	   	       }
         }
      }); 
      return result;
 }

 
 /**
  * 通过验证商品是否需要维护
  */
  function check_maintain_warranty(party_id,barcode)
  {
     // 双十一前收货先 不维护生产日期,因为基本上1库位
//	 return false;
	 
 	 if(!party_id || !barcode) {
 		 alert('检测有效期控制时条码或组织为空');
 		 return;
 	 }
      var result = false;
      $.ajax({
          async : false,
          type: 'POST',
          dataType: 'json',
          url : 'ajax.php?act=check_maintain_warranty', 
          data: 'barcode=' + barcode +'&party_id=' + party_id,
          error: function() { alert("检查有效期ajax请求错误"); result = false; },
          success: function(data) {
        	  if(data == true) {
        		  result = true;
        	  }
          }
      }); 
      return result;
  }

function check_validity_format(validity) {
	var exp = /^[1-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/;
	if(!exp.test(validity)) {
		return false;
	}
	return true;
}

// 盘点专用检测数字
function check_take_stock_input_number(number) {
	var exp = /^(-)?[1-9][0-9]*$/;
	if(!exp.test(number)) {
		alert('输入的数字格式不对！');
		return false;
	}
	
	return true;
}

// 通用版检测数字
function check_input_number(number) {
	var exp = /^[0]$|^[1-9][0-9]*$/;
	if(!exp.test(number)) {
		alert('输入的数字格式不对！');
		return false;
	}
	
	return true;
}

// 显示生产日期框
function sub_validity_show() {
	$('#tr_validity').show();
	$('#tr_validity_note').show();
}

//隐藏生产日期框
function sub_validity_hide() {
	$('#tr_validity').hide();
	$('#tr_validity_note').hide();
}

//显示串号
function sub_serial_number_show() {
	$('#tr_serial_number').show();
	$('#tr_serial_number_note').show();
}

//隐藏串号
function sub_serial_number_hide() {
	$('#tr_serial_number').hide();
	$('#tr_serial_number_note').hide();
}

//得到当前操作功能
function get_current_func() {
	var current_func = $('#current_func').val();
	return current_func;
}

//得到当前操作页面
function get_current_page() {
	var current_page  = $('#current_page').val();
	return current_page;
}

//起始容器完结
var from_location_over_lock = true;
function sub_from_location_over() {
	
	if(!from_location_over_lock) {
		alert('起始容器完结还在进行，请勿重复提交！');
		return false;
	}
	from_location_over_lock = false;
	// 页面数据初始化
	page_data_clear('from_location');
	
	 // 页面跳转
	sub_page_change('from_location','current_page');
	from_location_over_lock = true;
}

// 商品完结
var goods_over_lock = true; // 对操作进行加锁
function sub_goods_over() {
	if(!goods_over_lock) {
		alert('商品完结还在进行，请勿重复提交！');
		return false;
	}
	goods_over_lock = false;
	
	//商品完结中不允许做修改的动作
	//批量检查输入数据的合法性
	if(!sub_not_allow_edit()){
		sub_allow_edit();
		goods_over_lock = true;
		return false;
	}
	// 商品结束触发动作
	if(!action_goods_over()) {
		sub_allow_edit();
		goods_over_lock = true;
		return false;
	}
	//商品完结后允许编辑操作
	
	//判断操作是否完成，回到from_location界面
	if(check_action_finish()) {
		sub_allow_edit();
		sub_from_location_over();
		goods_over_lock = true;
		return false;
	}
	// 页面数据初始化
	page_data_clear('goods_barcode');
	sub_allow_edit();

	// 页面跳转,要放最后，不然鼠标定位有问题
	sub_page_change('goods_barcode','current_page');
	goods_over_lock = true;
}


//目的容器完结
var to_location_over_lock = true; // 对操作进行加锁
function sub_to_location_over() {
	if(!to_location_over_lock) {
		alert('目的容器完结还在进行，请勿重复提交！');
		return false;
	}
	to_location_over_lock = false;
	
	// 上架不需要这个快捷键
	if(get_current_func() == 'grouding' || get_current_func() =='moving') {
		to_location_over_lock = true;
		return false;
	}
	
	// 执行相应的动作
	action_to_location_over();
	
	//判断操作是否完成，回到from_location界面
	if(check_action_finish()) {
		sub_from_location_over();
		to_location_over_lock = true;
		return false;
	}
	
	// 页面数据初始化
	page_data_clear('to_location');
	
	// 页面跳转,要放最后，不然鼠标定位有问题
	sub_page_change('to_location','current_page');
	
	to_location_over_lock = true;
}

//商品完结中不允许做修改的动作，并且批量检查输入数据的合法性
function sub_not_allow_edit(){
	var current_func = get_current_func();
	if(current_func == 'receive') {
		for(var i=0; i<SHOW_RECEIVE_ALL_IDS.length; i++){
			$('#'+SHOW_RECEIVE_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_receive_input()){
			return false;
		}
	} else if(current_func == 'grouding') {
		for(var i=0; i<SHOW_GROUDING_ALL_IDS.length; i++){
			$('#'+SHOW_GROUDING_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_grouding_input()){
			return false;
		}
	} else if(current_func == 'common_grouding') {
		for(var i=0; i<SHOW_COMMON_GROUDING_ALL_IDS.length; i++){
			$('#'+SHOW_COMMON_GROUDING_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_common_grouding_input()){
			return false;
		}
	} else if(current_func == 'common_undercarriage') {
		for(var i=0; i<SHOW_COMMON_UNDERCARRIAGE_ALL_IDS.length; i++){
			$('#'+SHOW_COMMON_UNDERCARRIAGE_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_common_undercarriage_input()){
			return false;
		}
	} else if(current_func == 'moving') {
		for(var i=0; i<SHOW_MOVING_ALL_IDS.length; i++){
			$('#'+SHOW_MOVING_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_moving_input()){
			return false;
		}
	} else if(current_func == 'batch_pick') {
	} else if(current_func == 'take_stock') {
	} else if(current_func == 'take_stock_simple') {
		for(var i=0; i<SHOW_TAKE_STOCK_SIMPLE_ALL_IDS.length; i++){
			$('#'+SHOW_TAKE_STOCK_SIMPLE_ALL_IDS[i]).attr('disabled',true);
		}
		if(!check_all_take_stock_simple_input()){
			return false;
		}
	}
	return true;
}
//商品完结后允许编辑操作
function sub_allow_edit(){
	var current_func = get_current_func();
	if(current_func == 'receive') {
		for(var i=0; i<SHOW_RECEIVE_ALL_IDS.length; i++){
			$('#'+SHOW_RECEIVE_ALL_IDS[i]).attr('disabled',false);
		}
	} else if(current_func == 'grouding') {
		for(var i=0; i<SHOW_GROUDING_ALL_IDS.length; i++){
			$('#'+SHOW_GROUDING_ALL_IDS[i]).attr('disabled',false);
		}
	} else if(current_func == 'common_grouding') {
		for(var i=0; i<SHOW_COMMON_GROUDING_ALL_IDS.length; i++){
			$('#'+SHOW_COMMON_GROUDING_ALL_IDS[i]).attr('disabled',false);
		}
	} else if(current_func == 'common_undercarriage') {
		for(var i=0; i<SHOW_COMMON_UNDERCARRIAGE_ALL_IDS.length; i++){
			$('#'+SHOW_COMMON_UNDERCARRIAGE_ALL_IDS[i]).attr('disabled',false);
		}
	} else if(current_func == 'moving') {
		for(var i=0; i<SHOW_MOVING_ALL_IDS.length; i++){
			$('#'+SHOW_MOVING_ALL_IDS[i]).attr('disabled',false);
		}
	} else if(current_func == 'batch_pick') {
	} else if(current_func == 'take_stock') {
	} else if(current_func == 'take_stock_simple') {
		for(var i=0; i<SHOW_TAKE_STOCK_SIMPLE_ALL_IDS.length; i++){
			$('#'+SHOW_TAKE_STOCK_SIMPLE_ALL_IDS[i]).attr('disabled',false);
		}
	}      
	
}
//批量检查收货入库输入项合法性
function check_all_receive_input(){
	var batch_order_sn = $("#from_location").val();
	var grouding_location_barcode = $("#to_location").val();
	var goods_barcode = $("#goods_barcode").val();
	var goods_number = $("#goods_number").val();
	var validity = $("#validity").val();
	var serial_number = $("#serial_number").val();
	
	//订单号检测
//	if(batch_order_sn == ''){
//		alert("");
//	}
	if(!check_batch_order_sn(batch_order_sn,grouding_location_barcode)){
	   return false;
	}
	//上架容器检测
	if(!check_grouding_location_barcode_party(batch_order_sn,grouding_location_barcode,'IL_GROUDING')){
	   return false;
	}
    // 数字格式检查
    if(!check_input_number(goods_number)) {
    	return false;
    }
    
	//商品条码检测
	var check_goods_barcode = check_receive_goods_barcode(batch_order_sn,goods_barcode);
	if(!check_goods_barcode['success']){
		return false;
	}
	//先判断是否串号控制
	if(check_goods_barcode['is_serial']){
		//商品串号检测
		if(!check_receive_serial_number(serial_number)){
		    return false;
	    }
		if(parseInt(goods_number) > 1) {
			alert('串号控制的商品数量不能超过1');
			return false;
		}
	}
	
	//商品数量检测
	if(!check_receive_goods_number(batch_order_sn,goods_barcode,goods_number)){
	   return false;
    }
	
	//生产日期检测
	var party_id = get_party_by_batch_order_sn(batch_order_sn);
	if(check_maintain_warranty(party_id,goods_barcode)) {
		if(validity == ''){
			alert("生产日期为空！");
			return false;
		}
		if(!check_validity_format(validity)){
		   alert("请输入2013-08-08这种格式的日期!");
		   return false;
	    }
		
		// 检测目的容器生产日期的唯一性
		$is_validity_unique = check_receive_goods_barcode_location_validity ();
		if(!$is_validity_unique) {
			return false;
		}
	}
	sub_allow_edit();
	//return false;
	return true;
}
//批量检查收货上架输入项合法性
function check_all_grouding_input(){
	var grouding_location_barcode = $("#from_location").val();
	var goods_barcode = $("#goods_barcode").val();
	var goods_number = $("#goods_number").val();
	var to_location = $("#to_location").val();
	//上架容器检测
	if(!check_grouding_location_task(grouding_location_barcode)){
	   return false;
	}
	//商品条码检测
	if(!check_grouding_goods_barcode(grouding_location_barcode,goods_barcode)){
		return false;
	}
	
    // 数字格式检查
    if(!check_input_number(goods_number)) {
    	return false;
    }
    
	//检测商品数量合法性
	//先判断是否串号控制
    var party_id = get_party_by_location(grouding_location_barcode,goods_barcode);
	var res = check_goods_is_serial(party_id,goods_barcode);
    if(res['success']) {
   	  //非串号控制的情况检测数量是否合法
   	  if(!res['res']) {
   		 if(!check_grouding_goods_number(grouding_location_barcode,goods_barcode,goods_number)){
  			 return false;
  		 }
   	  } 
   	  // 串号情况也要检查，防止已经上架的重复被上架
   	  else 
   	  {
   		  var serial_number = goods_barcode;
    	  var res_barcode = get_goods_barcode(serial_number); // 根据串号得到条码
       	  goods_barcode = res_barcode['goods_barcode'];
	  	  var res_serial = get_location_serail_goods_number(grouding_location_barcode,goods_barcode,serial_number);
	      if (parseInt(res_serial['goods_number']) == 0) {
	           alert('该串号商品已经上架');
	           return false;
	      } else if(parseInt(goods_number) > 1) {
	           alert('串号商品数量不能超过1');
	           $("#goods_number").val(1);
	           return false;
	      }
   	  }
    }
	//储位容器检测
	if(!check_to_location_barcode_party(grouding_location_barcode,to_location,'IL_LOCATION')){
		return false;
	}
	
	// 检测目的容器生产日期的唯一性
	$is_validity_unique = check_moving_goods_barcode_location_validity ();
	//alert($is_validity_unique);
	if(!$is_validity_unique) {
		return false;
	}
	return true;
	
}
//批量检查移库输入项合法性
function check_all_moving_input(){
	var from_location = $("#from_location").val();
	var to_location = $("#to_location").val();
	var goods_barcode = $("#goods_barcode").val();
	//起始容器检查
	if(!check_from_location(from_location,'IL_LOCATION')){
		 return false;
	}
	//商品条码检查
	if(!check_goods_barcode(from_location,goods_barcode)){
		return false;
	}
	var goods_number = $("#goods_number").val();
	//数量检查
	if(!check_goods_number(from_location,goods_barcode,goods_number)){
		return false;
	}
	//目的容器检查
	if(!check_to_location_barcode_party(from_location,to_location,'IL_LOCATION')){
		return false;
	}
	return true;
}
//批量检查通用上架输入项合法性
function check_all_common_grouding_input(){
	var order_sn = $("#from_location").val();
	var goods_barcode = $("#goods_barcode").val();
	var goods_number = $("#goods_number").val();
	var validity = $("#validity").val();
	var to_location = $("#to_location").val();
	//订单检测
	if(!check_common_grouding_order_sn(order_sn)){
	   return false;
	}
	//商品条码检测
	if(!check_common_grouding_goods_barcode(order_sn,goods_barcode)){
		return false;
	}
	
    // 数字格式检查
    if(!check_input_number(goods_number)) {
    	return false;
    }
    
	//检测商品数量合法性
	//先判断是否串号控制
    var party_id = get_party_by_order_sn(order_sn,goods_barcode);
	var res = check_goods_is_serial(party_id,goods_barcode);
	
    if(res['success']) {
      /**
   	  //非串号控制的情况检测数量是否合法
   	  if(!res['res']) {
   		 if(!check_grouding_goods_number(grouding_location_barcode,goods_barcode,goods_number)){
  			 return false;
  		 }
   	  } 
   	  // 串号情况也要检查，防止已经上架的重复被上架
   	  else 
   	  {
   		  
   		  var serial_number = goods_barcode;
    	  var res_barcode = get_goods_barcode(serial_number); // 根据串号得到条码
       	  goods_barcode = res_barcode['goods_barcode'];
	  	  var res_serial = get_location_serail_goods_number(grouding_location_barcode,goods_barcode,serial_number);
	      if (parseInt(res_serial['goods_number']) == 0) {
	           alert('该串号商品已经上架');
	           return false;
	      } else if(parseInt(goods_number) > 1) {
	           alert('串号商品数量不能超过1');
	           $("#goods_number").val(1);
	           return false;
	      }
	      
   	  }
   	  */
    } else {
    	return false;
    }
    
	//储位容器检测
	if(!check_to_location_barcode(to_location,'IL_LOCATION')){
		return false;
	}
	
	//生产日期检测
	if(check_maintain_warranty(party_id,goods_barcode)) {
		if(validity == ''){
			alert("生产日期为空！");
			return false;
		}
		if(!check_validity_format(validity)){
		   alert("请输入2013-08-08这种格式的日期!");
		   return false;
	    }
		
		// 检测目的容器生产日期的唯一性
		$is_validity_unique = check_common_grouding_goods_barcode_location_validity ();
		//alert($is_validity_unique);
		if(!$is_validity_unique) {
			return false;
		}
	}

	return true;
	
}

//批量检查通用下架输入项合法性
function check_all_common_undercarriage_input(){
	return true;
	var order_sn = $("#from_location").val();
	var goods_barcode = $("#goods_barcode").val();
	var goods_number = $("#goods_number").val();
	var validity = $("#validity").val();
	var to_location = $("#to_location").val();
	//订单检测
	if(!check_common_grouding_order_sn(order_sn)){
	   return false;
	}
	//商品条码检测
	if(!check_common_grouding_goods_barcode(order_sn,goods_barcode)){
		return false;
	}
	
    // 数字格式检查
    if(!check_input_number(goods_number)) {
    	return false;
    }
    
	//检测商品数量合法性
	//先判断是否串号控制
    var party_id = get_party_by_order_sn(order_sn,goods_barcode);
	var res = check_goods_is_serial(party_id,goods_barcode);
	
    if(res['success']) {
      /**
   	  //非串号控制的情况检测数量是否合法
   	  if(!res['res']) {
   		 if(!check_grouding_goods_number(grouding_location_barcode,goods_barcode,goods_number)){
  			 return false;
  		 }
   	  } 
   	  // 串号情况也要检查，防止已经上架的重复被上架
   	  else 
   	  {
   		  
   		  var serial_number = goods_barcode;
    	  var res_barcode = get_goods_barcode(serial_number); // 根据串号得到条码
       	  goods_barcode = res_barcode['goods_barcode'];
	  	  var res_serial = get_location_serail_goods_number(grouding_location_barcode,goods_barcode,serial_number);
	      if (parseInt(res_serial['goods_number']) == 0) {
	           alert('该串号商品已经上架');
	           return false;
	      } else if(parseInt(goods_number) > 1) {
	           alert('串号商品数量不能超过1');
	           $("#goods_number").val(1);
	           return false;
	      }
	      
   	  }
   	  */
    } else {
    	return false;
    }
    
	//储位容器检测
	if(!check_to_location_barcode(to_location,'IL_LOCATION')){
		return false;
	}
	
	//生产日期检测
	if(check_maintain_warranty(party_id,goods_barcode)) {
		if(validity == ''){
			alert("生产日期为空！");
			return false;
		}
		if(!check_validity_format(validity)){
		   alert("请输入2013-08-08这种格式的日期!");
		   return false;
	    }
		
		// 检测目的容器生产日期的唯一性
		$is_validity_unique = check_common_grouding_goods_barcode_location_validity ();
		//alert($is_validity_unique);
		if(!$is_validity_unique) {
			return false;
		}
	}

	return true;
	
}


//批量检查"简单盘点"输入项合法性
function check_all_take_stock_simple_input(){
	var location_barcode = $("#from_location").val();
	var goods_barcode = $("#goods_barcode").val();
	var goods_number = $("#goods_number").val();
	//上架容器检测
	if(!check_to_location_barcode(location_barcode,'IL_LOCATION')){
	   return false;
	}
	
	//数字格式检测
	if(!check_take_stock_input_number(goods_number)){
		return false;
	}
	
	//商品条码检测
    var res = check_take_stock_goods_barcode(goods_barcode);
    if(!res['success']) {
    	return false;
    } else {
    	//判断是否串号控制
    	if(res['is_serial']) {
    		if(parseInt(goods_number) > 1 || parseInt(goods_number) < -1) {
     			 alert('串号控制的商品数量的绝对值不能超过1');
     			 return false;
     		 }
    	}
    }

	return true;
	
}


//from_location完结动作
function action_from_location_over() {
	var current_func = get_current_func();
	if(current_func == 'receive') {
	} else if(current_func == 'grouding') {
	} else if(current_func == 'moving') {
	} else if(current_func == 'batch_pick') {
	} else if(current_func == 'take_stock') {
	}    
}

//goods完结动作
function action_goods_over() {
	var current_func = get_current_func();
	var result =false;
	if(current_func == 'receive') {
		result = purchase_accept_and_location_transaction(); // 收货入库并且容器转换
	} else if(current_func == 'grouding') {
		result = grouding_and_location_transaction(); // 上架容器转换
	} else if(current_func == 'common_grouding') {
		result = common_grouding_and_location_transaction(); // 通用上架容器转换
	} else if(current_func == 'common_undercarriage') {
		result = common_undercarriage_and_location_transaction(); // 通用下架容器转换
	} else if(current_func == 'moving') {
		result = moving_and_location_transaction(); // 移库容器转换
	} else if(current_func == 'batch_pick') {
		result = batch_pick_and_location_transaction(); // 批拣出库并且容器转换
	} else if(current_func == 'take_stock') {
	} else if(current_func == 'take_stock_simple') { 
		result = take_stock_simple_goods_transaction(); // 简单盘点商品完结
	}
	return result;
}

//to_location完结动作
function action_to_location_over() {
	var current_func = get_current_func();
	if(current_func == 'receive') {
	} else if(current_func == 'grouding') {
	} else if(current_func == 'moving') {
	} else if(current_func == 'batch_pick') {
	} else if(current_func == 'take_stock') {
	}    
}

//某个动作完结提示
function check_action_finish() {
	var current_func = get_current_func();
	var result = false;
	if(current_func == 'receive') {
	   // 检测订单号是否完全入库
  	   var batch_order_sn = $('#from_location').val();
       result = check_receive_all_in(batch_order_sn);
	} else if(current_func == 'grouding') {
		// 检测上架容器是否完全上架
	  	var grouding_location_barcode = $('#from_location').val();
	  	result = check_all_grouding(grouding_location_barcode);
	} else if(current_func == 'moving') {
		
	} else if(current_func == 'batch_pick') {
		
	} else if(current_func == 'take_stock') {
	
	}
	return result;
}

function mouse_focus(step_id) {
	 $('#'+PAGE_FOCUS[step_id]).focus();
}

// 上一页
function sub_page_left() {
	 var current_page = get_current_page();
	 if(current_page == PAGE_CHANGE['start_page']['current_page']) {
		 return false;
	 }
	 // 页面跳转
	 sub_page_change(current_page,'left_page');
}

// 下一页
function sub_page_right() {
	 var current_page = get_current_page();
	 if(current_page == PAGE_CHANGE['end_page']['current_page']) {
		 return false;
	 }
	 // 页面跳转
	 sub_page_change(current_page,'right_page');
}

//页面跳转
function sub_page_change(current_page,indicate) {
	 // 页面跳转指示 
	 var new_step_id = PAGE_CHANGE[current_page][indicate];
	 $('#current_page').val(new_step_id);
	 show_hide_ids(SHOW_IDS[new_step_id]);
	 //当前位置提示
	 //show_cur_position();
	 // 新的页面,光标定位:由于都是一页显示，所以目前的位置就是标光位置
	 mouse_focus(current_page);
}

//当前位置提示
function show_cur_position(){
	var current_func = get_current_func();
	//显示采购订单、上架容器
	if(current_func == 'receive') {
		var from_location = $("#from_location").val();
		var to_location   = $("#to_location").val();
		var cur_position_value = "上架容器："+to_location;
		$("#cur_position").val(cur_position_value);
	} else if(current_func == 'grouding') {
		var from_location = $("#from_location").val();
		var to_location   = $("#to_location").val();
		var cur_position_value = "储位容器："+to_location;
		$("#cur_position").val(cur_position_value);
	}
	
}

// 光标上移一个位置
function sub_mouse_up(location_id) {
	sub_mouse_change(location_id,'mouse_up');
}

// 光标下移一个位置
function sub_mouse_down(location_id) {
	sub_mouse_change(location_id,'mouse_down');
}

//光标移动
function sub_mouse_change(location_id,indicate) {
	$('#'+ STEP_MAP[location_id][indicate]).focus();
}


//页面加载时数据初始化
function page_load_data_init(data) {
	for(var k in data) {
		$('#'+k).val(data[k]);
	}
}

// 初始化日历函数
function set_up_calendar(input_field_value,button_value) {
    Zapatec.Calendar.setup({
        weekNumbers       : false,
        electric          : false,
        inputField        : input_field_value,
        button            : button_value,
        ifFormat          : "%Y-%m-%d",
        daFormat          : "%Y-%m-%d"
    });
}


function btn_action(obj) {
	//alert(obj.value);
	var location_id ='';
	action_event_target(obj.name,location_id);
}

//////////////////////////////////////////公共函数模块///////////////////////////////////////////////

//容器检查
function check_to_location_barcode(location_barcode,location_type){
	if(!location_barcode || !location_type){
		alert("容器条码或容器类型为空！");
		return false;
	}
    var result = "";
    $.ajax({
        mode: 'abort',
        async : false,
        type: 'POST',
        dataType: 'json',
        url : 'ajax.php?act=check_to_location_barcode', 
        data: 'location_barcode=' + location_barcode + '&location_type=' + location_type,
        success: function(data) {
       	if(data.success){
       		result = data.success;
       	}else{
       		alert(data.error);
       		result = false;
       	}
        },
        error: function() {
        	alert('ajax请求错误, 请重新扫描条码:' + location_barcode);
        	result = false;
        }
    });
    return result;
}
//目的容器检查，同时检查组织是否一致
function check_to_location_barcode_party(from_location_barcode,to_location_barcode,location_type){
	if(!to_location_barcode || !location_type){
		alert("目的容器条码或容器类型为空！");
		return false;
	}
	if(!from_location_barcode){
		alert("起始容器为空！");
		return false;
	}
    var result = "";
    $.ajax({
        mode: 'abort',
        async : false,
        type: 'POST',
        dataType: 'json',
        url : 'ajax.php?act=check_to_location_barcode_party', 
        data: 'from_location_barcode=' + from_location_barcode + '&to_location_barcode=' + to_location_barcode +'&location_type=' + location_type,
        success: function(data) {
       	if(data.success){
       		result = data.success;
       	}else{
       		alert(data.error);
       		result = false;
       	}
        },
        error: function() {
        	alert('ajax请求错误, 请重新扫描条码:' + to_location_barcode);
        	result = false;
        }
    });
    return result;
}
function check_grouding_location_barcode_party(batch_order_sn,location_barcode,location_type) {
	if(!batch_order_sn || !location_barcode || !location_type){
		alert("批次订单号或容器条码或容器类型为空！");
		return false;
	}
    var result = "";
    $.ajax({
        mode: 'abort',
        async : false,
        type: 'POST',
        dataType: 'json',
        url : 'ajax.php?act=check_grouding_location_barcode_party', 
        data: 'batch_order_sn='+batch_order_sn+'&location_barcode=' + location_barcode + '&location_type=' + location_type,
        success: function(data) {
	       	if(data.success){
	       		result = data.success;
	       	}else{
	       		alert(data.error);
	       		result = false;
	       	}
        },
        error: function() {
        	alert('check_grouding_location_barcode_party ajax请求错误, 请重新扫描条码:' + location_barcode);
        	result = false;
        }
    });
    return result;
}

/**
 * 收货入库专用
 * 一个库位的1个商品只有一种生产日期检测（不维护的不检查）  ljzhou 2013-09-13
 */
 function check_receive_goods_barcode_location_validity () {	
	    return true;
	    var location_barcode = $.trim($('#to_location').val());
	    var goods_barcode    = $.trim($('#goods_barcode').val());
	    var validity         = $.trim($('#validity').val());
	    validity = validity+' 00:00:00';
	    var result  = check_goods_barcode_location_validity(location_barcode,goods_barcode,validity);
	    return result;
 }
 
 /**
  * 上架/移库专用
  * 一个库位的1个商品只有一种生产日期检测（不维护的不检查）  ljzhou 2013-09-14
  */
  function check_moving_goods_barcode_location_validity () {
	    return true;
 	    var from_location_barcode = $.trim($('#from_location').val());
 	    var to_location_barcode = $.trim($('#to_location').val());
 	    var goods_barcode    = $.trim($('#goods_barcode').val());
 	    
        // 检查是否串号控制
        var party_id = get_party_by_location(from_location_barcode,goods_barcode);
   	    var res = check_goods_is_serial(party_id,goods_barcode);
        if(res['success']) {
	       	 // 串号控制的话，找到goods_barcode
	       	 if(res['res']) {
	       	   	 var res_barcode = get_goods_barcode(goods_barcode);
	       	   	 goods_barcode = res_barcode['goods_barcode'];
	       	 }
        } 
        
 	    var validity = get_location_barcode_validity(from_location_barcode,goods_barcode);
 	    //alert(validity);alert(to_location_barcode);alert(goods_barcode);
 	    // 如果目的库位上还没维护该商品
 	    if(!validity) {
 	    	return true;
 	    }
 	    
	    var result  = check_goods_barcode_location_validity(to_location_barcode,goods_barcode,validity);
	    return result;
  }
  
  /**
   * 通用上架专用
   * 一个库位的1个商品只有一种生产日期检测（不维护的不检查）  ljzhou 2013-09-13
   */
   function check_common_grouding_goods_barcode_location_validity () {
	    return true;
	    var order_sn = $.trim($('#from_location').val());
  	    var location_barcode = $.trim($('#to_location').val());
  	    var goods_barcode    = $.trim($('#goods_barcode').val());
  	    var validity         = $.trim($('#validity').val());
        // 检查是否串号控制
        var party_id = get_party_by_order_sn(order_sn,goods_barcode);
   	    var res = check_goods_is_serial(party_id,goods_barcode);
        if(res['success']) {
	       	 // 串号控制的话，找到goods_barcode
	       	 if(res['res']) {
	       	   	 var res_barcode = get_goods_barcode(goods_barcode);
	       	   	 goods_barcode = res_barcode['goods_barcode'];
	       	 }
        } 
        
  	    validity = validity+' 00:00:00';
  	    //alert('location_barcode:'+location_barcode+' goods_barcode:'+goods_barcode+' validity:'+validity);
  	    //return true;
  	    var result  = check_goods_barcode_location_validity(location_barcode,goods_barcode,validity);
  	    return result;
   }
  
  
 /**
  * 一个库位的1个商品只有一种生产日期检测（不维护的不检查）
  * @param location_barcode
  * @param goods_barcode
  * @param validity
  * @returns bool
  */
 function check_goods_barcode_location_validity(location_barcode,goods_barcode,validity) {
	    return true;
	    //alert('2location_barcode:'+location_barcode+'goods_barcode:'+goods_barcode+'validity:'+validity);

	    var result = true;
	    $.ajax({
	        mode: 'abort',
	        async : false,
	        type: 'POST',
	        dataType: 'json',
	        url : 'ajax.php?act=check_goods_barcode_location_validity', 
	        data: 'location_barcode=' + location_barcode + '&goods_barcode=' + goods_barcode+ '&validity=' + validity,
	        success: function(data) {
	       	if(data.success){
	       		result = true;
	       	}else{
	       		alert(data.error);
	       		result = false;
	       	}
	        },
	        error: function() {
	        	alert('ajax请求错误, 请重新扫描条码:' + location_barcode);
	        	result = false;
	        }
	    });
	    return result;
 }

  
  /**
   * 
   * 得到库位上某商品的生产日期  ljzhou 2013-09-14
   */
   function get_location_barcode_validity(location_barcode,goods_barcode) {	

  	    var result = "";
  	    $.ajax({
  	        mode: 'abort',
  	        async : false,
  	        type: 'POST',
  	        dataType: 'json',
  	        url : 'ajax.php?act=get_location_barcode_validity', 
  	        data: 'location_barcode=' + location_barcode + '&goods_barcode=' + goods_barcode,
  	        success: function(data) {
	  	       	if(data.success){
	  	       		result = data['validity'];
	  	       	}else{
	  	       		alert(data.error);
	  	       	}
  	        },
  	        error: function() {
  	        	alert('得到库位上某商品的生产日期ajax请求错误location_barcode:' + location_barcode+' goods_barcode:'+goods_barcode);
  	        }
  	    });
  	    return result;
   }
   
     // 根据串号得到商品条码
	 function get_goods_barcode(serial_number)
	 {
	  	 if(!serial_number) {
	  		 alert('根据串号得到商品条码时串号为空!');
	  		 return false;
	  	 }
	     var result = Array();
	     $.ajax({
	         async : false,
	         type: 'POST',
	         dataType: 'json',
	         url : 'ajax.php?act=get_goods_barcode', 
	         data: 'serial_number=' + serial_number,
	         error: function() { alert("根据串号得到商品条码时ajax请求错误！"); result['success'] = false; },
	         success: function(data) {
		       	if(data.success == true) {
		       		result['success'] = true;
		       		result['goods_barcode'] = data.goods_barcode;
		       	} else {
		       		result['success'] = false;
		       		alert(data.error);
		       	}
	         }
	     }); 
	     return result;
	 }
	 
	 /**
	  * 根据订单号得到组织id
	  */
	  function get_party_by_order_sn(order_sn)
	  {
		  if(!order_sn) {
			  alert('根据订单号得到组织id时参数为空！');
			  return false;
		  }
		  //alert(order_sn);
	      var result = false;
	      $.ajax({
	          async : false,
	          type: 'POST',
	          dataType: 'json',
	          url : 'ajax.php?act=get_party_by_order_sn', 
	          data: 'order_sn=' + order_sn,
	          error: function() { alert("根据订单号得到组织id ajax请求错误"); result = false; },
	          success: function(data) {
	        	  if(data.success) {
	        		  result = data.party_id;
	        	  } else {
	        		  alert(data.error);
	        	  }
	          }
	      }); 
	      return result;
	  }
	  
	// 判断是否要显示生产日期
    function check_show_validity(party_id,goods_barcode) {
         // 判断是否要显示生产日期
         if(check_maintain_warranty(party_id,goods_barcode)) {
         	//alert('need validity');
         	sub_validity_show();
         } else {
         	//alert('not need validity');
         	sub_validity_hide();
         }
    }
	    
	 
// 捕捉键盘事件
// F1,F2,F3 事件 不在输入框也要有效果
var KEY_F123 = new Array(112,113,114);
function keyDown(event) {
	if ($.inArray(event.keyCode,KEY_F123) == -1) {
		return;
	}
	
	action_event(event,'');
}
document.onkeydown = keyDown;
