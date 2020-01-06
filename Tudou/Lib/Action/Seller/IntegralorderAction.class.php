<?php
class IntegralorderAction extends CommonAction{

    public function order(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $keyword = (int) $this->_param('keyword');
        $this->assign('keyword', $keyword);
        $this->assign('nextpage', linkto('integralorder/loaddata', array('aready'=>$aready,'keyword'=>$keyword,'t' => NOW_TIME, 'p' => '0000')));
        $this->display();
    }

    public function loaddata(){
        $Tuanorder = D('Integralorder');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array('shop_id' => $this->shop_id);

        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $keyword = intval($keyword);
            if (!empty($keyword)) {
                $map['order_id'] = array('LIKE', '%' . $keyword . '%');
                $this->assign('keyword', $keyword);
            }
        }
        if (isset($_GET['aready']) || isset($_POST['aready'])) {
            $aready = (int) $this->_param('aready');
            if ($aready != 999) {
                $map['status'] = $aready;
            }
            $this->assign('aready', $aready);
        } else {
            $map['status'] = 0;
            $this->assign('aready', 999);
        }
        $count = $Tuanorder->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Tuanorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = $tuan_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $address_ids[$val['address_id']] = $val['address_id'];
        }

        if (!empty($order_ids)) {
            $goods = D('Integralordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['goods_id']] = $val['goods_id'];
            }
            $this->assign('goods', $goods);
            $this->assign('products', D('Integralgoodslist')->itemsByIds($goods_ids));
        }

        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('addrs', D('Paddress')->itemsByIds($address_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function detail($order_id){
        $order_id = (int) $order_id;
        if (empty($order_id) || !($detail = D('Integralorder')->find($order_id))) {
            $this->error('该订单不存在');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->error('请不要操作其他商家的订单');
        }
        $order_goods = D('Integralordergoods')->where(array('order_id' => $order_id))->select();
        $goods_ids = array();
        foreach ($order_goods as $k => $val) {
            $goods_ids[$val['goods_id']] = $val['goods_id'];
        }
        if (!empty($goods_ids)) {
            $this->assign('goods', D('Integralgoodslist')->itemsByIds($goods_ids));
        }


        $this->assign('ordergoods', $order_goods);
        $this->assign('users', D('Users')->find($detail['user_id']));
        $this->assign('Paddress', D('Paddress')->find($detail['address_id']));
        $this->assign('detail', $detail);
        $this->display();
    }


}