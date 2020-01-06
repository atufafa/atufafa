<?php
class UsersModel extends CommonModel{
    protected $pk = 'user_id';
    protected $tableName = 'users';
    protected $_integral_type = array(
		'login' => '每日登陆', 
		'dianping_shop' => '商家点评', 
		'thread' => '回复帖子', 
		'mobile' => '手机认证', 
		'email' => '邮件认证',
		'sign' => '用户每天签到',
		'register' => '用户首次注册',
		'useraux' => '用户实名认证成功',
	);
	
	protected $Type = array(
        'goods' => '商城',
		'tuan' => '抢购',
		'ele' => '外卖',
    );
	
	public function getError() {
        return $this->error;
    }
	
	
	//判断是不是商家
    public function get_is_shop($user_id){
        $Shop = D('Shop')->where(array('user_id'=>$user_id))->find();
        if (empty($Shop)) {
            return false;
        }else{
			return true;	
		}
    }
	//判断是不是配送员
    public function get_is_delivery($user_id){
        $Deliver = D('Delivery')->where(array('user_id'=>$user_id))->find();
        if (empty($Deliver)) {
            return false;
        }else{
			return true;	
		}
    }
    public function getUserByAccount($account){
        $data = $this->find(array('where' => array('account' => $account)));
        return $this->_format($data);
    }
    public function getUserByMobile($mobile){
        $data = $this->find(array('where' => array('mobile' => $mobile)));
        return $this->_format($data);
    }
    //邮件登录暂时不处理
    public function getUserByEmail($email){
        $data = $this->find(array('where' => array('email' => $email)));
        return $this->_format($data);
    }
    public function getUserByUcId($uc_id){
        $data = $this->find(array('where' => array('uc_id' => (int) $uc_id)));
        return $this->_format($data);
    }
    //声望不记录日志了
    public function prestige($user_id, $mdl){
        static $CONFIG;
        if (empty($CONFIG)) {
            $CONFIG = D('Setting')->fetchAll();
        }
        $user = $this->find($user_id);
        if (!empty($user) && $CONFIG['prestige'][$mdl]) {
            $data = array('user_id' => $user_id, 'prestige' => $user['prestige'] + $CONFIG['prestige'][$mdl]);
            $userrank = D('Userrank')->fetchAll();
            foreach ($userrank as $val) {
                if ($val['prestige'] <= $data['prestige']) {
                    $data['rank_id'] = $val['rank_id'];
                }
            }
			$this->add_user_prestige($user_id,$CONFIG['prestige'][$mdl], $this->_integral_type[$mdl].'奖励'.$CONFIG['prestige'][$name]);
            return $this->save($data);
        }
        return false;
    }
	
	//实际销售额返利声望【万能接口】
    public function reward_prestige($user_id, $prestige, $intro){
        $user = $this->find($user_id);
        if (!empty($user) && !empty($prestige)) {
            $data = array('user_id' => $user_id, 'prestige' => $user['prestige'] + $prestige);
            $userrank = D('Userrank')->fetchAll();
            foreach ($userrank as $val) {
                if ($val['prestige'] <= $data['prestige']) {
                    $data['rank_id'] = $val['rank_id'];
                }
            }
			
			$this->add_user_prestige($user_id,$prestige, $intro);
            return $this->save($data);
        }
        return false;
    }
	//写入声望日志，暂时不想做啊
	public function add_user_prestige($user_id,$prestige, $intro){
		   if(!empty($user_id) && !empty($prestige)) {
				D('Userprestigelogs')->add(array(
					'user_id' => $user_id, 
					'prestige' => $prestige, 
					'intro' => $intro, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip()
				));
				D('Weixinmsg')->weixinTmplCapital($type = 4,$user_id,$prestige,$intro);//声望微信模板通知
			    return true;
		  }
        return false;
    }
	
    public function integral($user_id, $mdl){
        static $CONFIG;
        if (empty($CONFIG)) {
            $CONFIG = D('Setting')->fetchAll();
        }
        if (!isset($this->_integral_type[$mdl])) {
            return false;
        }
        if ($CONFIG['integral'][$mdl]) {
            return $this->addIntegral($user_id, $CONFIG['integral'][$mdl], $this->_integral_type[$mdl]);
        }
        return false;
    }
	
