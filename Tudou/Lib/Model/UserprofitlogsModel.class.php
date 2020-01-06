
<?php
class UserprofitlogsModel extends CommonModel{
    protected $pk = 'log_id';
    protected $tableName = 'user_profit_logs';
	
	protected $Type = array(
        'goods' => '商城',
		'appoint' => '家政',
		'tuan' => '抢购',
		'ele' => '外卖',
		'booking'  => '订座',
		'breaks'=>'优惠买单',
		'hotel' =>'酒店',
		'farm'=>'农家乐', 
		'rank'=>'会员购买等级', 
		'grade'=>'商家购买等级', 
		'market' => '菜市场',
		'store' => '便利店',
    );
	
	protected $separate = array(
        1 => '已分成',
        2 => '已取消',
    );

    public function getType(){
        return $this->Type;
    }

    public function getSeparate(){
        return $this->separate;
    }
	
	//反转数组
	public function get_money_type($type){
		$types = $this->getType();
		$result = array_flip($types);//反转数组
		$types = array_search($type, $result);
		if(!empty($types)){
			return $types;
		}else{
			return false;
		}
        return false;
	}
	
	
	protected $_type = array(
		'tuan' => '抢购', 
		'ele' => '外卖', 
		'farm' => '农家乐', 
		'goods' => '商城', 
		'booking' => '订座', 
		'hotel' => '酒店',
		'appoint' => '家政',
		'breaks' => '优惠买单',
		'market' => '菜市场',
		'store' => '便利店',
	);
	
	protected $_profit_price_type = array(
		'1' => '用户实付金额', 
		'2' => '商家结算金额', 
		'3' => '用户实付金额-商家结算金额=差价', 
	);
	
	
	//分销判断权限
	public function determinePower($uid){
		$config = D('Setting')->fetchAll();
		$Users = D('Users')->find($uid);
		
		if($config['profit']['profit_min_rank_id'] == 0){
			return true;
		}
		
		$rank = D('Userrank')->find($config['profit']['profit_min_rank_id']);//后台分销配置
		$userRank = D('Userrank')->find($Users['rank_id']);//会员的分销配置
 		
        if($rank){
            if($userRank && $userRank['integral'] >= $rank['integral']){
                return true;
            }else{
               return false;
            }
			return false;
        }
        return true;
    }




    //返佣日志
    public static function rebate_log($log = array())
    {
        $data['order_id'] = isset($log['order_id']) ? $log['order_id'] : 0;
        $data['user_id'] = isset($log['user_id']) ? $log['user_id'] : 0;
        $data['total_money'] = isset($log['total_money']) ? $log['total_money'] : 0;
        $data['rate'] = isset($log['rate']) ? $log['rate'] : 0;
        $data['user_rate'] = isset($log['user_rate']) ? $log['user_rate'] : 0;
        $data['user_money'] = isset($log['user_money']) ? $log['user_money'] : 0;
        $data['type'] = isset($log['type']) ? $log['type'] : 0;
        $data['remark'] = isset($log['remark']) ? $log['remark'] : 0;
        $data['create_time'] = date('Y-m-d H:i:s', time());
        M('rebate_log')->add($data);
    }

