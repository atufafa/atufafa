<?php
class UnhealthyAction extends CommonAction
{
    //投诉
    public function index(){
        $obj = D('Integralcomplaint');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'colse'=>0,'status'=>1);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('time  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }

    //回复
    public function tousureply($id){
        $id = (int) $id;
        $where =array('id' => $id);
        $hf = D('Integralcomplaint')->where($where)->find();

        if ($this->_Post()) {
            if($sjcontent=$this->_param('sjcontent','htmlspecialchars')){
                $data=array('id'=>$id,'sjcontent'=>$sjcontent);
                $con=D('Integralcomplaint')->where($where)->save($data);
                if($con>0){
                    $this->tuSuccess('回复成功', U('unhealthy/index'));
                }else{
                    $this->tuError('回复失败');
                }
            }
            $this->tuError('请填写回复信息');
        }else{

            $this->assign('list', $hf);
            $this->display();

        }

    }

    //评价
    public function lists(){
        $Goodsdianping = D('Integralcomment');
        import('ORG.Util.Page');
        $map = array('closed' => 0,'shop_id'=>$this->shop_id);
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

    //回复
    public function mallreply($id){
        $id = (int) $id;
        $obj = D('Integralcomment');
        $detail = D('Integralcomment')->where(array('id' => $id))->find();
        $order_id = $detail['order_id'];
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->tuError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('id' => $id, 'reply' => $reply,'reply_time'=>NOW_TIME);
                if ($obj->where("id=".$id)->save($data)) {
                    $this->tuSuccess('回复成功', U('unhealthy/lists'));
                }
            }
            $this->tuError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }




    }



}