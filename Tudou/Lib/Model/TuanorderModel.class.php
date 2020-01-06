<?php
class TuanorderModel extends CommonModel{
    protected $pk = 'order_id';
    protected $tableName = 'tuan_order';
	//检测抢购订单过期时间
	public function chenk_guoqi_time(){
		$CONFIG = D('Setting')->fetchAll();
		$guoqi_time = $CONFIG['tuan']['tuan_time']*60;
		$time = time();
		$jiancha_time = $CONFIG['tuan']['tuan_time']/10*60;
		if(file_exists(BASE_PATH.'/tuantime.txt')){
			$up_time = filemtime(BASE_PATH.'/tuantime.txt');
			if($time-$up_time>$jiancha_time){
				 $a =  fopen(BASE_PATH.'/tuantime.txt', 'w');
				 $this->update_guoqi_time($guoqi_time);
			}
		}else{
			$a =  fopen(BASE_PATH.'/tuantime.txt', 'w');
		}
	}

    //PC端输入物流单号发货
    public function pc_express_deliver($order_id){
        D('Tuanorder')->save(array('status' => 2), array("where" => array('order_id' => $order_id)));
        return true;
    }


	//更新过期时间
	public function update_guoqi_time($guoqi_time){
		$time = time();
		$max_time = $time - $guoqi_time;
		$itmes = D('Tuanorder')->where(array('create_time'=>array('lt',$max_time),'status'=>'0'))->select();
		$array = $orders = array();
		foreach($itmes as $k => $v){
			$array[$v['tuan_id']] += $v['num'];
			$orders[] = $v['order_id'];
		}
		$order_list = implode(',',$orders);
		if(D('Tuanorder')->where(array('order_id'=>array('in',$order_list)))->save(array('status'=>'-1','update_time'=>$time))){
			foreach($array as $k => $v){
				D('Tuan')->where(array('tuan_id'=>$k))->setInc('num',$v);
				D('Tuan')->where(array('tuan_id'=>$k))->setDec('sold_num',$v);
			}
		}
	}
	//获取抢购实际价格
	public function get_tuan_need_pay($order_id,$user_id,$type){
        $order_id = (int)$order_id;
        $order = D('Tuanorder')->find($order_id);
		$users = D('Users')->find($user_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $user_id) {
            return false;
        }else{
			$tuan = D('Tuan')->find($order['tuan_id']);
			if (empty($tuan) || $tuan['closed'] == 1 || $tuan['end_date'] < TODAY) {
               return false;
            }
			$canuse = $tuan['use_integral'] * $order['num'];
            $used = 0;
            if ($users['integral'] < $canuse) {
                $used = $users['integral'];
                $users['integral'] = 0;
            } else {
                $used = $canuse;
                $users['integral'] -= $canuse;
            }
            D('Users')->save(array('user_id' => $user_id, 'integral' => $users['integral']));

			//如果后台没有开启积分比例按照原来的积分设置，如果以开启乘以比例数
			$config = D('Setting')->fetchAll();
			
			if($config['integral']['buy'] ==0 ){
				$integral_price = $used;
			}elseif($config['integral']['buy'] == 10){
				$integral_price = $used * $config['integral']['buy'];
			}elseif($config['integral']['buy'] == 100){
				$integral_price = $used * $config['integral']['buy'];
			}else{
				$integral_price = 0;
			}
			
			
			//这里加上判断，就是不管你怎么样，积分兑换的金额大于抢购结算价就返回失败
			if($integral_price == 0 && $integral_price > ($order['total_price'] - $order['mobile_fan'])){
				if($type ==1){
					$order['need_pay'] = $order['total_price']; //PC不减去手机下单立减
				}else{
					$order['need_pay'] = $order['total_price'] - $order['mobile_fan'];
				}
				$order['use_integral'] = 0;
			}else{//扣除成功
			    if($users['integral'] > 0 && $users['integral'] >= $integral_price){
					$intro = '抢购【'.$tuan["title"].'】订单' . $order_id . '积分抵用';
					D('Users')->addIntegral($user_id,-$canuse,$intro);
				}
				if($type ==1){
					$order['need_pay'] = $order['total_price']  - $integral_price; //PC不减去手机下单立减
				}else{
					$order['need_pay'] = $order['total_price'] - $order['mobile_fan'] - $integral_price  + ($order['freight_price']);
				}
				$order['use_integral'] = $used;
			}
			D('Tuanorder')->save(array('order_id' => $order_id, 'use_integral'=>$order['use_integral'],'need_pay' => $order['need_pay']));
			return $order['need_pay'];
		}
        return false;
    }
	
	
    public function source(){
        $y = date('Y', NOW_TIME);
        $data = $this->query(" SELECT count(1) as num,is_mobile,FROM_UNIXTIME(create_time,'%c') as m from  " . $this->getTableName() . "  where status=1 AND FROM_UNIXTIME(create_time,'%Y') ='{$y}'  group by  is_mobile,FROM_UNIXTIME(create_time,'%c')");
        $showdata = array();
        $mobile = array();
        $pc = array();
        for ($i = 1; $i <= 12; $i++) {
            $mobile[$i] = 0;
            $pc[$i] = 0;
            foreach ($data as $val) {
                if ($val['m'] == $i) {
                    if ($val['is_mobile']) {
                        $mobile[$i] = $val['num'];
                    } else {
                        $pc[$i] = $val['num'];
                    }
                }
            }
        }
        ksort($mobile);
        ksort($pc);
        $showdata['mobile'] = join(',', $mobile);
        $showdata['pc'] = join(',', $pc);
        return $showdata;
    }
    public function money_yue(){
        $y = date('Y', NOW_TIME);
        $data = $this->query(" SELECT sum(total_price) as price,FROM_UNIXTIME(create_time,'%c') as m from  " . $this->getTableName() . "  where status=1 AND FROM_UNIXTIME(create_time,'%Y') ='{$y}'  group by  FROM_UNIXTIME(create_time,'%c')");
        $showdata = array();
        for ($i = 1; $i <= 12; $i++) {
            $showdata[$i] = 0;
            foreach ($data as $val) {
                if ($val['m'] == $i) {
                    $showdata[$i] = $val['price'];
                }
            }
        }
        ksort($showdata);
        return join(',', $showdata);
    }
    public function money($bg_time, $end_time, $shop_id){
        $bg_time = (int) $bg_time;
        $end_time = (int) $end_time;
        $shop_id = (int) $shop_id;
        if (!empty($shop_id)) {
            $data = $this->query(" SELECT sum(total_price) as price,FROM_UNIXTIME(create_time,'%m%d') as d from  " . $this->getTableName() . "   where status=1 AND create_time >= '{$bg_time}' AND create_time <= '{$end_time}' AND shop_id = '{$shop_id}'  group by  FROM_UNIXTIME(create_time,'%m%d')");
        } else {
            $data = $this->query(" SELECT sum(total_price) as price,FROM_UNIXTIME(create_time,'%m%d') as d from  " . $this->getTableName() . "   where status=1 AND create_time >= '{$bg_time}' AND create_time <= '{$end_time}'  group by  FROM_UNIXTIME(create_time,'%m%d')");
        }
        $showdata = array();
        $days = array();
        for ($i = $bg_time; $i <= $end_time; $i += 86400) {
            $days[date('md', $i)] = '\'' . date('m月d日', $i) . '\'';
        }
        $price = array();
        foreach ($days as $k => $v) {
            $price[$k] = 0;
            foreach ($data as $val) {
                if ($val['d'] == $k) {
                    $price[$k] = $val['price'];
                }
            }
        }
        $showdata['d'] = join(',', $days);
        $showdata['price'] = join(',', $price);
        return $showdata;
    }
    public function weeks(){
        $y = NOW_TIME - 86400 * 6;
        $data = $this->query(" \r\n\r\n            SELECT count(1) as num,is_mobile,FROM_UNIXTIME(create_time,'%d') as d from  __TABLE__ \r\n\r\n            where status=1 AND create_time >= '{$y}'  group by  \r\n\r\n                is_mobile,FROM_UNIXTIME(create_time,'%d')");
        $showdata = array();
        $mobile = array();
        $pc = array();
        $days = array();
        for ($i = 0; $i <= 6; $i++) {
            $d = date('d', $y + $i * 86400);
            $mobile[$i] = 0;
            $pc[$i] = 0;
            $days[] = '\'' . $d . '号\'';
            foreach ($data as $val) {
                if ($val['d'] == $d) {
                    if ($val['is_mobile']) {
                        $mobile[$i] = $val['num'];
                    } else {
                        $pc[$i] = $val['num'];
                    }
                }
            }
        }
        ksort($mobile);
        ksort($pc);
        $showdata['mobile'] = join(',', $mobile);
        $showdata['pc'] = join(',', $pc);
        $showdata['days'] = join(',', $days);
        return $showdata;
    }

