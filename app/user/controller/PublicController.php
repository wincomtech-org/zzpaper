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
use app\user\model\UserModel;
use think\Validate;

class PublicController extends HomeBaseController
{
    //生成二维码
    public function qrcode(){
        $url=$this->request->param('url');
        import('phpqrcode',EXTEND_PATH);
        
        $url = urldecode($url);
        \QRcode::png($url);
    }
     
}
