<?php
class AdvanceAction extends CommonAction {
    //显示
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    //加载
    public function advanceload(){
        $aready = I('aready', '', 'trim,intval');
        if($aready==1){
            $Order = D('Order');
            import('ORG.Util.Page');
            $map = array('closed' => 0, 'user_id' => $this->uid,'is_yuyeu'=>1);
            $count = $Order->where($map)->count();
            $Page = new Page($count, 10);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
            $list = $Order->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = $addr_ids = array();
            foreach ($list as $key => $val) {
                $user_ids[$val['user_id']] = $val['user_id'];
                $order_ids[$val['order_id']] = $val['order_id'];
                $addr_ids[$val['addr_id']] = $val['addr_id'];
                if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>0,'closed'=>0))->find()){
                    $comment = D('DeliveryComment')->where(array('order_id'=>$delivery_order['order_id'],'type'=>0,'delivery_id'=>$delivery_order['delivery_id']))->find();//配送订单点评
                    $list[$key]['delivery_order'] = $delivery_order;
                    $list[$key]['comment'] = $comment;
                }
            }
            if (!empty($order_ids)) {
                $goods = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
                $goods_ids = $shop_ids = array();
                foreach ($goods as $val) {
                    $goods_ids[$val['goods_id']] = $val['goods_id'];
                    $shop_ids[$val['shop_id']] = $val['shop_id'];
                }
                $this->assign('goods', $goods);
                $this->assign('products', D('Goods')->itemsByIds($goods_ids));
                $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            }
            $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
            $this->assign('areas', D('Area')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('users', D('Users')->itemsByIds($user_ids));
            $this->assign('types', D('Order')->getType());
            $this->assign('goodtypes', D('Order')->getType());
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }elseif($aready==2){
            $obj = D('Marketorder');
            import('ORG.Util.Page');
            $map = array('user_id' => $this->uid, 'closed' => 0,'is_yuyue'=>1);
            $count = $obj->where($map)->count();
            $Page = new Page($count, 25);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = $addr_ids = $shop_ids = array();
            foreach($list as $k => $val){
                $order_ids[$val['order_id']] = $val['order_id'];
                $addr_ids[$val['addr_id']] = $val['addr_id'];
                $user_ids[$val['user_id']] = $val['user_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
                if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>3,'closed'=>0))->find()){
                    $list[$k]['delivery_order'] = $delivery_order;
                }
            }

            $this->assign('shopss', D('Shop')->itemsByIds($shop_ids));
            if(!empty($order_ids)){
                $products = D('Marketorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
                $product_ids = $shop_ids = array();
                foreach($products as $val){
                    $product_ids[$val['product_id']] = $val['product_id'];
                    $shop_ids[$val['shop_id']] = $val['shop_id'];
                }
                $this->assign('products', $products);
                $this->assign('marketproducts', D('Marketproduct')->itemsByIds($product_ids));
                $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            }
            $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
            $this->assign('areas', D('Area')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('users', D('Users')->itemsByIds($user_ids));
            $this->assign('cfg', D('Marketorder')->getCfg());
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display(marketadvance);
        }elseif($aready==3){
            $obj = D('Storeorder');
            import('ORG.Util.Page');
            $map = array('user_id' => $this->uid, 'closed' => 0,'is_yuyue'=>1);
            $count = $obj->where($map)->count();
            $Page = new Page($count, 25);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
            $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = $addr_ids = $shop_ids = array();
            foreach ($list as $k => $val) {
                $order_ids[$val['order_id']] = $val['order_id'];
                $addr_ids[$val['addr_id']] = $val['addr_id'];
                $user_ids[$val['user_id']] = $val['user_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
                if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>4,'closed'=>0))->find()){
                    $list[$k]['delivery_order'] = $delivery_order;
                }
            }
            $this->assign('shopss', D('Shop')->itemsByIds($shop_ids));
            if(!empty($order_ids)){
                $products = D('Storeorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
                $product_ids = $shop_ids = array();
                foreach ($products as $val) {
                    $product_ids[$val['product_id']] = $val['product_id'];
                    $shop_ids[$val['shop_id']] = $val['shop_id'];
                }
                $this->assign('products', $products);
                $this->assign('storeproducts', D('Storeproduct')->itemsByIds($product_ids));
                $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            }
            $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
            $this->assign('areas', D('Area')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('users', D('Users')->itemsByIds($user_ids));
            $this->assign('cfg', D('Storeorder')->getCfg());
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display(storeadvance);
        }elseif($aready==4){
            $Eleorder = D('Eleorder');
            $map = array('user_id' => $this->uid,'closed'=>'0','is_yuyue'=>1);
            import('ORG.Util.Page');
            $count = $Eleorder->where($map)->count();
            $Page = new Page($count, 25);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if ($Page->totalPages < $p) {
                die('0');
            }
            $list = $Eleorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
            $user_ids = $order_ids = $addr_ids = $shop_ids = array();
            foreach ($list as $k => $val) {
                $order_ids[$val['order_id']] = $val['order_id'];
                $addr_ids[$val['addr_id']] = $val['addr_id'];
                $user_ids[$val['user_id']] = $val['user_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
                if($delivery_order = D('DeliveryOrder')->where(array('type_order_id'=>$val['order_id'],'type'=>1,'closed'=>0))->find()){
                    $comment = D('DeliveryComment')->where(array('order_id'=>$delivery_order['order_id'],'type'=>1,'delivery_id'=>$delivery_order['delivery_id']))->find();//配送订单点评
                    $list[$k]['delivery_order'] = $delivery_order;
                    $list[$k]['comment'] = $comment;
                }
            }
            $this->assign('shopss', D('Shop')->itemsByIds($shop_ids));
            if (!empty($order_ids)) {
                $products = D('Eleorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
                $product_ids = $shop_ids = array();
                foreach ($products as $val) {
                    $product_ids[$val['product_id']] = $val['product_id'];
                    $shop_ids[$val['shop_id']] = $val['shop_id'];
                }
                $this->assign('products', $products);
                $this->assign('eleproducts', D('Eleproduct')->itemsByIds($product_ids));
                $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            }
            $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
            $this->assign('areas', D('Area')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('users', D('Users')->itemsByIds($user_ids));
            $this->assign('cfg', D('Eleorder')->getCfg());
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display(eleadvance);
        }

    }

}