    //最终确认收货，不按照类目结算价按照订单用户实际金额扣点结算
    public function overOrder($order_id){
        $config = D('Setting')->fetchAll();
        if($detail = $this->find($order_id)){
            if($detail['status'] != 2 && $detail['status'] != 3) {
                return false;
            }else{
                if($this->save(array('status' => 8, 'order_id' => $order_id))){
                    D('Tuanorder')->save(array('status' => 8), array('where' => array('order_id' => $order_id)));//先更新
                    $Shop = D('Shop')->find($detail['shop_id']);
                    list($settlement_price,$intro) = $this->get_order_settlement_price_intro($detail);//获取结算价封装

                    if($detail['is_daofu'] == 0){
                        D('Shopmoney')->insertData($order_id,$id = '0',$detail['shop_id'],$settlement_price,$type ='goods',$intro);//结算给商家
                        D('Users')->integral_restore_user($detail['user_id'],$order_id, $id = '0',$settlement_price,$type ='goods');//商城购物返利积分
                        D('Users')->return_integral($Shop['user_id'], $detail['use_integral'] , '积分商城用户积分兑换返还给商家');//商城用户积分兑换返还给商家
                        D('Users')->getProit($detail['user_id'],'goods',$settlement_price,$detail['shop_id'],$order_id); //新增商品分销结算
                        if($config['prestige']['is_goods']){
                            D('Users')->reward_prestige($detail['user_id'], (int)($settlement_price),'商城购物返'.$config['prestige']['name']);//返威望
                        }
                    }
                    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 2,$status = 8);
                    D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 2,$status = 8);
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }

    }

