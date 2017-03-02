<?php

/**
 * ECSHOP IP ����ز�ѯ��
 * ============================================================================
 * ��Ȩ���� (C) 2005-2006 �������̻����Ƽ���չ���޹�˾������������Ȩ��
 * ��վ��ַ: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * ����һ����ѿ�Դ�����������ζ��������ڲ�������ҵĿ�ĵ�ǰ���¶Գ������
 * �����޸ġ�ʹ�ú��ٷ�����
 * ============================================================================
 * @author:     ���Ң <http://www.CoolCode.CN>
 * @version:    v1.5
 * @copyright:  2005 CoolCode.CN
 * ---------------------------------------------
 * $Date: 2007-05-17 13:33:17 +0800 (星期四, 17 五月 2007) $
 * $Id: cls_ip.php 8653 2007-05-17 05:33:17Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class ip_search
{
    var $fp;
    var $firstip;
    var $lastip;
    var $totalip;

    function getlong()
    {
        $result = unpack('Vlong', fread($this->fp, 4));

        return $result['long'];
    }

    function getlong3()
    {
        $result = unpack('Vlong', fread($this->fp, 3) . chr(0));

        return $result['long'];
    }

    function packip($ip)
    {
        return pack('N', intval(ip2long($ip)));
    }

    function getstring($data = '')
    {
        $char = fread($this->fp, 1);
        while (ord($char) > 0)
        {
            $data .= $char;
            $char = fread($this->fp, 1);
        }

        return $data;
    }

    function getarea()
    {
        $byte = fread($this->fp, 1);
        switch (ord($byte))
        {
            case 0:
                $area = '';
                break;
            case 1:
            case 2:
                fseek($this->fp, $this->getlong3());
                $area = $this->getstring();
                break;
            default:
                $area = $this->getstring($byte);
                break;
        }

        return $area;
    }

    function getlocation($ip)
    {
        if (!$this->fp)
        {
            return NULL;
        }

        $location['ip'] = gethostbyname($ip);
        $ip = $this->packip($location['ip']);

        $ipone = ord($ip[0]);
        if ($ipone == 10 || $ipone == 127 || ($ipone == 192 && ord($ip[1]) == 168) || ($ipone == 172 && (ord($ip[1]) >= 16 && ord($ip[1]) <= 31)))
        {
            $location['country'] = '���ؾ�����';
            $location['area']    = '';

            return $location;
        }

        $l = 0;
        $u = $this->totalip;
        $findip = $this->lastip;
        while ($l <= $u)
        {
            $i = floor(($l + $u) / 2);
            fseek($this->fp, $this->firstip + $i * 7);
            $beginip = strrev(fread($this->fp, 4));

            if ($ip < $beginip)
            {
                $u = $i - 1;
            }
            else
            {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));
                if ($ip > $endip)
                {
                    $l = $i + 1;
                }
                else
                {
                    $findip = $this->firstip + $i * 7;
                    break;
                }
            }
        }

        fseek($this->fp, $findip);
        $location['beginip'] = long2ip($this->getlong());
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());
        $byte = fread($this->fp, 1);
        switch (ord($byte))
        {
            case 1:
                $countryOffset = $this->getlong3();
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);
                switch (ord($byte))
                {
                    case 2:
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default:
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2:
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default:
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }
        if ($location['country'] == ' CZ88.NET')
        {
            $location['country'] = '';
        }
        if ($location['area'] == ' CZ88.NET')
        {
            $location['area'] = '';
        }

        return $location;
    }

    function ip_search($dir = '')
    {
        $filename = $dir . 'includes/ip/ipdata.dat';

        if (($this->fp = @fopen($filename, 'rb')) !== false)
        {
            $this->firstip = $this->getlong();
            $this->lastip  = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
            register_shutdown_function(array(&$this, '_IpLocation'));
        }
    }

    function _IpLocation()
    {
        fclose($this->fp);
    }
}

?>