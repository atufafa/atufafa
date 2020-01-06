<?php
class CashAction extends CommonAction{
    public function index(){
		if($this->_CONFIG['cash']['is_cash'] !=1){
			$this->error('网站暂时没开启提现功能，请联系管理员');
		}
	/*	if (false == D('Userscash')->check_cash_addtime($this->uid,1)){
			$this->error('请不要频繁操作');
		}*/
		$start =date('Y-m-d', mktime(0,0,0,date('m')-1,$t,date('Y')));
		$end =date('Y-m-d', mktime(0,0,0,date('m')-1,1,date('Y')));
        $Users = D('Users');
        if(!$detail = $Users->find($this->uid)){
			$this->error('会员信息不存在');
		}elseif($detail['is_lock'] == 1){
			$this->error('您的账户已被锁，暂时无法提现');
		}

         //判断是不是配送员提现
        if($deliverys=D('Delivery')->where(array('user_id'=>$detail['user_id'],'level'=>1))->find()){
            $cash_money = $this->_CONFIG['cash']['delivery'];
            $cash_money_big = $this->_CONFIG['cash']['delivery_big'];
            $this->assign('deliverys',$deliverys['user_id']);
           
        //判断是不是跑腿提现   
        }elseif($running=D('Delivery')->where(array('user_id'=>$detail['user_id'],'level'=>2))->find()){
             $cash_money = $this->_CONFIG['cash']['errands'];
             $cash_money_big = $this->_CONFIG['cash']['errands_big'];
             $this->assign('running',$running['user_id']); 
             
        //判断是不是配送管理员提现  
        }elseif($deliveryadmins=D('Applicationmanagement')->where(array('user_id'=>$detail['user_id']))->find()){
            $this->assign('deliveryadmins',$deliveryadmins['user_id']);
             $cash_money = $this->_CONFIG['cash']['deliveryadmin'];
             $cash_money_big = $this->_CONFIG['cash']['deliveryadmin_big'];
             
        //判断是不是司机提现
        }elseif($vehicle=D('Userspinche')->where(array('user_id'=>$detail['user_id']))->find()){
            $this->assign('vehicle',$vehicle['user_id']);
            $cash_money = $this->_CONFIG['cash']['driver'];
            $cash_money_big = $this->_CONFIG['cash']['driver_big'];
        //判断是不是城市代理提现
        }elseif($agent=D('UsersAgentApply')->where(array('user_id'=>$detail['user_id'],'level'=>1))->find()){
            $this->assign('agent',$agent['user_id']);
            $cash_money = $this->_CONFIG['cash']['agent'];
            $cash_money_big = $this->_CONFIG['cash']['agent_big'];
        //判断是不是城市合伙人提现
        }elseif($partnership=D('UsersAgentApply')->where(array('user_id'=>$detail['user_id'],'level'=>2))->find()){
            $this->assign('partnership',$partnership['user_id']);
            $cash_money = $this->_CONFIG['cash']['partnership'];
            $cash_money_big = $this->_CONFIG['cash']['partnership_big'];
        }else{


		$Connect = D('Connect')->where(array('uid' => $this->uid,'type'=>weixin,))->find();
        $shop = D('Shop')->where(array('user_id' => $this->uid))->find();
		
        if($shop == ''){
            $cash_money = $this->_CONFIG['cash']['user'];
            $cash_money_big = $this->_CONFIG['cash']['user_big'];
        }elseif($shop['is_renzheng'] == 0){
            $cash_money = $this->_CONFIG['cash']['shop'];
            $cash_money_big = $this->_CONFIG['cash']['shop_big'];
        }elseif($shop['is_renzheng'] == 1){
            $cash_money = $this->_CONFIG['cash']['renzheng_shop'];
            $cash_money_big = $this->_CONFIG['cash']['renzheng_shop_big'];
        }else{
            $cash_money = $this->_CONFIG['cash']['user'];
            $cash_money_big = $this->_CONFIG['cash']['user_big'];
        }
        }

        $this->assign('cash_money', $cash_money);
        $this->assign('cash_money_big', $cash_money_big);


        if(IS_POST){

            //判断是不是配送员提现
            if($deliverys=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>1))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['frequency2'];
                //判断是不是跑腿提现
            }elseif($running=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>2))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['frequency2'];
                //判断是不是配送管理员提现
            }elseif($deliveryadmins=D('Applicationmanagement')->where(array('user_id'=>$this->uid))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['deliveryadminfrequency2'];
                //判断是不是司机提现
            }elseif($vehicle=D('Userspinche')->where(array('user_id'=>$this->uid))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['frequency4'];
                //判断是不是城市代理提现
            }elseif($agent=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>1))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['reflect'];
                //判断是不是城市合伙人提现
            }elseif($partnership=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>2))->find()){
                $cash_money_bigs = $this->_CONFIG['cash']['reflect1'];
            }else{
                $cash_money_bigs = $this->_CONFIG['cash']['frequency1'];
            }

        	$where = 'from_unixtime(addtime,"%Y-%m") =DATE_FORMAT(now( ) , "%Y-%m" ) and user_id='.$this->uid;
            $count=D('Userscash')->where($where)->count();

        	if($count>=$cash_money_bigs){
        		$this->tuMsg('本月可提现次数不足');
        	}
            $money = abs((float) ($_POST['money']));
            if($money == 0) {
                $this->tuMsg('提现金额不合法');
            }
            if($money < $cash_money){
                $this->tuMsg('提现金额小于最低提现额度');
            }
