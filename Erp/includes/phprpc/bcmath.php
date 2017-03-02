<?php
if (!function_exists('bcpowmod')) {
    function bcpowmod($x, $y, $modulus, $scale = 0)
    {
        $t = '1';
        while (bccomp($y, '0')) {
            if (bccomp(bcmod($y, '2'), '0')) {
                $t = bcmod(bcmul($t, $x), $modulus);
                $y = bcsub($y, '1');
            }

            $x = bcmod(bcmul($x, $x), $modulus);
            $y = bcdiv($y, '2');
        }
        return $t;
    }
}

function bcstr2dec($str) {
    $len = strlen($str);
    $result = '0';
    $m = '1';
    for ($i = 0; $i < $len; $i++) {
        $result = bcadd(bcmul($m, ord($str{$len - $i - 1})), $result);
        $m = bcmul($m, '256');
    }
    return $result;
}

function bcdec2str($dec) {
    $str = "";
    while (bccomp($dec, '0') == 1) {
       $str = chr(bcmod($dec, '256')) . $str;
       $dec = bcdiv($dec, '256');
    }
    return $str;
}

function bcrand($n, $s) {
    $lowBitMasks = array(0x0000, 0x0001, 0x0003, 0x0007,
                         0x000f, 0x001f, 0x003f, 0x007f,
                         0x00ff, 0x01ff, 0x03ff, 0x07ff,
                         0x0fff, 0x1fff, 0x3fff, 0x7fff);

    $r = $n % 16;
    $q = floor($n / 16);
    $result = '0';
    $m = '1';
    for ($i = 0; $i < $q; $i++) {
        $rand = mt_rand(0, 0xffff);
        if (($q - 1 == $i) and ($r == 0) and ($s == 1)) {
            $rand |= 0x8000;
        }
        $result = bcadd(bcmul($m, $rand), $result);
        $m = bcmul($m, '65536');
    }
    if ($r != 0) {
        $rand = mt_rand(0, $lowBitMasks[$r]);
        if ($s == 1) {
            $rand |= 1 << ($r - 1);
        }
        $result = bcadd(bcmul($m, $rand), $result);
    }
    return $result;
}

function bcnextprime($num) {
    if (!bccomp(bcmod($num, '2'), '0')) {
        $num = bcsub($num, '1');
    }
    do {
        $num = bcadd($num, '2');
    } while (!bcisprime($num));
    return $num;
}

function bcisprime($num) {
    $primes = array(2, 3, 5, 7, 11, 13, 17);
    for ($i = 0; $i < 7; $i++) {
        if (!bcmillertest($num, $primes[$i])) {
            return false;
        }
    }
    return true;
}

function bcmillertest($num, $base) {
    if (!bccomp($num, '1')) {
        return false;
    }
    $tmp = bcsub($num, '1');

    $zero_bits = 0;
    while (!bccomp(bcmod($tmp, '2'), '0')) {
        $zero_bits++;
        $tmp = bcdiv($tmp, '2');
    }

    $tmp = bcpowmod($base, $tmp, $num);
    if (!bccomp($tmp, '1')) {
        return true;
    }

    while ($zero_bits--) {
        if (!bccomp(bcadd($tmp, '1'), $num)) {
            return true;
        }
        $tmp = bcpowmod($tmp, '2', $num);
    }
    return false;
}
?>