<?php
class CityorderModel extends CommonModel {
    protected $pk = 'order_id';
    protected $tableName = 'city_order';
	
	public function getError() {
        return $this->error;
    }
	//统计当前等级下面多少商家
	public function shop_pay_gradess($agent_id,$apply_id){
		$obj = D('Cityagent');
        $Shop = D('UsersAgentApply')->find($apply_id);
        //var_dump($apply_id);die;

		$users = D('Users')->find($Shop['user_id']);
		$shop_grade = $obj->find($agent_id);//准备购买的商家等级
		$old_shop_grade = $obj->find($Shop['agent_id']);//当前商家的等级
		if(empty($shop_grade)){
			$this->error = '您购买的等级不存在或者被删除了';
			return false;
		}elseif($Shop['agent_id'] == $agent_id){
			$this->error = '购买的等级跟您的商家等级一致，无法购买';
			return false;
	    }elseif($old_shop_grade['orderby'] >= $shop_grade['orderby']){
			$this->error = '您不能降级，只能购买高权限的等级';
			return false;
		}elseif($users['money'] < $shop_grade['price']){
			$this->error = '您的会员余额不足，无法购买，请先到会员中心充值后购买';
			return false;
		}
		
		$order_id = $this->add(array(
			'apply_id' => $apply_id,
			'user_id' => $users['user_id'], 
			'agent_id' => $agent_id, 
			'money' => $shop_grade['price'], 
			'status' => 1,//
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip(), 
		));
		
	   if($order_id){
			if (false !== D('Users')->addMoney($users['user_id'], -$shop_grade['price'], '提升商家等级【' . $shop_grade['agent_name'] . '】扣费成功')) {
				D('UsersAgentApply')->save(array('apply_id' => $apply_id, 'agent_id' => $agent_id));
				//D('Userprofitlogs')->buy_shop_grade_profit_user($apply_id,$shop_grade['money'],$shop_grade['grade_name']);//商家购买等级对接三级分销
				return TRUE; 
			} else {
				$this->error = '扣费失败请重试';
				return false;
			}
		}else{
			$this->error = '订单处理非法错误，请稍后再试试';
			return false;
		}
    }

}