//            if($money > $cash_money_big){
//                $this->tuMsg('您单笔最多能提现' . $cash_money_big . '元');
//            }
            if($money > $detail['money'] || $detail['money'] == 0) {
                $this->tuMsg('余额不足，无法提现');
            }
			
			
			if(!($code = $this->_post('code'))){
				$this->tuMsg('请选择提现方式');
			}
			
			if($code == 'bank'){
				if(!($data['bank_name'] = htmlspecialchars($_POST['bank_name'])) || $data['bank_name'] =='请输入开户银行'){
					$this->tuMsg('开户行不能为空');
				}
				if(!($data['bank_num'] = htmlspecialchars($_POST['bank_num']))){
					$this->tuMsg('银行账号不能为空');
				}
				if(!is_numeric($data['bank_num'])){
					$this->tuMsg('银行账号只能为数字');
				}
				if(strlen($data['bank_num']) < 15){
					$this->tuMsg('银行账号格式不正确');
				}
				if(!($data['bank_realname'] = htmlspecialchars($_POST['bank_realname']) || $data['bank_realname'] =='输入开户名' || $data['bank_realname'] ==1)){
					$this->tuMsg('开户姓名不能为空');
				}
				$data['bank_branch'] = htmlspecialchars($_POST['bank_branch']);
				
			}

			if(empty($Connect['open_id'])){
				if($code == weixin){
					$this->tuMsg('您非微信登录，暂时不能选择微信提现方式');
				}
			}
			
			if($code == weixin){
				 if (!($data['re_user_name'] = htmlspecialchars($_POST['re_user_name'])) || $_POST['re_user_name'] =='') {
					$this->tuMsg('请填写真实姓名');
				}
			} 
			// print_r($_POST);die;
			if (!($data['re_user_name'] = htmlspecialchars($_POST['re_user_name'])) || $_POST['re_user_name'] =='') {
					$this->tuMsg('请填写真实姓名');
				}
			
			if($code == 'alipay'){
				if(!($data['alipay_account'] = htmlspecialchars($_POST['alipay_account']))){
					$this->tuMsg('支付宝账户');
				}
				if(!($data['alipay_real_name'] = htmlspecialchars($_POST['alipay_real_name']))){
					$this->tuMsg('支付宝真实姓名不能为空');
				}
			}
			
            $data['user_id'] = $this->uid;
			

         //判断是不是配送员提现
        if($deliverys=D('Delivery')->where(array('user_id'=>$detail['user_id'],'level'=>1))->find()){
            
            if(!empty($this->_CONFIG['cash']['delivery_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['delivery_cash_commission']/100)));
            }

        //判断是不是跑腿提现   
        }elseif($running=D('Delivery')->where(array('user_id'=>$detail['user_id'],'level'=>2))->find()){
            
            if(!empty($this->_CONFIG['cash']['errands_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['errands_cash_commission']/100)));
            }

        //判断是不是配送管理员提现  
        }elseif($deliveryadmins=D('Applicationmanagement')->where(array('user_id'=>$detail['user_id']))->find()){
           
           if(!empty($this->_CONFIG['cash']['deliveryadmin_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['deliveryadmin_cash_commission']/100)));
            }

        //判断是不是司机提现
        }elseif($vehicle=D('Userspinche')->where(array('user_id'=>$detail['user_id']))->find()){

            if(!empty($this->_CONFIG['cash']['driver_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['driver_cash_commission']/100)));
            }
        //判断是不是城市代理提现 
        }elseif($agent=D('UsersAgentApply')->where(array('user_id'=>$detail['user_id'],'level'=>1))->find()){

            if(!empty($this->_CONFIG['cash']['agent_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['agent_cash_commission']/100)));
            }
        //判断是不是城市合伙人提现
        }elseif($partnership=D('UsersAgentApply')->where(array('user_id'=>$detail['user_id'],'level'=>2))->find()){

            if(!empty($this->_CONFIG['cash']['partnership_cash_commission'])){
                $commission = intval(($money*($this->_CONFIG['cash']['partnership_cash_commission']/100)));
            }
        //普通会员提现
        }else{

			if(!empty($this->_CONFIG['cash']['user_cash_commission'])){
				$commission = intval(($money*($this->_CONFIG['cash']['user_cash_commission']/100)));
			}
        }
       
            $arr = array();
            $arr['user_id'] = $this->uid;
            $arr['apply_money'] = $money; //申请提现的金额 @author pingdan
            $arr['money'] = $money - $commission;
			$arr['commission'] = $commission;
            $arr['type'] = user;
            $arr['addtime'] = NOW_TIME;
            $arr['account'] = $detail['account'];
            $arr['bank_name'] = $data['bank_name'];
            $arr['bank_num'] = $data['bank_num'];
            $arr['bank_realname'] = $data['bank_realname'];
            $arr['bank_branch'] = $data['bank_branch'];
			$arr['alipay_account'] = $data['alipay_account'];
			$arr['alipay_real_name'] = $data['alipay_real_name'];
			$arr['re_user_name'] = $_POST['re_user_name'];
			$arr['code'] = $code;
            //var_dump($money,$commission);die;
            $shop = D('Shop')->where("user_id=".$this->uid)->find();
            if($shop){
              $arr['shop_id'] = $shop['shop_id'];
            }
			
			if(!empty($commission)){
				$intro = '您申请提现，扣款'.round($money,2).'元，其中手续费：'.round($commission,2).'元';
			}else{
				$intro = '您申请提现，扣款'.round($money,2).'元';
			}
			if($cash_id = D('Userscash')->add($arr)){
			//	if($Users->addMoney($detail['user_id'], -$money,$intro)){
					D('Usersex')->save($data);
					D('Weixintmpl')->weixin_cash_user($this->uid,1);//申请提现：1会员申请，2商家同意，3商家拒绝
					$this->tuMsg('申请成功，请等待管理员审核', U('cash/cashlog'));
			//	}else{
			//		$this->tuMsg('抱歉，提现扣余额失败');
			//	}
			}else{
				$this->tuMsg('抱歉，提现操作失败');
			}	
           
        }
		$this->assign('cash_money', $cash_money);
        $this->assign('cash_money_big', $cash_money_big);
		$this->assign('connect', $Connect);
        $this->assign('money', $detail['money']);
		$this->assign('detail', $detail);
        $this->assign('info', D('Usersex')->getUserex($this->uid));
        $this->display();
    }
	
	//检测银行卡
	public function getbankinfo(){
		$card = I('card');
		if(!$card){
           $this->ajaxReturn(array('code' => '0', 'msg' => '请输入银行卡号'));
        }
		if(!is_numeric($card)){
			$this->ajaxReturn(array('code' => '0', 'msg' => '银行账号只能为数字'));
        }
		if(strlen($card) < 15) {
			$this->ajaxReturn(array('code' => '0', 'msg' => '银行账号格式不正确'));
		}
		$res = D('BankList')->getBankInfo($card);
		if($res){
			$this->ajaxReturn(array('code' => '1', 'msg' => $res));
		}else{
			$this->ajaxReturn(array('code' => '0', 'msg' => '操作错误'));
		}
    }
	
	
	
	public function cashlog(){
        $this->display();
    }
    public function cashlogloaddata(){
        $Userscash = D('Userscash');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'type' => user);
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //卖车返利充值日志
    public function vehicle()
    {
    	$this->display();
    }

    public function vehicleload(){

    	$log = D('Rebatelog');
    	
        import('ORG.Util.Page');
        $maps = array('user_id' =>$this->uid, 'type'=>vehicle);
        $count = $log->where($maps)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = $log->where($maps)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('lists', $lists);
        $this->assign('page', $show);
          $this->display();
    }

     //卖房返利充值日志
    public function room()
    {
    	$this->display();
    }

    public function roomload(){

    	$log = D('Rebatelog');
    	
        import('ORG.Util.Page');
        $maps = array('user_id' =>$this->uid, 'type'=>room);
        $count = $log->where($maps)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $lists = $log->where($maps)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('lists', $lists);
        $this->assign('page', $show);
          $this->display();
    }
}