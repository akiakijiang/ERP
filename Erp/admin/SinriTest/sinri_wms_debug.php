<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../function.php');

require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');


/*
require_once('distribution.inc.php');
admin_priv('distribution_purchase_request', 'distribution_generate_purchase_order');
require_once('function.php');
require_once(ROOT_PATH. 'includes/lib_order.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once('includes/lib_product_code.php');
*/
require_once('../includes/lib_sinri_DealPrint.php');
?>
<html>
    <head>
        <TITLE>SINRI WMS PROJECT DEBUG</TITLE>
    </head>
    <BODY>
        <div>
            LOGS IN filelock:
            <?php
            $dir="../filelock";
            if(file_exists($dir)){
                //获取某目录下所有文件、目录名（不包括子目录下文件、目录名）
                $handler = opendir($dir);
                while (($filename = readdir($handler)) !== false) {//务必使用!==，防止目录下出现类似文件名“0”等情况
                    if ($filename != "." && $filename != ".." && stristr($filename,'.log')) {
                        $files[] = $filename;
                    }
                }
                closedir($handler);
                natsort($files);
                $last_index=sizeof($files)-1;
                $last_url=$dir."/".$files[$last_index];
                echo "<select id='file_select' onchange=\"
                    var fs=document.getElementById('file_select');
                    var lf=document.getElementById('log_frame');
                    lf.src=fs.value;
                \">";
                foreach ($files as $key => $filename) {
                    //echo "<a href=\"$dir/$filename\" target=\"log_frame\">$filename</a> ";
                    echo "<option value='$dir/$filename'";
                    if(($dir."/".$filename)==$last_url){
                        echo " selected='selected'";
                    }
                    echo ">$filename</option>";
                }
                echo "</select>";
            }else{
                echo "NO filelock FOLDER";
            }
            ?>
        </div>
        <?php
            echo "<IFRAME id=\"log_frame\" width='100%' height='90%' src=\"$last_url\"></IFRAME>";
        ?>
    </BODY>
</html>
<!--
        
    -->