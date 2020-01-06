<?php
class MarketorderAction extends CommonAction{
    protected $status = 0;
    protected $market;
	
    public function _initialize(){
        parent::_initialize();
        $getMarketCate = D('Market')->getMarketCate();
        $this->assign('getMarketCate', $getMarketCate);
        $this->market = D('Market')->find($this->shop_id);
        if(!empty($this->market) && $this->market['audit'] == 0){
            $this->error('亲，您的申请正在审核中');
        }
        if(empty($this->market) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住菜市场频道', U('market/apply'));
        }
        $this->assign('market', $this->market);
		$this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
		$this->assign('types', D('Marketorder')->getCfg());
        $this->assign('logistics', $logistics = D('Logistics')->where(array('closed'=>0,'shop_id'=>$this->shop_id))->select());//快递
    }
	
    public function index(){
        $this->status = 1;
        $this->showdata();
        $this->display();
    }
    public function wait(){
        $this->status = 2;
        $this->showdata();
        $this->display();
    }
	public function wait_refunded(){
        $this->status = 3;
        $this->showdata();
        $this->display();
    }
	public function refunded(){
        $this->status = 4;
        $this->showdata();
        $this->display();
    }
    public function over(){
        $this->status = 8;
        $this->showdata();
        $this->display();
    }
    public function whole(){
        $obj = D('Marketorder');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))){
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
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
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
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    public function count(){
        $dvo = D('DeliveryOrder');
        $bg_date = strtotime(I('bg_date', 0, 'trim'));
        $end_date = strtotime(I('end_date', 0, 'trim'));
        $this->assign('btime', $bg_date);
        $this->assign('etime', $end_date);
        if ($bg_date && $end_date) {
            $pre_btime = date('Y-m-d H:i:s', $bg_date);
            $pre_etime = date('Y-m-d H:i:s', $end_date);
            $this->assign('pre_btime', $pre_btime);
            $this->assign('pre_etime', $pre_etime);
        }
        $map = array('shop_id' => $this->shop_id, 'type' => 3);
        if ($bg_date && $end_date) {
            $map['create_time'] = array('between', array($bg_date, $end_date));
        }
        import('ORG.Util.Page');
        $count = $dvo->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $dvo->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $value){
            $user_ids[]=$value['user_id'];
            $order_ids[]=$value['type_order_id'];
        }
        $this->assign("addrs", D("Delivery")->itemsByIds($user_ids));
        $this->assign('order',D('EleOrder')->itemsByIds($order_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
    function delivery_count(){
        $delivery_id = I('did', 0, 'intval,trim');
        $btime = I('btime', 0, 'trim');
        $etime = I('etime', 0, 'trim');
        $map = array();
        if($btime && $etime){
            $map['create_time'] = array('between', array($btime, $etime));
        }
        if(!$delivery_id || !$this->shop_id){
            $this->ajaxReturn(array('status' => 'error', 'message' => '错误'));
        }else{
            $map['delivery_id'] = $delivery_id;
            $map['shop_id'] = $this->shop_id;
            $map['type'] = 3;
            $count = D('DeliveryOrder')->where($map)->count();
            if($count){
                $this->ajaxReturn(array('status' => 'success', 'count' => $count));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'message' => '错误'));
            }
        }
    }
	
	
    private function showdata(){
        $obj = D('Marketorder');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'status' => $this->status);
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
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
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
    }
	
	
	//确认发货
    public function queren($order_id){
        $order_id = (int) $order_id;
        if(!($detail = D('Marketorder')->find($order_id))){
            $this->tuError('没有该订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('您无权管理该商家');
        }
        if($detail['status'] != 1){
            $this->tuError('该订单状态不正确');
        }
		
		if(!($shop = D('Shop')->find($detail['shop_id']))){
            $this->tuError('没有该商家');
        }


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
        $orderInfos .='订单号：'.$order_id;
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


		if($shop['is_market_pei'] == 1 && $detail['type'] !=3 && $detail['type'] !=4){
			D('Marketorder')->market_delivery_order($order_id);//接单时候给配送
		}else{
			D('Marketorder')->save(array('order_id' => $order_id, 'status' => 9, 'orders_time' => NOW_TIME));
		}
		
		D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 9,$status = 2);
		D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 9,$status = 2);
        $this->tuSuccess('菜市场订单已确认', U('marketorder/index'));
    }


    //确认发货
    public function queren2($order_id){
        $order_id = (int) $order_id;
        if(!($detail = D('Marketorder')->find($order_id))){
            $this->tuError('没有该订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('您无权管理该商家');
        }
        if($detail['status'] != 1){
            $this->tuError('该订单状态不正确');
        }

        if(!($shop = D('Shop')->find($detail['shop_id']))){
            $this->tuError('没有该商家');
        }


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
        $orderInfos .='订单号：'.$order_id;
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
        D('Marketorder')->save(array('order_id' => $order_id, 'status' => 9, 'orders_time' => NOW_TIME));
        D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 9,$status = 2);
        D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 9,$status = 2);
        $this->tuSuccess('菜市场订单已确认', U('marketorder/index'));
    }

    //炒制完成
    public function chaozhi($order_id){
        $order_id = (int) $order_id;
        if (!($detail = D('Marketorder')->find($order_id))) {
            $this->tuError('没有该订单');
        }
        if ($detail['shop_id'] != $this->shop_id) {
            $this->tuError('您无权管理该商家');
        }
        if ($detail['status'] != 10) {
            $this->tuError('该订单状态不正确');
        }
        D('Marketorder')->save(array('order_id' => $order_id,'shop_time' => NOW_TIME));
        $this->tuSuccess('已确认,等待配送员取货', U('marketorder/index'));
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


    //确认完成
    public function send($order_id){
        $order_id = (int) $order_id;
        $config = D('Setting')->fetchAll();
        $h = isset($config['site']['market']) ? (int) $config['site']['market'] : 6;
        $t = NOW_TIME - $h * 3600;
        if(!($detail = D('Marketorder')->find($order_id))) {
            $this->tuError('没有该订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('您无权管理该商家');
        }
        $shop = D('Shop')->find($detial['shop_id']);
        if($shop['is_market_pei'] == 1 && $detail['type'] !=3){
            $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type' =>3))->find();
            if(!empty($DeliveryOrder)){
                $this->tuError('您开通了配送员配货，无权管理');
            }
        }else{
            if($detail['create_time'] < $t && $detail['status'] == 2){
                D('Marketorder')->overOrder($order_id);
                $this->tuSuccess('确认完成，资金已经结算到账户', U('marketorder/wait'));
            }else{
                $this->tuError('操作失败');
            }
        }
        
    }


    public function quer($order_id){
        $order_id = (int) $order_id;
        $config = D('Setting')->fetchAll();
        $h = isset($config['site']['market']) ? (int) $config['site']['market'] : 6;
        $t = NOW_TIME - $h * 3600;
        if(!($detail = D('Marketorder')->find($order_id))) {
            $this->tuError('没有该订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('您无权管理该商家');
        }

        if($detail['create_time'] < $t && $detail['status'] == 8){
            D('Marketorder')->overOrder($order_id);
            $this->tuSuccess('确认完成，资金已经结算到账户', U('marketorder/wait'));
        }else{
            $this->tuError('操作失败,操作时间不正确');
        }


    }

	
	//退款
	public function refund($order_id = 0){
            $order_id = (int)$order_id;
            $detail = D('Marketorder')->find($order_id);
			if($detail['status'] != 3){
                $this->tuError('菜市场状态不正确');               
            }
			if ($detail['shop_id'] != $this->shop_id) {
				$this->tuError('您无权管理该商家');
			}
            if($detail['status'] == 3){
                if(D('Marketorder')->save(array('order_id'=>$order_id,'status'=>4))){ //将内容变成
                    $obj = D('Users');
                    if($detail['need_pay'] >0){
						//D('Sms')->marketorder_refund_user($order_id); //菜市场退款短信通知用户
                        $obj->addMoney($detail['user_id'],$detail['need_pay'],'菜市场退款,订单号：'.$order_id);
						D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 9,$status = 4);
						D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 9,$status = 4);
						$this->tuSuccess('退款成功', U('marketorder/refunded'));
                    }
                }else{
					$this->tuError('未知错误');	
				}              
            }else{
				$this->tuError('状态不正确');	
			}  
    }
	

	  public function detail(){
        $order_id = I('order_id', '', 'intval,trim');
        if(!($order = D('MarketOrder')->find($order_id))){
            $this->error('错误');
        }else{
            $op = D('MarketOrderProduct')->where('order_id =' . $order['order_id'])->select();
            if($op){
                $ids = array();
                foreach ($op as $k => $v){
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
                $do = D('DeliveryOrder')->where(array('type' =>3, 'type_order_id' => $order['order_id']))->find();
                $data = D('Logistics')->get_market_express($order_id);//查询清单物流
                if($do){
                    if($do['delivery_id'] > 0){
                        $delivery = D('Delivery')->find($do['delivery_id']);
                        $this->assign('delivery', $delivery);
                    }
                    $this->assign('do', $do);
                }
            }
			$this->assign('users', D('Users')->find($order['user_id']));
            $this->assign('order', $order);
            $this->display();
        }
    }

    //预约订单
    public function yuyue(){
        $obj = D('Marketorder');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'is_yuyue' => 1);
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
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
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //外地物流发货
    public function fahuo(){
        $obj = D('Marketorder');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id, 'type' => 3);
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = $addr_ids = array();
        foreach ($list as $key => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
            $addr_ids[$val['addr_id']] = $val['addr_id'];
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
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //确认发货
    public function express($order_id = 0)
    {
        $data = $_POST;
        $order_id = $data['order_id'];
        if (empty($this->uid)) {
            $this->ajaxReturn(array('status' => 'login'));
        }
        if (!($detail = D('Marketorder')->find($order_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有该订单'.$order_id));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单已经被删除'));
        }
        if ($detail['status'] == 2 || $detail['status'] == 3 || $detail['status'] == 8 || $detail['status'] == 4 || $detail['status'] == 5) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该订单状态不正确，不能发货'));
        }
        $express_id = $data['express_id'];
        if (empty($express_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择快递'));
        }
        if (!($detail = D('Logistics')->find($express_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '没有'.$detail['express_name'].'快递'));
        }
        if ($detail['closed'] != 0) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该快递已关闭'));
        }
        $express_number = $data['express_number'];
        if (empty($express_number)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '快递单号不能为空'));
        }
        $add_express = array(
            'status' => 2,
            'express_id'=>$express_id,
            'express_number'=>$express_number
        );
        if(D('Marketorder')->where(['order_id'=>$order_id])->save($add_express)){
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 2);
            D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 2);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '一键发货成功', 'url' => U('marketorder/fahuo')));
        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '发货失败'));
        }
    }








}