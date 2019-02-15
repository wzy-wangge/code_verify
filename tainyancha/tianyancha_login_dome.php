<?php
/**
 * Created by PhpStorm.
 * User: wangzhiying
 * Date: 2019/2/15
 * Time: 10:10
 */

/**
 * 天眼查登录实例
 * 仅供学习参考
 */

requests::set_header('Content-Type','application/json; charset=utf-8');

$geetestData = requests::post('https://www.tianyancha.com/verify/geetest.xhtml',json_encode(['uuid'=>'1550040663067']));
if(strpos($geetestData,'state')){
    $geetestData = json_decode($geetestData,1);
    if($geetestData['state'] == 'ok'){
        $gt = $geetestData['data']['gt'] ?? null;
        $challenge = $geetestData['data']['challenge'] ?? null;
    }
}
if(isset($gt) && isset($challenge)){
    //TODO 极验API效验
    $url = 'http://jiyanapi.c2567.com/shibie?gt='.$gt.'&challenge='.$challenge.'&referer=https://www.tianyancha.com&user=你的账号&pass=你的密码&return=json&model=0&format=utf8&devuser=wzy';
    $jsonjiyanapi = requests::get($url);
    $jsonjiyanapi = json_decode($jsonjiyanapi,1);
    if(isset($jsonjiyanapi['status']) && $jsonjiyanapi['status'] == 'ok'){
        //登录方法
        loginUser('天眼查账号','天眼查密码',$jsonjiyanapi['challenge'],$jsonjiyanapi['validate']);
    }else{
        //TODO  验证失败重试  自行实现
    }
}


function loginUser($user,$pw,$challenge,$validate){
    $pw = md5($pw);
    $loginUrl = 'https://www.tianyancha.com/cd/login.json';
    $param = [
        'autoLogin'=>true,
        'cdpassword'=>$pw,
        'challenge'=>$challenge,
        'loginway'=>'PL',
        'mobile'=>$user,
        'seccode'=>$validate.'|jordan',
        'validate'=>$validate
    ];
    $userjson = requests::post($loginUrl,json_encode($param));
    //TODO  登录错误自行实现
    $userjson = json_decode($userjson,1);
    //保存Cookie
    requests::set_cookie('tyc-user-info',json_encode($userjson['data']),'www.tianyancha.com');
    requests::set_cookie('auth_token',$userjson['data']['token'],'www.tianyancha.com');
}