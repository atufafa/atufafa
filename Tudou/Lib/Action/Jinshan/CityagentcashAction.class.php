<?php

class CityagentcashAction extends CommonAction{
	
    public function index(){
        $Userscash = D('Userscash');
        import('ORG.Util.Page');
        $list = M("cityagent_cash")->select();
        foreach($list as &$v){
            $mobj = M('users')->field('account')->where(array('user_id'=>$v['user_id']))->find();
            $cobj = M('city_agents')->field('agent_name')->where(array('agent_id'=>$v['agent_id']))->find();
            $v['account'] = $mobj['account'];
            $v['agent_name'] = $cobj['agent_name']; 
        }
		$this->assign('user_cash', round($user_cash = $Userscash->where(array('type' => user,'status' =>1))->sum('money'),2));
		$this->assign('user_cash_commission', round($user_cash_commission = $Userscash->where(array('type' => user,'status' =>1))->sum('commission'),2));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
	
	//微信提现
    public function weixin_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Userscash');
        $cash_id = (int) $cash_id;
		$detail = $obj->find($cash_id);
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0){
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
				if(false == $obj->weixinUserCach($cash_id,1)){//微信提现逻辑封装
					$this->tuError($obj->getError());
				}else{
					
						$obj->save($data);
						D('Weixintmpl')->weixin_cash_user($detail['user_id'],1,$detail['type']);//申请提现：1会员申请，2商家同意，3商家拒绝
                  
                  		//后台提现转账
                        $commission = $detail['commission'];
                        if(!empty($commission)){
                          $intro = '您申请提现，扣款'.round($money,2).'元，其中手续费：'.round($commission,2).'元';
                        }else{
                            $intro = '您申请提现，扣款'.round($money,2).'元';
                        }
                        $money = $detail['money'];
                  $Users = D('Users');
                        $Users->addMoney($detail['user_id'], -($money+$commission),$intro);
                  
						if($detail['type'] == shop){
							$this->tuSuccess('商家提现操作成功', U('usercash/gold'));	
						}else{
							$this->tuSuccess('会员提现操作成功', U('usercash/index'));	
						}
					
				}
            }else{
                $this->tuError('当前订单状态不正确');
			}
	    }else{
			$this->tuError('没找到对应的提现订单');
		}
    }
	
	
	
	//支付宝提现
    public function alipay_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
		$detail = D('Userscash')->find($cash_id);
		if($detail['status'] == 0){
			$data = array();
            $data['cash_id'] = $cash_id;
            $data['status'] = $status;
         
		/*	if(false == D('Userscash')->alipayUserCach($cash_id,1)){//支付宝提现逻辑封装
				$this->tuError(D('Userscash')->getError());
			}else{ */
              
              
				D('Userscash')->save($data);
              
             //后台提现转账
              $commission = $detail['commission'];
              if(!empty($commission)){
                $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元，其中手续费：'.round($commission,2).'元';
              }else{
                $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元';
              }
             $money = $detail['money'];
              $Users = D('Users');
              $Users->addMoney($detail['user_id'], -($money+$commission),$intro);
              
				D('Weixintmpl')->weixin_cash_user($detail['user_id'],1,$detail['type']);//申请提现：1会员申请，2商家同意，3商家拒绝
				if($detail['type'] == shop){
					$this->tuSuccess('商家支付宝提现操作成功', U('usercash/gold'));	
				}else{
					$this->tuSuccess('会员支付宝提现操作成功', U('usercash/index'));	
				}
		//	}
		}else{
            $this->tuError('当前订单状态不正确');
		}
    }
	
	
	
	//银行卡提现
	public function bank_audit($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Userscash');
		$cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if($obj->save($data)){
                  //后台提现转账
                  $commission = $detail['commission'];
                  if(!empty($commission)){
                    $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元，其中手续费：'.round($commission,2).'元，' . '实际到账' . round($detail['money'],2) . '元';
                  }else{
                    $intro = '您申请提现，扣款'.round($money,2).'元';
                  }
                  $money = $detail['money'];
                  $Users = D('Users');
                  $Users->addMoney($detail['user_id'], -($money+$commission),$intro);
                  
					D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                	$this->tuSuccess('操作成功', U('usercash/index'));
				}else{
					$this->tuError('更新数据库失败');
				}
            } else {
                $this->tuError('请不要重复操作');
            }
			
		}else{
			$this->tuError('没找到对应的提现订单');
		}
    }
		
		
		
	//商户微信提现
	public function weixin_audit_gold($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Userscash');
        $cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
				if(false == $obj-> weixinUserCach($cash_id,2)) {//微信提现逻辑封装，1会员，2商家
					$this->tuError($obj->getError());
				}else{
					if($obj->save($data)){
                      //后台提现转账
                        $commission = $detail['commission'];
                        if(!empty($commission)){
                          $intro = '您申请提现，扣款'.round($detail['apply_money'],2).'元，其中手续费：'.round($commission,2).'元，' . '实际到账' . round($detail['money'],2) . '元';
                        }else{
                            $intro = '您申请提现，扣款'.round($money,2).'元';
                        }
                        $money = $detail['money'];
                      $Users = D('Users');
                        $Users->addMoney($detail['user_id'], -($money+$commission),$intro);
                      
                      
						D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
						$this->tuSuccess('操作成功', U('usercash/gold'));
					}else{
						$this->tuError('请不要重复操作');
					}
				}
			}else{
				$this->tuError('更新数据库失败');
			}
	    }else{
			$this->tuError('没找到对应的提现订单');
		}
    }
	
	
	
	//商户银行卡提现
	public function bank_audit_gold($cash_id = 0, $status = 0){
        if(!$status){
            $this->tuError('参数错误');
        }
        $obj = D('Userscash');
		$cash_id = (int) $cash_id;
		if($detail = $obj->find($cash_id)){
			if ($detail['status'] == 0) {
                $data = array();
                $data['cash_id'] = $cash_id;
                $data['status'] = $status;
                if($obj->save($data)){
                  //后台提现转账
                  $commission = $detail['commission'];
                  $money = $detail['money']+$detail['commission'];
                  if(!empty($commission)){
                    $intro = '您申请提现，扣款'.round($apply_money,2).'元，其中手续费：'.round($commission,2).'元，' . '实际到账' . round($money,2) . '元';
                  }else{
                    $intro = '您申请提现，扣款'.round($money,2).'元';
                  }
                  $money = $detail['money'];
                  $Users = D('Users');
                  $Users->addMoney($detail['user_id'], - ($money+$commission),$intro);
                  
              
					D('Weixintmpl')->weixin_cash_user($detail['user_id'],1);//申请提现：1会员申请，2商家同意，3商家拒绝
                	$this->tuSuccess('操作成功', U('usercash/gold'));
				}else{
					$this->tuError('更新数据库失败');
				}
            } else {
                $this->tuError('请不要重复操作');
            }
			
		}else{
			$this->tuError('没找到对应的提现订单');
		}
    }

    //拒绝用户提现
    public function jujue(){
		$status = (int) $_POST['status'];
		$cash_id = (int) $_POST['cash_id'];
        $value = $this->_param('value', 'htmlspecialchars');
        if(empty($value)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝理由请填写'));
        }
        if(empty($cash_id)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => 'ID错误'));
        }
		if(!($detail = D('Userscash')->find($cash_id))){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '提现订单详情错误'));
        }
		if($detail['status'] != 0){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝状态错误'));
        }
        if($status == 2){
            D('Users')->addMoney($detail['user_id'], $detail['money'] + $detail['commission'], '提现ID【'.$cash_id.'】会员申请提现拒绝退款，理由【'.$value.'】');
			if(D('Userscash')->save(array('cash_id' => $cash_id, 'status' => $status, 'reason' => $value))){
				D('Weixintmpl')->weixin_cash_user($detail['user_id'],3);
            	$this->ajaxReturn(array('status' => 'success', 'msg' => '拒绝退款操作成功', 'url' => U('usercash/index')));
			}else{
				$this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝失败'));
			}
        }else{
			$this->ajaxReturn(array('status' => 'error', 'msg' => '提现状态不正确'));
		}
	
    }
    //拒绝商家提现
    public function jujue_gold(){
		$status = (int) $_POST['status'];
		$cash_id = (int) $_POST['cash_id'];
        $value = $this->_param('value', 'htmlspecialchars');
        if(empty($value)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝理由请填写'));
        }
        if(empty($cash_id)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => 'ID错误'));
        }
		if(!($detail = D('Userscash')->find($cash_id))){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '提现订单详情错误'));
        }
		if($detail['status'] != 0){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝状态错误'));
        }
        if($status == 2){
            D('Users')->addGold($detail['user_id'], $detail['gold'] + $detail['commission'], '提现ID【'.$cash_id.'】商家申请提现拒绝退款，理由【'.$value.'】');
			if(D('Userscash')->save(array('cash_id' => $cash_id, 'status' => $status, 'reason' => $value))){
				D('Weixintmpl')->weixin_cash_user($detail['user_id'],3);
            	$this->ajaxReturn(array('status' => 'success', 'msg' => '拒绝退款操作成功', 'url' => U('usercash/gold')));
			}else{
				$this->ajaxReturn(array('status' => 'error', 'msg' => '拒绝失败'));
			}
        }else{
			$this->ajaxReturn(array('status' => 'error', 'msg' => '提现状态不正确'));
		}
    }
   
}