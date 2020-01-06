<?php
class ErrandsAction extends CommonAction {

	//跑腿人员提现
	public function index(){
		$start =date('Y-m-d', mktime(0,0,0,date('m')-1,$t,date('Y')));
		$end =date('Y-m-d', mktime(0,0,0,date('m')-1,1,date('Y')));
        $Users = D('Users');

        if(!$detail = $Users->find($this->uid)){
			$this->error('会员信息不存在');
		}elseif($detail['is_lock'] == 1){
			$this->error('您的账户已被锁，暂时无法提现');
		}
		
		$Connect = D('Connect')->where(array('uid' => $this->uid,'type'=>weixin,))->find();

		
        if(IS_POST){
            $errandsmoney = abs((float) ($_POST['errandsmoney']));
            if($errandsmoney == 0) {
                $this->tuMsg('提现金额不合法');
            }
            if($errandsmoney > $detail['errandsmoney'] || $detail['errandsmoney'] == 0) {
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
       
            $arr = array();
            $arr['user_id'] = $this->uid;
            $arr['apply_money'] = $errandsmoney; //申请提现的金额 @author pingdan
            $arr['errandsmoney'] = $errandsmoney;
			//$arr['commission'] = $commission;
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
          
			if($cash_id = D('Errands')->add($arr)){
			//	if($Users->addMoney($detail['user_id'], -$money,$intro)){
					D('Usersex')->save($data);
					D('Weixintmpl')->weixin_cash_user($this->uid,1);//申请提现：1会员申请，2商家同意，3商家拒绝
					$this->tuMsg('申请成功，请等待管理员审核', U('errands/cashlog'));
			//	}else{
			//		$this->tuMsg('抱歉，提现扣余额失败');
			//	}
			}else{
				$this->tuMsg('抱歉，提现操作失败');
			}	
           
        }
		$this->assign('connect', $Connect);
        $this->assign('errandsmoney', $detail['errandsmoney']);
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
	
	//提现日志
	public function cashlog(){

		$this->display();
	}

	//日志显示
	public function cashlogloaddata(){
 		$Userscash = D('Errands');
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



}