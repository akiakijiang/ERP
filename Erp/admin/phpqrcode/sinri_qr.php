<?php
include "qrlib.php"; 

$sinri_content=$_REQUEST['sinri_content'];

QRcode::png($sinri_content);
?>