    //分销2
    public function is_fenxiao($order_id,$user_id, $money, $type)
    {
        file_put_contents('fenxiao.log', "");

        //获取商品分销总比例
        $config = D('Setting')->fetchAll();
        $sum = $config['profit']['shop_goods'];

        //获取发放分销资金的时间
        $capital = $config['profit']['prescription'];

        //将当前时间往后推迟天数
        $bgn = date('Y-m-d H:i:s', strtotime('+' . $capital . 'day'));
        $end = strtotime($bgn);

        //获取用户订单金额
        $obj = D('Users');
        $user = $obj->find($user_id);
        $money = $money ;
        $platform_rate =$sum;
        //结算金额大于0
        if ($money >= 0.01) {
            $UsersAgentApply = D('UsersAgentApply')->where(array('user_id' => $user['fuid1']))->find();
            $tui = D('Users')->where(array('user_id' => $user['fuid1']))->find();
            $one3 = D('Userrank')->where(array('rank_id' => $tui['rank_id']))->find();

            $city_agents = M('city_agents')->where(array('agent_id' => $UsersAgentApply['agent_id']))->find();
            //判断是否有退款或到时间发放
            $is_end = $this->is_refund($order_id);
            //判断是不是推手
            if (!empty($tui['is_backers']) && $tui['is_backers'] == 2) {
                if ($is_end == true) {
                    $platform_rate -= $one3['reward'];
                    $money1 = round(($one3['reward']/100) * $money,2);
                    $info1 = '获得推手分成金额' . $money1 . '元，推手分成【' . $one3['reward'].'】%';

                    $log['order_id'] = $order_id;
                    $log['user_id'] = $UsersAgentApply['user_id'];
                    $log['total_money'] = $money;
                    $log['rate'] = $sum;
                    $log['user_rate'] = $one3['reward'];
                    $log['user_money'] = $money1;
                    $log['type'] = $type;
                    $log['remark'] = $info1;
                    self::rebate_log($log);
                    D('Users')->addMoney($UsersAgentApply['user_id'], $money1, $info1);
                }
                //判断是不是代理直推
            } elseif (!empty($UsersAgentApply['user_id'])) {
                if ($is_end == true) {
                    $platform_rate -= $city_agents['rate'];
                    $money1 = round(($city_agents['rate']/100) * $money,2);
                    $info1 = '获得代理分成金额' . $money1 . '元，代理分成【 ' . $city_agents['rate'].'】%';

                    $log['order_id'] = $order_id;
                    $log['user_id'] = $UsersAgentApply['user_id'];
                    $log['total_money'] = $money;
                    $log['rate'] = $sum;
                    $log['user_rate'] = $city_agents['rate'];
                    $log['user_money'] = $money1;
                    $log['type'] = $type;
                    $log['remark'] = $info1;
                    self::rebate_log($log);
                    D('Users')->addMoney($UsersAgentApply['user_id'], $money1, $info1);
                }
            }


            //判断有上级==城市合伙人
            $RebateData1 = D('UsersAgentApply')->RebateData($UsersAgentApply['user_id']);
            //判断推手和代理有没有上级
            if (!empty($RebateData1['parent_id'])) {

                //判断是否有退款或到时间发放
                $is_end = $this->is_refund($order_id);
                if ($is_end == true) {
                    $platform_rate -= $RebateData1['rate'];
                    $money2 = round(($RebateData1['rate']/100) * $money,2);
                    $info1 = '获得城市合伙人分成金额' . $money2 . '元，分成比例【' . $RebateData1['rate'] . '】%';

                    $log['order_id'] = $order_id;
                    $log['user_id'] = $RebateData1['user_id'];
                    $log['total_money'] = $money;
                    $log['rate'] = $sum;
                    $log['user_rate'] = $RebateData1['rate'];
                    $log['user_money'] = $money2;
                    $log['type'] = $type;
                    $log['remark'] = $info1;
                    self::rebate_log($log);
                    D('Users')->addMoney($RebateData1['user_id'], $money2, $info1);
                }

                //判断有上级==县级
                $RebateData2 = D('UsersAgentApply')->RebateData($RebateData1['parent_id']);
                if (!empty($RebateData2['parent_id']) && $RebateData1['rate_level'] < $RebateData2['rate_level']) {
                    //判断是否有退款或到时间发放
                    $is_end = $this->is_refund($order_id);
                    if ($is_end == true) {
                        $platform_rate -= $RebateData2['rate'];
                        $money3 = round(($RebateData2['rate']/100) * $money,2);
                        $info1 = '获得县级分成金额' . $money3 . '元，分成比例【' . $RebateData2['rate'] . '】%';

                        $log['order_id'] = $order_id;
                        $log['user_id'] = $RebateData2['user_id'];
                        $log['total_money'] = $money;
                        $log['rate'] = $sum;
                        $log['user_rate'] = $RebateData2['rate'];
                        $log['user_money'] = $money3;
                        $log['type'] = $type;
                        $log['remark'] = $info1;
                        self::rebate_log($log);
                        D('Users')->addMoney($RebateData2['user_id'], $money3, $info1);
                    }

                    //判断有上一级==市级
                    $RebateData3 = D('UsersAgentApply')->RebateData($RebateData2['parent_id']);
                    if (!empty($RebateData3) && $RebateData2['rate_level'] < $RebateData3['rate_level']) {
                        //判断是否有退款或到时间发放
                        $is_end = $this->is_refund($order_id);
                        if ($is_end == true) {
                            $platform_rate -= $RebateData3['rate'];
                            $money4 = round(($RebateData3['rate']/100) * $money,2);
                            $info1 = '获得市级分成金额' . $money4 . '元，分成比例【' . $RebateData3['rate'] . '】%';

                            $log['order_id'] = $order_id;
                            $log['user_id'] = $RebateData3['user_id'];
                            $log['total_money'] = $money;
                            $log['rate'] = $sum;
                            $log['user_rate'] = $RebateData3['rate'];
                            $log['user_money'] = $money4;
                            $log['type'] = $type;
                            $log['remark'] = $info1;
                            self::rebate_log($log);
                            D('Users')->addMoney($RebateData3['user_id'], $money4, $info1);
                        }
                    }
                }
            }
            $money5 = ($platform_rate/100) * $money;
            $info1 = '平台获得分成金额' . $money5 . '元,分成比例【' . $platform_rate.'】%';
            $log['order_id'] = $order_id;
            $log['total_money'] = $money;
            $log['rate'] = $sum;
            $log['user_id'] = 0;
            $log['user_rate'] = $platform_rate;
            $log['user_money'] = $money5;
            $log['type'] = $type;
            $log['remark'] = $info1;
            self::rebate_log($log);


            $total_price = $money1 + $money2 + $money3 + $money4 + $money5;
            $info1 = '返回分销金额' . round($total_price, 2) . '元,分成比例【' . $sum.'】%';
            $log['order_id'] = $order_id;
            $log['total_money'] = $money;
            $log['rate'] = $sum;
            $log['user_id'] = 0;
            $log['user_rate'] = $sum;
            $log['user_money'] = $total_price;
            $log['type'] = $type;
            $log['remark'] = $info1;
            self::rebate_log($log);

            return $total_price;//返回分销金额
        }

    }


