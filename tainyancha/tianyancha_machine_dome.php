<?php
/**
 * Created by PhpStorm.
 * User: wangzhiying
 * Date: 2019/2/15
 * Time: 10:17
 */

/**
 * 过天眼查机器验证实例
 * 非第三方极验
 * 选字验证
 * 仅供学习参考
*/


/**
 *   获取验证图片信息
 *   需要携带登录后cookie信息
 *   请求携带的  www.tianyancha.com  是cookie作用域
 */
$Captcha =  requests::get('https://antirobot.tianyancha.com/captcha/getCaptcha.json?t='.time().rand(100,999).'&_='.time().rand(100,999));

echo date('Y-m-d H:i:s').' 开始解析验证图片'.PHP_EOL;
$Captcha = json_decode($Captcha,1);
if(is_array($Captcha) && $Captcha['state'] == 'ok'){
    /** 合成图片（二张验证码图片合二为一 中间10px间隔 ） */
    echo date('Y-m-d H:i:s').' 开始合成图片'.PHP_EOL;
    $bg_w    = 320; // 背景图片宽度
    $bg_h    = 140; // 背景图片高度

    $background = imagecreatetruecolor($bg_w,$bg_h); // 背景图片
    $color   = imagecolorallocate($background, 202, 201, 201); // 为真彩色画布创建白色背景，再设置为透明
    imagefill($background, 0, 0, $color);
    imageColorTransparent($background, $color);

    $resource = imagecreatefromstring(base64_decode($Captcha['data']['targetImage']));
    $pic_w = imagesx($resource);
    $pic_h = imagesy($resource);
    imagecopyresized($background,$resource,0,0,0,0,$pic_w,$pic_h,$pic_w,$pic_h);


    $resource = imagecreatefromstring(base64_decode($Captcha['data']['bgImage']));
    $pic_w = imagesx($resource);
    $pic_h = imagesy($resource);
    imagecopyresized($background,$resource,0,40,0,0,$pic_w,$pic_h,$pic_w,$pic_h);

    //TODO 文件路径 自己更改
    $imgpath = __DIR__.'/cache/img/'.$Captcha['data']['id'].'.jpg';

    // 输出图像
    imagejpeg($background,$imgpath);

    echo date('Y-m-d H:i:s').' 保存图片成功'.PHP_EOL;
    // 释放内存
    imagedestroy($background);

    $curl_api_xy = function ($imgpath){
        $curl = function ($url,$fields){
            $ch = curl_init() ;
            curl_setopt($ch, CURLOPT_URL,$url) ;
            curl_setopt($ch, CURLOPT_POST,count($fields)) ;
            curl_setopt($ch, CURLOPT_POSTFIELDS,$fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
            curl_setopt($ch, CURLOPT_REFERER,'') ;
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3') ;
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        };

        $url = 'http://code.chaojiying.net/Upload/GetScore.php' ;
        $fields = array(
            'user'=>'你的账号' ,
            'pass2'=>md5('你的密码')
        );

        $userinfo = json_decode($curl($url,$fields),1);
        if(is_array($userinfo) && $userinfo['err_str'] == 'OK'){
            echo date('Y-m-d H:i:s').' 超级鹰账号获取成功，账户余额:'.$userinfo['tifen'].PHP_EOL;
            if($userinfo['tifen'] < 25){
                echo date('Y-m-d H:i:s').' 当前余额不足请前往充值'.PHP_EOL;
                exit;
            }
        }
        //TODO  超级鹰官网自己注册 https://www.chaojiying.com
        //TODO  关注公众号可以得1000分  可用于免费测试
        //TODO  验证一次25分 1块钱1000分  40次
        $url = 'http://upload.chaojiying.net/Upload/Processing.php' ;
        $fields = array(
            'user'=>'你的账号' ,
            'pass2'=>md5('你的密码'),
            'softid'=>'你的应用ID' ,
            'codetype'=>'9004',  //TODO 验证类型  9004 坐标多选,返回1~4个坐标,如:x1,y1|x2,y2|x3,y3
            'userfile'=> new \CURLFile(realpath($imgpath)),
        );

        return json_decode($curl($url,$fields),1);
    };

    $xy = $curl_api_xy($imgpath);
    if(is_array($xy) && $xy['err_str'] == 'OK'){
        echo date('Y-m-d H:i:s').' API获取坐标成功 '.$xy['pic_str'].PHP_EOL;
        //TODO 链接API得到做坐标   返回案例:  219,59|272,87|187,62
        $tmparr = explode('|',$xy['pic_str']);
        foreach($tmparr as $val){
            $temarr2 = explode(',',$val);
            //TODO 转换格式并减去相应Y轴坐标值
            $data[] = (object)array('x'=>(int)$temarr2[0],'y'=>(int)$temarr2[1]-40);
        }
        $clickLocs =  json_encode($data);

        $url = 'https://antirobot.tianyancha.com/captcha/checkCaptcha.json';
        $url .= '?captchaId='.$Captcha['data']['id'];
        $url .= '&clickLocs='.$clickLocs;
        echo date('Y-m-d H:i:s').' 点击坐标为:'.$url.PHP_EOL;
//            requests::set_header('Content-Type','application/json; charset=utf-8');
        $jsondata =  requests::get($url,'','','','www.tianyancha.com');
        echo date('Y-m-d H:i:s').' 得到发送坐标验证数据返回结果 '.$jsondata.PHP_EOL;
        $checkCaptcha =  json_decode($jsondata,1);
        if(is_array($checkCaptcha) && $checkCaptcha['state'] == 'ok'){
            echo date('Y-m-d H:i:s').' 机器验证已完成 '.PHP_EOL;
            //TODO 验证通过
        }

    }else{
        echo date('Y-m-d H:i:s').' API获取坐标失败。。'.PHP_EOL;
    }

}else{
    echo date('Y-m-d H:i:s').' 验证图片获取错误'.PHP_EOL;
}
exit;