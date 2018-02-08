<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class IndexController extends HomeBaseController
{
    private $token='zzpaper';
    public function index()
    {
        //测试
        //$openid='oyHSG1Rq1YeiZ1o8OoqFyt4ri4yw'; 
        //检测网页授权
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']); 
        if( preg_match('/micromessenger/', $ua) && empty(session('user'))){
            // 公众号的id和secret
            $appid = config('wx_appid');
            $appsecret = config('wx_appsecret');
            $index=url('portal/index/index','',true,true);
            $index0= urlencode($index);
            $scope='snsapi_userinfo';
            
            $code = $_GET["code"];
            if(empty($code)){ 
               //开始只获取openid 
                $scope='snsapi_base';
                $url0='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.
                '&redirect_uri='.$index0.'&response_type=code&scope='.$scope.'&state=STATE#wechat_redirect';
                session('wx',['scope'=>$scope,'url0'=>$url0]);
                header("Location: ".$url0);
                exit('正在获取微信授权openid');
            }
            
            $scope=session('wx.scope');
            //判断是获取用户信息还是基本信息
            if($scope=='snsapi_base'){
                //openid
                $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid.
                "&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
                $res = $this->https_request($url); 
            }elseif($scope=='snsapi_userinfo'){
                //userinfo
                //通过code换取网页授权access_token（访问令牌） 
                $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$appsecret.'&code='.$code.'&grant_type=authorization_code';
                //获取到access_token
                $json_obj = $this->https_request($get_token_url);
                //根据openid和access_token查询用户信息
                $access_token = $json_obj['access_token'];
                $openid = $json_obj['openid'];
                $get_user_info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
                //获取到用户信息
                $userinfo =$this->https_request($get_user_info_url);
                session('wx',$userinfo);
                //获取信息后跳转到注册页
                $this->redirect(url('user/register/register'));
            }
             //获取到openid就查询用户信息，没有信息需要查询微信信息后注册，有信息到主页
            if(empty($res['openid'])){
                exit('微信授权失败，退出或<a href="'.session('wx.url0').'">点击重试</a>');
            }else{
                session('wx.openid',$res['openid']);
                $user=Db::name('user')->where('openid',$res['openid'])->find();
                if(empty($user)){ 
                    //需要授权获取微信信息
                    $url0='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.
                    '&redirect_uri='.$index0.'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
                    session('wx.url0',$url0);
                    header("Location: ".$url0);
                    exit('正在授权获取微信信息'); 
                }else{
                    session('user',$user);
                    $this->redirect(url('user/index/index'));
                }
            }
            
        }
        $code=1;
        switch ($code){
            case 1:
                //$this->redirect(url('user/register/register'));
                $this->redirect(url('user/index/index'));
                break;
            case 2:
                $this->redirect(url('user/register/register'));
                break;
           default:
                $this->redirect(url('user/login/login'));
                break; 
        }
        exit;
    }
   /*  cURL函数简单封装 */
    function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output, true);
    }
    
    public function checkSignature()
    {
         
        $echoStr = $_GET["echostr"]; 
        // you must define TOKEN by yourself
        if (empty($this->token)) {
            throw new \Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        
        $token = $this->token;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        
        if( $tmpStr == $signature ){
            echo $echoStr;
        }else{
            echo false;
        }
        exit();
    }
     
    
}
