<?php
class LogsAction extends CommonAction{
    public function index(){
        $logs = D('Communityorderlogs');
        import('ORG.Util.Page');
        $map = array('community_id' => $this->community_id);
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($number = $this->_param('number', 'htmlspecialchars')) {
            if (!empty($number)) {
                $owner = D('Communityowner')->where(array('number' => $number, 'community_id' => $this->community_id))->find();
                $map['user_id'] = $owner['user_id'];
                $this->assign('number', $number);
            }
        }
        if ($type = (int) $this->_param('type')) {
            if ($type != 999) {
                $map['type'] = $type;
                $this->assign('type', $type);
            } else {
                $this->assign('type', 999);
            }
        }
        $count = $logs->where($map)->count();
        $Page = new Page($count, 16);
        $show = $Page->show();
        $list = $logs->order(array('log_id' => 'desc'))->where($map)->select();
        $user_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        $sum = $logs->where($map)->sum('money');
        $this->assign('sum', $sum);
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('types', D('Communityorder')->getType());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function create($user_id){
        $user_id = (int) $user_id;
        $obj = D('Communityorder');
        if (empty($user_id)) {
            $this->error('该用户不存在');
        }
        if (!($detail = D('Communityowner')->where(array('user_id' => $user_id, 'community_id' => $this->community_id))->find())) {
            $this->error('该业主不存在');
        }
        if ($detail['audit'] != 1 || empty($detail['number'])) {
            $this->error('该业主不符合条件');
        }
        if ($this->isPost()) {
            $data['order_date'] = htmlspecialchars($_POST['order_date']);
            $data['community_id'] = $this->community_id;
            $data['user_id'] = $user_id;
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $datas = $this->_post('data', false);
            if (!($res = $obj->where(array('user_id' => $user_id, 'order_date' => $data['order_date']))->find())) {
                if ($order_id = $obj->add($data)) {
                    foreach ($datas as $k => $val) {
                        if (!empty($val['money']) && !empty($val['bg_date']) && !empty($val['end_date'])) {
                            D('Communityorderproducts')->add(array('order_id' => $order_id, 'type' => $k, 'money' => $val['money'], 'bg_date' => $val['bg_date'], 'end_date' => $val['end_date']));
                        }
                    }
                    $this->tuSuccess('添加成功', U('order/index', array('user_id' => $user_id)));
                } else {
                    $this->tuError('添加失败');
                }
            }
            $this->tuError('该账单已存在');
        } else {
            $this->assign('types', D('Communityorder')->getType());
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function edit($order_id){
        $order_id = (int) $order_id;
        $obj = D('Communityorder');
        if (empty($order_id)) {
            $this->error('该账单不存在');
        }
        if (!($detail = D('Communityorder')->find($order_id))) {
            $this->error('该账单不存在');
        }
        if ($detail['community_id'] != $this->community_id) {
            $this->error('不能操作其他小区账单');
        }
        $products = D('Communityorderproducts')->where(array('order_id' => $order_id))->select();
        foreach ($products as $k => $val) {
            $products[$val['type']] = $val;
        }
        if ($this->isPost()) {
            $datas = $this->_post('data', false);
            foreach ($datas as $k => $val) {
                if ($val['is_pay'] != 1) {
                    D('Communityorderproducts')->save(array('id' => $val['id'], 'type' => $k, 'money' => $val['money'], 'bg_date' => $val['bg_date'], 'end_date' => $val['end_date']));
                }
            }
            $this->tuSuccess('修改成功', U('order/index', array('user_id' => $detail['user_id'])));
        } else {
            $this->assign('products', $products);
            $this->assign('types', D('Communityorder')->getType());
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function edpay($owner_id, $pay_id){
        $owner_id = (int) $owner_id;
        $pay_id = (int) $pay_id;
        $obj = D('Communitypay');
        if (empty($owner_id)) {
            $this->error('该业主不存在');
        }
        if (!($detail = D('Communityowner')->find($owner_id))) {
            $this->error('该业主不存在');
        }
        if ($detail['community_id'] != $this->community_id) {
            $this->error('不能操作其他小区的业主');
        }
        if ($detail['audit'] != 1 || empty($detail['number'])) {
            $this->error('该业主不符合条件');
        }
        if (empty($pay_id)) {
            $this->error('该账单不存在');
        }
        if (!($result = $obj->find($pay_id))) {
            $this->error('该账单不存在');
        }
        if ($result['owner_id'] != $owner_id) {
            $this->error('该账单不存在');
        }
        if ($this->isPost()) {
            $data['electric'] = intval($_POST['electric']);
            $data['water'] = intval($_POST['water']);
            $data['gas'] = intval($_POST['gas']);
            $data['property'] = intval($_POST['property']);
            $data['parking'] = intval($_POST['parking']);
            $data['pay_id'] = $pay_id;
            if (false !== $obj->save($data)) {
                $this->tuSuccess('编辑成功', U('owner/pays', array('owner_id' => $owner_id)));
            } else {
                $this->tuError('编辑失败');
            }
        } else {
            $this->assign('detail', $detail);
            $this->assign('result', $result);
            $this->display();
        }
    }
}