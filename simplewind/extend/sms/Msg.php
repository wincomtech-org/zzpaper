<?php
namespace sms;
/* 短信 */
class Msg{
    /* 短信验证码接口 */
    public function reg($phone,$content){
        $msg=session('sms');
        $time=time();
        if(empty($msg)){
            session('sms',['mobile'=>$phone,'content'=>$content,'time'=>$time]);
        }elseif(($time-$msg['time'])<60){
            return '1分钟内只能发送一次';
        }
         
        $statusStr = array(
            "0" => "success",
            "-1" => "参数不全",
            "-2" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间！",
            "30" => "密码错误",
            "40" => "账号不存在",
            "41" => "余额不足",
            "42" => "帐户已过期",
            "43" => "IP地址限制",
            "50" => "内容含有敏感词"
        );
        $smsapi = "http://www.smsbao.com/"; //短信网关
        //短信平台帐号
        $user=config('sms_id');
        $pass = md5(config('sms_psw')); //短信平台密码
        $content='【'.config('zztitle').'】您的验证码是'.$content;
        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$phone."&c=".urlencode($content);
        $result =file_get_contents($sendurl) ;
         
        return empty($statusStr[$result])?'发送失败':$statusStr[$result];
    }
    /* 短信验证  */
    public function verify($phone,$content){
        $msg=session('sms'); 
        $time=time();
        if(empty($msg)){
            return '短信验证失效';
        }elseif(($msg['time']-$time)>600){
            return '短信验证码过期';
        }elseif($msg['content']!=$content){
            return '短信验证码错误';
        }else{ 
            return 'success';
        }
    }
    
}