	 //积分兑换商品返还积分给商家中间层
    public function return_integral($user_id, $jifen, $intro){
        static $CONFIG;
        if (empty($CONFIG)) {
            $CONFIG = D('Setting')->fetchAll();
        }
        if (empty($CONFIG['integral']['return_integral'])) {
            return false;
        }
        $integral = intval(($jifen * $CONFIG['integral']['return_integral']));
        if ($integral <= 0) {
            return false;
        }
        return $this->addIntegral($user_id, $integral, $intro);
    }
	
	//写入商户金块已就是商户资金余额
    public function addGold($user_id, $num, $intro = ''){
		D('Weixinmsg')->weixinTmplCapital($type = 3,$user_id,$num,$intro);//商户资金模板通知
        if ($this->updateCount($user_id, 'gold', $num)) {
            return D('Usergoldlogs')->add(array(
				'user_id' => $user_id, 
				'gold' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }

	//写入用户余额
    public function addMoney($user_id, $num, $intro = ''){
        if ($this->updateCount($user_id, 'money', $num)) {
        	//var_dump($num);die;
			D('Weixinmsg')->weixinTmplCapital($type = 1,$user_id,$num,$intro);//余额模板通知
            return D('Usermoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }

    //写入用户跑腿钱包余额
    public function addMoneys($user_id, $num, $intro = ''){
    	//var_dump($num);die;
        if ($this->updateCount($user_id, 'errandsmoney', $num)) {

			D('Weixinmsg')->weixinTmplCapital($type = 1,$user_id,$num,$intro);//余额模板通知
            return D('Errandsmoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }

     //写入司机跑腿钱包余额
    public function addMoneyss($user_id, $num, $intro = ''){
    	
        if ($this->updateCount($user_id,'vehiclemoney', $num)) {
        	
			D('Weixinmsg')->weixinTmplCapital($type = 1,$user_id,$num,$intro);//余额模板通知
            return D('Vehiclemoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }

    //退款写入余额日志
    public function addMoneyLogs($user_id, $num, $intro = '')
    {
    	return D('Usermoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
    }

     //退款跑腿写入余额日志
    public function addMoneyLogss($user_id, $num, $intro = '')
    {
    	return D('Errandsmoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
    }

     //退款司机写入余额日志
    public function addMoneyLogsss($user_id, $num, $intro = '')
    {
    	return D('Vehiclemoneylogs')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
    }

	//写入用户威望
    public function addPrestige($user_id, $num, $intro = ''){
        if ($this->updateCount($user_id, 'prestige', $num)) {
			D('Weixinmsg')->weixinTmplCapital($type = 4,$user_id,$num,$intro);//威望模板通知
            return D('Userprestigelogs')->add(array(
				'user_id' => $user_id, 
				'prestige' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }
	//用户积分增加修改
    public function addIntegral($user_id, $num, $intro = ''){
        if ($this->updateCount($user_id, 'integral', $num)) {
			D('Weixinmsg')->weixinTmplCapital($type = 2,$user_id,$num,$intro);//积分模板通知
            return D('Userintegrallogs')->add(array(
				'user_id' => $user_id, 
				'integral' => $num, 
				'intro' => $intro, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
			
        }
	
        return false;
    }


    //商家积分增加修改
    public function addShopIntegra($user_id,$num,$intro=''){
	  if($this->updateCount($user_id,'shopintegral',$num)){
          D('Weixinmsg')->weixinTmplCapital($type = 2,$user_id,$num,$intro);//积分模板通知
          return D('Shopintegrallogs')->add(array(
              'user_id' => $user_id,
              'integral' => $num,
              'intro' => $intro,
              'create_time' => NOW_TIME,
              'create_ip' => get_client_ip()
          ));
      }
        return false;
    }
	
	
	//三级分销封装
    public function addProfit($user_id, $orderType = 0,  $type, $orderId,$shop_id, $num, $is_separate, $info){
        return D('Userprofitlogs')->add(array(
			'order_type' => $orderType, 
			'type' => $type, 
			'order_id' => $orderId, 
			'user_id' => $user_id, 
			'shop_id' => $shop_id, 
			'money' => $num, 
			'info' => $info, 
			'create_time' => NOW_TIME, 
			'is_separate' => $is_separate
		));
    }
	//积分返利
	 public function add_Integral_restore($library_id,$user_id, $integral, $intro = '', $logo_id = 0, $restore_date){
       if($integral > 0){
           if($user_id){
			   $data = array();
			   $data['library_id'] = $library_id;
			   $data['user_id'] = $user_id;
			   $data['integral'] = $integral;
			   $data['intro'] = $intro;
			   $data['create_time'] = NOW_TIME;
			   $data['create_ip'] = get_client_ip();
			   $data['restore_date'] = $restore_date;
			   if($restore_id = D('Userintegralrestore')->add($data)){
				   if($this->addIntegral($user_id, $integral, $intro)){
					  $obj = D('Userintegrallibrary');
					  $obj->where(array('library_id'=>$library_id))->setInc('integral_library_total_success',1);
					  $obj->where(array('library_id'=>$library_id))->setInc('integral_library_success',$integral);
					  $obj->where(array('library_id'=>$library_id))->setDec('integral_library_surplus',$integral); 
					  return true;
				   }else{
					  return false; 
				   }
				  
				}else{
					return false;
				}
			}else{
				return false;
			}
	   }
	   return false;
   }  
	
    public function CallDataForMat($items){
        if (empty($items)) {
            return array();
        }
        $obj = D('Userrank');
        $rank_ids = array();
        foreach ($items as $k => $val) {
            $rank_ids[$val['rank_id']] = $val['rank_id'];
        }
        $userranks = $obj->itemsByIds($rank_ids);
        foreach ($items as $k => $val) {
            $val['rank'] = $userranks[$val['rank_id']];
            $items[$k] = $val;
        }
        return $items;
    }
	//检测积分设置合法性
	public function check_integral_buy($integral_buy){
		$config = D('Setting')->fetchAll();
		if($config['integral']['integral_exchange'] !=1){//没开启返回假
			return false;	
		}else{
			if($integral_buy  == 0 || $integral_buy  == 10 || $integral_buy  == 100){
				 return true;
			}else{
				return false;
			}	
		}
         return true;
    }
	//获取积分兑换设置比例
	public function obtain_integral_scale($integral_buy){
		if($integral_buy  == 0){
			$scale = 1;
			return $scale;
		}elseif($integral_buy  == 10){
			$scale = 10;
			return $scale;
		}elseif($integral_buy  == 100){
			$scale = 100;
			return $scale;
		}else{
			return false;
		}
       return false;
    }
	
	//导入会员
	public function ImportMember($shop_ids,$shop_id,$mobile,$school_year,$addr,$identity){
		$Shop = D('Shop')->find($shop_ids);
		if($shop_ids != $shop_id){
			return false;
		}
		if(!isPhone($mobile) && !isMobile($mobile)) {
            return false;
        }
		if($this->where(array('mobile'=>$mobile))->find()){
			return false;
		}
		if($this->where(array('account'=>$mobile))->find()){
			return false;
		}
		$data = array();
		$data['account'] = $mobile;
		$data['password'] =rand(100000, 999999);
		$data['nickname'] = $mobile;
		$data['mobile'] = $mobile;;
		$data['school_year'] = $school_year;
		$data['addr'] = $addr;
		$data['identity'] = $identity;
		$data['create_time'] =NOW_TIME;
		$data['create_ip'] =get_client_ip();
	
		$user_id = D('Passport')->register($data,$Shop['user_id'],$type = '1');//注册数据，推荐人id，类型1支持返回会员id
		
		D('Sms')->register($user_id,$mobile,$data['account'],$data['password'],$shop_ids);//会员id，手机号，昵称，密码,商家id可用弃
		
		D('Shopfavorites')->add(array('user_id'=>$user_id,'shop_id'=>$shop_ids,'is_sms'=>'1','is_weixin'=>'1','create_time'=>NOW_TIME,'create_ip' =>get_client_ip()));
		
		D('Useraddr')->add(array(
			'user_id'=>$user_id,
			'city_id'=>$Shop['city_id'],
			'area_id'=>$Shop['area_id'],
			'business_id'=>$Shop['business_id'],
			'name'=>$mobile,
			'mobile' =>$mobile,
			'addr' =>$addr,
			'is_default' =>'1',
			'closed' =>'0',
		));
		
       return true;
    }
	
	 //购物返积分总体封装
    public function integral_restore_user($user_id,$order_id,$id, $settlement_price, $type){
        $config = D('Setting')->fetchAll();
		if($config['integral']['is_restore'] == 1){
			$integral = $this->get_integral_restore_num($order_id,$id, $settlement_price, $type);
			return $this->addIntegral($user_id, $integral, $intro = $this->_type[$type].'购物积分返利');
		}else{
			return false;
		}
    }
	
	//获取具体返利积分,1会员id，2订单id，3其他id，4结算价，5类型
    public function get_integral_restore_num($order_id,$id, $settlement_price, $type){
        $config = D('Setting')->fetchAll();
		if($type == 'goods'){
			$Order = D('Order')->find($order_id);
			if($config['integral']['is_goods_restore'] == 1){
				if($config['integral']['restore_type'] == 1){
					$integral = $Order['need_pay'];
				}elseif($config['integral']['restore_type'] == 2){
					$integral = $settlement_price;
				}elseif($config['integral']['restore_type'] == 3){
					$integral = $Order['need_pay']- $settlement_price;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}elseif($type == 'ele'){
			$order = D('Eleorder')->find($order_id);
			if($config['integral']['is_ele_restore'] == 1){
				if($config['integral']['restore_type'] == 1){
					$integral = $order['need_pay'];;
				}elseif($config['integral']['restore_type'] == 2){
					$integral = $order['settlement_price'];
				}elseif($config['integral']['restore_type'] == 3){
					$integral = $order['need_pay'] - $order['settlement_price'];
				}else{
					return false;
				}
			}else{
				return false;
			}
		}elseif($type == 'tuan'){
			$Tuancode = D('Tuancode')->find($id);
			if($config['integral']['is_tuan_restore'] == 1){
				if($config['integral']['restore_type'] == 1){
					$integral = $Tuancode['real_money'];
				}elseif($config['integral']['restore_type'] == 2){
					$integral = $Tuancode['settlement_price'];
				}elseif($config['integral']['restore_type'] == 3){
					$integral = $Tuancode['real_money']-$Tuancode['settlement_price'];
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		if($config['integral']['restore_points'] < 100){
			if($config['integral']['restore_points']){
				$integral = (int)($integral - (($integral * $config['integral']['restore_points'])));
				if($integral > 0){
					return $integral;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			return false;
		}
		
    }
	
	//设置冻结商户资金入账
	public function set_frozen_gold($user_id,$gold,$intro){
		   if(!$detail = D('Users')->find($user_id)){
              $this->error = '没有该用户';
			  return false;
           }
		   if($detail['gold'] < $gold){
			   $this->error = '商户冻结金不得大于商户资金余额';
			   return false;
           }
		   if($gold < $detail['frozen_gold']){
			   $this->error = '恢复冻结金不得大于'.($detail['frozen_gold']).'元';
			   return false;
           }
           D('Users')->save(array(
			   'user_id'=>$user_id,
			   'gold'=> $detail['gold'] - $gold,
			   'frozen_gold'=> $detail['frozen_gold'] + $gold,
			   'frozen_gold_time'=>NOW_TIME
		   ));
           D('Usergoldlogs')->add(array(
			   'user_id' => $user_id,
			   'gold'=>$gold,
			   'intro' => $intro,
			   'create_time' => NOW_TIME,
			   'create_ip'  => get_client_ip()
		   ));
		   D('Weixinmsg')->weixinTmplCapital($type = 3,$user_id,$gold,$intro);//商户冻结金变动通知
		 return true;
    }


    //设置冻结商户资金入账
    public function set_frozen_huifu($user_id,$gold,$intro){
        if(!$detail = D('Users')->find($user_id)){
            $this->error = '没有该用户';
            return false;
        }
     
        if((float)$detail['frozen_gold'] < $gold){
            $this->error = '恢复冻结金大于实际冻结金余额';
            return false;
        }
        D('Users')->save(array(
            'user_id'=>$user_id,
            'gold'=> $detail['gold'] + $gold,
            'frozen_gold'=> $detail['frozen_gold'] - $gold,
            'frozen_gold_time'=>NOW_TIME
        ));
        D('Usergoldlogs')->add(array(
            'user_id' => $user_id,
            'gold'=>$gold,
            'intro' => $intro,
            'create_time' => NOW_TIME,
            'create_ip'  => get_client_ip()
        ));
        D('Weixinmsg')->weixinTmplCapital($type = 3,$user_id,$gold,$intro);//商户冻结金变动通知
        return true;
    }
	
	//设置冻结会员资金入账
	public function set_frozen_money($user_id,$money,$intro){
		   if(!$detail = D('Users')->find($user_id)){
              $this->error = '没有该用户';
			  return false;
           }
		   if($detail['money'] < $money){
			   $this->error = '会员冻结金不得大于商户资金余额';
			   return false;
           }
		   if($money < $detail['money_gold']){
			   $this->error = '恢复冻结金不得大于'.($detail['money_gold']).'元';
			   return false;
           }
           D('Users')->save(array(
			   'user_id'=>$user_id,
			   'money'=> $detail['money'] - $money,
			   'frozen_money'=> $detail['frozen_money'] + $money,
			   'frozen_money_time'=>NOW_TIME
		   ));
           D('Usermoneylogs')->add(array(
			   'user_id' => $user_id,
			   'money'=>$money,
			   'intro' => $intro,
			   'create_time' => NOW_TIME,
			   'create_ip'  => get_client_ip()
		   ));
		  D('Weixinmsg')->weixinTmplCapital($type = 1,$user_id,$money,$intro);//冻结余额模板通知
		 return true;
    }
	
	//检测积分兑换余额的合法性
	public function check_integral_exchange_legitimate($exchange,$scale){
		$config = D('Setting')->fetchAll();
		if($scale == 1){
			if ($exchange % 100 != 0) {
				$this->error = '积分必须为100的倍数';
				return false;
			}
		}elseif($scale == 10){
			if ($exchange % 10 != 0) {
				$this->error = '积分必须为10的倍数';
				return false;
			}
		}elseif($scale == 100){
			if ($exchange % 1 != 0) {
				$this->error = '积分必须为1的倍数，不支持小数点';
				return false;
			}
		}else{
			 $this->error = '网站后台配置有错误请注意检查';
			 return false;
		}
		if($exchange <= $config['integral']['integral_exchange_small']){
			$this->error = '输入的积分小于网站设置的最少积分数量'.$config['integral']['integral_exchange_small'];
			return false;
        }
		if($exchange >= $config['integral']['integral_exchange_big']){
			$this->error = '输入的积分太多了，最多输入积分数量：'.$config['integral']['integral_exchange_big'];
			return false;
        }
		return true;
    }
	
    
	
	//充值余额送积分，余额
    public function return_recharge_integral_prestige($logs_id,$user_id, $money){
		$CONFIG = D('Setting')->fetchAll();
		$MONEY = intval($money);//先除以100这里获取整数
		
		if($CONFIG['prestige']['activate']){
			if($MONEY >= $CONFIG['prestige']['activate']){
				$this->save(array('user_id'=>$user_id,'is_prestige_frozen'=>'1'));
			}
		}
		
        if($CONFIG['cash']['is_recharge_integral']) {
 			$integral = $this->get_return_recharge_integral_prestige($MONEY,1);
			if($integral > 0){
				$intro = '余额充值订单号'.$logs_id.'赠送积分';
				$this->addIntegral($user_id, $integral, $intro);
		    }
        }
		
		if($CONFIG['cash']['is_recharge_prestige']) {
 			$prestige = $this->get_return_recharge_integral_prestige($MONEY,2);
			if($prestige > 0){
				$intro = '余额充值订单号'.$logs_id.'赠送'.$CONFIG['prestige']['name'];
				$this->addPrestige($user_id, $prestige, $intro);
		    }
        }
		return true;
		
    }
	//获取赠送具体资金
	public function get_return_recharge_integral_prestige($money,$type){
		$CONFIG = D('Setting')->fetchAll();
		
		if($type == 1){
			if($CONFIG['cash']['return_recharge_integral'] ==1) {
				$number = $money * $CONFIG['cash']['return_recharge_integral'];
			}elseif($CONFIG['cash']['return_recharge_integral'] ==10){
				$number = $money * $CONFIG['cash']['return_recharge_integral'];
			}elseif($CONFIG['cash']['return_recharge_integral'] ==100){
				$number = $money * $CONFIG['cash']['return_recharge_integral'];
			}else{
				$number = 0;
			}
		}else{
			if($CONFIG['cash']['return_recharge_prestige'] ==1) {
				$number = $money * $CONFIG['cash']['return_recharge_prestige'];
			}elseif($CONFIG['cash']['return_recharge_prestige'] ==10){
				$number = $money * $CONFIG['cash']['return_recharge_prestige'];
			}elseif($CONFIG['cash']['return_recharge_prestige'] ==100){
				$number = $money * $CONFIG['cash']['return_recharge_prestige'];
			}else{
				$number = 0;
			}
		}
		return $number;
    }
	
	
	//充值多少送多少
    public function Recharge_Full_Gvie_User_Money($user_id, $money){
		$CONFIG = D('Setting')->fetchAll();
        if (!empty($CONFIG['cash']['is_recharge'])) {
			$money = round($money,2);//先除以100再去对比
 			$give_money_array = $this->Check_Gvie_User_Money($money);
			if(!empty($give_money_array)){
				extract($give_money_array); 
				$give_money = $give_money;
				$intro = $intro;
				if($give_money > 0){
					return $this->addIntegral($user_id, $give_money, $intro);
				}	
		    }
        }else{
			return true;//忽略报错
		}
    }
	
    //检测应该送多少钱
    public function Check_Gvie_User_Money($money){
		$CONFIG = D('Setting')->fetchAll();
		if (!empty($CONFIG['cash']['is_recharge']) && !empty($money)) {
			
		//正常模式，后台填写1,2,3，判断都有，就走这里
		if (!empty($CONFIG['cash']['recharge_full_1']) && !empty($CONFIG['cash']['recharge_full_2']) && !empty($CONFIG['cash']['recharge_full_3'])) {
			if(!empty($CONFIG['cash']['recharge_full_1']) && $money >= $CONFIG['cash']['recharge_full_1'] && $money < $CONFIG['cash']['recharge_full_2']){
				if(!empty($CONFIG['cash']['recharge_give_1'])){
					$give_money = $CONFIG['cash']['recharge_give_1'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_1'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}elseif(!empty($CONFIG['cash']['recharge_full_2']) && $money >= $CONFIG['cash']['recharge_full_2'] && $money < $CONFIG['cash']['recharge_full_3']){
				if(!empty($CONFIG['cash']['recharge_give_2'])){
					$give_money = $CONFIG['cash']['recharge_give_2'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_2'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}elseif(!empty($CONFIG['cash']['recharge_full_3']) && $money >= $CONFIG['cash']['recharge_full_3']){
				if(!empty($CONFIG['cash']['recharge_give_3'])){
					$give_money = $CONFIG['cash']['recharge_give_3'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_3'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}
		}
			
		//1，2模式，后台填写1，2，只有1，2，后面的3是空走这里
		if (!empty($CONFIG['cash']['recharge_full_1']) && !empty($CONFIG['cash']['recharge_full_2']) && empty($CONFIG['cash']['recharge_full_3'])) {
			if(!empty($CONFIG['cash']['recharge_full_1']) && $money >= $CONFIG['cash']['recharge_full_1'] && $money < $CONFIG['cash']['recharge_full_2']){
				if(!empty($CONFIG['cash']['recharge_give_1'])){
					$give_money = $CONFIG['cash']['recharge_give_1'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_1'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}elseif(!empty($CONFIG['cash']['recharge_full_2']) && $money >= $CONFIG['cash']['recharge_full_2']){
				if(!empty($CONFIG['cash']['recharge_give_2'])){
					$give_money = $CONFIG['cash']['recharge_give_2'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_2'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}
		 }
		
		//1模式，后台填写1，只有1，后面的2并3都是空走这里
		if (!empty($CONFIG['cash']['recharge_full_1']) && empty($CONFIG['cash']['recharge_full_2']) && empty($CONFIG['cash']['recharge_full_3'])) {
			if(!empty($CONFIG['cash']['recharge_full_1']) && $money >= $CONFIG['cash']['recharge_full_1']){
				if(!empty($CONFIG['cash']['recharge_give_1'])){
					$give_money = $CONFIG['cash']['recharge_give_1'];
					$intro = '您单笔充值'.$money.'返积分'.$CONFIG['cash']['recharge_give_1'].'。';
					return array('give_money' => $give_money, 'intro' =>$intro );
				}else{
					return false;
				}
			}
		 }
			
		}else{
			return false;
		}
		return false;
    }
	
	//检测会员支付密码
	public function check_pay_password($user_id){
			$Users = D('Users')->find($user_id);
			if(!empty($Users['pay_password'])){
				return true;
			}else{
				return false;
			}
        return false;
    }
	//新增用户分销佣金结算部分
	public function AddUser_guide($user_id,$money,$order_id,$type)
	{
		switch ($type) {
			case 'market':
				$obj = D('Marketorderproduct');
				$product = D('Marketproduct');
				$type = "菜市场";
				break;
			
			case 'store':
				$obj = D('Storeorderproduct');
				$product = D('Storeproduct');
				$type = "便利店";
				break;
			case 'ele':
				$obj = D('Eleorderproduct');
				$product = D('Eleproduct');
				$type = "外卖";
				break;
		}
		if(false !== ($order = $obj->where(['order_id=>$order_id]'])->find())){
			$order_product = $product->where(['product_id'=>$order['product_id']])->find();
			if($order_product['product_fen']>0){
				$users = D('Users')->where(['user_id'=>$user_id])->find();
				$money_gold = $money*($order_product['product_fen']);
				if($users['fuid1']){
					$intro = "您的推荐用户".$users['nickname']."在".$type."下单消费，您收到商家给您发放的推荐佣金".$money_gold/2;
					$this->addMoney($users['fuid1'],$money_gold/2,$intro);
					$introA = $users['nickname']."下单消费".$type."将从您的商户资金扣除商品分成佣金".$money_gold/2;
					$this->addGold($order_product['shop_id'],-$money_gold/2,$introA);
				}
				if($users['fuid2']){
					$intro = "您的推荐用户".$users['nickname']."在".$type."下单消费，您收到商家给您发放的推荐佣金".$money_gold/2;
					$this->addMoney($users['fuid1'],$money_gold/2,$intro);
					$introA = $users['nickname']."下单消费".$type."将从您的商户资金扣除商品分成佣金".$money_gold/2;
					$this->addGold($order_product['shop_id'],-$money_gold/2,$introA);
				}
			}
		}
		return true;
	}
	
	//新增商品分销计算
	/*
	**  
		$type  结算类型
		$user_id  结算用户ID
		$money  结算金额
		$shop_id  结算商家ID
		$order_id  结算订单ID
	**
	***
	*/
	public function getProit($user_id,$type,$money,$shop_id,$order_id)
	{
		$CONFIG = D('Setting')->fetchAll();
		$proit = $CONFIG['site']['proit'];
		switch ($type) {
			case 'goods':
				$goods = D('Ordergoods')->where(['order_id'=>$order_id])->select();
				foreach ($goods as $key => $value) {
					$proit_f = D('Goods')->where(['goods_id'=>$value['goods_id']])->find();
					$proitAll+=$proit_f['product_fen'];
				}
				$newProit = $proitAll + $proit;
				$type = "商城";
				break;
			case 'edu':
				$goods = D('Eduorder')->where(['order_id'=>$order_id])->find();
				$proit_f = D('Edu')->where(['edu_id'=>$goods['edu_id']])->find();
				$newProit = $proit_f['product_fen'] + $proit;
				$type = "教育";
				break;
			case 'farm':
				$goods = D('Farmorder')->where(['order_id'=>$order_id])->find();
				$proit_f = D('Farm')->where(['farm_id'=>$goods['farm_id']])->find();
				$newProit = $proit_f['product_fen'] + $proit;
				$type = "农家乐";
				break;
			case 'appoint':
				$goods = D('Appointorder')->where(['order_id'=>$order_id])->find();
				$proit_f = D('Appoint')->where(['appoint_id'=>$goods['appoint_id']])->find();
				$newProit = $proit_f['product_fen'] + $proit;
				$type = "家政";
				break;
			case 'ktv':
				$goods = D('Ktvorder')->where(['order_id'=>$order_id])->find();
				$proit_f = D('Ktvroom')->where(['room_id'=>$goods['room_id']])->find();
				$newProit = $proit_f['product_fen'] + $proit;
				$type = "KTV";
				break;
			case 'hotel':
				$goods = D('Hotelorder')->where(['order_id'=>$order_id])->find();
				$proit_f = D('Hotel')->where(['room_id'=>$goods['room_id']])->find();
				$newProit = $proit_f['product_fen'] + $proit;
				$type = "酒店";
				break;
		}
		if($money <0 ){
			return false;
		}else{
			$give_money = $money* $newProit;
		}
		$users = D('Users')->where(['user_id'=>$user_id])->find();
		if($users['fuid1']){
			$intro = "您的推荐用户".$users['nickname']."在".$type."下单消费，您收到商家给您发放的推荐佣金".$give_money/2;
			$this->addMoney($users['fuid1'],$give_money、2,$intro);
			$introA = $users['nickname']."下单消费".$type."将从您的商户资金扣除商品分成佣金".$give_money/2;
			$this->addGold($shop_id,-$give_money/2,$introA);
		}
		if($users['fuid2']){
			$intro = "您的推荐用户".$users['nickname']."在".$type."下单消费，您收到商家给您发放的推荐佣金".$give_money/2;
			$this->addMoney($users['fuid1'],$give_money/2,$intro);
			$introA = $users['nickname']."下单消费".$type."将从您的商户资金扣除商品分成佣金".$give_money/2;
			$this->addGold($shop_id,-$give_money/2,$introA);
		}
	}
	///用户控制
    public function hongbaos($uid,$order_id,$envelope_id){

        if(!empty($envelope_id)){

            $envelope=D('UserEnvelope')->where(array('user_envelope_id'=>$envelope_id))->find();
            if(!empty($envelope['shop_id'])){
                D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
                $arr=array(
                    'user_id'=>$uid,
                    'envelope'=>$envelope['envelope'],
                    'intro'=>'使用商家红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                );
            }else{
                $ss =$envelope['num']-1;
                D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('num'=>$ss));
                $cha=D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->find();

                if($cha['num']==0){
                    D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
                }
                $arr=array(
                    'user_id'=>$uid,
                    'envelope'=>$envelope['envelope'],
                    'intro'=>'使用通用红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                    'create_time' => NOW_TIME,
                    'create_ip' => get_client_ip(),
                );
            }
            D('UserEnvelopeLogs')->add($arr);
        }
        return $arr;
    }


 //商家充值资金
    public function save_capital($log_id)
    {
        $detail = M('PaymentLogs')->where(array('log_id'=>$log_id))->find();
        // print_r($detail);die;
        $shop = $this->where(['user_id'=>$detail['user_id']])->find();
        // print_r($shop);die;
        if (!empty($shop)) {
            // if (M('PaymentLogs')->where(array('log_id'=>$log_id))->save(array('log_id' => $log_id, 'order_status' => '1'))){
            //$shop['gold'] +=$detail['need_pay'];
            //D('Users')->where(array('user_id'=>$shop['user_id']))->save(['gold'=>$shop['gold']]);
            D('Users')->addGold($shop['user_id'],$detail['need_pay'],'充值商户资金');
            return TRUE;
            // }
        }else{
            return TRUE;
            //由于支付回调，直接忽略报错 return false;
        }
    }
	
	//商家广告积分
	public function save_jifen($log_id){
		$CONFIG = D('Setting')->fetchAll();
		
		$jifen=$CONFIG['cash']['return_recharge_jifen'];
		//var_dump($jifen);die;
		$detail = M('PaymentLogs')->where(array('log_id'=>$log_id))->find();
        $shop = $this->where(['user_id'=>$detail['user_id']])->find();
		$sum=($detail['need_pay'])*$jifen;

        if (!empty($shop)) {
            $shop['shopintegral'] +=$sum;
            D('Users')->addShopIntegra($shop['user_id'],$sum,'充值广告积分');
            D('Users')->where(array('user_id'=>$shop['user_id']))->save(['shopintegral'=>$shop['shopintegral']]);
            return TRUE;
            // }
        }else{
            return TRUE;
            //由于支付回调，直接忽略报错 return false;
        }
		
	}

	//用户兑换商品
    public function save_exchange($log_id){
        $detail = M('PaymentLogs')->where(array('log_id'=>$log_id))->find();
        $order=D('Integralorder')->where(array('order_id'=>$detail['order_id']))->find();
        if(D('Users')->addIntegral($detail['user_id'],-$order['use_integral'],'兑换积分产品')){
            return TRUE;

        }else{
            return TRUE;
            //由于支付回调，直接忽略报错 return false;
        }
    }
}