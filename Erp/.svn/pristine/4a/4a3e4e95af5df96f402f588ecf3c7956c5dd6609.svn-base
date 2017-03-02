<?php

// 某魔法的禁书目录 加载

define('IN_ECS', true);

require('includes/init.php');

// 某魔法的禁书目录 构筑魔法阵

$index = new IndexLibrorumProhibitorum();

if ($_REQUEST['act'] == 'party') {	
	// 更改魔法系统
    $index->executeSwitchParty();
}
else{
	// 某魔法的禁书目录 魔法发动
	$index->executeDisplay();
}
/**
* 某魔法的禁书目录
*/
class IndexLibrorumProhibitorum
{
	public $my_admin_user_id='';
	public $my_role='';
	public $user_info='';

	public $is_third_party_warehouse=false;
	
	public $user_party_list=array();
	public $user_party_group=array();
	public $shortcut_menu=array();
	public $menus=array();

	/**
	 * 根据禁书目录构建魔法阵
	 */
	function __construct()
	{
		// 设定术者
		$this->my_admin_user_id=$_SESSION['admin_id'];
	}

	private function getUserInfo(){
		// 获取基本魔导器
		global $db;

		// 判断术者是否具有被封印指定的超能力
		$exclude_role = array('third_party_warehouse','zhongliang_ERP_system','ecco_ERP_system');
		// 判断该用户是否拥有中粮或者第三方仓库权限，有则返回相应的role
		foreach($exclude_role as $role){
		    if(check_admin_priv($role)){
		    	$this->my_role = $role;
		    	break;
		    }
		}
		// 特殊的权限要屏蔽上面的菜单，如中粮ERP系统权限和北京第三方外包权限
		$this->is_third_party_warehouse = $this->my_role && ($_SESSION['action_list']!='all');

		// 术者身份确认
		$user_sql="SELECT user_id, nav_list, real_name
			FROM ecshop.ecs_admin_user  
			WHERE user_id = '{$this->my_admin_user_id}'
		";
		$this->user_info = $db->getRow($user_sql);
		if(empty($this->user_info)){
			throw new Exception("当前用户已被封印，或魔法行使已被限制。", 1);
			
		}
		$this->my_admin_user_id=$this->user_info['user_id'];
	}

	/**
	 * 构筑魔法系统 PARTY LIST
	 */
	private function getUserPartyList(){
		// 获取基本魔导器
		global $db;

		// $this->user_party_list = party_get_user_party_new($this->my_admin_user_id);
		$this->user_party_list = party_get_user_party_by_sinri($this->my_admin_user_id);

		// 为了魔改ERP INDEX
		$sql="SELECT PARTY_ID,NAME,party_group 
			FROM romeo.party 
			where 
			party_group is not null
			AND PARTY_ID in ('".implode("','", $this->user_party_list)."')
		";
		$party_group_list=$db->getAll($sql);
		$party_group_mapping=array();
		foreach ($party_group_list as $line) {
			$fa_icon=$this->getPartyClusterFaIcon($line['party_group']);
			$party_group_mapping[$line['party_group']]['fa_icon']=$fa_icon;
		    $party_group_mapping[$line['party_group']]['party_list'][$line['PARTY_ID']]=$line['NAME'];
		}

		ksort($party_group_mapping);

		$this->user_party_group=$party_group_mapping;
	}

	/**
	 * 获取快速咏唱用简式魔法
	 */
	private function getUserNavList(){
		$lst = array();
		if (!empty($this->user_info['nav_list'])) {
		    $arr = explode(',', $this->user_info['nav_list']);

		    foreach ($arr AS $val) {
		        $tmp = explode('|', $val);
		        // for ($i=0; $i < strlen($tmp[0]); $i++) echo "[".ord(substr($tmp[0],$i,1))."]";
				$lst[$tmp[1]] = ltrim($tmp[0],' '.chr(194).chr(160));
		    }
		}
		$this->shortcut_menu=$lst;
	}