    //判断当前订单是否有退款状态
    public function is_refund($order_id)
    {
        $ele = D('Eleorder')->where(array('order_id' => $order_id))->find();//外卖
        $market = D('Marketorder')->where(array('order_id' => $order_id))->find();//菜市场
        $store = D('Storeorder')->where(array('order_id' => $order_id))->find();//便利店
        $goods = D('Ordergoods')->where(array('order_id' => $order_id))->find();//商城
        $ktv = D('KtvOrder')->where(array('order_id' => $order_id))->find();//KTV
        $housekeeping = D('Appointorder')->where(array('order_id' => $order_id))->find();//家政
        $education = D('EduOrder')->where(array('order_id' => $order_id))->find();//教育
        $agritainment = D('FarmOrder')->where(array('order_id' => $order_id))->find();//农家乐
        $hotel = D('Hotelorder')->where(array('order_id' => $order_id))->find();//酒店

        if (!empty($ele['refund_time'])) {
            return false;
        }
        if (!empty($market['refund_time'])) {
            return false;
        }
        if (!empty($store['refund_time'])) {
            return false;
        }
        if ($goods['status'] == 2) {
            return false;
        }
        if ($ktv['status'] == 3) {
            return false;
        }
        if ($housekeeping['status'] == 3) {
            return false;
        }
        if ($education['order_status'] == 3) {
            return false;
        }
        if ($agritainment['order_status'] == 3) {
            return false;
        }
        if ($hotel['order_status'] == 3) {
            return false;
        }

        return true;

    }

