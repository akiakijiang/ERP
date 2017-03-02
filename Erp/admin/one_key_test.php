<?php
/**
All Hail Sinri Edogawa!
@author ljni@i9i8.com
われはシンリなり。みなわが軍門に下がれ。
**/
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
batch_over_batch_pick($_SESSION['party_id']);
die();