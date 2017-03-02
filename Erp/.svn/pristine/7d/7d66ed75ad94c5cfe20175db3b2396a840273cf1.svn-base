<?php 

/**
 * 输出定制图片
 */

define('IN_ECS', true);
require('includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
error_reporting(E_ALL);

/**
 * 预定义字体
 */
$fonts = array
(
	'華康娃娃體' => ROOT_PATH .'admin/templates/distributor/font/hkwwt.TTC',
    '华康娃娃体简W5' => ROOT_PATH .'admin/templates/distributor/font/hkwwtjW5.ttc',
    '华康少女文字简W5' => ROOT_PATH .'admin/templates/distributor/font/hkxnwzjW5.ttc',
    '华文隶书' => ROOT_PATH .'admin/templates/distributor/font/ChineseLishu.TTF',
    'Coconut-Medium' => ROOT_PATH .'admin/templates/distributor/font/Coconut-Medium.ttf',
    '迷你简小标宋' => ROOT_PATH .'admin/templates/distributor/font/mnjxbs.TTF',
    '华文行楷' => ROOT_PATH .'admin/templates/distributor/font/STXINGKA.TTF',
    '方正徐静蕾字体' => ROOT_PATH .'admin/templates/distributor/font/fzxjl.fon',
);

// 定制文字
if ( isset($_GET['order_goods_id']) && 
        $attr = get_order_goods_attribute((int)$_GET['order_goods_id'])) {
	$text = $attr['customize_text'];
	
    ob_start();
    $im = @imagecreatetruecolor(250, 80);                  // 创建空白画布
    $bgcolor = imagecolorallocate($im, 255, 255, 255);     // 指定背景色
    imagefill($im, 0, 0, $bgcolor);                        // 用背景色填充
    //imagecolortransparent($im, $bgcolor);                  // 将背景色设置为透明色
	$style = array
	(
	    'angle' => 0,                                      // 角度
		'size'  => (int)$attr['customize_font_size'],      // 字体大小
		'font'  => isset($fonts[$attr['customize_font']])  // 字体
	        ? $fonts[$attr['customize_font']]
	        : reset($fonts) ,   
		'color' => imagecolorallocate($im, 0, 0, 0)        // 字体颜色 (黑色)
	);
	
	if (function_exists('mb_substr')) {
	    $first = mb_substr($text, 0, 1, 'UTF-8');    
	} else {
	    
	}
	// 取得第一个字符的定位
	$position = imagettfbbox($style['size'], $style['angle'], $style['font'], $first);
	$x = $position[6];
	$y = abs($position[7]);
	
    imagettftext($im, $style['size'], $style['angle'], $x, $y, $style['color'], $style['font'], $text); // 把字符串写在图像左上角
    imagepng($im);
    imagedestroy($im);
    $response = new View_Output('logo', 'image/png', ob_get_clean());   // 输出图片
    $response->enableClientCache(false);
    $response->execute();
}





/**
 * 类 View_Output 用于向浏览器直接输出数据（例如下载文件）
 */
class View_Output
{
    /**
     * 所有要输出的内容
     *
     * @var array
     */
    protected $_output = array();

    /**
     * 输出文件名
     *
     * @var string
     */
    protected $_output_filename;

    /**
     * 输出类型
     *
     * @var string
     */
    protected $_mime_type;

    /**
     * 输出文件名的字符集
     *
     * @var string
     */
    protected $_filename_charset = 'utf-8';

    /**
     * 允许客户端缓存输出的文件
     *
     * @var boolean
     */
    protected $_enabled_client_cache = true;

    /**
     * 构造函数
     *
     * @param string $output_filename
     * @param string $mime_type
     * @param string $content
     */
    function __construct($output_filename, $mime_type = 'application/octet-stream', $content = null)
    {
        $this->_output_filename  = $output_filename;
        $this->_mime_type        = $mime_type;
        if ($content) { $this->appendData($content); }
    }

    /**
     * 添加一个要输出的文件
     *
     * @param string $filename
     *
     * @return QView_Output
     */
    function addFile($filename)
    {
        $this->_output[] = array('file', $filename);
        return $this;
    }

    /**
     * 追加要输出的数据
     *
     * @param string $content
     *
     * @return QView_Output
     */
    function appendData($content)
    {
        $this->_output[] = array('raw', $content);
        return $this;
    }

    /**
     * 设置输出文件名
     *
     * @param string $output_filename
     *
     * @return QView_Output
     */
    function setOutputFilename($output_filename)
    {
        $this->_output_filename = $output_filename;
        return $this;
    }

    /**
     * 设置输出文件名的编码
     *
     * @param string $charset
     *
     * @return QView_Output
     */
    function setOutputFilenameCharset($charset)
    {
        $this->_filename_charset = $charset;
        return $this;
    }

    /**
     * 设置是否允许客户端缓存输出的文件
     *
     * @param boolean $enabled
     *
     * @return QView_Output
     */
    function enableClientCache($enabled = true)
    {
        $this->_enabled_client_cache = $enabled;
        return $this;
    }

    /**
     * 设置输出类型
     *
     * @param string $mime_type
     *
     * @return QView_Output
     */
    function setMimeType($mime_type)
    {
        $this->_mime_type = $mime_type;
        return $this;
    }

    /**
     * 执行响应
     */
    function execute()
    {
        header("Content-Type: {$this->_mime_type}");
        $filename = '"' . htmlspecialchars($this->_output_filename) . '"';

        $filesize = 0;
        foreach ($this->_output as $output)
        {
            list($type, $data) = $output;
            if ($type == 'file')
            {
                $filesize += filesize($data);
            }
            else
            {
                $filesize += strlen($data);
            }
        }

        header("Content-Disposition: inline; filename={$filename}; charset={$this->_filename_charset}");
        if ($this->_enabled_client_cache)
        {
            header('Pragma: cache');
        }
        header('Cache-Control: public, must-revalidate, max-age=0');
        header("Content-Length: {$filesize}");

        foreach ($this->_output as $output) {
            list($type, $data) = $output;
            if ($type == 'file') {
                readfile($data);
            } else {
                echo $data;
            }
        }
    }
}
    
?>