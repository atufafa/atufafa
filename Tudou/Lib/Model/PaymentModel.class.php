<?php

class PaymentModel extends CommonModel{
   protected $pk = 'payment_id';
    protected $tableName = 'payment';
    protected $token = 'payment';
    protected $types = array(
        'goods' => '商城购物',
        'appoint' => '家政购买',
        'tuan' => '生活购物',
        'money' => '余额充值',
        'ele' => '在线订餐',
        'booking'  => '订座定金',
        'hotel'=> '酒店订单',
        'breaks'=>'优惠买单',
        'pintuan' => '拼团',//拼团添加
        'crowd' =>'众筹',
        'donate' =>'打赏',
        'running'=>'跑腿',
        'farm'=>'农家乐预订',
        'cloud'=>'云购',
        'zhe'=>'五折卡',
        'life'=>'分类信息',
        'edu'=>'课程付款',
        'stock'=>'股权',
        'community' =>'小区缴费',
        'book'=>'服务预约',
        'ktv'=>'KTV',
        'market'=>'菜市场',
        'store'=>'便利店',
        'rank'=>'会员等级购买',
        'shop'=>'商家入驻',
	    'vip'=>'兑换会员礼品',
	    'exchange'=>'积分商城兑换',
	    'administrators'=>'购买配送管理员',
        'pingche'=>'拼车司机',
        'room'=>'卖房信息认证',
        'vehicle'=>'卖车信息认证',
        'profit'=>'购买反利劵',
        'decorate'=>'装修返利劵',
        'rebate'=>'装修商家返利充值',
        'xinxi'=>'认证信息充值',
        'delivery'=>'入驻配送员或跑腿',
        'agent'=>'入驻代理'
    );

    protected $type = null;
    protected $log_id = null;
    public function getType(){
        return $this->type;
    }

    public function getLogId(){
        return $this->log_id;
    }

    public function getTypes(){
        return $this->types;
    }

