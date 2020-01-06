<?php
class DeliveryAction extends CommonAction
{
    public function index()
    {
        $d = D('Delivery');
        // 实例化User对象
        import('ORG.Util.Page');
        // 导入分页类
        $count = $d->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $list = $d->order('add_time')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);
        // 赋值分页输出
        $this->display();
        // 输出模板
    }
    public function create()
    {
        $this->display();
    }

    //配送员所有的费用记录
     public function finance(){
        $Runningmoney = D('Runningmoney');
        import('ORG.Util.Page');
        if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        }else{
            if($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($order_id = (int) $this->_param('order_id')){
            $map['order_id'] = $order_id;
            $this->assign('order_id', $order_id);
        }
        if($user_id = (int) $this->_param('user_id')){
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if(isset($_GET['type']) || isset($_POST['type'])){
            $type = $this->_param('type');
            if($type == 1) {
                $map['type'] = goods;
            }elseif($type == 2){
                $map['type'] = ele;
            }elseif($type == 3){
                $map['type'] = running;
            }else{
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
        $count = $Runningmoney->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Runningmoney->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
            //跑腿订单信息
            $Delivery = D('DeliveryOrder')->where(array("type_order_id"=>$val['running_id']))->find();
            $shop_ids[$val['shop_id']] = $Delivery['shop_id'];
            $user_ids[$val['user_id']] = $Delivery['user_id'];
            $list[$k]['order_id'] = $Delivery['order_id'];
            $list[$k]['shop_id'] = $Delivery['shop_id'];
        }
        // dump($list);die;
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    

    public function check()
    {
        $username = I('username', '', 'trim,htmlspecialchars');
        $password = I('password');
        $rpw = I('rpw');
        $name = I('name', '', 'trim,htmlspecialchars');
        $mobile = I('mobile', '', 'trim');
        if (!$username) {
            $this->tuError('帐号没有填写');
        }
        if (!$password || strlen($password) < 6) {
            $this->tuError('密码错误或小于6位');
        }
        if (!$rpw || strlen($rpw) < 6) {
            $this->tuError('确认密码错误或小于6位');
        }
        if ($password != $rpw) {
            $this->tuError('两次密码不一致');
        }
        if (!$name) {
            $this->tuError('姓名没有填写');
        }
        if (!$mobile || strlen($mobile) != 11) {
            $this->tuError('手机号填写错误');
        }
        $dv = D('Delivery');
        $fu = $dv->where('username ="' . $username . '"')->find();
        p($dv->getLastSql());
        if ($fu) {
            $this->tuError('重复的帐号');
        }
        $fm = $dv->where('mobile =' . $mobile)->find();
        if ($fm) {
            $this->tuError('重复的手机号');
        }
        $result = array('username' => $username, 'password' => md5($password), 'name' => $name, 'mobile' => $mobile, 'add_time' => time());
        $r = $dv->add($result);
        if ($r) {
            $this->tuSuccess('添加成功', U('delivery/index'));
        } else {
            $this->tuError('添加失败');
        }
    }
    public function del()
    {
        $id = I('id', '', 'intval,trim');
        if (!$id) {
            $this->tuError('没有选择');
        } else {
            $dv = D('Delivery');
            $dec = $dv->where('id =' . $id)->delete();
            if ($dec) {
                $this->success('删除成功', U('delivery/index'));
            } else {
                $this->error('删除失败');
            }
        }
    }
    public function lists()
    {
        $id = I('id', '', 'intval,trim');
        if (!$id) {
            $this->tuError('没有选择');
        } else {
            $this->assign('delivery', D('Delivery')->where('id =' . $id)->find());
            $dvo = D('DeliveryOrder');
            import('ORG.Util.Page');
            $count = $dvo->where('delivery_id =' . $id)->count();
            $Page = new Page($count, 25);
            $show = $Page->show();
            $list = $dvo->where('delivery_id =' . $id)->order('order_id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }
    }
}