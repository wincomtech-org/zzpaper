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
use think\db;
/*处理每日定时任务  */
class TimeController extends HomeBaseController
{
    /*处理每日定时任务  */
    public function time()
    {
        zz_log('每日任务开始','time.log');
        set_time_limit(600);
        
        $data_action=[];
        //获取凌晨0点时间
        $time=zz_get_time0();
        //24小时过期时间
        $time0=$time-86400;
        $m_user=Db::name('user');
        //更新 了登录失败次数
        $rows=$m_user->where('login_fail','gt',0)->update(['login_fail'=>0]);
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'清空了登录失败次数'.$rows.'条',
        ];
        zz_log('清空了登录失败次数'.$rows.'条','time.log');
        
        //删除过期申请
        $m_reply=Db::name('reply');
        $where_reply1=[
            'is_overtime'=>['eq',1],
            'update_time'=>['eq',$time0]
        ];
        $rows=$m_reply->where($where_reply1)->delete();
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'删除了过期申请'.$rows.'条',
        ];
        zz_log('删除了过期申请'.$rows.'条','time.log');
        $where_reply2=[ 
            'is_overtime'=>['eq',0],
            'update_time'=>['lt',$time0]
        ];
        $rows=$m_reply->where($where_reply2)->update(['is_overtime'=>1,'update_time'=>$time]);
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了过期申请'.$rows.'条',
        ];
        zz_log('更新了过期申请'.$rows.'条','time.log');
         
        
        //借条处理
        $m_paper=Db::name('paper');
        //先删除过期借条 
        $rows=$m_reply->where(['status'=>2,'update_time'=>$time0])->delete();
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'删除了过期借条'.$rows.'条',
        ];
        zz_log('删除了过期借条'.$rows.'条','time.log');
        //逾期天数追加
        $rows=$m_paper->where('status',5)->setInc('overdue_day');
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了逾期天数'.$rows.'条',
        ];
        zz_log('更新了逾期天数'.$rows.'条','time.log');
        //更新用户逾期7天的次数
        $list_overdue7=$m_paper->field('borrower_id')->where(['status'=>3,'overdue_day'=>7])->select();
        $uids7=[];
        foreach($list_overdue7 as $v){
            $uids7[]=$v['borrower_id'];
        }
        $rows=$m_user->where('id','in',$uids7)->setInc('overdue7');
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了用户逾期7天的次数'.$rows.'条',
        ];
        zz_log('更新了用户逾期7天的次数'.$rows.'条','time.log');
        
        //到期的改为逾期 ,获取刚逾期的借款人，更新用户逾期次数
        $list_overdue1=$m_paper->field('borrower_id')->where('status',4)->select();
        $uids1=[];
        foreach($list_overdue1 as $v){
            $uids1[]=$v['borrower_id'];
        }
        $rows=$m_user->where('id','in',$uids1)->setInc('overdue1');
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了用户逾期次数'.$rows.'条',
        ];
        zz_log('更新了用户逾期次数'.$rows.'条','time.log');
        $rows=$m_paper->where('status',4)->update(['status'=>5,'overdue_day'=>1]);
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了今日到期为逾期'.$rows.'条',
        ];
        zz_log('更新了今日到期为逾期'.$rows.'条','time.log');
        
        //更新借条即将到期天数
        $rows=$m_paper->where('status',3)->setDec('expire_day');
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了即将到期天数'.$rows.'条',
        ];
        zz_log('更新了即将到期天数'.$rows.'条','time.log');
        $rows=$m_paper->where(['status'=>3,'expire_day'=>0])->update(['status'=>4]);
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新了即将到期为今日到期'.$rows.'条',
        ];
        zz_log('更新了即将到期为今日到期'.$rows.'条','time.log');
        
        //更新借条发起和借条不同意为过期
         
        $where_overtime=[
            'status'=>['in',[0,1]],
            'update_time'=>['elt',$time0],
        ];
        $rows=$m_paper->where($where_overtime)->update(['status'=>2,'update_time'=>$time]);
        $data_action[]=[
            'aid'=>1,
            'time'=>time(),
            'ip'=>get_client_ip(),
            'type'=>'system',
            'action'=>'更新借条发起和借条不同意为过期'.$rows.'条',
        ];
        zz_log('更新借条发起和借条不同意为过期'.$rows.'条','time.log');
        Db::name('action')->insertAll($data_action);
        zz_log('end','time.log');
       exit('执行结束');
    }
}
