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
/* 个人中心 */
class InfoController extends UserBaseController
{

    public function _initialize()
    {
        parent::_initialize();
        
    }
    /**
     * 用户信息首页 
     */
    public function index()
    {
        
       $this->assign('html_title','个人中心'); 
       return $this->fetch();

    }
    /**
     * 用户手册
     */
    public function guide()
    {
        $list=Db::name('guide')->where('type',0)->order('sort asc,id asc')->column('');
        $this->assign('html_title','用户手册');
        $this->assign('list',$list);
        $this->assign('aa','cccc');
        return $this->fetch();
        
    }
    /* 借出记录 */
    public function lender(){
        $uid=session('user.id');
        $name=$this->request->param('name','');
        $where=[
            'lender_id'=>['eq',$uid], 
            'status'=>['in',[3,4,5]]
        ];
        if($name!=''){
            $where['borrower_name']=['like','%'.$name.'%'];
        }
        $m_paper=Db::name('paper');
//         $where['status']=['eq',4];
//         $list_on=$m_paper->where($where)->column('');
//         $where['status']=['eq',3];
//         $list_expire=$m_paper->where($where)->order('expire_day asc')->column(''); 
//         $where['status']=['eq',5];
//         $list_overdue=$m_paper->where($where)->order('overdue_day asc')->column('');
         
        $list=$m_paper->where($where)->order('status asc,expire_day asc,overdue_day asc')->column('');
        unset($where['status']);
        $list_old=Db::name('paper_old')->where($where)->order('overdue_day asc')->column('');
       
        $this->assign('list',$list);
        
        $this->assign('list_old',$list_old);
        $this->assign('name',$name);
        $this->assign('html_title','出借记录'); 
        return $this->fetch();
    }
    /* 借款记录 */
    public function borrower(){
        $uid=session('user.id');
        $name=$this->request->param('name','');
        $where=[
            'borrower_id'=>['eq',$uid],
            'status'=>['in',[3,4,5]]
        ];
        if($name!=''){
            $where['lender_name']=['like','%'.$name.'%'];
        }
        $m_paper=Db::name('paper');
        
        $list=$m_paper->where($where)->order('status asc,expire_day asc,overdue_day asc')->column('');
        unset($where['status']);
        $list_old=Db::name('paper_old')->where($where)->order('overdue_day asc')->column('');
        
        $this->assign('list',$list); 
        $this->assign('list_old',$list_old);
        $this->assign('name',$name);
        $this->assign('html_title','借款记录'); 
        return $this->fetch();
    }
    /* 借款详情 */
    public function paper(){
        $id=$this->request->param('id',0,'intval');
        $where_paper=['id'=>['eq',$id]];
        $paper=Db::name('paper_old')->where($where_paper)->find();
        if(empty($paper)){
            $where_paper['status']=['in',[3,4,5]];
            $paper=Db::name('paper')->where($where_paper)->find();
            if(empty($paper)){
                $this->error('此借条不存在');
            }
            $statuss=config('paper_status');
            $paper['status_name']=$statuss[$paper['status']];
            //未完成的计算最终还款金额
            $paper['final_money']=zz_get_money_overdue($paper['real_money'],$paper['money'],config('rate_overdue'),$paper['overdue_day']);
            
        }else{
            $paper['status_name']='已还款结束'; 
        }
        $replys=Db::name('reply')->where('oid',$paper['oid'])->order('id desc')->column('');
        $uid=session('user.id');
        //如果是借款人操作则back==0
        $paper['back']=0;
        if($paper['lender_id']==$uid){
            $paper['back']=1;
        }
        $this->assign('paper',$paper);
        $this->assign('replys',$replys);
        $this->assign('reply_status',config('reply_status'));
        $this->assign('reply_types',config('reply_types'));
        $this->assign('html_title','借条详情');
        return $this->fetch();
        
    }
    
