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

class IndexController extends HomeBaseController
{
    public function index()
    {
        $code=1;
        //检测网页授权
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $index=url('portal/index/index','',true,true);
        
        if( preg_match('/micromessenger/', $ua) && empty(session('wx'))){
            // 公众号的id和secret
            $appid = config('wx_appid');
            $appsecret = config('wx_appsecret');
           
            if(empty($_GET['code'])){
                $url = urlencode($index); 
                $url0='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.
                '&redirect_uri='.$index.'&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
                header("Location: ".$url0);
                exit('正在获取微信授权');
            }
            $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid.
            "&secret=".$appsecret."&code=".$_GET['code']."&grant_type=authorization_code";
            $res = $this->https_request($url);
            $res=json_decode($res, true);
            session('wx',$res);
            
        }
        if( preg_match('/micromessenger/', $ua)){
            dump(session('wx'));
            exit('fff');
        }
        
         
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
    // cURL函数简单封装
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
        return $output;
    }
    
}
