<?php
/**
 * 商城售后模型
 * @author pingdan <[<email address>]>
 */
class RefundModel extends CommonModel {
	/**
	 * 主键
	 * @var string
	 */
	protected $pk   = 'refund_id';

	/**
	 * 表名
	 * @var string
	 */
    protected $tableName =  'refund';


    /**
     * 售后类型数组
     * @author pingdan
     * @var array
     */
    protected $refund_type = array(
        1 => '仅退款',
        2 => '退货退款',
        3 => '换货',
    );

    /**
     * 售后状态数组
     * @author pingdan 
     * @var array
     */
    protected $refund_status = array(
        0 => '待审核',
        1 => '已通过',
        2 => '已拒绝',
        3 => '商家已同意,等待买家退货', //退款退货
        4 => '买家已发货,等待商家确认收货', //退款退货
        5 => '商家已确认收货', //退款退货
        6 => '商家已发货,等待买家确认收货', //换货
        7 => '买家已确认收货', //换货
        8 => '订单已完成',
    );


    /**
     * 获取售后类型数组
     * @return array
     */
    public function getRefundType() {
    	return $this->refund_type;
    }

    /**
     * 售后状态数组
     * @return array
     */
    public function getRefundStatus() {
    	return $this->refund_status;
    }

    //计算退款
    public function getRefund_Pay($id,$type)
    {
        if($type){
            switch ($type) {
                case '1':
                $where = " Apporder.order_id = ".$id;
                // print($where);die;
                $info = M("AppointOrder")
                        ->alias("Apporder")
                        ->join("tu_payment_logs AS logs ON logs.type = 'appoint' and logs.order_id = Apporder.order_id")
                        ->field('Apporder.order_id,logs.*')
                        ->where($where)->find();
                //如果余额支付
                if($info['code'] =='money'){

                    D('Users')->addMoney($info['user_id'],$info['need_pay'],"外订单取消，收到退款金额".$info['need_pay']);
                    //修改订单状态
                    D('Appointorder')->where(['order_id'=>$id])->save(['status'=>4]);
                    M('OrderRefund')->where(['goods_id'=>$id,'type'=>1])->save(['status'=>1]);
                    return true;
                }
                //如果支付宝  
                if($info['code'] =='alipay'){
                    //待调试
                    $CONFIG = D('Setting')->fetchAll();
                    $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                    $payinfo = unserialize($pay_info['setting']);
                    // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                    // 具体的返回参数可以去看文档，那里有详细的列表~
                    if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg']))){
                        // 到这里就完成支付宝的退款了，你可以尽情的操作了   
                        if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"预约订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                D('Appointorder')->where(['order_id'=>$id])->save(['status'=>4]);
                                 M('OrderRefund')->where(['goods_id'=>$id,'type'=>1])->save(['status'=>1]);
                                 return true;
                            }else{
                                return false;
                            }
                    }else{
                        $this->error = '退款失败';
                        return false;
                    }
                }
                break;
                case '2':
                    break;
                case '3':
                    $where = " eleorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('EleOrder')
                            ->alias('eleorder')
                            ->join("tu_payment_logs AS logs ON logs.type = 'ele' and logs.order_id = eleorder.order_id")  
                            ->where($where)->find();
                    //判断是不是
                    $delivery=D('DeliveryOrder')->where(array('type_order_id'=>$id))->find();
                    $user=D('Delivery')->where(array('id'=>$delivery['delivery_id']))->find();
                    $money= D('EleOrder')->where(['order_id'=>$id])->find();
                    //如果余额支付
                    if($info['code'] =='money'){
                        //查询是否有配送员接单
                        if(!empty($delivery['delivery_id'])){
                            D('Users')->addMoney($user['user_id'],$money['logistics'],"外卖订单取消，收到配送配".$info['logistics']);
                            $pay_money=$info['need_pay']-$money['logistics'];
                            D('Users')->addMoney($info['user_id'],$pay_money,"外卖订单取消，收到退款金额".$pay_money);
                        }else{
                            D('Users')->addMoney($info['user_id'],$info['need_pay'],"外卖订单取消，收到退款金额".$info['need_pay']);
                        }
                        //修改订单状态
                        D('EleOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>3])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝  
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){

                            //查询是否有配送员接单
                            if(!empty($delivery['delivery_id'])) {
                                $pay_money=$info['need_pay']-$money['logistics'];
                                if(D('Users')->addMoney($user['user_id'],$money['logistics'],"外卖订单取消，收到配送配".$info['logistics']) && false !== D('Users')->addMoneyLogs($info['user_id'],$pay_money,"外卖订单取消，退款至支付宝，退款金额为".$pay_money) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])) {
                                    D('EleOrder')->where(['order_id' => $id])->save(['status' => 4]);
                                    M('OrderRefund')->where(['goods_id' => $id, 'type' => 3])->save(['status' => 1]);
                                    return true;
                                }else{
                                    return false;
                                }
                            }else{
                                // 到这里就完成支付宝的退款了，你可以尽情的操作了
                                if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"外卖订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                    D('EleOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                                    M('OrderRefund')->where(['goods_id'=>$id,'type'=>3])->save(['status'=>1]);
                                    return true;
                                }else{
                                    return false;
                                }
                            }
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    
                    break;
                case '4':
                    $where = " farmorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('FarmOrder')
                            ->alias('farmorder')
                            ->join("tu_payment_logs AS logs ON logs.type = 'farm' and logs.order_id = farmorder.order_id")
                            ->where($where)->find();
                    if($info['code'] =='money'){
                        D('Users')->addMoney($info['user_id'],$info['need_pay'],"农家乐订单取消，收到退款金额".$info['need_pay']);
                        //修改订单状态
                        D('FarmOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>4])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝  
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            // 到这里就完成支付宝的退款了，你可以尽情的操作了   
                            if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"预约订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                D('FarmOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                                M('OrderRefund')->where(['goods_id'=>$id,'type'=>4])->save(['status'=>1]); 
                                return true;
                            }else{
                                return false;
                            }
                           
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    break;
                case '5':
                    $where = " storeorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('StoreOrder')
                        ->alias('storeorder')
                        ->join("tu_payment_logs AS logs ON logs.type = 'store' and logs.order_id = storeorder.order_id")
                        ->where($where)->find();
                    //判断是不是
                    $delivery=D('DeliveryOrder')->where(array('type_order_id'=>$id))->find();
                    $user=D('Delivery')->where(array('id'=>$delivery['delivery_id']))->find();
                    $money= D('StoreOrder')->where(['order_id'=>$id])->find();
                    //如果余额支付
                    if($info['code'] =='money'){
                        //查询是否有配送员接单
                        if(!empty($delivery['delivery_id'])){
                            D('Users')->addMoney($user['user_id'],$money['logistics'],"便利店订单取消，收到配送配".$info['logistics']);
                            $pay_money=$info['need_pay']-$money['logistics'];
                            D('Users')->addMoney($info['user_id'],$pay_money,"便利店订单取消，收到退款金额".$pay_money);
                        }else {
                            //var_dump($info['need_pay']*100);die;
                            D('Users')->addMoney($info['user_id'], $info['need_pay'], "便利店订单取消，收到退款金额" . $info['need_pay'] );
                        }
                        //修改订单状态
                        D('StoreOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>5])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            //查询是否有配送员接单
                            if(!empty($delivery['delivery_id'])) {
                                $pay_money=$info['need_pay']-$money['logistics'];
                                if(D('Users')->addMoney($user['user_id'],$money['logistics'],"便利店订单取消，收到配送配".$info['logistics']) && false !== D('Users')->addMoneyLogs($info['user_id'],$pay_money,"便利店订单取消，退款至支付宝，退款金额为".$pay_money) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])) {
                                    D('EleOrder')->where(['order_id' => $id])->save(['status' => 4]);
                                    M('OrderRefund')->where(['goods_id' => $id, 'type' => 3])->save(['status' => 1]);
                                    return true;
                                }else{
                                    return false;
                                }
                            }else {
                                // 到这里就完成支付宝的退款了，你可以尽情的操作了
                                if (D('Users')->addMoneyLogs($info['user_id'], $info['need_pay'], "便利店订单取消，退款至支付宝，退款金额为" . $info['need_pay']) && false !== D('Users')->addGold($info['shop_id'], -$info['money'], "订单取消，扣除退款金额" . $info['money'])) {
                                    D('StoreOrder')->where(['order_id' => $id])->save(['status' => 4]);
                                    M('OrderRefund')->where(['goods_id' => $id, 'type' => 5])->save(['status' => 1]);
                                    return true;
                                } else {
                                    return false;
                                }
                            }
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    break;
                case '6':
                    $where = "marketorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('MarketOrder')
                        ->alias('marketorder')
                        ->join("tu_payment_logs AS logs ON logs.type = 'market' and logs.order_id = marketorder.order_id")
                        ->where($where)->find();
                    //判断是不是
                    $delivery=D('DeliveryOrder')->where(array('type_order_id'=>$id))->find();
                    $user=D('Delivery')->where(array('id'=>$delivery['delivery_id']))->find();
                    $money= D('MarketOrder')->where(['order_id'=>$id])->find();
                    //如果余额支付
                    if($info['code'] =='money'){
                        //查询是否有配送员接单
                        if(!empty($delivery['delivery_id'])){
                            D('Users')->addMoney($user['user_id'],$money['logistics'],"菜市场订单取消，收到配送配".$info['logistics']);
                            $pay_money=$info['need_pay']-$money['logistics'];
                            D('Users')->addMoney($info['user_id'],$pay_money,"菜市场订单取消，收到退款金额".$pay_money);
                        }else {
                            //var_dump($info['need_pay']*100);die;
                            D('Users')->addMoney($info['user_id'], $info['need_pay'], "菜市场订单取消，收到退款金额" . $info['need_pay']);
                        }
                        //修改订单状态
                        D('MarketOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>6])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            //查询是否有配送员接单
                            if(!empty($delivery['delivery_id'])) {
                                $pay_money=$info['need_pay']-$money['logistics'];
                                if(D('Users')->addMoney($user['user_id'],$money['logistics'],"菜市场订单取消，收到配送配".$info['logistics']) && false !== D('Users')->addMoneyLogs($info['user_id'],$pay_money,"菜市场订单取消，退款至支付宝，退款金额为".$pay_money) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])) {
                                    D('EleOrder')->where(['order_id' => $id])->save(['status' => 4]);
                                    M('OrderRefund')->where(['goods_id' => $id, 'type' => 3])->save(['status' => 1]);
                                    return true;
                                }else{
                                    return false;
                                }
                            }else {
                                // 到这里就完成支付宝的退款了，你可以尽情的操作了
                                if (D('Users')->addMoneyLogs($info['user_id'], $info['need_pay'], "菜市场订单取消，退款至支付宝，退款金额为" . $info['need_pay']) && false !== D('Users')->addGold($info['shop_id'], -$info['money'], "订单取消，扣除退款金额" . $info['money'])) {
                                    D('MarketOrder')->where(['order_id' => $id])->save(['status' => 4]);
                                    M('OrderRefund')->where(['goods_id' => $id, 'type' => 6])->save(['status' => 1]);
                                    return true;
                                } else {
                                    return false;
                                }
                            }
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    break;
                case '7':
                    $where = " eduorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('EduOrder')
                            ->alias('eduorder')
                            ->join("tu_payment_logs AS logs ON logs.type = 'Edu' and logs.order_id = eduorder.order_id")
                            ->where($where)->find();
                    if($info['code'] =='money'){
                        D('Users')->addMoney($info['user_id'],$info['need_pay'],"教育订单取消，收到退款金额".$info['need_pay']);
                        //修改订单状态
                        D('EduOrder')->where(['order_id'=>$id])->save(['order_status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>7])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝  
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"预约订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                D('EduOrder')->where(['order_id'=>$id])->save(['order_status'=>4]);
                                M('OrderRefund')->where(['goods_id'=>$id,'type'=>7])->save(['status'=>1]);
                                return true;
                            }else{
                                return false;
                            }
                            // 到这里就完成支付宝的退款了，你可以尽情的操作了   
                           
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    break;
                case '8':
                    $where = " hotelorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('HotelOrder')
                            ->alias('hotelorder')
                            ->join("tu_payment_logs AS logs ON logs.type = 'hotel' and logs.order_id = hotelorder.order_id")
                            ->where($where)->find();
                    if($info['code'] =='money'){
                        D('Users')->addMoney($info['user_id'],$info['need_pay'],"酒店订单取消，收到退款金额".$info['need_pay']);
                        //修改订单状态
                        D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>8])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝  
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            // 到这里就完成支付宝的退款了，你可以尽情的操作了   
                            if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"酒店订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                D('Hotelorder')->where(['order_id'=>$id])->save(['status'=>4]);
                                M('OrderRefund')->where(['goods_id'=>$id,'type'=>8])->save(['status'=>1]);
                                return true;
                            }else{
                                return false;
                            }
                            
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    break;
                case '9':
                    $where = "ktvorder.order_id = ".$id;
                    // print($where);die;
                    $info = M('KtvOrder')
                        ->alias('ktvorder')
                        ->join("tu_payment_logs AS logs ON logs.type = 'ktv' and logs.order_id = ktvorder.order_id")
                        ->where($where)->find();
                    if($info['code'] =='money'){
                        D('Users')->addMoney($info['user_id'],$info['need_pay'],"KTV订单取消，收到退款金额".$info['need_pay']);
                        //修改订单状态
                        D('KtvOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>9])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            // 到这里就完成支付宝的退款了，你可以尽情的操作了
                            if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"KTV订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){
                                D('KtvOrder')->where(['order_id'=>$id])->save(['status'=>4]);
                                M('OrderRefund')->where(['goods_id'=>$id,'type'=>9])->save(['status'=>1]);
                                return true;
                            }else{
                                return false;
                            }

                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
                    break;
                case '10':

                    $where = " bookingorder.order_id = ".$id;

                    $info = M('BookingOrder')
                            ->alias('bookingorder')
                            ->join("tu_payment_logs AS logs ON logs.type = 'booking' and logs.order_id = bookingorder.order_id")
                            ->where($where)->find();
                    if($info['code'] =='money'){
                        D('Users')->addMoney($info['user_id'],$info['need_pay'],"预约订单取消，收到退款金额".$info['need_pay']);
                        //修改订单状态
                        D('Bookingorder')->where(['order_id'=>$id])->save(['order_status'=>-1]);
                        M('OrderRefund')->where(['goods_id'=>$id,'type'=>10])->save(['status'=>1]);
                        return true;
                    }
                    //如果支付宝  
                    if($info['code'] =='alipay'){
                        //待调试
                        $CONFIG = D('Setting')->fetchAll();
                        $pay_info = D('Payment')->where(['code'=>'alipay'])->find();
                        $payinfo = unserialize($pay_info['setting']);
                        // $this->alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id) //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
                        // 具体的返回参数可以去看文档，那里有详细的列表~
                        if(false !== ($this->alipay_refund($payinfo,$info['need_pay'],$info['return_trade_no'],$info['return_msg'].$info['log_id']))){
                            // 到这里就完成支付宝的退款了，你可以尽情的操作了   
                            if(D('Users')->addMoneyLogs($info['user_id'],$info['need_pay'],"预约订单取消，退款至支付宝，退款金额为".$info['need_pay']) && false !== D('Users')->addGold($info['shop_id'],-$info['money'],"订单取消，扣除退款金额".$info['money'])){

                                D('Bookingorder')->where(['order_id'=>$id])->save(['order_status'=>-1]);
                                M('OrderRefund')->where(['goods_id'=>$id,'type'=>10])->save(['status'=>1]);
                                return true;
                            }else{
                                return false;
                            }
                            
                        }else{
                            $this->error = '退款失败';
                            return false;
                        }
                    }
            }
        }else{
            return false;
        }
    }

    public function alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id)
    {
        //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
        Vendor('Alipay.aop.AopClient');
        Vendor('Alipay.aop.request.AlipayTradeRefundRequest');
        $aop = new \AopClient();
        $aliConfig = C('ALI_CONFIG');
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $payinfo['alipay_app_id'];//appid不用多说
        $aop->rsaPrivateKey = $payinfo['alipay_private_key'];//商户私钥
        $aop->alipayrsaPublicKey= $payinfo['alipay_rsa_public_key'];//支付宝公钥
        $aop->apiVersion = '1.0';//固定
        $wx_replace_sn_id= $wx_replace_sn_id;
        $wx_pdr_id = $wx_pdr_id;
        $wx_price = $wx_price; //退款金额 此处请不要随意修改
        $aop->signType = 'RSA2';//固定
        $aop->postCharset='utf-8';//固定
        $aop->format='json';//固定
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent("{" .
            "\"out_trade_no\":\"$wx_replace_sn_id\"," .
            "\"trade_no\":\"$wx_pdr_id\"," .
            "\"refund_amount\":$wx_price," .
            "\"refund_reason\":\"正常退款\"," .
            "\"out_request_no\":\"HZ01RF001\"," . //此处的标识为退款标识，唯一值，但可以不一样
            "\"operator_id\":\"OP001\"," .
            "\"store_id\":\"NJ_S_001\"," .
            "\"terminal_id\":\"NJ_T_001\"" .
        "  }");
        // print_r($request);die;
        $result = $aop->execute($request);//这里会返回一个对象
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code; //返回这次的状态码，10000为成功
        $return_trade = $result->$responseNode->trade_no;//这个是支付宝生成的订单号
        if(!empty($resultCode)&&$resultCode == 10000){
            return true;
        }else{
            return false;
        }
    }

}
