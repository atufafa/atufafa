<?php
class DeliverypurchaseModel extends CommonModel
{

	  protected $pk = 'order_id';
      protected $tableName = 'delivery_purchase';
	
	public function getError() {
        return $this->error;
    }
	//统计当前等级下面多少管理
	public function shop_pay_grades($dj_id,$user_id){
		$obj = D('Deliveryadmin');
        $ss=D('Applicationmanagement')->where(array('user_id'=>$user_id))->find();
		$users = D('Users')->find($ss['user_id']);
		//var_dump($users);die;
		$shop_grade = $obj->find($dj_id);//准备购买的商家等级 
		$old_shop_grade = $obj->find($ss['dj_id']);//当前商家的等级
		//var_dump($dj_id);die;
		if(empty($shop_grade)){
			$this->error = '您购买的等级不存在或者被删除了';
			return false;
		}elseif($ss['dj_id'] == $dj_id){
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
			'user_id' => $user_id, 
			'dj_id' => $dj_id, 
			'money' => $shop_grade['price'], 
			'status' => 1,//
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip(), 
		));
		//var_dump($order_id);die;
	   if($order_id){
			if (false !== D('Users')->addMoney($users['user_id'], -$shop_grade['price'], '提升管理等级【' . $shop_grade['name'] . '】扣费成功')) {

				$mm=D('Applicationmanagement');
				$mm->where(array('user_id'=>$user_id))->save(array('dj_id' => $dj_id));
				//var_dump($mm);die;
			//echo $mm->getlastsql();die;
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