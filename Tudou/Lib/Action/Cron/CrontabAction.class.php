<?php

class CrontabAction extends Action{
    public function queren($order_id = 0){
        echo "start\n";

        // 后台设置
        $config = D('Setting')->where(array('k' => 'site'))->find();
        $config = unserialize($config['v']);
        // dump($config);
        $time_goods = time()-3600*24*$config['goods'];
        $time_ele = time()-3600*$config['ele'];
        $time_market = time()-3600*$config['market'];
        $time_store = time()-3600*$config['store'];

        // 商城-已经到货
        $list = D('Order')->where(['status'=>1,'is_daofu'=>1,'sendtime'=>['gt',$time_goods]])->select();
        // dump($list);
        foreach ($list as $k => $v) {
            $order_id = $v['order_id'];

            //检测配送状态
            $shop = D('Shop')->find($v['shop_id']);
            if ($shop['is_goods_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->find();
                if ($DeliveryOrder['status'] != 8) {
                    // $this->tuMsg('配送员还未完成订单');
                    continue;
                }
            }

            // 已收货操作
            if (D('Order')->save(array('order_id' => $order_id, 'status' => 3))) {
                echo "do Order $order_id \n";
                D('Order')->overOrder($order_id); //确认到账入口
            }
        }
        // 商城-已发货
        $list = D('Order')->where(['status'=>2,'sendtime'=>['gt',$time_goods]])->select();
        foreach ($list as $k => $v) {
            $order_id = $v['order_id'];
            
            //检测配送状态
            $shop = D('Shop')->find($v['shop_id']);
            if ($shop['is_goods_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->find();
                if ($DeliveryOrder['status'] != 8) {
                    // $this->tuMsg('配送员还未完成订单');
                    continue;
                }
            }

            // 已收货操作
            if (D('Order')->save(array('order_id' => $order_id, 'status' => 3))) {
                echo "do Order $order_id \n";
                D('Order')->overOrder($order_id); //确认到账入口
            }
        }
        
        // 外卖  
        $list = D('Eleorder')->where(['status'=>2,'pay_time'=>['gt',$time_ele]])->select();
        // dump($list);
        foreach ($list as $k => $v) {
            $order_id = $v['order_id'];

            //检测配送状态
            $shop = D('Shop')->find($v['shop_id']);
            if ($shop['is_goods_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 1))->find();
                if(!empty($DeliveryOrder)){
                    // $this->tuMsg('您开通了配送员配货，无权管理');
                    continue;
                }
            }

            echo "do Eleorder $order_id \n";
            D('Eleorder')->overOrder($order_id);
        }

        // 菜市场 
        $list = D('Marketorder')->where(['status'=>2,'update_time'=>['gt',$time_market]])->select();
        // dump($list);
        foreach ($list as $k => $detail) {
            $order_id = $detail['order_id'];

            // 商家判断
            $shop = D('Shop')->find($detail['shop_id']);
            if($shop['is_market_pei'] == 1){
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type' =>3))->find();
                if(!empty($DeliveryOrder)){
                    // $this->tuError('您开通了配送员配货，无权管理');
                    continue;
                }
            }else{
                D('Marketorder')->overOrder($order_id);
                // $this->tuSuccess('确认完成，资金已经结算到账户', U('marketorder/wait'));
            }
        }

        // 便利店 
        $list = D('Storeorder')->where(['status'=>2,'update_time'=>['gt',$time_market]])->select();
        // dump($list);
        foreach ($list as $k => $detail) {
            $order_id = $detail['order_id'];

            // 商家判断
            $shop = D('Shop')->find($detial['shop_id']);
            if($shop['is_market_pei'] == 1){
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id'=>$order_id,'type' =>3))->find();
                if(!empty($DeliveryOrder)){
                    // $this->tuError('您开通了配送员配货，无权管理');
                    continue;
                }
            }else{
                D('Storeorder')->overOrder($order_id);
                // $this->tuSuccess('确认完成，资金已经结算到账户', U('marketorder/wait'));
            }
        }

        echo "over\n";
    }

    //过期红包退还给商家
    public function Envelope_tui()
    {
        $envelope = D('Envelope')->where(['closed'=>0,'type'=>3])->select();
        foreach ($envelope as $key => $value) {
            $lasttime = strtotime($value['end_time']);
            $userss = D('Shop')->find($value['shop_id']);
            if(NOW_TIME > $lasttime){
                D('Envelope')->where(['envelope_id'=>$value['envelope_id']])->save(['closed'=>1]);
                D('Users')->addMoney($userss['user_id'], $value['prestore'] ,'红包过期退款，退还红包金额'.$value['prestore']);
            }
            $users = D('UserEnvelope')->where(['type'=>2,'close'=>1,'envelope_id'=>$value['envelope_id']])->select();
            $money =0;
            foreach ($users as $k => $val) {
                if(NOW_TIME > $lasttime){
                    $money += $val['envelope'];
                    D('UserEnvelope')->where(['user_id'=>$val['user_id']])->save(['close'=>2]);
                }
            }
            D('Users')->addMoney($userss['user_id'], $money ,'用户领取红包过期退款，退还红包金额'.$money);
        }

        $bylog=D('BuyenvelopesLogs')->where(['is_show'=>0])->select();
        foreach ($bylog as $v){
            $id=$v['eid'];
            $end=$v['end_time'];
            if(NOW_TIME>$end){
                D('BuyenvelopesLogs')->where(['eid'=>$id])->save(['is_show'=>1]);
            }
        }


    }

    //当天第一单的管理费
    public function Insurance()
    {
        $where =" id >0";
        D('Delivery')->where($where)->save(['is_insurance'=>0]);
    }

    //通过已支付订单代理分成
    public function agentRebate()
    {
        $shopList = M('shop')->where(['audit' => 1, 'shop_id' => 104])->field('shop_id,cate_id')->select();
        foreach ($shopList as $shop) {
            $order = getShopOrderTable($shop['cate_id']);
            $table_name = $order->getTableName();
            $orderList = getShopOrderData($shop['cate_id'], $shop['shop_id']);
            foreach ($orderList as $order_data) {
                if (!empty($order_data['order_id']) && !empty($order_data['user_id']) && !empty($order_data['money'])) {
                    $total_price = D('Userprofitlogs')->is_fenxiao($order_data['order_id'], $order_data['user_id'], $order_data['money'], $table_name);
                    if ($total_price > 0) {
                        $order->where('order_id=' . $order_data['order_id'])->save(array('is_profit' => 1));
                    } else {
                        echo '返佣失败订单号' . $order_data['order_id'];
                        die;
                    }
                }
            }
        }
    }

    //当天领取的超过24小时作废，包括已使用未使用的平台红包
    public function todayRedPacketOver()
    {
        $config = D('Setting')->where(array('k' => 'platform'))->find();
        $config = unserialize($config['v']);

        $where= array('shop_id'=>0,'close'=>2,'is_use'=>0);
        $user_envelope = M('user_envelope')->where($where)->select();
        foreach ($user_envelope as $envelope) {
            M('user_envelope')->where(['user_envelope_id' => $envelope['user_envelope_id']])->save(['close'=>1,'is_use'=>1]);
        }

        $hongbao=D('Envelope')->where(['type'=>1,'shop_id'=>0])->find();
        if(!empty($hongbao)){
            D('Envelope')->where(['type'=>1,'shop_id'=>0])->delete();
        }
        $data=array(
            'type'=>1,
            'is_shop'=>0,
            'shop_id'=>0,
            'single'=>round($this->randFloat(0.1,$config['single']),2 ),
            'title'=>$config['title'],
            'intro'=>$config['intro'],
            'status'=>1,
            'bg_date'=>date('Y-m-d H:i:s'),
            'create_time'=>time(),
            'num'=>rand(1,$config['prestore'])
        );
        D('Envelope')->add($data);
    }
    function randFloat($min=0, $max=1){
        return $min + mt_rand()/mt_getrandmax() * ($max-$min);
    }
    //收取每天商家在预约平台的费用
    public function DayAppointment()
    {
        $shopList = M('shop')->where(['audit' => 1, 'is_yue' => 1])->select();
        $config = D('Setting')->where(array('k' => 'shop'))->find();
        $config = unserialize($config['v']);
        $money = $config['shop_money'];
        foreach ($shopList as $user) {
            D('Users')->addGold($user['user_id'], -$money, '每日预约占位费');
        }

    }

    //点赞免费购商品，转发有效时间
    public function EffectiveShareTime(){
        $time=time();//获取当前时间

        $share=D('Zeroelementforward')->where(['colse'=>0])->select();
        foreach ($share as $val){
            $times['end_time']=$val['end_time'];
            $tuan_id[]=$val['tuan_id'];
            //如果当前时间大于结束时间，就将状态改为1
            if($time>=$times['end_time']){
                D('Zeroelementforward')->where(array('tuan_id'=>array('IN',$tuan_id)))->save(['colse'=>1]);
            }
        }
    }


    //算出当月奖金
    public function monthBonus()
    {
        $config = D('Setting')->where(array('k' => 'ele'))->find();
        $config = unserialize($config['v']);
        $shopList = M('shop')->where(['audit' => 1])->select();
        $months = date('Y-m', strtotime('-1 month'));
        $start_time = date('Y-m-01', strtotime('-1 month'));
        $end_time = date('Y-m-t', strtotime('-1 month'));
        $map['orders_time'] = array('between', array(strtotime($start_time), strtotime($end_time . ' 23:59:59')));


        foreach ($shopList as $shop) {
            $map['shop_id'] = $shop['shop_id'];
            $orderList = M('ele_order')->where($map)->select();
            $ViolationsCount = 0;
            $total_amount = M('ele_order')->where($map)->sum('total_price');
            foreach ($orderList as $order) {
                $diff_time = $order['orders_time'] - $order['shop_time'];
                if ($diff_time > 1200 && $diff_time < 2400) {
                    $ViolationsCount++;
                } elseif ($diff_time > 2400) {
                    $ViolationsCount += 3;
                }
            }
            if ($ViolationsCount == 0) {
                $rate = $config['no_violation'];
                $reward_amount = ($total_amount * $rate);
            } elseif ($ViolationsCount == 1) {
                $rate = $config['one_violation'];
                $reward_amount = ($total_amount * $rate);
            } elseif ($ViolationsCount == 2) {
                $rate = $config['two_violation'];
                $reward_amount = ($total_amount * $rate);
            } elseif ($ViolationsCount == 3) {
                $rate = $config['three_violation'];
                $reward_amount = ($total_amount * $rate);
            } elseif ($ViolationsCount > 3) {
                $reward_amount = 0;
            }

            $data = array(
                'shop_id' => $shop['shop_id'],
                'violations_count' => $ViolationsCount,
                'months' => $months,
                'rate' => $rate,
                'reward_amount' => $reward_amount,
                'months_amount' => $total_amount,
                'create_time' => date('Y-m-d H:i:s', time()),
            );
            $violations = M('violations')->where(array('shop_id' => $shop['shop_id'], 'months' => $months))->find();
            if ($violations == null) {
                M('violations')->add($data);
            }

        }
    }

    //外卖自动派单
    public function send_order()
    {

        $where['delivery_id'] = 0;
        $where['type'] = 1;//外卖
        $order_list = M('delivery_order')->where($where)->order('create_time', 'asc')->select();
        foreach ($order_list as $order) {
            if (time() - $order['create_time'] > 180) {
                $shopInfo = M('ele')->where(['shop_id' => $order['shop_id']])->field('lng,lat')->find();
                $shop_lng = $shopInfo['lng'];//经度
                $shop_lat = $shopInfo['lat'];//纬度
                $applicationmanagement = M('applicationmanagement')->where(['dj_id' => 9])->select();
                $sort_array = array();
                foreach ($applicationmanagement as $address) {
                    $Distance = getDistanceNone($shop_lng, $shop_lat, $address['lng'], $address['lat']);
                    $address2['distance'] = $Distance;
                    $address2['user_id'] = $address['user_id'];
                    $sort_array[] = $address2;
                }
                array_multisort(array_column($sort_array, 'distance'), SORT_ASC, $sort_array);
                $user_id = $sort_array[0]['user_id'];
                $deliveryList = M('delivery')->where(['recommend' => $user_id, 'is_open' => 1])->select();
                foreach ($deliveryList as $delivery) {
                    $res = M('delivery_order')->where(['delivery_id' => $delivery['id'], 'status' => 2])->select();
                    if (count($res) == 0) {
                        M('delivery_order')
                            ->where(array('order_id' => $order['order_id']))
                            ->save(['delivery_id' => $delivery['id'], 'update_time' => time(), 'status' => 2]);
                        break;
                    }
                }
            }
        }


        $where['delivery_id'] = 0;
        $where['type'] = 3;//菜市场
        $order_list = M('delivery_order')->where($where)->order('create_time', 'asc')->select();
        foreach ($order_list as $order) {
            if (time() - $order['create_time'] > 180) {
                $shopInfo = M('market')->where(['shop_id' => $order['shop_id']])->field('lng,lat')->find();
                $shop_lng = $shopInfo['lng'];//经度
                $shop_lat = $shopInfo['lat'];//纬度
                $applicationmanagement = M('applicationmanagement')->where(['dj_id' => 9])->select();
                $sort_array = array();
                foreach ($applicationmanagement as $address) {
                    $Distance = getDistanceNone($shop_lng, $shop_lat, $address['lng'], $address['lat']);
                    $address2['distance'] = $Distance;
                    $address2['user_id'] = $address['user_id'];
                    $sort_array[] = $address2;
                }
                array_multisort(array_column($sort_array, 'distance'), SORT_ASC, $sort_array);
                $user_id = $sort_array[0]['user_id'];
                $deliveryList = M('delivery')->where(['recommend' => $user_id, 'is_open' => 1])->select();
                foreach ($deliveryList as $delivery) {
                    $res = M('delivery_order')->where(['delivery_id' => $delivery['id'], 'status' => 2])->select();
                    if (count($res) == 0) {
                        M('delivery_order')
                            ->where(array('order_id' => $order['order_id']))
                            ->save(['delivery_id' => $delivery['id'], 'update_time' => time(), 'status' => 2]);
                        break;
                    }
                }
            }
        }



        $where['delivery_id'] = 0;
        $where['type'] = 4;//便利店
        $order_list = M('delivery_order')->where($where)->order('create_time', 'asc')->select();
        foreach ($order_list as $order) {
            if (time() - $order['create_time'] > 180) {
                $shopInfo = M('store')->where(['shop_id' => $order['shop_id']])->field('lng,lat')->find();
                $shop_lng = $shopInfo['lng'];//经度
                $shop_lat = $shopInfo['lat'];//纬度
                $applicationmanagement = M('applicationmanagement')->where(['dj_id' => 9])->select();
                $sort_array = array();
                foreach ($applicationmanagement as $address) {
                    $Distance = getDistanceNone($shop_lng, $shop_lat, $address['lng'], $address['lat']);
                    $address2['distance'] = $Distance;
                    $address2['user_id'] = $address['user_id'];
                    $sort_array[] = $address2;
                }
                array_multisort(array_column($sort_array, 'distance'), SORT_ASC, $sort_array);
                $user_id = $sort_array[0]['user_id'];
                $deliveryList = M('delivery')->where(['recommend' => $user_id, 'is_open' => 1])->select();
                foreach ($deliveryList as $delivery) {
                    $res = M('delivery_order')->where(['delivery_id' => $delivery['id'], 'status' => 2])->select();
                    if (count($res) == 0) {
                        M('delivery_order')
                            ->where(array('order_id' => $order['order_id']))
                            ->save(['delivery_id' => $delivery['id'], 'update_time' => time(), 'status' => 2]);
                        break;
                    }
                }
            }
        }

    }

    //拼单自动发红包
    public function SpellList(){
        $config = D('Setting')->where(array('k' => 'pin'))->find();
        $config = unserialize($config['v']);

        //外卖
        $ele=D('Paymentlogs')->where(['is_reward'=>0,'type'=>'ele','is_paid'=>1])->select();
        foreach ($ele as $val){
            $is_pin=$val['is_pin'];//发起人
            $order_id=$val['order_id'];//订单号
            $user_id=$val['user_id'];//购买用户
            $times=$val['pay_time'];//支付时间
            $ele_order=D('Eleorder')->where(['order_id'=>$order_id,'status'=>8])->find();//查询订单商家
            $ele_shop=D('Ele')->where(['shop_id'=>$ele_order['shop_id']])->find();//查询外卖商家
            $ele_hongbao=D('Envelope')->where(['shop_id'=>$ele_shop['shop_id'],'type'=>2,'closed'=>0,'status'=>1])->find();//查询外卖商家是否存在订单红包
            $ele_time=$config['ele_time'];
            $minute=$ele_time*60*60*1000;//将小时化毫秒
            $sun_time=$times+$minute;//获取可以发红包的时间，发红包时间=支付时间+发放红包时间
            if(!empty($is_pin) && !empty($ele_hongbao) && $sun_time>time()){
                $ele_money = $config['ele_money'];//分成总金额
                $ele_kou=$config['ele_kou'];//平台扣费
                $ele_num1=$config['ele_num1'];//拼单者
                $ele_num2=$config['ele_num2'];//发布者
                $nedd_pay=$ele_money-$ele_kou;//实际用户分钱
                $one=(100-$ele_num1)/100;
                $shi=$nedd_pay-$one;
                D('Users')->addMoney($user_id,$one, '用户'.$is_pin.'推荐购买商品获得红包'.$one.'元');
                D('Users')->addMoney($is_pin,$shi, '推荐用户'.$user_id.'购买商品获得红包'.$shi.'元');
                $money=$ele_hongbao['prestore']-$ele_money;
                D('Envelope')->where(['envelope_id'=>$ele_hongbao['envelope_id']])->save(['prestore'=>$money]);
                $ele_arr=array(
                    'type'=>3, 'order_id'=>$order_id, 'envelope_id'=>$ele_hongbao['envelope_id'], 'shop_id'=>$ele_hongbao['shop_id'],
                    'user_id'=>$user_id, 'money'=>$ele_money, 'intro'=>'用户'.$is_pin.'推荐'.$user_id.'购买获得订单拼团红包',
                    'create_time'=>NOW_TIME, 'is_use'=>1
                );
                D('EnvelopeLogs')->add($ele_arr);
                D('Paymentlogs')->where(['user_id'=>$user_id,'order_id'=>$order_id])->save(['is_reward'=>1]);
            }
        }

        //便利店
        $store=D('Paymentlogs')->where(['is_reward'=>0,'type'=>'store','is_paid'=>1])->select();
        foreach ($store as $val){
            $is_pin=$val['is_pin'];//发起人
            $order_id=$val['order_id'];//订单号
            $user_id=$val['user_id'];//购买用户
            $times=$val['pay_time'];//支付时间
            $ele_order=D('Storeorder')->where(['order_id'=>$order_id,'status'=>8])->find();//查询订单商家
            $ele_shop=D('Store')->where(['shop_id'=>$ele_order['shop_id']])->find();//查询外卖商家
            $ele_hongbao=D('Envelope')->where(['shop_id'=>$ele_shop['shop_id'],'type'=>2,'closed'=>0,'status'=>1])->find();//查询外卖商家是否存在订单红包
            $ele_time=$config['store_time'];
            $minute=$ele_time*60*60*1000;//将小时化毫秒
            $sun_time=$times+$minute;//获取可以发红包的时间，发红包时间=支付时间+发放红包时间
            if(!empty($is_pin) && !empty($ele_hongbao) && $sun_time>time()){
                $ele_money = $config['store_money'];//分成总金额
                $ele_kou=$config['store_kou'];//平台扣费
                $ele_num1=$config['store_num1'];//拼单者
                $ele_num2=$config['store_num2'];//发布者
                $nedd_pay=$ele_money-$ele_kou;//实际用户分钱
                $one=(100-$ele_num1)/100;
                $shi=$nedd_pay-$one;
                D('Users')->addMoney($user_id,$one, '用户'.$is_pin.'推荐购买商品获得红包'.$one.'元');
                D('Users')->addMoney($is_pin,$shi, '推荐用户'.$user_id.'购买商品获得红包'.$shi.'元');
                $money=$ele_hongbao['prestore']-$ele_money;
                D('Envelope')->where(['envelope_id'=>$ele_hongbao['envelope_id']])->save(['prestore'=>$money]);
                $ele_arr=array(
                    'type'=>3, 'order_id'=>$order_id, 'envelope_id'=>$ele_hongbao['envelope_id'], 'shop_id'=>$ele_hongbao['shop_id'],
                    'user_id'=>$user_id, 'money'=>$ele_money, 'intro'=>'用户'.$is_pin.'推荐'.$user_id.'购买获得订单拼团红包',
                    'create_time'=>NOW_TIME, 'is_use'=>1
                );
                D('EnvelopeLogs')->add($ele_arr);
                D('Paymentlogs')->where(['user_id'=>$user_id,'order_id'=>$order_id])->save(['is_reward'=>1]);
            }
        }

        //菜市场
        $market=D('Paymentlogs')->where(['is_reward'=>0,'type'=>'market','is_paid'=>1])->select();
        foreach ($market as $val){
            $is_pin=$val['is_pin'];//发起人
            $order_id=$val['order_id'];//订单号
            $user_id=$val['user_id'];//购买用户
            $times=$val['pay_time'];//支付时间
            $ele_order=D('Marketorder')->where(['order_id'=>$order_id,'status'=>8])->find();//查询订单商家
            $ele_shop=D('Market')->where(['shop_id'=>$ele_order['shop_id']])->find();//查询外卖商家
            $ele_hongbao=D('Envelope')->where(['shop_id'=>$ele_shop['shop_id'],'type'=>2,'closed'=>0,'status'=>1])->find();//查询外卖商家是否存在订单红包
            $ele_time=$config['market_time'];
            $minute=$ele_time*60*60*1000;//将小时化毫秒
            $sun_time=$times+$minute;//获取可以发红包的时间，发红包时间=支付时间+发放红包时间
            if(!empty($is_pin) && !empty($ele_hongbao) && $sun_time>time()){
                $ele_money = $config['market_money'];//分成总金额
                $ele_kou=$config['market_kou'];//平台扣费
                $ele_num1=$config['market_num1'];//拼单者
                $ele_num2=$config['market_num2'];//发布者
                $nedd_pay=$ele_money-$ele_kou;//实际用户分钱
                $one=(100-$ele_num1)/100;
                $shi=$nedd_pay-$one;
                D('Users')->addMoney($user_id,$one, '用户'.$is_pin.'推荐购买商品获得红包'.$one.'元');
                D('Users')->addMoney($is_pin,$shi, '推荐用户'.$user_id.'购买商品获得红包'.$shi.'元');
                $money=$ele_hongbao['prestore']-$ele_money;
                D('Envelope')->where(['envelope_id'=>$ele_hongbao['envelope_id']])->save(['prestore'=>$money]);
                $ele_arr=array(
                    'type'=>3, 'order_id'=>$order_id, 'envelope_id'=>$ele_hongbao['envelope_id'], 'shop_id'=>$ele_hongbao['shop_id'],
                    'user_id'=>$user_id, 'money'=>$ele_money, 'intro'=>'用户'.$is_pin.'推荐'.$user_id.'购买获得订单拼团红包',
                    'create_time'=>NOW_TIME, 'is_use'=>1
                );
                D('EnvelopeLogs')->add($ele_arr);
                D('Paymentlogs')->where(['user_id'=>$user_id,'order_id'=>$order_id])->save(['is_reward'=>1]);
            }
        }

        //商城
        $goods=D('Paymentlogs')->where(['is_reward'=>0,'type'=>'goods','is_paid'=>1])->select();
        foreach ($goods as $val){
            $is_pin=$val['is_pin'];//发起人
            $order_id=$val['order_id'];//订单号
            $user_id=$val['user_id'];//购买用户
            $times=$val['pay_time'];//支付时间
            $ele_order=D('Order')->where(['order_id'=>$order_id,'status'=>8])->find();//查询订单商家
            $ele_shop=D('Shop')->where(['shop_id'=>$ele_order['shop_id']])->find();//查询外卖商家
            $ele_hongbao=D('Envelope')->where(['shop_id'=>$ele_shop['shop_id'],'type'=>2,'closed'=>0,'status'=>1])->find();//查询外卖商家是否存在订单红包
            $ele_time=$config['mall_time'];
            $minute=$ele_time*60*60*1000;//将小时化毫秒
            $sun_time=$times+$minute;//获取可以发红包的时间，发红包时间=支付时间+发放红包时间
            if(!empty($is_pin) && !empty($ele_hongbao) && $sun_time>time()){
                $ele_money = $config['mall_money'];//分成总金额
                $ele_kou=$config['mall_kou'];//平台扣费
                $ele_num1=$config['mall_num1'];//拼单者
                $ele_num2=$config['mall_num2'];//发布者
                $nedd_pay=$ele_money-$ele_kou;//实际用户分钱
                $one=(100-$ele_num1)/100;
                $shi=$nedd_pay-$one;
                D('Users')->addMoney($user_id,$one, '用户'.$is_pin.'推荐购买商品获得红包'.$one.'元');
                D('Users')->addMoney($is_pin,$shi, '推荐用户'.$user_id.'购买商品获得红包'.$shi.'元');
                $money=$ele_hongbao['prestore']-$ele_money;
                D('Envelope')->where(['envelope_id'=>$ele_hongbao['envelope_id']])->save(['prestore'=>$money]);
                $ele_arr=array(
                    'type'=>3, 'order_id'=>$order_id, 'envelope_id'=>$ele_hongbao['envelope_id'], 'shop_id'=>$ele_hongbao['shop_id'],
                    'user_id'=>$user_id, 'money'=>$ele_money, 'intro'=>'用户'.$is_pin.'推荐'.$user_id.'购买获得订单拼团红包',
                    'create_time'=>NOW_TIME, 'is_use'=>1
                );
                D('EnvelopeLogs')->add($ele_arr);
                D('Paymentlogs')->where(['user_id'=>$user_id,'order_id'=>$order_id])->save(['is_reward'=>1]);
            }
        }

    }

    //清除积分
    public function EliminateIntegral(){
        //查询用户表
        $user=D('Users')->where(['closed'=>0])->select();
        foreach ($user as $val){
            $user_id=$val['user_id'];
            $integral=$val['integral'];
            if($integral>0){
                D('Users')->where(['user_id'=>$user_id])->save(['integral'=>0]);
            }
        }
    }

    //配送员奖励
    public function DistributorReward(){
        //查询配送订单是否大于奖励订单
        $config = D('Setting')->where(array('k' => 'delivery'))->find();
        $config = unserialize($config['v']);
        //查询第一阶段订单量
        $d1num=$config['one_num'];
        $d1money1=$config['one_money1'];//一公里以内
        $d1money2=$config['one_money2'];//一公里以外
        //查询第二阶段订单量
        $d2num=$config['two_num'];
        $d2money1=$config['two_money1'];//一公里以内
        $d2money2=$config['two_money2'];//一公里以外
        //查询第三阶段订单量
        $d3num=$config['three_num'];
        $d3money1=$config['three_money1'];//一公里以内
        $d3money2=$config['three_money2'];//一公里以外
        //查询第四阶段订单量
        $d4num=$config['four_num'];
        $d4money1=$config['four_money1'];//一公里以内
        $d4money2=$config['four_money2'];//一公里以外

        //查询配送员
        $pei=M('delivery')->where(['closed'=>0,'audit'=>1])->select();
        foreach ($pei as $val){
            $id=$val['id'];
            $user_id=$val['user_id'];
            //查询配送订单
            $times=date('Y-m-d',time());
            $maps['shijian']=array('EQ',$times);
            $peicount=M('delivery_order')->where($maps)->where(['status'=>8,'delivery_id'=>$id])->count();
            //判断订单是否大于奖励最低奖励订单量
            if($peicount>=$d1num && $peicount<$d2num){
                //查询订单距离小于一公里的
                $where['distance']=array('lt','1');
                $peicount1=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($where)->count();
                if($peicount1>0){
                    $pay=$peicount1*$d1money1;
                    if($pay>0){
                        $needpay1=$pay;
                    }else{
                        $needpay1=0;
                    }
                }
                //查询订单距离大于一公里的
                $map['distance']=array('GT','1');
                $peicount2=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($map)->count();
                if($peicount2>0){
                    $pay2=$peicount2*$d1money2;
                    if($pay2>0){
                        $needpay2=$pay2;
                    }else{
                        $needpay2=0;
                    }
                }
                //计算奖励配送员的钱
                $sum=$needpay1+$needpay2;
                $info='恭喜您今日跑单：'.$peicount.'单获得平台奖励'.$sum.'元，继续加油哦！';
                D('Users')->addMoney($user_id,$sum,$info);
                $attr=array(
                    'user_id'=>$user_id,
                    'money'=>$sum,
                    'info'=>$info,
                    'create_time'=>NOW_TIME
                );
                M('deliverylog')->add($attr);
            //第二阶段
            }elseif($peicount>=$d2num && $peicount<$d3num){
                //查询订单距离小于一公里的
                $where['distance']=array('lt','1');
                $peicount1=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($where)->count();
                if($peicount1>0){
                    $pay=$peicount1*$d2money1;
                    if($pay>0){
                        $needpay1=$pay;
                    }else{
                        $needpay1=0;
                    }
                }
                //查询订单距离大于一公里的
                $map['distance']=array('GT','1');
                $peicount2=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($map)->count();
                if($peicount2>0){
                    $pay2=$peicount2*$d2money2;
                    if($pay2>0){
                        $needpay2=$pay2;
                    }else{
                        $needpay2=0;
                    }
                }
                //计算奖励配送员的钱
                $sum=$needpay1+$needpay2;
                $info='恭喜您今日跑单：'.$peicount.'单获得平台奖励'.$sum.'元，继续加油哦！';
                D('Users')->addMoney($user_id,$sum,$info);
                $attr=array(
                    'user_id'=>$user_id,
                    'money'=>$sum,
                    'info'=>$info,
                    'create_time'=>NOW_TIME
                );
                M('deliverylog')->add($attr);
            //第三阶段
            }elseif($peicount>=$d3num && $peicount<$d4num){
                //查询订单距离小于一公里的
                $where['distance']=array('lt','1');
                $peicount1=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($where)->count();
                if($peicount1>0){
                    $pay=$peicount1*$d3money1;
                    if($pay>0){
                        $needpay1=$pay;
                    }else{
                        $needpay1=0;
                    }
                }
                //查询订单距离大于一公里的
                $map['distance']=array('GT','1');
                $peicount2=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($map)->count();
                if($peicount2>0){
                    $pay2=$peicount2*$d3money2;
                    if($pay2>0){
                        $needpay2=$pay2;
                    }else{
                        $needpay2=0;
                    }
                }
                //计算奖励配送员的钱
                $sum=$needpay1+$needpay2;
                $info='恭喜您今日跑单：'.$peicount.'单获得平台奖励'.$sum.'元，继续加油哦！';
                D('Users')->addMoney($user_id,$sum,$info);
                $attr=array(
                    'user_id'=>$user_id,
                    'money'=>$sum,
                    'info'=>$info,
                    'create_time'=>NOW_TIME
                );
                M('deliverylog')->add($attr);
            //查询第四阶段的
            }elseif($peicount>=$d4num){
                //查询订单距离小于一公里的
                $where['distance']=array('lt','1');
                $peicount1=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($where)->count();
                if($peicount1>0){
                    $pay=$peicount1*$d4money1;
                    if($pay>0){
                        $needpay1=$pay;
                    }else{
                        $needpay1=0;
                    }
                }
                //查询订单距离大于一公里的
                $map['distance']=array('GT','1');
                $peicount2=M('delivery_order')->where(['status'=>8,'delivery_id'=>$id])->where($map)->count();
                if($peicount2>0){
                    $pay2=$peicount2*$d4money2;
                    if($pay2>0){
                        $needpay2=$pay2;
                    }else{
                        $needpay2=0;
                    }
                }
                //计算奖励配送员的钱
                $sum=$needpay1+$needpay2;
                $info='恭喜您今日跑单：'.$peicount.'单获得平台奖励'.$sum.'元，继续加油哦！';
                D('Users')->addMoney($user_id,$sum,$info);
                $attr=array(
                    'user_id'=>$user_id,
                    'money'=>$sum,
                    'info'=>$info,
                    'create_time'=>NOW_TIME
                );
                M('deliverylog')->add($attr);
            }

        }

    }

    //会员结束查询
    public function EndVip(){
        //查询所有会员
        $vip=D('BuyVip')->where(['colsed'=>0])->select();
        $time=date('Y-m-d',time());
        //遍历所有到期会员将其踢出
        foreach ($vip as $val){
            $times=$val['end_time'];
            $ids=$val['id'];
            $userid=$val['user_id'];
            //判断当前时间是否大于
            if($time>$times){
                D('BuyVip')->where(['id'=>$ids])->save(['colsed'=>1]);
                D('Users')->where(['user_id'=>$userid])->save(['rank_id'=>0]);
            }
        }
    }

    //司机结束时间
    public function EndDriver(){
        //查询所有司机
        $driver=D('UsersPinche')->where(['closed'=>0])->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['user_id'];
            if($time>$times){
                D('UsersPinche')->where(['user_id'=>$user_id])->save(['closed'=>1]);
            }
        }
    }

    //配送管理员结束时间
    public function ManagerDriver(){
        //查询所有配送员
        $map['end_time'] = array('neq','NULL');
        $driver=D('Applicationmanagement')->where(['sq_delete'=>0,'sq_state'=>1])->where($map)->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['sq_id'];
            if($time>$times){
                D('Applicationmanagement')->where(['sq_id'=>$user_id])->save(['sq_delete'=>1]);
            }
        }
    }


    //代理结束时间
    public function AgentEnd(){
        //查询所有司机
        $map['end_time'] = array('neq','NULL');
        $driver=D('UsersAgentApply')->where(['closed'=>0,'audit'=>1])->where($map)->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['apply_id'];
            if($time>$times){
                D('UsersAgentApply')->where(['apply_id'=>$user_id])->save(['closed'=>1]);
            }
        }
    }