    public function getPayments($mobile = false){
        $datas = $this->fetchAll();
        $return = array();
        foreach($datas as $val){
            if($val['is_open']){
                if ($mobile == false){
                    if (!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else{
                   if($val['code'] != 'tenpay' && $val['code'] != 'native' && $val['code'] != 'micro'){
                      $return[$val['code']] = $val;
                   }
                }
            }
        }
        if(!is_weixin()){
            unset($return['weixin']);
        }
        if(is_weixin()){
            unset($return['alipay']);
        }
        return $return;
    }

    //个人中心购买需要使用扫码
    public function getPaymentscode($mobile = false){
        $datas = $this->fetchAll();
        $return = array();
        foreach($datas as $val){
            if($val['is_open']){
                if ($mobile == false){
                    if (!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else{
                    if($val['code'] != 'tenpay' && $val['code'] != 'weixinh5' && $val['code'] != 'micro'){
                        $return[$val['code']] = $val;
                    }
                }
            }
        }
        if(!is_weixin()){
            unset($return['weixin']);
        }
        if(is_weixin()){
            unset($return['alipay']);
        }
        return $return;
    }

    //外卖关闭在线支付

     public function getPayments_delivery($mobile = false){
        $datas = $this->fetchAll();
        $return = array();
        foreach($datas as $val){
            if($val['is_open']){
                if($mobile == false){
                    if(!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else{
                    if($val['code'] != 'tenpay'){
                        $return[$val['code']] = $val;
                    }
                }
            }
        }
        unset($return['money']);
        unset($return['tenpay']);
        unset($return['native']);
        unset($return['weixin']);
        unset($return['alipay']);
        return $return;
    }

    

    //订座关闭WAP扫码支付

     public function getPayments_booking($mobile = false){
        $datas = $this->fetchAll();
        $return = array();
        foreach($datas as $val){
            if($val['is_open']){
                if($mobile == false){
                    if(!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else{
                    if($val['code'] != 'tenpay'){
                        $return[$val['code']] = $val;
                    }
                }
            }
        }
        if(!is_weixin()){
            unset($return['weixin']);
            unset($return['native']);
        }
        if(is_weixin()){
            unset($return['alipay']);
            unset($return['native']);
        }
        return $return;
    }

    //跑腿直接只能在线支付

     public function getPayments_running($mobile = false){
        $datas = $this->fetchAll();
        $return = array();
        foreach($datas as $val){
            if($val['is_open']){
                if($mobile == false){
                    if(!$val['is_mobile_only'])
                        $return[$val['code']] = $val;
                }else{
                    if($val['code'] != 'tenpay'){
                        $return[$val['code']] = $val;
                    }
                }
            }
        }
        if(!is_weixin()){
            unset($return['weixin']);
            unset($return['native']);
        }
        if(is_weixin()){
            unset($return['alipay']);
            unset($return['native']);
        }
        return $return;
    }
    
    public function _format($data){
        $data['setting'] = unserialize($data['setting']);
        return $data;
    }   
    
    //全站回调  
    public function respond($code,$id = 0){
        $payment = $this->checkPayment($code);
        if(empty($payment))
            return false;
        if($code == 'native' || $code == 'micro' ){
              require_cache( APP_PATH . 'Lib/Payment/' . $code . '.weixin' . '.class.php');//扫码支付
        }elseif(defined('IN_MOBILE')) {
            require_cache(APP_PATH . 'Lib/Payment/' . $code . '.mobile.class.php');
        }else{
            require_cache(APP_PATH . 'Lib/Payment/' . $code . '.class.php');
        }
        $obj = new $code();
        return $obj->respond($id);//传一个参数2018年4月新增这里的ID是日志ID
    }

    public function getCode($logs){
        $CONFIG = D('Setting')->fetchAll();
        $datas = array(
            'subject' => $CONFIG['site']['sitename'] . $this->types[$logs['type']],
            'logs_id' => $logs['log_id'],
            'logs_amount' => $logs['need_pay'],
        );
        $payment = $this->getPayment($logs['code']);
        if($logs['code'] == 'native' || $logs['code'] == 'micro' ){
             require_cache( APP_PATH . 'Lib/Payment/' . $logs['code'] . '.weixin' . '.class.php' );//扫码支付
        }elseif (defined('IN_MOBILE')) {
            require_cache(APP_PATH . 'Lib/Payment/' . $logs['code'] . '.mobile.class.php');
        }else{
            require_cache(APP_PATH . 'Lib/Payment/' . $logs['code'] . '.class.php');
        }
        $obj = new $logs['code']();
        return $obj->getCode($datas, $payment);
    }   



    public function checkMoney($logs_id,$money){
        $money = (int) ($money );
        $logs = D('Paymentlogs')->find($logs_id);
        if($logs['need_pay'] == $money)
            return true;
        return false;
    }

    public function checkPayment($code){
        $datas = $this->fetchAll();
        foreach($datas as $val){
            if($val['code'] == $code)
                return $val;
        }
        return array();
    }

    public function getPayment($code){
        $datas = $this->fetchAll();
        foreach($datas as $val){
            if($val['code'] == $code)
                return $val['setting'];
        }
        return array();

    }

    public function logsPaid($logs_id,$trade_no,$seller_id,$notify_time,$intro) {
      
        $this->log_id = $logs_id; //用于外层回调
        
        $logs = D('Paymentlogs')->where(['log_id'=>$logs_id])->find();
        
        if (!empty($logs) && !$logs['is_paid']){
            $data = array('log_id' => $logs_id,'is_paid' => 1,'return_order_id'=>$seller_id,'return_trade_no'=>$trade_no,'return_msg'=>$intro,'return_date'=>$notify_time);
            if (D('Paymentlogs')->save($data)){ //总之 先更新 然后再处理逻辑  这里保障并发是安全的
                $ip = get_client_ip();
                D('Paymentlogs')->save(array('log_id' => $logs_id,'pay_time' => NOW_TIME,'pay_ip' => $ip));//更新付款时间
                $this->type = $logs['type'];
                
                if ($logs['type'] == 'appoint')
                {
                    $order = D('Appointorder')->find($logs['order_id']);
                    //查询是否使用优惠劵
                    if(!empty($order['coupun_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['coupun_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    D('Appointorder')->updateOrder($logs['order_id']);//家政订单回调
                    return true;
                }
                elseif($logs['type'] == 'rank')
                {
                     //提升会员等级
                    $Userrank = D('Userrank')->where(array('rank_id'=>$logs['types']))->find();
                    D('Users')->save(array('user_id'=>$logs['user_id'],'rank_id'=>$logs['types']));
                    if($this->_CONFIG['profit']['profit_buy_user_rank']){
                        D('Userprofitlogs')->pay_rank_profit_user($logs['user_id'],$logs['need_pay'],$Userrank['rank_name']);//会员购买等级对接三级分销
                    }
                    M('user_buy_rank')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    $config=D('Setting')->fetchAll();
                    $time=$config['backers']['end_time'];
                    $end_time=date("Y-m-d",strtotime('+'.$time.'year'));
                    $jifen=$config['backers']['jifen'];
                    $money=$config['backers']['exchange_money'];
                    $us=D('BuyVip')->where(['user_id'=>$logs['user_id']])->find();
                    if(empty($us)){
                        D('BuyVip')->addvip($us['id'],$logs['user_id'],$end_time,$logs['types'],$money,'用户购买会员');
                    }else{
                        D('BuyVip')->addExchange($us['id'],$logs['user_id'],$money,'购买会员送兑换值'.$money);
                    }
                    $user=D('Users')->where(['user_id'=>$logs['user_id']])->find();
                    D('Users')->addIntegral($logs['user_id'],$jifen,'购买会员送积分'.$jifen);
                    $cun=D('BuyVip')->where(['user_id'=>$user['fuid1']])->find();
                    if(!empty($user['fuid1'])){
                        if(empty($cun)){
                            D('BuyVip')->addvip($cun['id'],$user['fuid1'],$end_time,$logs['types'],$money,'下级购买会员送兑换值');
                        }else{
                            D('BuyVip')->addExchange($cun['id'],$user['fuid1'],$money,'下级购买会员送兑换值'.$money);
                        }
                        D('Users')->addIntegral($user['fuid1'],$jifen,'下级购买会员送积分'.$jifen);
                    }
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);

                    return true;
                }
                elseif($logs['type'] == 'breaks')
                {
                    //优惠买单
                    D('Breaksorder')->settlement($logs['order_id']);//优惠买单回调
                    return true;
                }
                elseif($logs['type'] == 'shop')
                {
                    //商家入驻更新费用
                    D('Shop')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    D('Depositmanagement')->addyajin($logs['user_id'],0,$logs['deposit'],$logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }
                elseif($logs['type'] == 'life')
                {
                    //分类信息订单回调
                    D('Life')->updateLife($logs['order_id'],$logs['need_pay']);//分类信息
                    return true;
                }
                elseif($logs['type'] == 'money')
                {
                    D('Users')->updateCount($logs['user_id'], 'money', $logs['need_pay']);
                    
                    $payment = D('Payment')->where(array('code'=>$logs['code']))->find();//获取支付
                    
                    D('Usermoneylogs')->add(array(
                        'user_id' => $logs['user_id'], 
                        'money' => $logs['need_pay'], 
                        'create_time' => NOW_TIME, 
                        'create_ip' => $ip, 
                        'intro' => '余额充值【'.round($logs['need_pay'],2).'】支付ID'.$logs['log_id'].'支付方式【'.$payment['name'].'】', 
                    ));
                    //D('Users')->return_recharge_integral_prestige($logs_id,$logs['user_id'], $logs['need_pay']);//充值余额送积分，威望，忽略错误
                    //判断要送多少积分
                    $config=D('Setting')->fetchAll();
                    if($logs['need_pay']>=$config['cash']['recharge_full_1']){
                        $jifen=$config['cash']['recharge_give_1'];
                        D('Users')->addIntegral($logs['user_id'], $jifen,'充值积分');//充值满送，忽略错误
                    }elseif($logs['need_pay']>=$config['cash']['recharge_full_2']){
                        $jifen=$config['cash']['recharge_give_2'];
                        D('Users')->addIntegral($logs['user_id'], $jifen,'充值积分');//充值满送，忽略错误
                    }elseif ($logs['need_pay']>=$config['cash']['recharge_full_3']){
                        $jifen=$config['cash']['recharge_give_3'];
                        D('Users')->addIntegral($logs['user_id'], $jifen,'充值积分');//充值满送，忽略错误
                    }
//                    //file_put_contents(APP_PATH . 'Lib/Model/a.txt', var_export($logs, true));
//                    if(!empty($logs['deposit'])){
//                        D('Depositmanagement')->addyajin($logs['user_id'],2,$logs['deposit'],$logs['code']);
//                    }
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }
                elseif($logs['type'] == 'tuan')
                {
                    //抢购都是发送抢购券
                    $member = D('Users')->find($logs['user_id']);
                    $codes = array();
                    $obj = D('Tuancode');
                    $order = D('Tuanorder')->find($logs['order_id']);
                    $tuan = D('Tuan')->find($order['tuan_id']);
                    //结束
                    for($i = 0; $i < $order['num']; $i++){
                        $local = $obj->getCode();
                        $insert = array(
                            'user_id' => $logs['user_id'], 
                            'shop_id' => $tuan['shop_id'], 
                            'order_id' => $order['order_id'], 
                            'tuan_id' => $order['tuan_id'], 
                            'code' => $local, 
                            'price' => $tuan['price'], 
                            'real_money' => (int)($order['need_pay'] / $order['num']), //退款的时候用
                            'real_integral' => (int)($order['use_integral'] / $order['num']), //退款的时候用
                            'fail_date' => $tuan['fail_date'], 
                            'settlement_price' => $tuan['settlement_price'], 
                            'create_time' => NOW_TIME, 
                            'create_ip' => $ip, 
                        );
                        $codes[] = $local;
                        $obj -> add($insert);
                    }
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    D('Tuanorder')->save(array('order_id' => $order['order_id'], 'status' => 1));//设置已付款
                   // D('Sms')->sms_tuan_user($member['user_id'],$order['order_id']);//团购商品通知用户
                    D('Tuan')->updateCount($tuan['tuan_id'], 'sold_num');//更新卖出产品
                    D('Tuan')->updateCount($tuan['tuan_id'], 'num', -$order['num']);
                   //D('Sms')->tuanTZshop($tuan['shop_id']);//发送短信通知商家
                    D('Users')->prestige($member['user_id'], 'tuan');
                    D('Tongji')->log(1, $logs['need_pay']);//统计//分销
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 4,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 4,$status = 1);
                    return true;
                }elseif($logs['type'] == 'pintuan'){
                    $member = D('Users')->find($logs['user_id']);
                    $order = D('Pindanorder')->find($logs['order_id']);
                    D('Zeroelementforward')->where(['user_id'=>$logs['user_id'],'tuan_id'=>$order['tuan_id']])->save(['colse'=>1]);
                    $pindan = D('Pindan')->find($order['tuan_id']);
                    $codes = array();
                    $obj = D('Pindancode');
                    for($i = 0; $i < $order['num']; $i++){
                        $local = $obj->getCode();
                        $insert = array(
                            'user_id' => $logs['user_id'], 
                            'shop_id' => $pindan['shop_id'], 
                            'order_id' => $order['order_id'], 
                            'tuan_id' => $order['tuan_id'], 
                            'code' => $local, 
                            'price' => $pindan['price'], 
                            'real_money' => (int)($order['need_pay'] / $order['num']), //退款的时候用
                            'real_integral' => (int)($order['use_integral'] / $order['num']), //退款的时候用
                            'fail_date' => $pindan['fail_date'], 
                            'settlement_price' => $tuan['settlement_price'], 
                            'create_time' => NOW_TIME, 
                            'create_ip' => $ip, 
                        );
                        $codes[] = $local;
                        $obj -> add($insert);
                    }
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    D('Pindanorder')->save(array('order_id' => $order['order_id'], 'status' => 1));//设置已付款
                   // D('Sms')->user_pindan($member['user_id'],$order['order_id']);//团购商品通知用户
                    D('Pindan')->updateCount($pindan['tuan_id'], 'sold_num');//更新卖出产品
                    D('Pindan')->updateCount($pindan['tuan_id'], 'num', -$order['num']);
                   //D('Sms')->shop_pindan($pindan['shop_id']);//发送短信通知商家
                    D('Tongji')->log(1, $logs['need_pay']);//统计//分销
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 4,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 4,$status = 1);
                    return true;

                }elseif ($logs['type'] == 'ele'){
                    //餐饮订餐
                    D('Eleorder')->save(array('order_id' => $logs['order_id'], 'status' => 1, 'is_pay' => 1,'pay_time' => NOW_TIME));
                    $order = D('EleOrder')->where('order_id =' . $logs['order_id'])-> find();
                    //查询是否使用优惠劵
                    if(!empty($order['download_coupon_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['download_coupon_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    D('Eleorder')->ele_month_num($logs['order_id']);//更新外卖销量
                    //查询订单
                    $order=D('Eleorder')->where(['order_id'=>$logs['order_id']])->find();
                    $eleshop=D('Ele')->where(['shop_id'=>$order['shop_id']])->find();
                    if(!empty($order['fruit']) && $eleshop['is_guozi']==1){
                        $time=date('Y-m-d',time());
                        $ma['create_time']=array('EQ',$time);
                        $glass=M('glass')->where(['shop_id'=>$eleshop['shop_id']])->where($ma)->find();
                        if(empty($glass)){
                            $arr=array('type'=>1, 'shop_id'=>$eleshop['shop_id'], 'create_time'=>$time, 'glass_num'=>1);
                            M('glass')->add($arr);
                            D('Ele')->where(['shop_id'=>$eleshop['shop_id']])->setDec('glass_num',$order['fruit']);
                        }else{
                            D('Ele')->where(['shop_id'=>$eleshop['shop_id']])->setDec('glass_num',$order['fruit']);
                            M('glass')->where(['shop_id'=>$eleshop['shop_id'],'id'=>$glass['id']])->setInc('glass_num',$order['fruit']);
                        }
                    }

                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    //判断商家是否发起自动接单
                    if($eleshop['is_print_deliver']==1 && $order['type']!=4){
                        D('Eleorder')->ele_delivery_order($logs['order_id'],0);//外卖配送接口
                    }
                    D('Tongji')->log(3, $logs['need_pay']);//统计
                    //D('Sms')->eleTZshop($logs['order_id']);//通知商家
                    D('Eleorder')->combination_ele_print($logs['order_id'],$order['addr_id']);//外卖打印万能接口
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 1,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 1,$status = 1);
                    return true;
                }elseif($logs['type'] == 'market'){
                    
                    //菜市场
                    D('Marketorder')->save(array('order_id' => $logs['order_id'],'status' => 1,'is_pay' => 1,'pay_time' => NOW_TIME));
                    $order = D('Marketorder')->where('order_id =' . $logs['order_id'])->find();
                    //查询是否使用优惠劵
                    if(!empty($order['download_coupon_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['download_coupon_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    //查询订单
                    $order=D('Marketorder')->where(['order_id'=>$logs['order_id']])->find();
                    $eleshop=D('Store')->where(['shop_id'=>$order['shop_id']])->find();
                    if(!empty($order['fruit']) && $eleshop['is_guozi']==1){
                        $num=$eleshop['glass_num']-1;
                        D('Store')->where(['shop_id'=>$eleshop['shop_id']])->save(['glass_num'=>$num]);
                    }
                    D('Marketorder')->market_month_num($logs['order_id']);//更新菜市场销量
                    //D('Sms')->marketTZshop($logs['order_id']);//通知商家
                    D('Marketorder')->combination_market_print($logs['order_id'],$order['addr_id']);//菜市场打印万能接口
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 9,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type =9,$status = 1);
//                    D('Marketorder')->updUseEnvelope($logs['order_id']); //更新用户使用红包折扣
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif($logs['type'] == 'store'){
                    //便利店订餐
                    D('Storeorder')->save(array('order_id' => $logs['order_id'],'status' => 1,'is_pay' => 1,'pay_time' => NOW_TIME));
                    $order = D('Storeorder')->where('order_id =' . $logs['order_id'])->find();
                    //查询是否使用优惠劵
                    if(!empty($order['download_coupon_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['download_coupon_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }

                    $eleshop=D('Store')->where(['shop_id'=>$order['shop_id']])->find();
                    if(!empty($order['fruit']) && $eleshop['is_guozi']==1){
                        $time=date('Y-m-d',time());
                        $ma['create_time']=array('EQ',$time);
                        $glass=M('glass')->where(['shop_id'=>$eleshop['shop_id']])->where($ma)->find();
                        if(empty($glass)){
                            $arr=array('type'=>2, 'shop_id'=>$eleshop['shop_id'], 'create_time'=>$time, 'glass_num'=>$order['fruit']);
                            M('glass')->add($arr);
                            D('Store')->where(['shop_id'=>$eleshop['shop_id']])->setDec('glass_num',$order['fruit']);
                        }else{
                            D('Store')->where(['shop_id'=>$eleshop['shop_id']])->setDec('glass_num',$order['fruit']);
                            M('glass')->where(['shop_id'=>$eleshop['shop_id'],'id'=>$glass['id']])->setInc('glass_num',$order['fruit']);
                        }
                    }

                    D('Storeorder')->store_month_num($logs['order_id']);//更新便利店销量
                    //D('Sms')->storeTZshop($logs['order_id']);//通知商家
                    D('Storeorder')->combination_store_print($logs['order_id'],$order['addr_id']);//便利店打印万能接口
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 10,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 10,$status = 1);
//                    D('Storeorder')->updUseEnvelope($logs['order_id']); //更新用户使用红包折扣
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif($logs['type'] == 'hotel'){   
                    //酒店预订
                    $order = D('Hotelorder')->find($logs['order_id']);
                    //查询是否使用优惠劵
                    if(!empty($order['coupun_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['coupun_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    $room = D('Hotelroom')->find($order['room_id']);
                    $hotel = D('Hotel')->find($order['hotel_id']);
                    $shop = D('Shop')->find($hotel['shop_id']);
                    D('Hotelorder')->save(array('order_id' => $logs['order_id'], 'order_status' => 1)); //设置已付款
                   // D('Sms')->sms_hotel_user($logs['order_id']);//短信通知用户
                    //D('Sms')->sms_hotel_shop($logs['order_id']);//短信通知酒店商家
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 6,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 6,$status = 1);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif ($logs['type'] == 'crowd'){
                    //众筹
                    D('Crowdorder')->save(array('order_id' => $logs['order_id'],'status' => 1 ));
                    D('Crowd')->update_crowd_order_status($logs['order_id']);
                    //D('Sms')->sms_crowd_user($logs['order_id']);//短信通知会员
                    //D('Sms')->sms_crowd_uid($logs['order_id']);//通知众筹发起人
                    return true;
                }elseif ($logs['type'] == 'farm'){   
                    //农家乐预订
                    $order = D('FarmOrder')->find($logs['order_id']);
                    //查询是否使用优惠劵
                    if(!empty($order['coupun_id'])){
                        $shop=D('Farm')->where(['farm_id'=>$order['farm_id']])->find();
                        $s=D('Coupondownload')->where(['coupon_id'=>$order['coupun_id'],'user_id'=>$logs['user_id'],'shop_id'=>$shop['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                   
                    $f = D('FarmPackage')->find($order['pid']);
                    $farm = D('Farm')->find($order['farm_id']);
                    $shop = D('Shop')->find($farm['shop_id']);

                    D('FarmOrder')->save(array('order_id' => $logs['order_id'], 'order_status' => 1)); //设置已付款
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 5,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 5,$status = 1);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif($logs['type'] == 'booking'){
                    D('Bookingorder')->updateBookingOrder($logs['order_id'],$logs['code']);//货到付款
                    return true;
                }elseif ($logs['type'] == 'community'){
                    //小区回调
                    D('Communityorder')->orderpay($logs['log_id']);
                    return true;
                }  elseif ($logs['type'] == 'running'){
                    //跑腿
                    D('Running')->save(array('running_id' => $logs['order_id'],'status' => 1 ));
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                   // D('Sms')->sms_running_user($logs['order_id']);
                   // D('Sms')->sms_delivery_user($logs['order_id'],2);
                    return true;
                } elseif ($logs['type'] == 'cloud'){
                    //元购
                    D('Cloudgoods')->save_cloud_order_status($logs['order_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                } elseif ($logs['type'] == 'zhe'){
                    //五折卡回调
                    D('Zheorder')->save_zhe_logs_status($logs['order_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif ($logs['type'] == 'stock'){
                    //股权频道回调
                    D('Stock')->save_stock_logs_status($logs['order_id']);
                    return true;
                }elseif ($logs['type'] == 'edu'){

                    $order = D('EduOrder')->find($logs['order_id']);
                    //查询是否使用优惠劵
                    if(!empty($order['coupun_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['coupun_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    D('EduOrder')->save_edu_logs_status($logs['order_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif ($logs['type'] == 'book'){
                    //预约
                    D('BookOrder')->save_book_logs_status($logs['order_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif($logs['type'] == 'jingjia'){
                    D('Shop')->save_jingjia($logs['log_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type'] == 'capital'){
                    D('Users')->save_capital($logs['log_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type']=='jifen'){
                    D('Users')->save_jifen($logs['log_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type'] == 'deposit'){
                    D('Depositmanagement')->save_deposit($logs['log_id']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif ($logs['type'] == 'ktv'){
                    $order = D('KtvOrder')->find($logs['order_id']);
                    //查询是否使用优惠劵
                    if(!empty($order['coupun_id'])){
                        D('Coupondownload')->where(['coupon_id'=>$order['coupun_id'],'user_id'=>$logs['user_id'],'shop_id'=>$order['shop_id']])
                            ->save(['is_used'=>1,'used_time'=>NOW_TIME,'used_ip'=>get_client_ip()]);
                    }
                    //KTV频道回调
                    D('KtvOrder')->save_ktv_logs_status($logs['order_id']);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 8,$status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 8,$status = 1);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    return true;
                }elseif($logs['type']=='exchange'){//积分商城
					    if (empty($logs['order_id']) && !empty($logs['order_ids'])){
                        //合并付款
                        $order_ids = explode(',', $logs['order_ids']);
                        D('Integralorder')->save(array('status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
                        //D('Sms')->mallTZshop($order_ids); //通知商家
                        D('Integralorder')->mallSolds($order_ids);//更新销售接口
                        D('Integralorder')->jifenPeisong($order_ids,0);//更新配送接口
                        D('Integralorder')->combination_jifen_print($order_ids);//万能商城订单打印
                    } else {
                        D('Integralorder')->save(array('order_id' => $logs['order_id'],'status' => 1));
                        D('Integralorder')->jifenPeisong(array($logs['order_id']),0);//更新配送接口
                        D('Integralorder')->mallSolds($logs['order_id']);//更新销售接口
                       // D('Sms')->mallTZshop($logs['order_id']);//通知商家
                        D('Integralorder')->combination_jifen_print($logs['order_id']);//万能商城订单打印
                        
                        D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 2,$status = 1);
                        D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 2,$status = 1);              
                    }
                    D('Tongji')->log(2, $logs['need_pay']); //统计
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
		    }elseif($logs['type'] == 'vip') {
                    //会员兑换
                    D('ExchangeOrder')->save(array('order_id' => $logs['order_id'], 'status' => 1));
                    D('ExchangeOrder')->mallPeisong(array($logs['order_id']), 0);//更新配送接口
                    D('ExchangeOrder')->mallSold($logs['order_id']);//更新销售接口
                    //D('Sms')->mallTZshop($logs['order_id']);//通知商家
                    //更新兑换值
                    $money = D('ExchangeOrderGoods')->where(['order_id' => $logs['order_id']])->find();
                    $money2 = M('ExchangeSpecGoodsPrice')->where(['goods_id' => $money['goods_id'], 'key' => $money['key']])->find();
                    $hui=D('BuyVip')->where(['user_id'=>$logs['user_id']])->find();
                    if(!empty($hui)){
                        D('BuyVip')->addExchange($hui['id'],$logs['user_id'], -$money2['price'], '兑换会员礼品，使用兑换值' . $money2['price']);
                    }
                    $user = D('Users')->where(['user_id' => $logs['user_id']])->find();
                    if ($money['type'] == 2 && $user['rank_id'] == 0) {
                        $config = D('Setting')->fetchAll();
                        $time = $config['backers']['end_time'];
                        $end_time = date("Y-m-d", strtotime('+' . $time . 'year'));
                        D('Users')->where(['user_id' => $logs['user_id']])->save(['rank_id' => 1]);
                        D('BuyVip')->addvip($logs['user_id'], $end_time, 1, 0, '购买会员专区商品成为会员');
                    }
                    D('ExchangeOrder')->combination_goods_print($logs['order_id']);//万能商城订单打印
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'], $cate = 1, $type = 2, $status = 1);
                    D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'], $cate = 2, $type = 2, $status = 1);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //配送管理员
                }elseif($logs['type'] == 'administrators') {
                    D('Applicationmanagement')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    D('Depositmanagement')->addyajin($logs['user_id'], 3, $logs['deposit'], $logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //拼车司机
                }elseif($logs['type'] == 'pingche'){
                    D('Depositmanagement')->addyajin($logs['user_id'], 5, $logs['deposit'], $logs['code']);
                    M('users_pinche')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //卖房公司认证
				}elseif($logs['type'] == 'room'){
                    D('Lifesauthentication')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    D('Depositmanagement')->addyajin($logs['user_id'], 6, $logs['deposit'], $logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
		        //卖车公司认证
                }elseif($logs['type'] == 'vehicle'){
                    M('lifes_vehicle')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    D('Depositmanagement')->addyajin($logs['user_id'], 7, $logs['deposit'], $logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //发布信息认证
                }elseif($logs['type'] == 'xinxi'){
                    M('life_audit')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    D('Depositmanagement')->addyajin($logs['user_id'], 9, $logs['deposit'], $logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type'] == 'profit') {
                    D('Users')->where(['user_id' => $logs['user_id']])->save(['rebate' => 1]);
                    D('Lifereserve')->where(['user_id' => $logs['user_id'], 'log_id' => $logs['log_id']])->save(['is_pay' => 1]);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type'] == 'delivery') {
                    D('Delivery')->where(array('user_id' => $logs['user_id']))->save(['is_pay' => 1]);
                    D('Depositmanagement')->addyajin($logs['user_id'], 2, $logs['deposit'], $logs['code']);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }elseif($logs['type'] == 'agent'){
                   M('user_agent_applys')->where(['user_id'=>$logs['user_id']])->save(['is_pay'=>1]);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //装修返利劵
                }elseif($logs['type'] == 'decorate'){
                    D('Users')->where(['user_id'=>$logs['user_id']])->save(['decorate'=>1]);
                    $arr=array('user_id'=>$logs['user_id'], 'money'=>$logs['need_pay'], 'is_pay'=>1, 'create_time'=>NOW_TIME);
                    D('Lifereserve')->where(['user_id'=>$logs['user_id'],'log_id'=>$logs['log_id']])->save(['is_pay'=>1]);
                    D('DecorateRebate')->add($arr);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                //装修商家返利
                }elseif($logs['type'] == 'rebate'){
                    $order=D('DecorateShopRebate')->where(['logs_id'=>$logs['log_id']])->find();
                    D('DecorateShopRebate')->where(['logs_id'=>$logs['log_id']])->save(['is_pay'=>1]);
                    D('Liferebate')->where(['life_id'=>$order['life_id'],'shop_id'=>$order['shop_id'],'type'=>3])->save(['is_pay'=>1,'pay_money'=>$logs['need_pay']]);
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                }else { // 商城购物

                    if (empty($logs['order_id']) && !empty($logs['order_ids'])){
                        //合并付款
                        $order_ids = explode(',', $logs['order_ids']);
                        D('Order')->save(array('status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
                        //D('Sms')->mallTZshop($order_ids); //通知商家
                        D('Order')->mallSold($order_ids);//更新销售接口
                        D('Order')->mallPeisong($order_ids,0);//更新配送接口
                        D('Order')->combination_goods_print($order_ids);//万能商城订单打印
                    } else {
                        D('Order')->save(array('order_id' => $logs['order_id'],'status' => 1));
                        D('Order')->mallPeisong(array($logs['order_id']),0);//更新配送接口
                        D('Order')->mallSold($logs['order_id']);//更新销售接口
                        //D('Sms')->mallTZshop($logs['order_id']);//通知商家
                        D('Coupon')->change_download_id_is_used($logs['order_id']);//如果有优惠劵就修改优惠劵的状态，合并付款暂时不做
                        D('Order')->combination_goods_print($logs['order_id']);//万能商城订单打印
                        
                        D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 1,$type = 2,$status = 1);
                        D('Weixinmsg')->weixinTmplOrderMessage($logs['order_id'],$cate = 2,$type = 2,$status = 1);
                        D('Order')->updUseEnvelope($logs['order_id']); //更新用户使用红包折扣
                    }
                    $this->Addjifen($logs['user_id'],$logs['need_pay']);
                    D('Tongji')->log(2, $logs['need_pay']); //统计
                }
                
            }
        return true;
      }

   }

       /**
    * 付款后记录订单操作日志
    * @param  array $logs 支付日志表记录（一行）
    * @return [type]       [description]
    */
   private function _order_opteration_logs($logs) {
        $data = array(
            'operation_type' => 1, //已付款
            'order_id' => $logs['order_id'],
            'order_type' => $logs['type'],
            'operator' => $logs['user_id'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'intro' => '用户付款',
            );

        if ($logs['order_ids']) {
            //如果是合并付款，分别取出订单id
            $order_ids = explode(',', $logs['order_ids']);
            foreach ($order_ids as $val) {
                $data['order_id'] = $val;
                D('OrderOperationLogs')->writeLog($data);
            }
        } else {
            D('OrderOperationLogs')->writeLog($data);
        }
        
   }

   //下单增积分
    public function Addjifen($user_id,$money){
        $user=D('Users')->find($user_id);
        $config = D('Setting')->fetchAll();
        $jifen=$config['integral']['shop_jifen'];

        //计算积分
        $sumjifen=$money*($jifen/100);
        if(!empty($user['fuid1'])){
            $dai=M('user_agent_applys')->where(['user_id'=>$user['fuid1']])->find();
            if(!empty($dai)){
                D('Users')->addIntegral($dai['user_id'],$sumjifen,'推荐人购买商品获得积分');
                $dai2=M('user_agent_applys')->where(['user_id'=>$dai['user_guide_id']])->find();
                if(!empty($dai2) && $dai['agent_id']>$dai2['agent_id']){
                    D('Users')->addIntegral($dai2['user_id'],$sumjifen,'推荐人购买商品获得积分');
                    $dai3=M('user_agent_applys')->where(['user_id'=>$dai2['user_guide_id']])->find();
                    if(!empty($dai3) && $dai2['agent_id']>$dai3['agent_id']) {
                        D('Users')->addIntegral($dai3['user_id'],$sumjifen,'推荐人购买商品获得积分');
                    }
                }
            }
            D('Users')->addIntegral($user['fuid1'],$sumjifen,'推荐人购买商品获得积分');
        }
        if(!empty($user['fuid2'])){
            D('Users')->addIntegral($user['fuid2'],$sumjifen,'推荐人购买商品获得积分');
        }
        if(!empty($user['fuid3'])){
            D('Users')->addIntegral($user['fuid3'],$sumjifen,'推荐人购买商品获得积分');
        }
        D('Users')->addIntegral($user_id,$sumjifen,'购买商品获得积分');
    }

}