    //返回结算价格，返回结算说明，顺便把配送员的运费给结算了
    public function get_order_settlement_price_intro($detail){
        $shop = D('Shop')->find($detail['shop_id']);

        if($shop['commission'] == 0 || $shop['commission'] < 0){
            $commission = '未设置佣金';
            $estimated_price = $detail['need_pay'];
        }else{
            //开通第三方配送佣金不含配送费
            if($shop['is_goods_pei'] == 1){
                $need_pay = $detail['need_pay'] - $detail['express_price'];//佣金计算应该是总价-运费
                $commission = (int)(($need_pay * $shop['commission'])/10000);//计算佣金
                $estimated_price = (int)($detail['need_pay'] - $commission);//实际结算给商家价格
            }else{
                $commission = (int)(($detail['need_pay'] * $shop['commission'])/10000);//佣金
                $estimated_price = (int)($detail['need_pay'] - $commission);
            }

        }

        if($estimated_price > 0){
            if($shop['is_goods_pei'] == 1){
                $express_price = isset($shop['express_price']) ? (int)$shop['express_price'] : 10;//商家自己配置的默认运费
                if($detail['express_price'] < $express_price){
                    $settlement_price = $estimated_price - $express_price;
                    $express_price = $express_price;
                    $intro .='状态：【已开通配送状态，用户支付运费小于商家默认配送费】---';
                    $intro .='结算金额：结算价'.round($detail['need_pay'],2).'-商家默认配送费'.round($express_price,2).'元'.'-积分商城结算佣金'.round($commission,2).'元】---';
                    $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
                }else{
                    $settlement_price = $estimated_price - $detail['express_price'];
                    $express_price = $detail['express_price'];
                    $intro .='状态：【已开通配送状态，用户支付运费大于商家默认配送费】---';
                    $intro .='结算金额：结算价'.round($detail['need_pay'],2).'-用户支付运费'.round($detail['express_price'],2).'元'.'-积分商城结算佣金'.round($commission,2).'元】---';
                    $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
                }
                D('Runningmoney')->add_express_price($detail['order_id'],$express_price,2);//配送员结算
            }else{
                //商家自主配送不结算给配送员，结算价 = 扣除佣金后价格
                $settlement_price = $estimated_price;
                $intro .='状态：【商家自主配送】---';
                $intro .='结算金额：结算价'.round($detail['need_pay'],2).'-佣金'.round($commission,2).'元】---';
                $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
            }
            return array($settlement_price,$intro);
        }else{
            return true;//错误不管
        }
    }


    /**
     * 获取退款最大金额@pingdan
     * @param  [type] $order_goods_id 订单商品id
     * @param  [type] $num            申请数量
     * @return [type]                 [description]
     */
    public function getRefundMaxMoney($order_goods_id, $num) {
        $order_goods_id = (int)$order_goods_id;
        $num = (int)$num;
        if ($order_goods_id < 1 || $num < 1) {
            return false;
        }

        $order_goods = $this->where(array('order_id' => $order_goods_id))->find();
        if (!$order_goods) {
            return false;
        }
        //var_dump($order_goods_id);
        $order=D('Tuanorder')->where(array('order_id'=>$order_goods_id))->find();
        //修改，用户申请退款只能退商品钱
        $actual_pay = $order['need_pay']-$order['freight_price'];
        //var_dump($actual_pay/$order_goods['num']);die;
        return round(($actual_pay/$order_goods['num']) *$num);
    }

    //更换地址
    public function update_add($uid,$type,$order_id,$address_id){
        $order = D('Tuanorder')->where(['user_id'=>$uid,'order_id'=>$order_id])->save(['addr_id'=>$address_id]);
        if(false ==$order){
            return false;
        }
        return true;
    }



}