    //卖车公司认证
    public function VehicleEnd(){
        //查询所有卖车公司
        $map['end_time'] = array('neq','NULL');
        $driver=M('lifes_vehicle')->where(['close'=>0,'examine'=>1])->where($map)->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['crz_id'];
            if($time>$times){
                M('lifes_vehicle')->where(['crz_id'=>$user_id])->save(['close'=>1]);
            }
        }
    }

    //卖房公司认证
    public function RoomEnd(){
        //查询所有卖房公司
        $map['end_time'] = array('neq','NULL');
        $driver=D('Lifesauthentication')->where(['close'=>0,'examine'=>1])->where($map)->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['rz_id'];
            if($time>$times){
                D('Lifesauthentication')->where(['rz_id'=>$user_id])->save(['close'=>1]);
            }
        }
    }

    //生活信息公司认证
    public function LifeEnd(){
        //查询所有生活信息公司
        $map['end_time'] = array('neq','NULL');
        $driver=M('LifeAudit')->where(['closed'=>0,'audit'=>1])->where($map)->select();
        $time=date('Y-m-d',time());
        foreach ($driver as $val){
            $times=$val['end_time'];
            $user_id=$val['user_id'];
            if($time>$times){
                M('LifeAudit')->where(['user_id'=>$user_id])->save(['closed'=>1]);
            }
        }
    }

    //送单奖励
    public function DistributionAward(){

        $config = D('Setting')->where(array('k' => 'backers'))->find();
        $config = unserialize($config['v']);
        $peinumber=$config['pei_number'];
        $peimoney=$config['pei_money'];
        //查询用户
        $pei=D('Delivery')->where(['closed'=>0,'audit'=>1])->select();
        foreach ($pei as $val){
            //查询上级
            $user=$val['user_id'];
            $id=$val['id'];
            $shangji=$val['recommend'];
            //查找是否是已经奖励过了
            $users=D('Users')->where(['closed'=>0,'user_id'=>$shangji])->find();
            //查询订单
            $peicount=D('DeliveryOrder')->where(['delivery_id'=>$id,'status'=>8])->count();
            if($users['is_pei']!=1 && $peicount>=$peinumber && !empty($users)){
                    D('Users')->addMoney($users['user_id'],$peimoney,'推荐会员'.$user.'注册配送员跑单'.$peicount.'单，奖励：'.$peimoney.'元');
                    D('Users')->where(['user_id'=>$users['user_id']])->save(['is_pei'=>1]);
            }

        }
        $sijinumber=$config['siji_number'];
        $sijimoney=$config['siji_money'];

        //查询司机
        $siji=M('users_pinche')->where(['closed'=>0,'audit'=>1])->select();
        foreach ($siji as $va){
            $us=$va['user_id'];
            $recommend=$va['recommend'];
            //查找是否是已经奖励过了
            $use=D('Users')->where(['closed'=>0,'user_id'=>$recommend])->find();
            //查询订单
            $peicount=D('Runningvehicle')->where(['cid'=>$us,'status'=>8])->count();
            if($use['is_siji']!=1 && $sijinumber>=$peinumber && !empty($use)){
                D('Users')->addMoney($use['user_id'],$sijimoney,'推荐会员'.$us.'注册司机跑单'.$peicount.'单，奖励：'.$sijimoney.'元');
                D('Users')->where(['user_id'=>$use['user_id']])->save(['is_siji'=>1]);
            }

        }

    }

    //清除活动
    public function Eliminate(){
        $obj=D('FabulousEleStoreMarkert');
        $ordinary=$obj->where(['type'=>4,'is_pingtai'=>1])->select();
        $t=time();
        foreach ($ordinary as $val){
            $id=$val['id'];
            $createtime=$val['create_time'];
            $sumtime=$createtime+86400;
            if($t>$sumtime){
                D('FabulousEleStoreMarkert')->where(['id'=>$id])->delete();
            }
        }

        $ordinary2=$obj->where(array('type'=>array('in','1,2,3')))->select();
        foreach ($ordinary2 as $vals){
            $ids=$vals['id'];
            $createti=$vals['create_time'];
            $sumti=$createti+172800;
            if($t>$sumti){
                D('FabulousEleStoreMarkert')->where(['id'=>$ids])->delete();
            }
        }
    }





}