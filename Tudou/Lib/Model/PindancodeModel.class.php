<?php
class PindancodeModel extends CommonModel{
    protected $pk = 'code_id';
    protected $tableName = 'pindan_code';
    public function getCode(){
        $i = 0;
        while (true) {
            $i++;
            $code = rand_string(8, 1);
            $data = $this->find(array('where' => array('code' => $code)));
            if (empty($data)) {
                return $code;
            }
            if ($i > 20) {
                return $code;
            }
        }
    }
	//抢购劵验证封装函数
	public function saveShopMoney($data,$shop){
		 	$count = $this->where(array('order_id' => $data['order_id'], 'is_used' => 0))->count();
            if(!$count || $count <= 1) {
                D('Pindanorder')->save(array('order_id' => $data['order_id'], 'status' => 8));
            }
			
			if($data['real_integral'] > 0){
				$settlement_price = $data['settlement_price'] - $data['real_integral'];
				$intro = '分销商品订单号【'.$data['order_id'].'】结算价格 = 结算价【'.round($data['settlement_price'],2).'元】';
			}else{
				$settlement_price = $data['settlement_price'];
				$intro = '分销商品订单号【'.$data['order_id'].'】结算价格 = 结算价【'.round($data['settlement_price'],2).'元】';
			}
			if(!empty($data['price'])){
				D('Shopmoney')->insertData($data['order_id'],$data['code_id'],$data['shop_id'],$settlement_price,$type ='tuan',$intro);//结算给商家
				D('Userguidelogs')->AddMoney($data['shop_id'], $data['settlement_price'], $data['order_id'],$type = "tuan");//推荐分成
                
				$config = D('Setting')->fetchAll();//获取配置
				if(!empty($data['real_integral'])){
					if($config['integral']['tuan_return_integral'] == 1){
						D('Users')->return_integral($shop['user_id'], $data['real_integral'] , '抢购用户消费积分返还给商家');//抢购返还积分给商家用户
					}
				}
				//D('Sms')->pindan_user($data['code_id']);//短信通知
				D('Weixinmsg')->weixinTmplOrderMessage($data['order_id'],$cate = 1,$type = 4,$status = 8);//微信通知
				D('Weixinmsg')->weixinTmplOrderMessage($data['order_id'],$cate = 2,$type = 4,$status = 8);
				return true;	
		  }
		
	}
	
	
}