<?php
/**
 * Created by PhpStorm.
 * User: feng
 * Date: 16/6/20
 * Time: 下午3:44
 */
define('PATH', __DIR__ . '/');
include_once PATH . 'function.php';
$url = 'http://dl.duobao999.com/v20IB/index.html';
$url = urlCheck($url);
$url_info_arr = getUrlInfo($url);


$curl_data = curlGet($url);
$css_arr = cssDetection($curl_data);
$js_arr = srcDetection('js', $curl_data);
$img_arr = srcDetection('img', $curl_data);

$curl_data = replaceWebContent($css_arr, $curl_data);
$curl_data = replaceWebContent($js_arr, $curl_data);
$curl_data = replaceWebContent($img_arr, $curl_data);
$img_url_str = getImgUrl($img_arr[0]['href_content']);


//$dir_name = $url_info_arr['file_info'][0] . "_" . time();
$dir_name = $url_info_arr['file_info'][0];
if (!file_exists(PATH . $dir_name)) {
    mkdir(PATH . $dir_name);
    mkdir(PATH . $dir_name . '/css');
    mkdir(PATH . $dir_name . '/js');
    mkdir(PATH . $dir_name . '/img');
}


$fp = fopen(PATH . $dir_name . '/index.php', "w+");
fwrite($fp, $curl_data);
fclose($fp);

wirteToFile('css', $css_arr, $dir_name, $url);
wirteToFile('js', $js_arr, $dir_name, $url);
wirteToFile('img', $img_arr, $dir_name, $url);
$css_info_arr = cssInfoDetection($dir_name, $img_url_str);
wirteToFile('img', $css_info_arr, $dir_name, $url);