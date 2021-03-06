<?php

class MarketAction extends CommonAction
{
    private $create_fields = array('product_name', 'desc', 'cate_id', 'photo', 'cost_price', 'price', 'tableware_price', 'is_new', 'is_hot', 'is_tuijian', 'create_time', 'create_ip');
    private $edit_fields = array('product_name', 'desc', 'cate_id', 'photo', 'cost_price', 'price', 'tableware_price', 'is_new', 'is_hot', 'is_tuijian');

    public function _initialize()
    {
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['market'])){
            $this->error('菜市场功能已关闭');
            die;
        }
        $this->market = D('Market')->find($this->shop_id);
        if(empty($this->market) && ACTION_NAME != 'apply'){
            $this->error('您还没有入住菜市场频道,即将为您跳转', U('marketapply/apply'));
        }
        if($this->market['audit'] == 0){
            $this->error('您的菜市场还在审核中哦', U('marketapply/apply'));
        }
        if($this->market['audit'] == 2){
            $this->error('您的审核未通过哦', U('marketapply/apply'));
        }
        $this->assign('market', $this->market);
        $getMarketCate = D('Market')->getMarketCate();
        $this->assign('getMarketCate', $getMarketCate);
        $this->marketcates = D('Marketcate')->where(array('shop_id' => $this->shop_id, 'closed' => 0))->select();
        $this->assign('marketcates', $this->marketcates);
    }

    public function gears()
    {
        if ($this->isPost()) {
            $is_open = (int) $_POST['is_open'];
            $busihour = $_POST['busihour'];
            $tags = $_POST['tags'];
            D('Market')->save(array('shop_id' => $this->shop_id, 'is_open' => $is_open, 'busihour' => $busihour, 'tags' => $tags));
            $this->tuMsg('操作成功', U('market/index'));
        }else{
			
        }
        $this->assign('market', $this->market);
        $this->display();
    }

    //订单语音提示
    public function voice_alert()
    {
        if (IS_AJAX) {
            session_start();
            if (empty($_SESSION['last_check'])) {
                $_SESSION['last_check'] = time();
            }
            $condition['create_time'] = array(array('egt', $_SESSION['last_check']));
            $condition['status'] = 1; //已付款
            $condition['shop_id'] = $this->shop_id;
            $list = D('Marketorder')->where($mapd)->select();
            if ($list) {
                $_SESSION['last_check'] = time();
                $this->ajaxReturn(array('status' => '2', 'message' => '有新的菜市场订单了'));
            } else {
                $this->ajaxReturn(array('status' => '0', 'message' => '没有新的订单'));
            }
        }
    }


    public function marketcate()
    {
        $obj = D('Marketcate');
        import('ORG.Util.Page');
        $map = array('closed' => '0');
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['cate_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('cate_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = array();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function create()
    {
        if (IS_AJAX) {
            $shop_id = $this->shop_id;
            $cate_name = I('cate_name', '', 'trim,htmlspecialchars');
			$cateid = I('cateid', '', 'trim,htmlspecialchars');
            if(empty($cate_name)){
                $this->ajaxReturn(array('status' => 'error', 'message' => '分类名称不能为空'));
            }
            $obj = D('Marketcate');
            $data = array('parent_id' => $cateid,'shop_id' => $shop_id, 'cate_name' => $cate_name, 'num' => 0, 'closed' => 0);
			
			if($cate_name = D('Marketcate')->where(array('cate_id'=>$cateid))->getField('cate_name')){
				$intro = '恭喜您在【'.$cate_name.'】添加子分类成功';
			}else{
				$intro = '添加分类成功';
			}
            if($obj->add($data)){
                $this->ajaxReturn(array('status' => 'success', 'message' =>$intro));
            }
            $this->ajaxReturn(array('status' => 'error', 'message' => '添加失败'));
        }
    }

    public function edit()
    {
        if (IS_AJAX) {
            $cate_id = I('v', '', 'intval,trim');
            if ($cate_id) {
                $obj = D('Marketcate');
                if(!($detail = $obj->find($cate_id))){
                    $this->ajaxReturn(array('status' => 'error', 'message' => '请选择要编辑的商品分类'));
                }
                if($detail['shop_id'] != $this->shop_id){
                    $this->ajaxReturn(array('status' => 'error', 'message' => '请不要操作其他商家的商品分类'));
                }
                $cate_name = I('cate_name', '', 'trim,htmlspecialchars');
                if(empty($cate_name)){
                    $this->ajaxReturn(array('status' => 'error', 'message' => '分类名称不能为空'));
                }
                $data = array('cate_name' => $cate_name);
                if (false !== $obj->where('cate_id =' . $cate_id)->setField($data)) {
                    $this->ajaxReturn(array('status' => 'success', 'message' => '操作成功'));
                }
                $this->ajaxReturn(array('status' => 'error', 'message' => '操作失败'));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'message' => '请选择要编辑的商品分类'));
            }
        }
    }

    public function index()
    {
        $obj = D('Marketproduct');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids = array();
        foreach($list as $k => $val){
            if($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if($cate_ids){
            $this->assign('cates', D('Marketcate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    public function shelves()
    {
        $obj = D('Marketproduct');
        import('ORG.Util.Page');
        $map = array('closed' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['product_name'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = $this->shop_id) {
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('product_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $cate_ids = array();
        foreach($list as $k => $val){
            if($val['cate_id']) {
                $cate_ids[$val['cate_id']] = $val['cate_id'];
            }
        }
        if($cate_ids){
            $this->assign('cates', D('Marketcate')->itemsByIds($cate_ids));
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    public function status()
    {
        $status = I('status', '', 'trim,intval');
        $order_id = I('order_id', '', 'trim,intval');
        $obj = D('MarketOrder');
        $detail = $obj->where('order_id =' . $order_id)->find();
        $Shop = D('Shop')->where(array('shop_id'=>$this->shop_id))->find();
        if($status == 0) {
            if($detail['status'] == 0) {
                $this->tuMsg('买家未付款不能操作!');
            }
        } elseif ($status == 1) {
            $add=D('Useraddr')->where(['addr_id'=>$detail['addr_id']])->find();
            $details=D('Shop')->where(['shop_id'=>$this->shop_id])->find();
            $goods=M('market_order_product')
                ->alias('e')
                ->join('tu_market_product p ON(e.product_id=p.product_id)','left')
                ->field('e.*,p.product_name,p.price')
                ->where(array('e.order_id'=>$order_id))
                ->select();
            $time=date('Y-m-d H:i:s',$detail['pay_time']);
            $orderInfos = '<CB>'.$details['shop_name'].'</CB><BR>';
            $orderInfos .= '名称　　　　　 单价  数量 金额<BR>';
            $orderInfos .= '--------------------------------<BR>';
            foreach ($goods as $val){
                $goods['product_name']=$val['product_name'];
                $len=strlen($goods['product_name']);
                if($len==3){
                    $goods['product_name'].='。。。。。。';
                }elseif($len==6){
                    $goods['product_name'].='。。。。。';
                }elseif($len==9){
                    $goods['product_name'].='。。。。';
                }elseif($len==12){
                    $goods['product_name'].='。。。';
                }elseif($len==15){
                    $goods['product_name'].='。。';
                }elseif($len==18){
                    $goods['product_name'].='。';
                }else{
                    $goods['product_name'];
                }
                $goods['total_price']=$val['total_price'];
                $goods['price']=$val['price'];
                $goods['num']=$val['num'];
                $orderInfos .= $goods['product_name'].　.$goods['price'].　.$goods['num'].　.$goods['total_price'].'<BR>';
            }
            $orderInfos .= '--------------------------------<BR>';
            $orderInfos .= '合计：'.$detail['total_price'].'元<BR>';
            $orderInfos .= '联系电话：'.$details['tel'].'<BR>';
            $orderInfos .= '支付时间：'.$time.'<BR>';
            $orderInfos .= '送货地点：'.$add['addr'].'<BR>';
            $orderInfos .= '备注：'.$detail['message'].'<BR>';
            $orderInfos .= '<QR>https://avycbh.zgtianxin.net/wap</QR>';//把二维码字符串用标签套上即可自动生成二维码

            $this->wp_print($details['mailbox'],$details['ukey'],$details['sn'],$orderInfos,$details['nums']);

            if ($Shop['is_market_pei'] == 1 && $detail['type'] !=3 && $detail !=4) {
                D('Marketorder')->market_delivery_order($order_id);//菜市场接单时候给配送
                $this->tuMsg('恭喜您接单成功，订单已经进去配送中心', U('market/marketorder', array('status' => 2)));
            }else{
                if($detail['status'] == 1) {
					$update = $obj->save(array('order_id' => $order_id, 'status' => 9,'orders_time' => NOW_TIME));
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 9,$status = 1);//通知买家
                }else{
                    $this->tuMsg('状态错误!');
                }
            }
        }else{
            $this->tuMsg('没有检测到订单状态');
        }
		
        if($update){
            $this->tuMsg('设置成功!', U('market/marketorder', array('status' => 2)));
        }else{
            $this->tuMsg('数据库操作失败!');
        }
    }


    //打印订单
    /*
 *  方法1
	拼凑订单内容时可参考如下格式
	根据打印纸张的宽度，自行调整内容的格式，可参考下面的样例格式
*/
    function wp_print($user,$ukey,$printer_sn,$orderInfos,$times){
        $time = time();			    //请求时间
        $content = array(
            'user'=>$user,
            'stime'=>$time,
            'sig'=>sha1($user.$ukey.$time),
            'apiname'=>'Open_printMsg',
            'sn'=>$printer_sn,
            'content'=>$orderInfos,
            'times'=>$times//打印次数
        );

        import('ORG/Util/HttpClient');
        $client = new HttpClient('api.feieyun.cn','80');
        if(!$client->post('/Api/Open/',$content)){
            echo 'error';
        }
        else{
            //服务器返回的JSON字符串，建议要当做日志记录起来
            echo $client->getContent();
        }

    }

    public function detail()
    {
        $order_id = I('order_id', '', 'intval,trim');
        if(!($order = D('MarketOrder')->find($order_id))){
            $this->error('错误');
        }else {
            $op = D('MarketOrderProduct')->where('order_id =' . $order['order_id'])->select();
            if($op){
                $ids = array();
                foreach ($op as $k => $v){
                    $ids[$v['product_id']] = $v['product_id'];
                }
                $ep = D('MarketProduct')->where(array('product_id' => array('in', $ids)))->select();
                $ep2 = array();
                foreach ($ep as $kk => $vv){
                    $ep2[$vv['product_id']] = $vv;
                }
                $this->assign('ep', $ep2);
                $this->assign('op', $op);
                $addr = D('UserAddr')->find($order['addr_id']);
                $this->assign('addr', $addr);
                $do = D('DeliveryOrder')->where(array('type' => 3, 'type_order_id' => $order['order_id']))->find();
                $data = D('Logistics')->get_market_express($order_id);//查询清单物流
                $this->assign('data',$data);
                if ($do) {
                    if ($do['delivery_id'] > 0) {
                        $delivery = D('Delivery')->find($do['delivery_id']);
                        $this->assign('delivery', $delivery);
                    }
                    $this->assign('do', $do);
                }
            }
            $this->assign('order', $order);
            $this->display();
        }
    }


    public function marketorder()
    {
        $status = I('status', '', 'intval,trim');
        $this->assign('status', $status);
		$keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $bg_time = (int) $this->_param('bg_time');
        $this->assign('bg_time', $bg_time);
        $this->assign('nextpage', LinkTo('market/marketorder_lada_data',array('status'=>$status,'keyword'=>$keyword,'t' => NOW_TIME, 'p' => '0000')));
        $this->display();
    }
	

    public function marketorder_lada_data()
    {
        $obj = D('Marketorder');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
		$status = (int) $this->_param('status');
        if($status){
            $map['status'] = $status;
        }
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
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if(isset($_GET['st']) || isset($_POST['st'])){
            $st = (int) $this->_param('st');
            if($st == 10){
                $map['status'] = array('in','2,10,11');
            }else{
               $map['status'] =$st;
            }
            $this->assign('st', $st);
        }else{
            $this->assign('st', 999);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
		
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }

        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
        }
		
        $market_shop = D('Shop')->where(array('shop_id'=>$this->shop_id))->find();
        if ($market_shop['is_pei'] == 0) {
            $DeliveryOrder = D('DeliveryOrder')->where(array('type' => 3, 'type_order_id' => array('IN', $order_ids)))->select();
            $delivery_ids = array();
            foreach ($DeliveryOrder as $val) {
                $delivery_ids[$val['delivery_id']] = $val['delivery_id'];
            }
            $this->assign('Delivery', D('Delivery')->itemsByIds($delivery_ids));
            $this->assign('DeliveryOrder', $DeliveryOrder);
        }
        if (!empty($order_ids)) {
            $goods = D('Marketorderproduct')->where(array('order_id' => array('IN', $order_ids)))->select();
            $goods_ids = array();
            foreach ($goods as $val) {
                $goods_ids[$val['product_id']] = $val['product_id'];
            }
            $this->assign('goods', $goods);
            $this->assign('products', D('Marketproduct')->itemsByIds($goods_ids));
        }

        $this->assign('addrs', D('Useraddr')->itemsByIds($addr_ids));
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('market_shop', $market_shop);
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->display();
    }
	
	
    //下架商品
    public function delete($product_id = 0)
    {
        $product_id = (int)$product_id;
        if (empty($product_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '访问错误'));
        }
        $obj = D('Marketproduct');
        if (!($detail = $obj->where(array('shop_id' => $this->shop_id, 'product_id' => $product_id))->find())) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择要下架的商品管理'));
        }
        D('Marketcate')->updateNum($detail['cate_id']);
        $obj->save(array('product_id' => $product_id, 'closed' => 1));
        $this->ajaxReturn(array('status' => 'success', 'msg' => '下架成功', U('market/shelves')));
    }
	
    //上架商品
    public function updates($product_id = 0)
    {
        $product_id = (int)$product_id;
        if (empty($product_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '访问错误'));
        }
        $obj = D('Marketproduct');
        if (!($detail = $obj->where(array('shop_id' => $this->shop_id, 'product_id' => $product_id))->find())) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择要上架的商品管理'));
        }
        D('Marketcate')->updateNum($detail['cate_id']);
        $obj->save(array('product_id' => $product_id, 'closed' => 0));
        $this->ajaxReturn(array('status' => 'success', 'msg' => '上架成功', U('market/index')));
    }
	
    //确认订单
    public function send($order_id)
    {
        $order_id = (int)$order_id;
        if (!($detail = D('Marketorder')->find($order_id))) {
            $this->tuMsg('没有该订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuMsg('您无权管理该商家');
        }
        $shop = D('Shop')->where(array('shop_id' => $this->shop_id))->find();
        if ($shop['is_market_pei'] == 1 && $detail['type']!=3 && $detail['type'] !=4) {
            $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 3))->find();
            if (!empty($DeliveryOrder)) {
                $this->tuMsg('您开通了配送员配货，无权管理');
            }
        }else{
            if($detail['create_time'] < $t && $detail['status'] == 2){
                D('Marketorder')->overOrder($order_id);
                $this->tuMsg('确认完成，资金已经结算到账户', U('market/marketorder', array('status' => 1)));
            }else{
                $this->tuMsg('操作失败,客户还未收货或者没到自动确认收货时间');
            }
        }
    }


    //炒制完成
    public function chaozhi($order_id){
        $order_id = (int) $order_id;
        if (!($detail = D('Marketorder')->find($order_id))) {
            $this->tuMsg('没有该订单');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->tuMsg('您无权管理该商家');
        }
        if ($detail['status'] != 10) {
            $this->tuMsg('该订单状态不正确');
        }
        D('Marketorder')->save(array('order_id' => $order_id,'shop_time' => NOW_TIME));
        $this->tuMsg('已确认,等待配送员取货', U('market/marketorder', array('status' => 1)));
    }



    public function create2()
    {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Marketproduct');
            if ($obj->add($data)) {
                D('Marketcate')->updateNum($data['cate_id']);
                $this->tuMsg('添加成功', U('market/index'));
            }
            $this->tuMsg('操作失败');
        } else {
            $this->display();
        }
    }


    private function createCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuMsg('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuMsg('商品介绍不能为空');
        }
        $data['shop_id'] = $this->shop_id;
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuMsg('分类不能为空');
        }
		$res = D('Marketcate')->where(array('cate_id'=>$data['cate_id']))->find();
		if($res['parent_id'] == 0){
			$this->tuMsg('请选择二级分类');
		}
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuMsg('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuMsg('缩略图格式不正确');
        }
		$data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price'] );
        if (empty($data['price'])) {
            $this->tuMsg('价格不能为空');
        }
		if($data['cost_price']){
			if($data['price'] >= $data['cost_price']){
				$this->tuMsg('售价不能高于原价');
			}
		}
		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->market['rate'] / 1000);
		
		if(false == D('Marketproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuMsg(D('Marketproduct')->getError());//检测餐具费合理性
		}
	
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['audit'] = 0;
        return $data;
    }


    public function edit2($product_id = 0)
    {
        if ($product_id = (int)$product_id) {
            $obj = D('Marketproduct');
            if (!($detail = $obj->find($product_id))) {
                $this->error('请选择要编辑的商品管理');
            }
            if ($detail['shop_id'] != $this->shop_id) {
                $this->error('请不要操作其他商家的商品管理');
            }
            if ($this->isPost()) {
                $data = $this->editCheck();
                $data['product_id'] = $product_id;
                if (false !== $obj->save($data)) {
                    D('Marketcate')->updateNum($data['cate_id']);
                    $this->tuMsg('操作成功', U('market/index'));
                }
                $this->tuMsg('操作失败');
            } else {
				$this->assign('parent_id',$parent_id = D('Marketcate')->getParentsId($detail['cate_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->error('请选择要编辑的商品管理');
        }
    }

    private function editCheck()
    {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['product_name'] = htmlspecialchars($data['product_name']);
        if (empty($data['product_name'])) {
            $this->tuMsg('商品名不能为空');
        }
        $data['desc'] = htmlspecialchars($data['desc']);
        if (empty($data['desc'])) {
            $this->tuMsg('商品介绍不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])) {
            $this->tuMsg('分类不能为空');
        }
		$res = D('Marketcate')->where(array('cate_id'=>$data['cate_id']))->find();
		if($res['parent_id'] == 0){
			$this->tuMsg('请选择二级分类');
		}
        $data['photo'] = htmlspecialchars($data['photo']);
        if (empty($data['photo'])) {
            $this->tuMsg('请上传缩略图');
        }
        if (!isImage($data['photo'])) {
            $this->tuMsg('缩略图格式不正确');
        }
		$data['cost_price'] = (float) ($data['cost_price']);
        $data['price'] = (float) ($data['price']);
        if (empty($data['price'])) {
            $this->tuMsg('价格不能为空');
        }
		if($data['cost_price']){
			if($data['price'] >= $data['cost_price']){
				$this->tuMsg('售价不能高于原价');
			}
		}
		$data['tableware_price'] = (float) ($data['tableware_price']);
        $data['settlement_price'] = (float) ($data['price'] - $data['price'] * $this->market['rate'] / 1000);
		if(false == D('Marketproduct')->gauging_tableware_price($data['tableware_price'],$data['settlement_price'])){
			$this->tuMsg(D('Marketproduct')->getError());//检测餐具费合理性
		}
        $data['is_new'] = (int) $data['is_new'];
        $data['is_hot'] = (int) $data['is_hot'];
        $data['is_tuijian'] = (int) $data['is_tuijian'];
        return $data;
    }

    //分类请求
    public function child($parent_id = 0)
    {
        $datas = D('Marketcate')->where(array('parent_id' => $parent_id, 'shop_id' => $this->shop_id))->select();
        $str .= '<option value="0">请选择子分类</option>' . "\n\r";
        foreach ($datas as $var) {
            $str .= '<option value="' . $var['cate_id'] . '">' . $var['cate_name'] . '</option>' . "\n\r";
        }
        echo $str;
        die;
    }


    //修改重量
    public function details($order_id)
    {
        if ($this->ispost()) {
            if (is_numeric($order_id) && ($order_id = (int)$order_id)) {
                $data1 = I('post.price');
                $data2 = I('post.num');
                $ids= I('post.ids');
                $len=count($data1);
                $total_price=I('post.total_price');
                $food=I('post.total_price');
                $total_num=0;
                for($i=0;$i<$len;$i++){
                    $total_num+=$data2[$i];
                }
                $total['logistics_full_money'] = D('Marketorder')->get_logistics($total_price, $this->shop_id);//获取配送费用
                $orderInfo=M('MarketOrder')->where('order_id='.$order_id)->find();
                $total_price += $orderInfo['logistics'];//加上配送费
                //$total['money'] += $total['tableware_price'];//加上餐具费

                $total['need_pay'] = $total_price;

                $total['full_reduce_price'] = D('Marketorder')->get_full_reduce_price($food, $this->shop_id);//获取满减费用

                //新客户满多少减去多少
                if ($orderInfo['is_new'] && !D('Marketorder')->checkIsNew($this->uid, $this->shop_id)) {
                    if ($total_price >= $orderInfo['full_money']) {
                        $total['new_money'] = $orderInfo['new_money'];
                    }
                };


                //结算金额逻辑后期封装，如果是第三方配送，如果开通新单立减后，配送费用商家出，如果商家开启满减优惠，满减优惠商家出
                if ($total['logistics_full_money']) {
                    var_dump(1);
                    $logistics = 0;
                    $shop_detail = D('Shop')->find($this->shop_id);
                    if ($shop_detail['is_market_pei'] == 1) {
                        $last_settlement_price = $total_price - $total['logistics_full_money'] - $total['full_reduce_price'];
                    }
                } else {
                    var_dump(2);
                    $logistics = $orderInfo['logistics'];
                    $last_settlement_price = $total_price - $total['full_reduce_price'];
                }
                var_dump($total_price,$total['logistics_full_money'],$total['full_reduce_price']);
                die;
                //查询用户是否领取了优惠劵
                $coupon=D('Coupondownload')->where(['shop_id'=>$this->shop_id,'user_id'=>$orderInfo['user_id'],'is_used'=>0])->find();
                if(!empty($coupon)){
                    $ypuhui=D('Coupon')->where(['coupon_id'=>$coupon['coupon_id']])->find();
                    if($food >= $ypuhui['full_price']){
                        $full_price=$ypuhui['reduce_price'];
                        $coupon_id=$ypuhui['coupon_id'];
                        $full_reduce_price=0;
                    }else{
                        $full_price=0;
                        $coupon_id=0;
                        $full_reduce_price=$total['full_reduce_price'];
                    }
                }else{
                    $full_price=0;
                    $coupon_id=0;
                    $full_reduce_price=$total['full_reduce_price'];
                }

                $total['need_pay'] = $total['need_pay'] - $total['new_money'] - $total['logistics_full_money'] - $full_price - $full_reduce_price + $total['tableware_price'];
                $month = date('Ym', NOW_TIME);
                M()->startTrans();
                $order_id = D('Marketorder')->save(array(
                    'order_id' => $order_id,
                    'total_price' =>$food,
                    'need_pay' => $total['need_pay'],
                    'num' => $total_num,
                    'download_coupon_id'=>$coupon_id,
                    'reduce_coupun_money'=>$full_price,
                    'new_money' => (int)$total['new_money'],
                    'logistics_full_money' => (int)$total['logistics_full_money'],
                    'full_reduce_price' => (int)$full_reduce_price,
                    'logistics' => $logistics,
                    'tableware_price' => (int)$total['tableware_price'],
                    'settlement_price' => $last_settlement_price,
                    'status' => 0,
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                    'is_pay' => 0,
                    'confirm' => 1,
                    'month' => $month
                ));
                if ($order_id){
                    for($i=0;$i<$len;$i++){
                        $total_price+=$data1[$i];
                        $total_num+=$data2[$i];
                        $update=array(
                            'num' =>$data2[$i],
                            'total_price' =>$data1[$i],
                            'tableware_price' => $orderInfo['tableware_price'],
                        );
                        $res=D('Marketorderproduct')
                            ->where(['id'=>$ids[$i]])
                            ->save($update);
                        if(!$res){
                            M()->rollback();
                        }
                    }
                    M()->rollback();
                    $this->tuMsg('修改成功,等待用户付款!', U('market/marketorder', array('order_id' => $order_id)));
                }else{
                    M()->rollback();
                }

                $this->tuMsg('修改失败');
                // $obj=D('MarketOrder')->save(array('num'=>$data2,'total_price'=>$data1*$data2,'order_id'=>$order_id));
                // $objs=D('Marketorderproduct')->save(array('num'=>$data2,'total_price'=>$data1*$data2,'order_id'=>$order_id));
                // $this->tuMsg('修改成功,等待用户付款!', U('market/marketorder', array('status' => 1)));

                //}else{
                //      $this->tuMsg('修改失败');
            }

        } else {
            $order_id = I('order_id', '', 'intval,trim');
            if (!($order = D('MarketOrder')->find($order_id))) {
                $this->error('错误');
            } else {
                $op = D('MarketOrderProduct')->where('order_id =' . $order['order_id'])->select();
                if ($op) {
                    $ids = array();
                    foreach ($op as $k => $v) {
                        $ids[$v['product_id']] = $v['product_id'];
                    }
                    $ep = D('MarketProduct')->where(array('product_id' => array('in', $ids)))->select();
                    $ep2 = array();
                    foreach ($ep as $kk => $vv) {
                        $ep2[$vv['product_id']] = $vv;
                    }
                    $this->assign('ep', $ep2);
                    $this->assign('op', $op);
                    $addr = D('UserAddr')->find($order['addr_id']);
                    $this->assign('addr', $addr);
                    $do = D('DeliveryOrder')->where(array('type' => 3, 'type_order_id' => $order['order_id']))->find();
                    if ($do) {
                        if ($do['delivery_id'] > 0) {
                            $delivery = D('Delivery')->find($do['delivery_id']);
                            $this->assign('delivery', $delivery);
                        }
                        $this->assign('do', $do);
                    }
                }
                $this->assign('order', $order);
                $this->display();
            }
        }

    }

    //快递物流
    public function express($order_id = 0)
    {
        $order_id = (int)$order_id;
        if (!($detail = D('Marketorder')->find($order_id))) {
            $this->error('没有该订单');
        }
        if ($detail['closed'] != 0) {
            $this->error('订单被删除');
        }
        if ($detail['status'] == 2 || $detail['status'] == 3 || $detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 5) {
            $this->error('该订单状态不正确，不能发货');
        }
        if ($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false), array('express_id', 'express_number'));
            $data['express_id'] = (int)$data['express_id'];
            if (empty($data['express_id'])) {
                $this->tuMsg('请选择快递');
            }
            if (!($Logistics = D('Logistics')->find($data['express_id']))) {
                $this->tuMsg('没有' . $detail['express_name'] . '快递');
            }
            if ($Logistics['closed'] != 0) {
                $this->tuMsg('该快递已关闭');
            }
            $data['express_number'] = (int)$data['express_number'];
            if (empty($data['express_number'])) {
                $this->tuMsg('快递单号不能为空');
            }

            $add_express = array(
                'status' => 2,
                'order_id' => $order_id,
                'express_id' => $data['express_id'],
                'express_number' => $data['express_number']
            );
            if (D('Marketorder')->save($add_express)) {
                if ($this->_get('wait')) {
                    $this->tuMsg('恭喜您，货到付款发货成功', U('market/marketorder', array('status' => 2)));
                } else {
                    $this->tuMsg('恭喜您，一键发货成功', U('market/marketorder', array('status' => 2)));
                }
            } else {
                $this->tuMsg('发货失败');
            }
        } else {
            $this->assign('detail', $detail);
            $this->assign('logistics', D('Logistics')->where(array('closed' => 0, 'shop_id' => $detail['shop_id']))->select());
            $this->display();
        }
    }


}