<?php
class AppointAction extends CommonAction {
    public function _initialize() {
        parent::_initialize();
        if(empty($this->_CONFIG['operation']['edu'])) {
            $this->error('家政功能已关闭');
            die;
        }
        $this->age = D('Edu')->getEduage();
        $this->assign('age', $this->age);
        $this->get_time = D('Edu')->getEduTime();
        $this->assign('get_time', $this->get_time);
        $this->get_edu_class = D('Edu')->getEduClass();
        $this->assign('class', $this->get_edu_class);
        $this->assign('cates', D('Educate')->fetchAll());
        $this->assign('types', D('EduOrder')->getType());
    }
  
    public function index() {
        $st = (int) $this->_param('st');
        $this->assign('st', $st);
        $this->display(); 
    }
    //家政订单列表
    public function loaddata() {
        $EduOrder = D('Appointorder');
        import('ORG.Util.Page'); 
        $map = array('shop_id' => $this->shop_id); 
        $st = (int) $this->_param('st');
        if ($st == 0 || empty($st)) { 
            // $map['status'] = 0;
        }elseif ($st == 1) {    
            $map['status'] = 1;
        }elseif ($st == 2) {    
            $map['status'] = 0;
        }elseif ($st == 8) {    
            $map['status'] = 8;
        }elseif($st == 3){
            $map['status'] = 3;
        }elseif ($st == 4){
            $map['status'] = 4;
        }
        $count = $EduOrder->where($map)->count(); 
        // echo $EduOrder->getlastSql();die;
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $EduOrder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        // echo $EduOrder->getlastSql();die;
        foreach($list as $k => $v){
            if($course = D('Educourse')->where(array('course_id'=>$v['course_id']))->find()){
                $list[$k]['course'] = $course;
            }
        }
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
    }
    

    //家政订单详情
    public function detail($order_id){
        if(!$order_id = (int)$order_id){
            $this->error('该订单不存在');
        }elseif(!$detail = D('Appointorder')->find($order_id)){
            $this->error('该订单不存在');
        }elseif($detail['shop_id'] != $this->shop_id){
            $this->error('非法的订单操作');
        }else{
           $detail['course'] = D('Appointorder')->where(array('course_id'=>$detail['course_id']))->find(); 
           $detail['edu'] = D('Appoint')->where(array('edu_id'=>$detail['edu_id']))->find();
           $detail['users'] = D('Users')->where(array('user_id'=>$detail['user_id']))->find();
           // print_r($detail);die;
           $this->assign('detail',$detail);
           $this->display();
        }
    }

    //家政订单取消
   public function cancel($order_id){
       if(!$order_id = (int)$order_id){
           $this->error('订单不存在');
       }elseif(!$detail = D('EduOrder')->find($order_id)){
           $this->error('该订单不存在或者验证码错误');
       }elseif($detail['shop_id'] != $this->shop_id){
           $this->error('非法操作订单');
       }else{
           if(false !== D('EduOrder')->cancel($order_id)){
               $this->success('订单取消成功');
           }else{
               $this->error('订单取消失败');
           }
       }
   }
   //验证码
    public function check() {
        if ($this->isPost()) {
            $code = $this->_post('code', false);
            if (empty($code)) {
                $this->tuMsg('请输入验证码');
            }
            $obj = D('EduOrder');
            if(!$detail = D('EduOrder')->where(array('code'=> $code))->find()){
               $this->tuMsg('该订单不存在或者验证码错误');
            }
            if($detail['order_status'] !=1){
               $this->tuMsg('该订单状态不正确');
            }
            if($detail['is_used_code'] !=0){
               $this->tuMsg('该验证码已使用');
            }
            if($detail['shop_id'] != $this->shop_id){
               $this->tuMsg('非法操作');
            }
            if(false !== $obj->complete($detail['order_id'])){
                $this->tuMsg('验证成功', U('edu/index',array('st'=>1)));
            }else{
                $this->tuMsg('操作失败');
            }
        } else {
            $this->display();
        }
    }
    
    //核销二维码
    public function used(){
        $order_id = (int) $this->_param('order_id');
        $obj = D('EduOrder');
        if(!$detail = D('EduOrder')->where(array('order_id'=> $order_id))->find()){
           $this->error('订单不存在');
        }
        if($detail['order_status'] !=1){
            $this->error('该订单状态不正确');
        }
        if($detail['is_used_code'] !=0){
            $this->error('该验证码已使用');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->error('非法操作');
        }
        if(false !== $obj->complete($detail['order_id'])){
            $this->error('验证成功', U('edu/index',array('st'=>1)));
        }else{
            $this->error('操作失败');
        }
    }
}
