<?php
class UnhealthyAction extends CommonAction
{
    //投诉
    public function index(){
        $obj = D('Integralcomplaint');
        import('ORG.Util.Page');
        $map = array('colse'=>0);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['oeder_id|user_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('keyword', $keyword);
        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //删除
    public function del($id){
        $obj = D('Integralcomplaint');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('colse' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('unhealthy/index'));

    }

    //罚款
    public function fakuan($id){
        $obj=D('Integralcomplaint');
        $list = $obj->where(array('id' => $id))->find();
        if($this->ispost()){
            $money=I('post.money');
            if($money==0){
                $this->tuError('请选择');
            }
            $shop=D('Shop')->where(array('shop_id'=>$list['shop_id']))->find();
            $user=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
            if($user['gold']<$money){
                $this->tuError('商家商户资金不足，请通知商家进行充值罚款');
            }

            if(false != D('Users')->addGold($user['user_id'],$money,'您于'.$list['time'].',被投诉，平台对您惩罚'.$money.'元作为提醒。')){
                $obj->save(array('id'=>$id,'stu'=>1,'money'=>$money));
                $this->tuSuccess('惩罚完成',U('unhealthy/index'));
            }else {
                $this->tuError('操作失败');
            }

        }else {
            $this->assign('list', $list);
            $this->display();
        }
    }


    //评价
    public function lists(){
        $Goodsdianping = D('Integralcomment');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($order_id = (int) $this->_param('order_id')) {
            $map['order_id'] = $order_id;
            $this->assign('order_id', $order_id);
        }

        if ($user_id = (int) $this->_param('user_id')) {
            $map['user_id'] = $user_id;
            $user = D('Users')->find($user_id);
            $this->assign('nickname', $user['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $Goodsdianping->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Goodsdianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($shop_ids)) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();

    }


    //删除评价
    public function delete($id){
        $obj = D('Integralcomment');
        if (!($detail = $obj->find($id))) {
            $this->error('请选择要删除的信息');
        }
        $data = array('closed' =>1, 'id' => $id);
        $obj->save($data);
        $this->tuSuccess('操作成功', U('unhealthy/lists'));

    }


}