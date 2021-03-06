<?php
class DianpingAction extends CommonAction{
	public function _initialize() {
        parent::_initialize();
		$this->error('开发中，请关注');
		if($this->workers['coupon'] != 1){
          $this->error('对不起，您无权限，请联系掌柜开通');
        }
		
    }
	
    public function index(){
        $Shopdianping = D('Shopdianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $Shopdianping->where($map)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $Shopdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function reply($dianping_id){
        $dianping_id = (int) $dianping_id;
        $detail = D('Shopdianping')->find($dianping_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('dianping_id' => $dianping_id, 'reply' => $reply);
                if (D('Shopdianping')->save($data)) {
                    $this->error('回复成功', U('dianping/index'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function waimai(){
        $eledianping = D('Eledianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'show_date' => array('ELT', TODAY));
        $count = $eledianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $eledianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($order_ids)) {
            $this->assign('pics', D('Eledianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['order_id'], $order_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function tuan(){
        $tuandianping = D('Tuandianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'show_date' => array('ELT', TODAY));
        $count = $tuandianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $tuandianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($order_ids)) {
            $this->assign('pics', D('Tuandianpingpics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['order_id'], $order_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    
    public function tuanreply($order_id){
        $order_id = (int) $order_id;
        $detail = D('Tuandianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('Tuandianping')->save($data)) {
                    $this->error('回复成功', U('dianping/tuan'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function elereply($order_id)
    {
        $order_id = (int) $order_id;
        $detail = D('Eledianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('Eledianping')->save($data)) {
                    $this->error('回复成功', U('dianping/waimai'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    public function booking(){
        $Bookingdianping = D('Bookingdianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $Bookingdianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Bookingdianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($order_ids)) {
            $this->assign('pics', D('Bookingdianpingpic')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['order_id'], $order_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	 public function bookingreply($order_id){
        $order_id = (int) $order_id;
        $detail = D('Bookingdianping')->find($order_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('order_id' => $order_id, 'reply' => $reply);
                if (D('Bookingdianping')->save($data)) {
                    $this->tuSuccess('回复成功', U('bookingdianping/ding'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
	//家政点评OEDER_ID主键
	 public function Appoint(){
        $Appointdianping = D('Appointdianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $Appointdianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Appointdianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($dianping_ids)) {
            $this->assign('pics', D('Bookingdianpingpic')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['dianping_id'], $dianping_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	//家政点评回复
	public function appointreply($dianping_id){
        $dianping_id = (int) $dianping_id;
        $obj = D('Appointdianping');
        $detail = $obj ->find($dianping_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('dianping_id' => $dianping_id, 'reply' => $reply);
                if ($obj->save($data)) {
                    $this->tuSuccess('回复成功', U('dianping/booking'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
	
	//农家乐点评
	 public function farm(){
        $FarmComment = D('FarmComment');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $FarmComment->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $FarmComment->where($map)->order(array('comment_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $comment_ids[$val['comment_id']] = $val['comment_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($comment_ids)) {
            $this->assign('pics', D('FarmCommentPics')->where(array('comment_id' => array('IN', $comment_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['comment_id'], $comment_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	//农家乐点评回复
	public function farmreply($comment_id){
        $comment_id = (int) $comment_id;
        $obj = D('FarmComment');
        $detail = $obj ->find($comment_id);
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->error('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('comment_id' => $comment_id, 'reply' => $reply);
                if ($obj->save($data)) {
                    $this->tuSuccess('回复成功', U('dianping/farm'));
                }
            }
            $this->error('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
}