	/**
	 * 获取魔法
	 */
	private function getMenu(){
		global $_CFG;
		//var_dump($_CFG);
		require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/common.php');
		include_once('includes/inc_menu.php');
		// var_dump($_LANG);

	    foreach ($modules AS $key => $value)
	    {
	        ksort($modules[$key]);
	    }
	    
	    foreach ($modules AS $key => $val)
	    {
			//判断是否为中粮或第三方仓库权限且不为all，那么就用对应的菜单栏
	    	if($this->my_role && ($_SESSION['action_list']!='all')){
	    		require_once('exclude_parties.php');
	    		//创建对应类
	    		$role_class_name=$this->my_role;
	    		$role = new $role_class_name();
	    		//获取菜单栏
	    		$facility_menu_list = $role->menuList();
	    		$facility_menu = array_keys($facility_menu_list);
				if (in_array($key, $facility_menu)) {
					$menus[$key]['label'] = $_LANG[$key];
					if (is_array($val)) {
						$menus[$key]['is_final']='0';
			            foreach ($val AS $k => $v) {
			            	if (in_array($k, $facility_menu_list[$key])) {

			            		$menus[$key]['children'][$k]['label']  = $_LANG[$k];
			            		if(is_array($v)){
			            			$menus[$key]['children'][$k]['is_final']='0';
						        	foreach ($v AS $k2 => $v2)
						            {
						            	$menus[$key]['children'][$k]['children2'][$k2]['is_final'] = '1';
						                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
						                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
						            }
			            		}else{
			            			$menus[$key]['children'][$k]['is_final']='1';
			            			$menus[$key]['children'][$k]['action'] = $v;
			            		}
			            	}
			            }
			        } else {
			        	$menus[$key]['is_final']='1';
			            $menus[$key]['action'] = $val;
			        }
				}
			} else {
				$menus[$key]['label'] = $_LANG[$key];
		        if (is_array($val))
		        {
		        	$menus[$key]['is_final']='0';
		            foreach ($val AS $k => $v)
		            {
		                $menus[$key]['children'][$k]['label']  = $_LANG[$k];
				        if (is_array($v))
				        {
				        	$menus[$key]['children'][$k]['is_final']='0';
				        	foreach ($v AS $k2 => $v2)
				            {
				            	ksort($modules[$key]);
				                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
				                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
				                $menus[$key]['children'][$k]['children2'][$k2]['is_final'] = '1';
				            }
				            foreach ($v AS $k2 => $v2)
				            {
				                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
				                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
				                $menus[$key]['children'][$k]['children2'][$k2]['is_final'] = '1';
				            }
				        } 
				        else 
				        {
				        	$menus[$key]['children'][$k]['is_final']='1';
				        	$menus[$key]['children'][$k]['action'] = $v;
				        }
		            }
		        }
		        else
		        {
		        	$menus[$key]['is_final']='1';
		            $menus[$key]['action'] = $val;
		        }
			}
	    }

	    foreach ($menus as $key => $value) {
	    	$menus[$key]['fa_icon']=IndexLibrorumProhibitorum::getMenuFaIcon($key);
	    }

	    //print_r($menus);die();
	    
	    $this->menus=$menus;
	}

	public static function getMenuFaIcon($menu_key){
		static $mapping=array(
			'11_for1111'=>'fa-dashboard',
			'00_REWITE_WORKFLOW'=>'fa-code',
			'00_MINUS_OUKOO_ERP'=>'fa-bug',
			'01_ERPDEV'=>'fa-desktop',
			'02_order_manage'=>'fa-copy',
			'03_purchase_manage'=>'fa-shopping-cart',
			'04_goods_manage'=>'fa-cubes',
			'05_shop_manage'=>'fa-line-chart',
			'06_activity_manage'=>'fa-group',
			'07_inventory_manage'=>'fa-pie-chart',
			'08_distribution_manage'=>'fa-sitemap',
			'09_waybill_manage'=>'fa-file-text-o',
			'10_kuajing_manage'=>'fa-plane',
			'11_finance_manage'=>'fa-money',
			'12_priv_admin'=>'fa-truck',
			'13_system'=>'fa-gears',
			'14_wms'=>'fa-area-chart',
			'15_analyze'=>'fa-bar-chart',
			'17_gymboree'=>'fa-tag',
			'18_ecco'=>'fa-tag',
		);
		if(isset($mapping[$menu_key])){
			return $mapping[$menu_key];
		}else{
			return 'fa-tag';
		}
	}

	public static function getPartyClusterFaIcon($pc_key){
		static $mapping = array(
			"<!--1-->母婴"=>'fa-child',
            "<!--2-->食品 / 冲饮"=>'fa-cutlery',
            "<!--3-->护肤 / 彩妆 / 洗护"=>'fa-magic',
            "<!--4-->数码"=>'fa-laptop',
            "<!--5-->文具 / 保健品 / 非标"=>'fa-pencil-square',
            "<!--6-->跨境"=>'fa-plane',
            "<!--7-->其它"=>'fa-tag',
		);
		if(isset($mapping[$pc_key])){
			return $mapping[$pc_key];
		}else{
			return 'fa-tag';
		}
	}

	/**
	 * 新世界大法术式发动，是时候伸出触手了！
	 * @return [type] [description]
	 */
	public function executeDisplay(){
		// 咏唱魔法式
		$this->getUserInfo();
		$this->getUserPartyList();
		$this->getUserNavList();
		$this->getMenu();

		// 掏出魔导器
		global $smarty;

		// 向魔导器注入魔力		
		$smarty->assign('nav_list',   $this->shortcut_menu);
		$smarty->assign('user_party_group', $this->user_party_group);
		$smarty->assign('user_name',  $this->user_info['real_name']);
		$smarty->assign('user_current_party_id', $_SESSION['party_id']);
		$smarty->assign('user_current_party_name', party_mapping($_SESSION['party_id']));
		$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
		$smarty->assign('is_third_party_warehouse', $this->is_third_party_warehouse);
		$smarty->assign('menus',$this->menus);

		//观测有无指定魔导书页码
		if(isset($_GET['target_url']) && !empty($_GET['target_url'])){
			$smarty->assign('target_url',$_GET['target_url']);
		}else{
			$smarty->assign('target_url','index.php?act=main');
		}

		// 术式发动
		$smarty->display('indexV2.html');
	}

	/**
	 * 魔法体系切换术式发动，是时候伸出触手了！
	 * @return [type] [description]
	 */
	public function executeSwitchParty(){
		$this->getUserInfo();
		$this->getUserPartyList();
		// 切换party需要有相应的权限
		if ($_SESSION['party_id'] && isset($_GET['party_id']) && in_array($_GET['party_id'], $this->user_party_list)) {
			$_SESSION['party_id'] = $_GET['party_id'];
		}
		print $_SESSION['party_id'];
	    exit; 
	}
}