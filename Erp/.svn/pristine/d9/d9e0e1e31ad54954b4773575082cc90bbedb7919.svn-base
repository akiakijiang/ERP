<?php

$path = $_REQUEST['p'];
if (!$path) {
    exit();
}

$ext = pathinfo($path);
if (!in_array($ext['extension'], array('jpg', 'png', 'gif'))) {
    exit();
}

$content = file_get_contents($path);
if (!$content) {
    exit();
}

header("Content-Type:image/png");
print $content;
