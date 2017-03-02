<?php

function csv_parse($str,$f_delim = ',',$r_delim = "\n",$qual = '"')
{
   $output = array();
   $row = array();
   $word = '';

   $str = trim($str);
   $len = strlen($str);
   $inside = false;
   
   $skipchars = array($qual,"\\");
      for ($i = 0; $i < $len; ++$i) {
       $c = $str[$i];
       if (!$inside && $c == $f_delim) {
           $row[] = $word;
           $word = '';
       } else if (!$inside && $c == $r_delim) {
           $row[] = $word;
           $word = '';
           $output[] = $row;
           $row = array();
       } else if ($inside && in_array($c,$skipchars) && ($i+1 < $len && $str[$i+1] == $qual)) {
           $word .= $qual;
           ++$i;
       } else if ($c == $qual) {
           $inside = !$inside;
       } else {
           $word .= $c;
       }
   }
   
   $row[] = $word;
   $output[] = $row;
   return $output;
}

//$str = "订单号,收货人
//0903831011,熊君芳";
//$c = csv_parse($str);
//print_r($c);