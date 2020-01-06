<?php
class ExchangeOrderGoodsModel extends CommonModel {
    protected $pk = 'id';
    protected $tableName = 'exchange_order_goods';
	
	 protected $types = array(
        0 => '等待发货',
        1 => '已经捡货',
        8 => '已完成配送',
    );

    public function getType() {
        return $this->types;
    }
	
	
   //第一次更新商品运费
   public function calculation_express_price($uid,$kuaidi_id,$num,$goods_id,$pc_order) {
		$obj = D('Paddress');
		$addressCount = $obj->where(array('user_id' => $uid)) -> count();//统计客户的收货地址
		if ($addressCount == 0) {
			if($pc_order ==1){
				$this->error = '客户还没收货地址';
			   	return false;
			}else{
				$this->error = '客户还没收货地址';
			   	return false;
			}
		} else {
			$defaultCount = $obj->where(array('user_id' => $uid, 'default' =>1))->count();//统计默认地址
			if ($defaultCount == 0) {
				$detail = $obj->where(array('user_id' => $uid))->order("id desc")->find();//没有默认地址
			} else {
			    $detail = $obj->where(array('user_id' => $uid,'default' => 1))->find();//找到默认地址
			}
		}
	   return $this->replace_add_express_price($uid,$detail['id'],$num,$goods_id); //获得运费，会员id，地址id，商品数量，商品id
    }
	
	
	//更换地址更新商品运费
   public function replace_add_express_price($uid,$id,$num,$goods_id){
	   
	   $Paddress = D('Paddress')->where(array('user_id' => $uid, 'id' => $id))->find();
	   $detail = D('ExchangeGoods')->where(array('goods_id'=>$goods_id))->find();
	
	   
	   if($detail['is_reight'] ==1){
		   
		    $Pyunfeiprovinces = D('Pyunfeiprovinces')->where(array('province_id' => $Paddress['city_id'],'kuaidi_id'=>$detail['kuaidi_id']))->find();//找到
		
		    if($Pyunfeiprovinces && $Pyunfei = D('Pyunfei')->find($Pyunfeiprovinces['yunfei_id'])){
				
				 if($num == 1){
					$reduce = $detail['weight'] - 1000;
					if($reduce >= 1){//如果大于1KG，只收首重费，比如商品重量1200g-1000g=200g
						$weight = $detail['weight'] - 1000; //返回200g
					}else{
						$weight = 0; //返回0g
					}
				 }else{
					$weights = $num *($detail['weight']) - 1000;  //10*1200g-1000g=11000g
					if($weights > 0){
						$weight = $weights; //12000g-1000g=11000g//扣除首重，返回可数
					}else{
						$weight = 0; 
					}
				 }
				 
				 $price = $Pyunfei['shouzhong'] + (($weight * $Pyunfei['xuzhong'])/1000);//返回费用
				 return $price; 
			}else{
				return 0; //没找到运费
			}
	   }
	   return 0; //商品不开启配送费
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}