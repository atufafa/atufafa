<?php



class PassportAction extends CommonAction {

    private $create_fields = array('account', 'password', 'nickname');

    public function bind() {
        $this->display();
    }

    public function login(){
        if ($this->isPost()){        
      
            $account = $this->_post('account');
            if (empty($account)) {
                 $this->ajaxReturn(array('status'=>'error','message'=>'请输入用户名'));
            }

            $password = $this->_post('password');
            if (empty($password)) {
                $this->ajaxReturn(array('status'=>'error','message'=>'请输入登录密码'));
            }
            $backurl = $this->_post('backurl', 'htmlspecialchars');
            if (empty($backurl))
                $backurl = U('index/index');
            if (true == D('Passport')->login($account, $password)) {
                $pei=$Delivery = D('Delivery')->where(array('user_id' => $this->uid))->find();
                if(!empty($pei)){
                    $this->ajaxReturn(array('status'=>'success','message'=>'登录配送中心成功','backurl'=>$backurl));
                }elseif($pei['audit']!=1 && !empty($pei)){
                    $this->ajaxReturn(array('status'=>'error','message'=>'还没有审核，请稍后或联系管理员审核'));
                }else{
                    $this->ajaxReturn(array('status'=>'error','message'=>'您未注册配送员，请先注册'));
                }

            }
            $this->ajaxReturn(array('status'=>'error','message'=>D('Passport')->getError()));

        } else{
            if (!empty($_SERVER['HTTP_REFERER'])&&strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            } else {
                $backurl = U('delivery/index/indexs');
            }
//            var_dump($_SERVER['HTTP_REFERER'],$_SERVER['HTTP_REFERER'],$_SERVER['HTTP_HOST']);
//            var_dump(strstr($_SERVER['HTTP_REFERER'], 'passport'));
//
//            var_dump($backurl);die;

            $this->assign('backurl', $backurl);
            $this->display();
        }
    }
    public function logout() {

        D('Passport')->logout();
        $this->success('退出登录成功', U('passport/login'));
    }

    
          public function logouts() {

        D('Passport')->logout();
        $this->success('退出登录成功', U('passport/vehiclelogin'));
    }
    public function vehiclelogin(){

         if ($this->isPost()){        
      
            $account = $this->_post('account');
            if (empty($account)) {
                 $this->ajaxReturn(array('status'=>'error','message'=>'请输入用户名'));
            }

            $password = $this->_post('password');
            if (empty($password)) {
                $this->ajaxReturn(array('status'=>'error','message'=>'请输入登录密码'));
            }
            $backurl = $this->_post('backurl', 'htmlspecialchars');
            if (empty($backurl))
                $backurl = U('vehicle/vehicleindex');
            if (true == D('Passport')->login($account, $password)) {
                $pei=$Delivery = D('Userspinche')->where(array('user_id' => $this->uid))->find();
                if(!empty($pei)){
                    $this->ajaxReturn(array('status'=>'success','message'=>'登录配送中心成功','backurl'=>$backurl));
                }elseif($pei['audit']!=1 && !empty($pei)){
                    $this->ajaxReturn(array('status'=>'error','message'=>'还没有审核，请稍后或联系管理员审核'));
                }else{
                    $this->ajaxReturn(array('status'=>'error','message'=>'您未注册司机接单，请先注册'));
                }
            }
            $this->ajaxReturn(array('status'=>'error','message'=>D('Passport')->getError()));

        } else{
            if (!empty($_SERVER['HTTP_REFERER'])&&strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            } else {
                $backurl = U('delivery/vehicle/vehicleindexs');
            }
            $this->assign('backurl', $backurl);
            $this->display();
        }

        
    }
    

}
