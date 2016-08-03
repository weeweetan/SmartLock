<?php
/**
 * 验证码检查
 */
function check_verify($code, $id = "")
{
    $verify = new \Think\Verify();
    return $verify->check($code, $id);
}

/**
 * 正则匹配cookie
 * @return array
 */
function get_cookie(){
    $res;
    $url = 'http://202.115.80.153/default2.aspx';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    $res = curl_exec($ch);
    list($header,$body) = explode("\r\n\r\n", $res,2);
    preg_match_all('/Set\-Cookie:([^;]*);/', $header,$matches);
    
    return $matches;
}

/**
 * 请求验证码
 * @param unknown $cookie
 * @return string
 */

function get_vertify($cookie){
    $header=array(
        'Host: 202.115.80.153',
        'Origin: http://202.115.80.153',
        'Referer: http://202.115.80.153/',
        "Cookie:$cookie",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2687.0 Safari/537.36'
    );
    $url = '202.115.80.153/CheckCode.aspx';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
/*     $path = "/Public/vertify/";
    $time = time();
    $filename =$path.$time.'.jpg';
    $file = 'vertify.jpg';
    @file_put_contents($file, $res);
    $file = "/SmartLock/".$file;   
    if (rename($file, $filename)){
        header('content-type:image/GIF');
        return $res;
    } */
    
}

/**
 * 模拟登陆
 * @param String $cookie
 * @param String $xh
 * @param String $password
 * @param String $vertify
 * @return string
 */
function moniLogin($cookie,$xh,$password,$vertify){
    header('content-type:text/html;charset=gb2312');
    $header=array(
        'Host: 202.115.80.153',
        'Origin: http://202.115.80.153',
        'Referer: http://202.115.80.153',
        "Cookie:$cookie",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2687.0 Safari/537.36'
    );
    $url = 'http://202.115.80.153/default2.aspx';
    $post['__VIEWSTATE'] = 'dDwyODE2NTM0OTg7Oz7QBx05W486R++11e1KrLTLz5ET2Q==';
    $post['txtUserName'] = "$xh";
    $post['TextBox2'] = "$password";
    $post['txtSecretCode'] = "$vertify";
    $post['lbLanguage'] = '';
    $post['RadioButtonList1'] = iconv('utf-8', 'gb2312', '学生');
    $post['Button1'] = iconv('utf-8', 'gb2312', '登录');
    $ch1 = curl_init();
    curl_setopt($ch1, CURLOPT_URL, $url);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch1, CURLOPT_POST, 1);
    curl_setopt($ch1, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch1, CURLOPT_FOLLOWLOCATION, 1);
    $result = curl_exec($ch1);
    curl_close($ch1);
    
    if (preg_match_all("/alert\(\'.*\'\);/", $result, $matches)){
        $str = mb_substr($matches[0][0], 7,7);
        $str = trim($str);
        
        $str1 = "验证码不正确";
        
        if ($str == mb_convert_encoding($str1, 'gb2312','utf-8')) {
            return  -1;    //表示验证码错误
        }  
    }else{
        return 1;  //表示登录成功
    }
}

/**
 * 
 * @param unknown $data
 * @param unknown $filename
 * @param string $picPath
 * @param string $logo
 * @param string $size
 * @param string $level
 * @param number $padding
 * @param string $saveandprint
 * @return string
 */
function qrcode($data,$filename,$picPath=false,$logo=false,$size='4',$level='L',$padding=2,$saveandprint=false){
    vendor("phpqrcode.phpqrcode");//引入工具包
    // 下面注释了把二维码图片保存到本地的代码,如果要保存图片,用$fileName替换第二个参数false
    $path = $picPath?$picPath:__APP__."/QRcode"; //图片输出路径
    mkdir($path);
    //在二维码上面添加LOGO
    if(empty($logo) || $logo=== false) { //不包含LOGO
        if ($filename==false) {
            \QRcode::png($data, false, $level, $size, $padding, $saveandprint); //直接输出到浏览器，不含LOGO
        }else{
            $filename=$path.'/'.$filename; //合成路径
            \QRcode::png($data, $filename, $level, $size, $padding, $saveandprint); //直接输出到浏览器，不含LOGO
        }
    }else { //包含LOGO
        if ($filename==false){
            //$filename=tempnam('','').'.png';//生成临时文件
            die('参数错误');
        }else {
            //生成二维码,保存到文件
            $filename = $path . '\\' . $filename; //合成路径
        }
        \QRcode::png($data, $filename, $level, $size, $padding);
        $QR = imagecreatefromstring(file_get_contents($filename));
        $logo = imagecreatefromstring(file_get_contents($logo));
        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        $logo_qr_width = $QR_width / 5;
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = $logo_height / $scale;
        $from_width = ($QR_width - $logo_qr_width) / 2;
        imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
        if ($filename === false) {
            Header("Content-type: image/png");
            imagepng($QR);
        } else {
            if ($saveandprint === true) {
                imagepng($QR, $filename);
                header("Content-type: image/png");//输出到浏览器
                imagepng($QR);
            } else {
                imagepng($QR, $filename);
            }
        }
    }
    return $filename;
}

/**
 * 发送推送消息
 * @param $title
 * @param $content
 * @param $alias
 * @param $data
 */
function sendNotification($title,$content,$alias,$data)
{
    $app_key = '8c4926634ed38aba25f262aa';
    $master_secret = 'ade388e39cfa0335a133a4b9';
    $client = new \JPush\Client($app_key, $master_secret);
    $client->push()
        ->setPlatform('Android')
        ->addAlias($alias)
        ->addAndroidNotification($content, $title, 1, $data)
        ->send();
}

function returnDistance($lat1,$lng1,$lat2,$lng2)
{
    $EARTH_RADIUS=6378.137;
    $PI=3.1415926;
    $radLat1 = $lat1 * $PI / 180.0;
    $radLat2 = $lat2 * $PI / 180.0;
    $a = $radLat1 - $radLat2;
    $b = ($lng1 * $PI / 180.0) - ($lng2 * $PI / 180.0);
    $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
    $s = $s * $EARTH_RADIUS;
    /*$s = round($s * 1000);*/

    return $s;
}