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
}
