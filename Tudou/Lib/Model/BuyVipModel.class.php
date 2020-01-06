<?php
class BuyVipModel extends CommonModel{
    protected $pk = 'id';
    protected $tableName = 'buy_vip';

	//兑换卷值增减
    public function addExchange($id,$user_id, $num, $intro = ''){
        if ($this->updateCount($id, 'money', $num)) {
            return D('Buyviplog')->add(array(
				'user_id' => $user_id, 
				'money' => $num, 
				'intro' => $intro,
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			));
        }
        return false;
    }

    public function addvip($id,$user_id,$end_time,$rank_id,$money,$intro=''){
        $data=array(
            'user_id'=>$user_id,
            'create_time' =>NOW_TIME,
            'end_time'=>$end_time,
            'create_ip' => get_client_ip(),
            'rank_id'=>$rank_id,
            'money'=>$money,
            'reamk'=>$intro
        );
        if($this->add($data)){
           return true;
        }
        return false;
    }


	
}
