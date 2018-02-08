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

use cmf\controller\UserBaseController;
use think\Db;

class PaperController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        
    }
    /**
     *查信用,搜索借条
     */
    public function search()
    { 
        $this->assign('html_title','查信用');
         
        if(empty(session('user.is_name'))){ 
            $this->assign('error','没有实名认证，不能查信用');
            return $this->fetch();
        } 
        $idcard=$this->request->param('identity_num','','trim');
        if(empty($idcard)){
            $this->assign('error','');
            return $this->fetch();
        }
        $this->assign('idcard',$idcard); 
        $info=Db::name('user')->where(['user_login'=>$idcard,'user_type'=>2])->find();
        if(empty($info)){
            $this->assign('error','没有此用户');
            return $this->fetch(); 
        }
       
        $list1=Db::name('paper')
        ->where(['borrower_idcard'=>['eq',$idcard],'status'=>['in',[3,4,5]]])
        ->order('status asc,expire_day asc,overdue_day asc')
        ->column(''); 
        $list2=Db::name('paper_old')
        ->where(['borrower_idcard'=>$idcard])
        ->order('overdue_day asc')
        ->column(''); 
         
        $this->assign('info',$info); 
        $this->assign('list1',$list1); 
        $this->assign('list2',$list2); 
        $this->assign('paper_status',config('paper_status')); 
        return $this->fetch('search_info');
      
    }
    
     
    /**
     *补借条
     */
    public function send()
    {
        $this->assign('html_title','补借条'); 
        //0借款1出借
        $this->assign('send_type',$this->request->param('send_type',0,'intval')); 
        $error='';
         if(empty(session('user.is_name'))){
            $error='没有实名认证，补借条'; 
        } 
        $this->assign('error',$error);
        return $this->fetch();
        
    }
    /**
     *补借条执行
     */
    public function sendPost()
    {
       
        $user0=Db::name('user')->where('id',session('user.id'))->find();
        if(empty($user0['is_name'])){
            $this->error('没有实名认证，不能补借条'); 
        }
        $data0=$this->request->param(); 
         
        $time=time();
        $today=date('Ymd',$time); 
        //判断时间
        $data=[ 
            'end_time'=>strtotime($data0['end']),
            'start_time'=>strtotime($today),
            'insert_time'=>$time,
            'update_time'=>$time,
            'rate'=>$data0['rate'],
            'money'=>$data0['money'],
            'use'=>$data0['use'],
             
        ];  
        //判断金钱格式
        if(preg_match(config('reg_psw'),$data0['psw'])!=1){
            $this->error('密码输入有误');
        }
        //判断金钱格式
        if(preg_match(config('reg_money'),$data['money'])!=1){
            $this->error('借款金额输入有误');
        }
        //计算到期天数
        $data['expire_day']=bcdiv(($data['end_time']-$data['start_time']),86400,0);
        if($data['expire_day']<1){
            $this->error('还款时间最早从明天开始');
        } 
        
       //计算利息保存利率为百倍整数，所以360*100=36000
        $data['real_money']=zz_get_money($data['money'],$data['rate'],$data['expire_day']);
        
         //获取对方信息
        $user1=Db::name('user')->where('user_login',$data0['idcard'])->find();
        if(empty($user1)){
            $this->error('对方身份证号不存在'); 
        }
        if(empty($user1['is_name'])){
            $this->error('对方未实名认证，不能补借条');
        }
        if($user1['id']==$user0['id']){
            $this->error('不能借给自己');
        }
        
        //比较密码
        $result=zz_psw($user0, $data0['psw']); 
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        $m_paper=Db::name('paper'); 
        //判断是借款还是出借
        if(empty($data0['send_type'])){ 
            $count=$m_paper->where(['borrower_id'=>$user0['id'],'start_time'=>$data['start_time']])->count();
            
            $data_reply=[
                'insert_time'=>$time,
                'update_time'=>$time,
                'type'=>'send',
                'is_borrower'=>1,
                'oid'=>$today.'-'.$user0['id'].'-'.($count+1),
            ];
            $data['borrower_id']=$user0['id'];
            $data['borrower_name']=$user0['user_nickname'];
            $data['borrower_idcard']=$user0['user_login'];
            $data['borrower_mobile']=$user0['mobile'];
            $data['lender_id']=$user1['id'];
            $data['lender_name']=$user1['user_nickname'];
            $data['lender_idcard']=$user1['user_login'];
            $data['lender_mobile']=$user1['mobile'];
            
        }else{
            $count=$m_paper->where(['lender_id'=>$user0['id'],'start_time'=>$data['start_time']])->count();
            $data_reply=[
                'insert_time'=>$time,
                'update_time'=>$time,
                'type'=>'send',
                'is_borrower'=>0,
                'oid'=>$today.'-'.$user1['id'].'-'.($count+1),
            ];
            $data['borrower_id']=$user1['id'];
            $data['borrower_name']=$user1['user_nickname'];
            $data['borrower_idcard']=$user1['user_login'];
            $data['borrower_mobile']=$user1['mobile'];
            $data['lender_id']=$user0['id'];
            $data['lender_name']=$user0['user_nickname'];
            $data['lender_idcard']=$user0['user_login'];
            $data['lender_mobile']=$user0['mobile'];
        }
        $data['oid']=$data_reply['oid'];
       
        Db::startTrans();
        try {
            $m_paper->insert($data);
            Db::name('reply')->insert($data_reply);
        } catch (\Exception $e) {
            Db::rollBack();
            $this->error('补借条失败，请重试!'.$e->getMessage());
        }
        
        Db::commit();
        $this->success('借条已经提交，等待对方确认',url('user/index/index')); 
         
    }
    /* 申请详情 */
    public function confirm(){
        $id=$this->request->param('id',0,'intval');
        $info_reply=Db::name('reply')->where('id',$id)->find();
        if(empty($info_reply)){
            $this->error('该申请不存在，刷新');
        }
        $info_paper=Db::name('paper')->where('oid',$info_reply['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        //判断是否显示同意
        $uid=session('user.id');
        $info_reply['send_type']=0;
        $send_type=0;
        if(($info_reply['is_borrower']==1 && $info_paper['lender_id']==$uid) || ($info_reply['is_borrower']==0 && $info_paper['borrower_id']==$uid)){
            $info_reply['send_type']=1;
        }
        $statuss=config('paper_status');
        $info_paper['status_name']=$statuss[$info_paper['status']];
        $this->assign('info_reply',$info_reply);
        $this->assign('info_paper',$info_paper);
        $this->assign('send_type',$send_type);
        $this->assign('html_title','申请详情');
        return $this->fetch();
    }
    
    /* 申请处理 */
    public function ajax_confirm(){
       
        $data=$this->request->param('');
        $m_reply=Db::name('reply');
        $m_paper=Db::name('paper');
        $where_reply=['id'=>$data['id'],'status'=>0,'is_overtime'=>0];
        $info_reply=$m_reply->where($where_reply)->find();
        if(empty($info_reply)){
            $this->error('该申请不存在，或已被处理');
        }
        
        $info_paper=$m_paper->where('oid',$info_reply['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        if($info_reply['is_borrower']==1 && $info_paper['lender_id']==$uid){
            $user1=$m_user->where('id',$info_paper['borrower_id'])->find();
            $user2=$user;
        }elseif($info_reply['is_borrower']==0 && $info_paper['borrower_id']==$uid){
            $user2=$m_user->where('id',$info_paper['lender_id'])->find();
            $user1=$user; 
        }else{
            $this->error('无权操作此该借条');
        }
        
        //判断是否显示同意s
        $data_reply=['update_time'=>time()];  
        //驳回
        if($data['op']==0){
            $data_reply['status']=2; 
            if($info_reply['type']=='send'){
                $data_paper=['status'=>1,'update_time'=>$data_reply['update_time']]; 
            }
        }else{
            //同意，处理借条
            $data_reply['status']=1; 
            
            switch($info_reply['type']){
                case 'send':
                    $data_paper=['update_time'=>$data_reply['update_time']];
                    //预期天数归0，计算到期天数
                    $data_paper['overdue_day']=0;
                    $data_paper['expire_day']=bcdiv(($info_paper['end_time']-strtotime(date('Y-m-d'))),86400,0);
                    if($data['expire_day']>=1){
                        $data_paper['status']=4;
                    }elseif($data['expire_day']==0){
                        $data_paper['status']=3;
                    }else{
                        $this->error('借条信息错误',url('user/index/index'));
                    }
                     
                    break;
                case 'delay':
                    $data_paper=['update_time'=>$data_reply['update_time']];
                    $data_paper['rate']=$info_reply['rate']; 
                    $data_paper['end_time']=$info_paper['end_time']+$info_reply['day']*86400;
                    $data_paper['real_money']=1;
                    //预期天数归0，计算到期天数
                    $data_paper['overdue_day']=0;
                    $data_paper['expire_day']=bcdiv(($data_paper['end_time']-strtotime(date('Y-m-d'))),86400,0);
                    if($data['expire_day']>=1){
                        $data_paper['status']=4; 
                    }elseif($data['expire_day']==0){
                        $data_paper['status']=3; 
                    }else{
                        $data_paper['status']=5;
                        $data_paper['overdue_day']=0-$data['expire_day'];
                    }
                    //计算利息
                    $days=bcdiv(($data_paper['end_time']-$info_paper['end_time']),86400,0);
                    $data_paper['real_money']=zz_get_money($info_paper['money'],$data_paper['rate'],$days);
                     break;
                case 'back':
                    //要删除paper，增加old,组装数据$info_paper
                    $info_paper['final_money']=$info_reply['final_money'];
                    $info_paper['update_time']=$data_reply['update_time'];
                    unset($info_paper['id']);
                    unset($info_paper['status']);
            }
                 
        }
        Db::startTrans();
        try {
            //更新申请
            $m_reply->where($where_reply)->update($data_reply);
            //更新借条状态
            if(isset($data_paper)){
                $m_paper->where('id',$info_paper['id'])->update($data_paper);
            }
            //删除paper，增加old,组装数据$info_paper
            if($data['op']==1 ){
                if($info_reply['type']=='back'){
                    $m_paper->where('id',$info_paper['id'])->delete();
                    Db::name('paper_old')->insert($info_paper);
                    //确认还款后更新用户信息
                    $data_user1=['back'=>bcsub($user1['back'],$info_paper['money'],2)];
                    $data_user2=['send'=>bcsub($user2['send'],$info_paper['money'],2)];
                     
                    $m_user->where('id',$user1['id'])->update($data_user1);
                    $m_user->where('id',$user2['id'])->update($data_user2);
                }elseif($info_reply['type']=='send'){
                    //确认借款后更新用户信息
                    $data_user1=['back'=>bcadd($user1['back'],$info_paper['money'],2)];
                    $data_user2=['send'=>bcadd($user2['send'],$info_paper['money'],2)];
                    //更新接款人数
                    $m_borrowers=Db::name('borrowers');
                    $data_borrowers=['borrower_id'=>$user1['id'],'lender_id'=>$user2['id']];
                    $tmp=$m_borrowers->where($data_borrowers)->find();
                    if(empty($tmp)){
                        $m_borrowers->insert($data_borrowers);
                        $data_user1['borrow_man']=$user1['borrow_man']+1;
                    }
                    //累计借款笔数
                    $data_user1['borrow_num']=$user1['borrow_num']+1; 
                    //borrow_money累计借款
                    $data_user1['borrow_money']=$user1['borrow_money']+$info_paper['money'];
                    
                    //出借人信息
                     //累计出借
                    $data_user2['lender_money']=$user2['lender_money']+$info_paper['money'];
                    $m_user->where('id',$user1['id'])->update($data_user1);
                    $m_user->where('id',$user2['id'])->update($data_user2);
                }
               
            }
            Db::commit();
            
        } catch (\Exception $e) {
            Db::rollBack();
            $this->error('操作失败！'.$e->getMessage());
        }
        $this->success('数据已更新成功',url('user/index/index'));
    }
    
    
     
}
