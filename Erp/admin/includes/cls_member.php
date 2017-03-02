<?php

/**
 * member class
 * @author : Zandy <zandyo@gmail.com, yzhang@oukoo.com>
 * @version  : 0.0.1
 */

class Shop_Member {
	public $userId;
	public $userName;
	private $db;

	public function __construct($db, $ecs) {
		$this->db = $db;
		$this->ecs = $ecs;
	}

	public function getUserNameById($userId) {
		$sql = "SELECT user_name FROM " . $this->ecs->table('users') . " WHERE user_id = '$userId' LIMIT 1 ";
		$r = $this->db->getOne($sql);
		return $r ? $r : '';
	}

	public function getUserIdByName($userName) {
		$sql = "SELECT user_id FROM " . $this->ecs->table('users') . " WHERE user_name = '$userName' LIMIT 1 ";
		$r = $this->db->getOne($sql);
		return $r ? $r : 0;
	}
}