    /* 提交申请 */
    public function ajax_reply(){
        
        $data=$this->request->param('');
        $m_reply=Db::name('reply');
        $m_paper=Db::name('paper');
        
        $info_paper=$m_paper->where('oid',$data['oid'])->find();
        if(empty($info_paper)){
            $this->error('该借条已完成或已废弃');
        }
        switch ($data['type']){
            case 'delay':
                if(preg_match('/^\d+$/', $data['day'])!=1){
                    $this->error('延期天数错误');
                }
                if(preg_match('/^\d{1,2}$/', $data['rate'])!=1){
                    $this->error('新利率错误');
                }
                if(!in_array($data['rate'], config('rate'))){
                    $this->error('利率不支持，请参考补借页面的利率');
                }
                break;
            case 'back':
                $tmp=zz_get_money_overdue($info_paper['real_money'], $info_paper['money'], $info_paper['rate'], $info_paper['overdue_day']);
                if($tmp!=$data['final_money']){
                    $this->error('还款信息错误',url('user/info/index'));
                }
                break;
            default:
                $this->error('提交信息错误',url('user/info/index'));
        }
        
        
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        }
        unset($data['psw']);
         
        if($info_paper['lender_id']==$uid){
            $data['is_borrower']=0;
        }elseif($info_paper['borrower_id']==$uid){
            $data['is_borrower']=1;
        }else{
            $this->error('借条信息错误',url('user/info/index'));
        }
        $data['insert_time']=time();
        $data['update_time']=$data['insert_time'];
        $id=$m_reply->insertGetId($data);
        if($id>=1){
            $this->success('申请提交成功,请尽快联系对方确认，否则该申请将在第三日凌晨过期！',url('user/info/paper',['id'=>$info_paper['id']]));
        }else{
            $this->error('申请提交失败');
        }
    }
    /* 借款协议 */
    public function protocol(){
        $id=$this->request->param('id',0,'intval');
        $where_paper=['id'=>['eq',$id]];
        $paper=Db::name('paper_old')->where($where_paper)->find();
        if(empty($paper)){
            $where_paper['status']=['in',[3,4,5]];
            $paper=Db::name('paper')->where($where_paper)->find();
            if(empty($paper)){
                $this->error('此借条不存在');
            }
            $statuss=config('paper_status');
            $paper['status_name']=$statuss[$paper['status']];
            //未完成的计算最终还款金额
            $paper['final_money']=zz_get_money_overdue($paper['real_money'],$paper['money'],config('rate_overdue'),$paper['overdue_day']);
            
        }else{
            $paper['status_name']='已还款结束';
        }
        $protocol=Db::name('guide')->where('name','borrower')->find();
        $paper['content']=$protocol['content'];
        $this->assign('info',$paper); 
        $this->assign('html_title','借款协议');
        return $this->fetch();
        
    }
    
    /* qq */
    public function bind(){
        $this->assign('html_title','绑定信息');
        return $this->fetch();
    }
    /* qq */
    public function qq(){
        $this->assign('html_title','修改QQ号');
        return $this->fetch();
    }
    
    /* 修改qq */
    public function ajax_qq(){
          
        $data=$this->request->param('');
        
        //判断密码
        $uid=session('user.id');
        $m_user=Db::name('user');
        $user=$m_user->where('id',$uid)->find();
        $result=zz_psw($user, $data['psw']);
        if(empty($result[0])){
            $this->error($result[1],$result[2]);
        } 
        Db::name('user')->where('id',$uid)->update(['qq'=>$data['qq']]);
        session('user.qq',$data['qq']);
        $this->error('修改成功',url('user/info/index'));
    }
    /* 头像 */
    public function avatar(){
        $this->assign('html_title','换头像');
        return $this->fetch();
    }
    /* 头像修改 */
    public function ajax_avatar(){
        
       
        $file=$_FILES['avatar1'];
        
        if($file['error']==0){
            if($file['size']>config('avatar_size')){
                $this->error('文件超出大小限制');
            }
            $avatar='avatar/'.session('user.user_login').'.jpg';
            $path=getcwd().'/upload/';
            
            $destination=$path.'/'.$avatar;  
            if(move_uploaded_file($file['tmp_name'], $destination)){
                $avatar=zz_set_image($avatar,$avatar,100,100,6);
                if(is_file($path.$avatar)){
                    session('user.avatar',$avatar);
                    $this->success('上传成功',url('user/info/index'));
                }else{
                    $this->error('头像修改失败');
                }
            }else{
                $this->error('文件上传失败');
            }
        }else{
            $this->error('文件传输失败');
        }
    }
    /* weixin */
    public function weixin(){
        $this->assign('html_title','修改微信号');
        return $this->fetch();
    }
    /* 实名认证 */
    public function name(){
        $this->error('暂不开放');
        $this->assign('html_title','实名认证');
        return $this->fetch();
    }

}
