<?php
define('IN_ECS', true);

set_time_limit(1000);
require('/var/www/http/erp/includes/master_init.php');
include('/var/www/http/erp/RomeoApi/lib_inventory.php');

if($argv[1] == 'iE9owUe3gNasFd8Adf1') {
        createInventoryItemSnapshotBatch();
        print "ok";
}