    //新版N级分销
    public function profitUsers($order_id, $id, $shop_id, $jiesuan_price, $type)
    {

       // var_dump($order_id,'看看id是什么：'.$id,'是不是结算佣金：'.$jiesuan_price,$type);
        //p($order_id.'----'.$id.'----'.$shop_id.'----'.$jiesuan_price.'----'. $type);die;
        $config = D('Setting')->fetchAll();
        $Shop = D('Shop')->where(array('shop_id' => $shop_id))->find();

        //如果开通分销
        if ($Shop['is_profit']) {
            list($user_id, $money) = $this->getModelMoneyUser($order_id, $id, $jiesuan_price, $type);
            $obj = D('Users');
            $Users = $obj->find($user_id);
//            $order=D('Order')->where(['order_id'=>$order_id])->find();
//            $money=$order['need_pay']-$order['express_price'];
            if ($money > 0) {

                if ($Users['fuid1'] && (true == $this->determinePower($Users['fuid1']))) {

                    $order = M('Order')->find($order_id);
                    $goods_profit = M('GoodsProfit')->find($order['goods_id']);

                    if ($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate1']) > 1) {
                        $money1 = round($goods_profit['profit_rate1'] * $money /100,2 );
                        $rate1 = $goods_profit['profit_rate1'];
                        $info1 = $this->_type[$type] . '商城订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '商品独立分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'goods') {
                        $money1 = round($config['profit']['goods_profit_rate1'] * $money /100,2 );
                        $rate1 = $config['profit']['goods_profit_rate1'];
                        $info1 = $this->_type[$type] . '商城订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'hotel') {
                        $money1 = round($config['profit']['hotel_profit_rate1'] * $money /100,2 );
                        $rate1 = $config['profit']['hotel_profit_rate1'];
                        $info1 = $this->_type[$type] . '酒店订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'farm') {
                        $money1 = round($config['profit']['farm_profit_rate1'] * $money  /100,2);
                        $rate1 = $config['profit']['farm_profit_rate1'];
                        $info1 = $this->_type[$type] . '农家乐订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'breaks') {
                        $money1 = round($config['profit']['breaks_profit_rate1'] * $money /100,2);
                        $rate1 = $config['profit']['breaks_profit_rate1'];
                        $info1 = $this->_type[$type] . '优惠买单订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } else {
                        $money1 = round($config['profit']['currency_profit_rate1'] * $money /100,2);
                        $rate1 = $config['profit']['currency_profit_rate1'];
                        $info1 = $this->_type[$type] . '订单ID:' . $order_id . ', 销售分成: ' . round($money1 , 2) . '分成比例【' . $rate1 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    }

                    if ($money1 >= 0) {
                        $fuser1 = $obj->find($goods['fuid1']);
                        if ($fuser1) {
                            $obj->addMoney($Users['fuid1'], $money1, $info1);
                            $obj->addProfit($Users['fuid1'], $order_type = 0, $type, $order_id, $shop_id, $money1, $is_separate = '1', $info1);
                        }
                    }
                }


                if ($Users['fuid2'] && (true == $this->determinePower($Users['fuid2']))) {
                    $order = M('Order')->find($order_id);
                    $goods_profit = M('GoodsProfit')->find($order['goods_id']);

                    if ($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate2']) > 1) {
                        $money2 = round($goods_profit['profit_rate2'] * $money /100,2);
                        $rate2 = $goods_profit['profit_rate2'];
                        $info2 = $this->_type[$type] . '订单ID:' . $order_id . ', 推荐分成: ' . round($money2, 2) . '商品独立分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'goods') {
                        $money2 = round($config['profit']['goods_profit_rate2'] * $money /100,2);
                        $rate2 = $config['profit']['goods_profit_rate2'];
                        $info2 = $this->_type[$type] . '订单ID:' . $order_id . ', 推荐分成: ' . round($money2, 2) . '分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'hotel') {
                        $money2 = round($config['profit']['hotel_profit_rate2'] * $money /100,2);
                        $rate2 = $config['profit']['hotel_profit_rate2'];
                        $info2 = $this->_type[$type] . '订单ID:' . $order_id . ', 推荐分成: ' . round($money2, 2) . '分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'farm') {
                        $money2 = round($config['profit']['farm_profit_rate2'] * $money /100,2);
                        $rate2 = $config['profit']['farm_profit_rate2'];
                        $info2 = $this->_type[$type] . '订单ID:' . $order_id . ', 推荐分成: ' . round($money2 , 2) . '分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } elseif ($type == 'breaks') {
                        $money2 = round($config['profit']['breaks_profit_rate2'] * $money /100,2);
                        $rate2 = $config['profit']['breaks_profit_rate2'];
                        $info2 = $this->_type[$type] . '优惠买单订单ID:' . $order_id . ', 推荐分成: ' . round($money2 , 2) . '分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    } else {
                        $money2 = round($config['profit']['currency_profit_rate2'] * $money /100,2);
                        $rate2 = $config['profit']['currency_profit_rate2'];
                        $info2 = $this->_type[$type] . '订单ID:' . $order_id . ', 推荐分成: ' . round($money2 , 2) . '分成比例【' . $rate2 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                    }


                    if ($money2 >= 0.01) {
                        $fuser2 = $obj->find($goods['fuid2']);
                        if ($fuser2) {
                            $obj->addMoney($Users['fuid2'], $money2, $info2);
                            $obj->addProfit($Users['fuid2'], $order_type = 0, $type, $order_id, $shop_id, $money2, $is_separate = '1', $info2);
                        }
                    }
                }

                    if(!empty($user_id)) {
                        if ($type == 'goods') {
                            $money3 = round($config['profit']['goods_profit_rate3'] * $money / 100,2);
                            $rate3 = $config['profit']['goods_profit_rate3'];
                            $info3 = $this->_type[$type] . '订单ID:' . $order_id . ', 自己购买分成: ' . round($money3, 2) . '分成比例【' . $rate3 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                        } elseif ($type == 'hotel') {
                            $money3 = round($config['profit']['hotel_profit_rate3'] * $money / 100,2);
                            $rate3 = $config['profit']['hotel_profit_rate3'];
                            $info3 = $this->_type[$type] . '订单ID:' . $order_id . ', 自己购买分成: ' . round($money3, 2) . '分成比例【' . $rate3 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                        } elseif ($type == 'farm') {
                            $money3 = round($config['profit']['farm_profit_rate3'] * $money / 100,2);
                            $rate3 = $config['profit']['farm_profit_rate3'];
                            $info3 = $this->_type[$type] . '订单ID:' . $order_id . ', 自己购买分成: ' . round($money3, 2) . '分成比例【' . $rate3 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                        } elseif ($type == 'breaks') {
                            $money3 = round($config['profit']['breaks_profit_rate3'] * $money / 100,2);
                            $rate3 = $config['profit']['breaks_profit_rate3'];
                            $info3 = $this->_type[$type] . '优惠买单订单ID:' . $order_id . ', 自己购买分成: ' . round($money3 , 2) . '分成比例【' . $rate3 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                        } else {
                          
                            $money3 = round($config['profit']['currency_profit_rate3'] * $money / 100,2);
                            $rate3 = $config['profit']['currency_profit_rate3'];
                            $info3 = $this->_type[$type] . '订单ID:' . $order_id . ', 自己购买分成: ' . round($money3 , 2) . '分成比例【' . $rate3 . '】%，分成类型【' . $this->_profit_price_type[$config['profit']['profit_price_type']] . '】';
                        }

                        if ($money3 >= 0.01) {
                            $obj->addMoney($user_id, $money3, $info3);
                            $obj->addProfit($user_id, $order_type = 0, $type, $order_id, $shop_id, $money3, $is_separate = '1', $info3);
                        }
                    }
//                if($Users['fuid3'] && (true == $this->determinePower($Users['fuid3']))){
//
//                             $order = M('Order')->find($order_id);
//                             $goods_profit = M('GoodsProfit')->find($order['goods_id']);
//
//                             if($type == 'goods' && $goods_profit['profit_enable'] == 1 && ($goods_profit['profit_rate3']*100) > 1){
//                                 $money3 = round($goods_profit['profit_rate3'] * $money / 100);
//                                 $rate3 = $goods_profit['profit_rate3'];
//                                 $info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'商品独立分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                             }elseif($type == 'goods'){
//                                 $money3 = round($config['profit']['goods_profit_rate3'] * $money / 100);
//                                 $rate3 = $config['profit']['goods_profit_rate3'];
//                                 $info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                             }elseif($type == 'hotel'){
//                                 $money3 = round($config['profit']['hotel_profit_rate3'] * $money / 100);
//                                 $rate3 = $config['profit']['hotel_profit_rate3'];
//                                 $info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                             }elseif($type == 'farm'){
//                                 $money3 = round($config['profit']['farm_profit_rate3'] * $money / 100);
//                                 $rate3 = $config['profit']['farm_profit_rate3'];
//                                 $info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                             }elseif($type == 'breaks'){
//                                 $money3 = round($config['profit']['breaks_profit_rate3'] * $money / 100);
//                                 $rate3 = $config['profit']['breaks_profit_rate3'];
//                                 $info3 = $this->_type[$type]. '优惠买单订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                             }else{
//                                 $money3 = round($config['profit']['currency_profit_rate3'] * $money / 100);
//                                 $rate3 = $config['profit']['currency_profit_rate3'];
//                                 $info3 = $this->_type[$type]. '订单ID:' . $order_id . ', 三级分成: ' . round($money3/ 100, 2).'分成比例【'.$rate3.'】%，分成类型【'.$this->_profit_price_type[$config['profit']['profit_price_type']].'】';
//                         }
//
//
//                             if($money3 > 0.01){
//                                 $fuser3 = $obj->find($goods['fuid3']);
//                                 if($fuser3){
//                                     $obj->addMoney($Users['fuid3'], $money3, $info3);
//                                     $obj->addProfit($Users['fuid3'], $order_type = 0, $type, $order_id, $shop_id,$money3, $is_separate = '1', $info3);
//                                 }
//                             }
//                         }
                //会员自己购买分成



                        $money4=$this->is_fenxiao($order_id,$user_id,$money,$type);

                return $money1 + $money2 + $money3 + $money4;//返回分成金额

            }
            return 0;//返回分成金额0元
        }

    }

