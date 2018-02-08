<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\HomeBaseController;
use think\Validate;
use think\Db;
 
use sms\Msg;

class RegisterController extends HomeBaseController
{

    /**
     * 前台用户注册
     */
    public function index()
    {
        $redirect = $this->request->post("redirect");
        if (empty($redirect)) {
            $redirect = $this->request->server('HTTP_REFERER');
        } else {
            $redirect = base64_decode($redirect);
        }
        session('login_http_referer', $redirect);

        if (cmf_is_user_login()) {
            return redirect($this->request->root() . '/');
        } else {
           // return $this->fetch(":register");
           $this->redirect(url('register'));
        }
    }
    /**
     * 前台用户注册页面
     */
    public function register()
    {
        $this->assign('html_title','注册');
       return $this->fetch();
         
    }
    /**
     * 发送验证码
     */
    public function sendmsg()
    { 
        $phone=$this->request->param('tel',0);
        $type=$this->request->param('type','reg');
        $tmp=Db::name('user')->where('mobile',$phone)->find();
        if($type=='reg'){ 
            if(!empty($tmp)){
                $this->error('该手机号已被使用');
            }
        }elseif($type=='find'){
            if(empty($tmp)){
                $this->error('该手机号不存在');
            }
        }
        $msg=new Msg();
         
        $this->error($msg->reg($phone,rand(100000,999999)));
    }
    
    /**
     * 前台用户注册提交
     */
    public function ajaxRegister()
    {
         
            $rules = [ 
                'user_pass' => 'require|number|length:6', 
                'mobile'=>'require|number|length:11', 
                'user_nickname'=>'require|chs|min:2', 
            ]; 
            $redirect                = url('user/index/index');
            $validate = new Validate($rules);
            $validate->message([ 
                'user_pass.require' => '密码不能为空', 
                'user_pass.length'     => '密码为6位数字',
                'mobile.require' => '手机号码不能为空',
                'mobile.length'     => '手机号码格式错误',
                'user_nickname.chs'=>'请填写真实姓名',
                'user_nickname.require'=>'请填写真实姓名',
                'user_nickname.min'=>'请填写真实姓名',
            ]);
            
            $data1 = $this->request->post();
            $data=[
                'user_login'=>$data1['idcard'],
                'user_nickname'=>$data1['username'],
                'user_pass'=>$data1['password'],
                'mobile'=>$data1['tel'],
                'qq'=>$data1['qq'],
                'last_login_ip'   => get_client_ip(0, true),
                'create_time'     => time(),
                'last_login_time' => time(),
                'user_status'     => 1,  
                "user_type"       => 2,//会员
            ];
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            //验证码
            $msg=new Msg();
            $sms=$msg->verify($data['mobile'],$data1['sms']);
            if($sms!='success'){
                $this->error($sms);
            }
            import('idcard1',EXTEND_PATH);
            $idcard1= new \Idcard1();
            if(($idcard1->validation_filter_id_card($data['user_login']))!==true){
                $this->error('身份证号码非法!');
            }
            if(preg_match(config('reg_mobile'), $data['mobile'])!=1){
                $this->error('手机号码错误');
            }
            
            $data['user_pass'] = cmf_password($data['user_pass']);
            $result = $this->validate($data, 'User');
            if ($result !== true) {
                $this->error($result);
            } else {
               
                $result             = Db::name('user')->insertGetId($data);
                if ($result !== false) {
                    $data   = Db::name("user")->where('id', $result)->find();
                    cmf_update_current_user($data);
                    $this->success("注册成功！");
                } else {
                    $this->error("注册失败！");
                }
            }
             
       
    }
}