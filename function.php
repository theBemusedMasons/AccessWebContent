<?php
/**
 * Created by PhpStorm.
 * User: feng
 * Date: 16/6/21
 * Time: 下午3:31
 */
function urlCheck($url)
{
    //地址类型检查
    if (strstr($url, "http://")) {
        $a_arr = explode("/", str_replace("http://", "", $url));
    } elseif (strstr($url, "https://")) {
        $a_arr = explode("/", str_replace("https://", "", $url));
    } else {
        $a_arr = explode("/", $url);
    }

    foreach ($a_arr as $key => $value) {
        if (strstr($value, '.php')) {
            if ($key != (sizeof($a_arr) - 1)) exit('暂不支持该格式地址');
        }
    }

    if (strstr($url, "aspx")) {
        exit('暂不支持该格式地址');
    } else {
        if (!strstr($url, "html") && !strstr($url, "php")) {
            $check_url = $url . 'index.html';
            $statusCode = curlCheck($url);
            if ($statusCode == 200) {
                return $check_url;
            } else {
                $check_url = $url . 'index.php';
                $statusCode = curlCheck($url);
                if ($statusCode == 200) {
                    return $check_url;
                } else {
                    echo '网页不完整';
                }
            }
        } else {
            return $url;
        }
    }
}

function getUrlInfo($url)
{
    preg_match('/\/[^\/]*$/', $url, $matches);
    if (strstr($matches[0], "?")) {
        $arr = explode("?", $matches[0]);
        $matches[0] = $arr[0];
    }

    $url_info_arr['header'] = substr($url, 0, 0 - strlen($matches[0])) . '/';
    $url_info_arr['file_name'] = substr($matches[0], 1, strlen($matches[0]) - 1);
    $url_info_arr['file_info'] = explode(".", $url_info_arr['file_name']);
    return $url_info_arr;
}

function curlCheck($url)
{
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    $result = curl_exec($curl);
    if ($result !== false) {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        return $statusCode;
    } else {
        return 404;
    }
}

function curlGet($url)
{
    // 初始化一个 cURL 对象
    $curl = curl_init();
    // 设置你需要抓取的URL
    curl_setopt($curl, CURLOPT_URL, $url);
    // 设置header
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // 运行cURL，请求网页
    $curl_data = curl_exec($curl);
    // 关闭URL请求
    curl_close($curl);
    return $curl_data;
}

/**
 * css 探机
 * @param $data
 * @return array
 */
function cssDetection($data)
{
    $return_arr = array();
    preg_match_all('/href[\s=\s]*"[\S]*css"/', $data, $matches);
    //$matches[0] = 'href="css/swiper.3.1.2.min.css"'
    foreach ($matches[0] as $key => $values) {
        $a_arr = explode('"', $values);//$a_arr[1]为css/swiper.3.1.2.min.css
        $b_arr = explode('/', $a_arr[1]);
        $return_arr[] = array('type' => 'css', 'href_content' => $a_arr[1], 'file_name' => $b_arr[sizeof($b_arr) - 1]);
    }
    return $return_arr;
}

function srcDetection($type, $data)
{
    $return_arr = array();
    preg_match_all('/src[\s=\s]*"[\S]*"/', $data, $matches);
    //$matches[0] = 'src="img/swiper.3.1.2.min.img"'
    foreach ($matches[0] as $key => $values) {
        if ($type == 'js') {
            $compare_str = ".js";
            if (strstr($values, $compare_str)) {
                $a_arr = explode('"', $values);//$a_arr[1]为js/swiper.3.1.2.min.js
                $b_arr = explode('/', $a_arr[1]);
                $return_arr[] = array('type' => $type, 'href_content' => $a_arr[1], 'file_name' => $b_arr[sizeof($b_arr) - 1]);
            }
        } else {
            $compare_str = ".js";
            if (!strstr($values, $compare_str)) {
                $a_arr = explode('"', $values);//$a_arr[1]为css/swiper.3.1.2.min.css
                $b_arr = explode('/', $a_arr[1]);
                $return_arr[] = array('type' => $type, 'href_content' => $a_arr[1], 'file_name' => $b_arr[sizeof($b_arr) - 1]);
            }
        }
    }
    return $return_arr;
}

function cssInfoDetection($dir_name, $img_url_str)
{
    $return_arr = array();
    $replace_arr = array();
    $dir = opendir(PATH . "{$dir_name}/css");
    while (($file = readdir($dir)) !== false) {
        if (strstr($file, 'css')) {
            $data = file_get_contents(PATH . "{$dir_name}/css/" . $file);
            $new_data = $data;
            preg_match_all('/url\s*\(.*\)/', $data, $matches);
            foreach ($matches as $key => $values) {
                $str = str_replace(array('url', '(', ')', ' '), '', $matches[0]);
                $arr = explode('/', $str[0]);
                $replace_arr = array('old' => $str[0], 'new' => '../img/' . $arr[sizeof($arr) - 1]);
                $new_data = replaceCssContent($replace_arr, $new_data);
                $return_arr[] = array('type' => 'img', 'href_content' => $img_url_str . '/' . $arr[sizeof($arr) - 1], 'file_name' => $arr[sizeof($arr) - 1]);
            }
            $fp = fopen(PATH . "{$dir_name}/css/" . $file, 'w+');
            fwrite($fp, $new_data);
            fclose($fp);
        }
    }
    return $return_arr;
}

function getImgUrl($one_img_url)
{
    $str = '';
    $arr = explode("/", $one_img_url);
    foreach ($arr as $key => $value) {
        if ($key != (sizeof($arr) - 1)) {
            $str .= $value;
        }
    }
    return $str;
}

function replaceWebContent($arr, $data)
{
    $new_data = $data;
    foreach ($arr as $key => $values) {
        $new_data = str_replace($values['href_content'], $values['type'] . '/' . $values['file_name'], $new_data);
    }
    return $new_data;
}

function replaceCssContent($arr, $data)
{
//    $new_date = $data;
    $new_date = str_replace($arr['old'], $arr['new'], $data);
    return $new_date;
}

function wirteToFile($type, $arr, $dir_name, $url)
{
    $url_info_arr = getUrlInfo($url);
    foreach ($arr as $key => $values) {
        if (empty($values['href_content']) || empty($values['file_name'])) {
            continue;
        }
        $data = curlGet($url_info_arr['header'] . $values['href_content']);
        $fp = fopen(PATH . $dir_name . '/' . $type . '/' . $values['file_name'], 'w+');
        fwrite($fp, $data);
        fclose($fp);
    }
}