    //获取会员ID，金额，模型
    public function getModelMoneyUser($order_id, $id, $jiesuan_price, $type)
    {
        $config = D('Setting')->fetchAll();
        //外卖
        if ($type == 'ele') {
            if ($config['profit']['profit_is_ele']) {
                $order = D('Eleorder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['need_pay'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['settlement_price'];
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $order['need_pay'] - $order['settlement_price'] - $order['logistics'];
                } else {
                    $money = 0;
                }
                D('Eleorder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'market') {
            if ($config['profit']['profit_is_market']) {
                $order = D('Marketorder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['need_pay'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['settlement_price'];
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $order['need_pay'] - $order['settlement_price'];
                } else {
                    $money = 0;
                }
                D('Marketorder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'store') {
            if ($config['profit']['profit_is_store']) {
                $order = D('Storeorder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['need_pay'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['settlement_price'];
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $order['need_pay'] - $order['settlement_price']- $order['logistics'];
                } else {
                    $money = 0;
                }
                D('Storeorder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'farm') {
            if ($config['profit']['profit_is_farm']) {
                $order = D('FarmOrder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['amount'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['jiesuan_amount'] ;
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = ($order['amount'] - $order['jiesuan_amount']);
                } else {
                    $money = 0;
                }
                D('FarmOrder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'goods') {
            if ($config['profit']['profit_is_goods']) {
                $Order = D('Order')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $Order['need_pay'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $jiesuan_price;
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $Order['need_pay'] - $jiesuan_price;
                } else {
                    $money = 0;
                }
                return array($Order['user_id'], $money);
            }
        } elseif ($type == 'tuan') {
            if ($config['profit']['profit_is_tuan']) {
                $Tuancode = D('Tuancode')->find($id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $Tuancode['real_money'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $Tuancode['settlement_price'];
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $Tuancode['real_money'] - $Tuancode['settlement_price'];
                } else {
                    $money = 0;
                }
                D('Tuancode')->save(array('code_id' => $id, 'is_profit' => 1));
                return array($Tuancode['user_id'], $money);
            }
        } elseif ($type == 'booking') {
            if ($config['profit']['profit_is_booking']) {
                $order = D('Bookingorder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['amount'];
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['amount'];
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = $order['amount'];
                } else {
                    $money = 0;
                }
                D('Bookingorder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'hotel') {
            if ($config['profit']['profit_is_hotel']) {
                $order = D('Hotelorder')->find($order_id);
                if ($config['profit']['profit_price_type'] == 1) {
                    $money = $order['amount'] ;
                } elseif ($config['profit']['profit_price_type'] == 2) {
                    $money = $order['jiesuan_amount'] ;
                } elseif ($config['profit']['profit_price_type'] == 3) {
                    $money = ($order['amount'] - $order['jiesuan_amount']) ;
                } else {
                    $money = 0;
                }
                D('Hotelorder')->save(array('order_id' => $order_id, 'is_profit' => 1));
                return array($order['user_id'], $money);
            }
        } elseif ($type == 'breaks') {
            //如果是优惠买单
            if ($config['profit']['profit_is_breaks']) {
                $order = D('Breaksorder')->find($order_id);
                return array($order['user_id'], $order['need_pay'] );
            }

        }


    }


    //会员购买等级三级分成，会员id，购买会员等级金额，等级昵称
    public function pay_rank_profit_user($user_id, $price, $rank_name)
    {
        $config = D('Setting')->fetchAll();
        $obj = D('Users');
        $Users = $obj->find($user_id);

        if ($Users['fuid1']) {
            $money1 = round($price * $config['profit']['rank_profit_rate1']);
            if ($money1 > 0) {
                $info1 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【' . $rank_name . '】一级分成: ' . round($money1 , 2);
                $fuser1 = $obj->find($Users['fuid1']);
                if ($fuser1) {
                    $obj->addMoney($Users['fuid1'], $money1, $info1);
                    $obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0', $money1, $is_separate = '1', $info1);
                }
            }
        }

        if ($Users['fuid2']) {
            $money2 = round($price * $config['profit']['rank_profit_rate2'] );
            if ($money2 > 0) {
                $info2 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【' . $rank_name . '】二级分成: ' . round($money2 , 2);
                $fuser2 = $obj->find($Users['fuid2']);
                if ($fuser2) {
                    $obj->addMoney($Users['fuid2'], $money2, $info2);
                    $obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0', $money2, $is_separate = '1', $info2);
                }
            }
        }

        if ($Users['fuid3']) {
            $money3 = round($price * $config['profit']['rank_profit_rate3']);
            if ($money3 > 0) {
                $info3 = '会员昵称:' . $Users['nickanme'] . ', 购买会员等级【' . $rank_name . '】一级分成: ' . round($money3, 2);
                $fuser3 = $obj->find($Users['fuid3']);
                if ($fuser3) {
                    $obj->addMoney($Users['fuid3'], $money3, $info3);
                    $obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'rank', $order_id = '0', $shop_id = '0', $money3, $is_separate = '1', $info3);
                }
            }
        }
    }


    //五折卡分销开始，传订单详情
    public function pay_zhe_profit_user($detail)
    {
        return true;
        $config = D('Setting')->fetchAll();
        $obj = D('Users');
        $Users = $obj->find($detail['user_id']);

        $price = $detail['need_pay'];
        $Zheinfo = D('Zhe')->where(array('zhe_id' => $detail['zhe_id']))->find();

		// print_r($Zheinfo);die;
		if($Users['user_id']) {
			$money1 = round($price * $config['profit']['zhe_profit_rate1'] );
			if ($money1 > 0) {
				$info1 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】1级分成: ' . round($money1 , 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['user_id'], $money1, $info1);
					$obj->addProfit($Users['user_id'], $order_type = 'goods', $type = 'goods', $order_id = $detail['order_id'], $shop_id = $Zheinfo['shop_id'],$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid1']) {
			$money2 = round($price * $config['profit']['zhe_profit_rate2']);
			if ($money2 > 0) {
				$info2 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】2级分成: ' . round($money2 , 2);
				$fuser2 = $obj->find($Users['fuid1']);
				if($fuser2){
					$obj->addMoney($Users['fuid1'], $money2, $info2);
					$obj->addProfit($Users['fuid1'], $order_type = 'goods', $type = 'goods', $order_id = $detail['order_id'], $shop_id = $Zheinfo['shop_id'],$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money3 = round($price * $config['profit']['zhe_profit_rate3']);
			if ($money3 > 0) {
				$info3 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】3级分成: ' . round($money3 , 2);
				$fuser3 = $obj->find($Users['fuid2']);
				if($fuser3){
					$obj->addMoney($Users['fuid2'], $money3, $info3);
					$obj->addProfit($Users['fuid2'], $order_type = 'goods', $type = 'goods', $order_id = $detail['order_id'], $shop_id = $Zheinfo['shop_id'], $money3, $is_separate = '1', $info3);
				}
			}
		}
		
		/*
		if($Users['fuid1']) {
			$money1 = round($price * $config['profit']['zhe_profit_rate1'] / 100);
			if ($money1 > 0) {
				$info1 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】1级分成: ' . round($money1 / 100, 2);
				$fuser1 = $obj->find($Users['fuid1']);
				if($fuser1){
					$obj->addMoney($Users['fuid1'], $money1, $info1);
					$obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money1, $is_separate = '1', $info1);
				}
			}
		}
		
		if($Users['fuid2']) {
			$money2 = round($price * $config['profit']['zhe_profit_rate2'] / 100);
			if ($money2 > 0) {
				$info2 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】2级分成: ' . round($money2 / 100, 2);
				$fuser2 = $obj->find($Users['fuid2']);
				if($fuser2){
					$obj->addMoney($Users['fuid2'], $money2, $info2);
					$obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money2, $is_separate = '1', $info2);
				}
			}
		}
		
		if($Users['fuid3']) {
			$money3 = round($price * $config['profit']['zhe_profit_rate3'] / 100);
			if ($money3 > 0) {
				$info3 = '会员昵称:' . $Users['nickanme'] . ', 购买五折卡订单ID【'.$detail['order_id'].'】3级分成: ' . round($money3 / 100, 2);
				$fuser3 = $obj->find($Users['fuid3']);
				if($fuser3){
					$obj->addMoney($Users['fuid3'], $money3, $info3);
					$obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'zhe', $order_id = '0', $shop_id = '0',$money3, $is_separate = '1', $info3);
				}
			}
		}
		*/
   }
   
   
    //商家购买等级三级分成，商家id，购买等级金额，购买商家等级名称
    public function buy_shop_grade_profit_user($shop_id, $price, $grade_name)
    {
        return true;
        $config = D('Setting')->fetchAll();
        $obj = D('Users');
        $Shop = D('Shop')->find($shop_id);
        $Users = $obj->find($Shop['user_id']);

        if ($Users['fuid1']) {
            $money1 = round($price * $config['profit']['grade_profit_rate1'] );
            if ($money1 > 0) {
                $info1 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【' . $grade_name . '】一级分成: ' . round($money1 , 2);
                $fuser1 = $obj->find($Users['fuid1']);
                if ($fuser1) {
                    $obj->addMoney($Users['fuid1'], $money1, $info1);
                    $obj->addProfit($Users['fuid1'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'], $money1, $is_separate = '1', $info1);
                }
            }
        }

        if ($Users['fuid2']) {
            $money2 = round($price * $config['profit']['grade_profit_rate2'] );
            if ($money2 > 0) {
                $info2 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【' . $grade_name . '】二级分成: ' . round($money2 , 2);
                $fuser2 = $obj->find($Users['fuid2']);
                if ($fuser2) {
                    $obj->addMoney($Users['fuid2'], $money2, $info2);
                    $obj->addProfit($Users['fuid2'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'], $money2, $is_separate = '1', $info2);
                }
            }
        }

        if ($Users['fuid3']) {
            $money3 = round($price * $config['profit']['grade_profit_rate3'] );
            if ($money3 > 0) {
                $info3 = '商家【:' . $Shop['shop_name'] . '】, 购买商家等级【' . $grade_name . '】三级分成: ' . round($money3 , 2);
                $fuser3 = $obj->find($Users['fuid3']);
                if ($fuser3) {
                    $obj->addMoney($Users['fuid3'], $money3, $info3);
                    $obj->addProfit($Users['fuid3'], $order_type = 0, $type = 'grade', $order_id = '0', $Shop['shop_id'], $money3, $is_separate = '1', $info3);
                }
            }
        }
    }


}
