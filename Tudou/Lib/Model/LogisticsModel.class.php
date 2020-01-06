<?php

class LogisticsModel extends CommonModel{
    protected $pk   = 'express_id';
    protected $tableName =  'logistics';
	
	//商城
	public function get_order_express($order_id){
		import('ORG.Util.Express');//引入类
			
		$config = D('Setting')->fetchAll();
			
			
		$express_obj = new Express();
		$express_obj->type = $config['config']['express_api_type'];//传入查询类型
		$express_obj->keys = $config['config']['express_api_key'];//传入KEY
		$express_obj->customer = $config['config']['express_api_customer'];//传入KEY
		$Order = D('Order')->where(array('order_id' => $order_id))->find();//订单
		$Logistics = D('Logistics')->where(array('express_id' => $Order['express_id']))->find();
		$express_obj->company = $Logistics['express_code'];//传入快递编号
		$express_obj->num = $Order['express_number'];//传入快递单号
		$data = $express_obj->getContent();//获取数组
		return $data;
	}
	
	//便利店
	public function get_store_express($order_id){
		import('ORG.Util.Express');//引入类
			
		$config = D('Setting')->fetchAll();
			
			
		$express_obj = new Express();
		$express_obj->type = $config['config']['express_api_type'];//传入查询类型
		$express_obj->keys = $config['config']['express_api_key'];//传入KEY
		$express_obj->customer = $config['config']['express_api_customer'];//传入KEY
		$Order = D('Storeorder')->where(array('order_id' => $order_id))->find();//订单
		$Logistics = D('Logistics')->where(array('express_id' => $Order['express_id']))->find();
		$express_obj->company = $Logistics['express_code'];//传入快递编号
		$express_obj->num = $Order['express_number'];//传入快递单号
		$data = $express_obj->getContent();//获取数组
		return $data;
	}
	
	//积分商城
    public function get_exchange_express($order_id){
        import('ORG.Util.Express');//引入类

        $config = D('Setting')->fetchAll();


        $express_obj = new Express();
        $express_obj->type = $config['config']['express_api_type'];//传入查询类型
        $express_obj->keys = $config['config']['express_api_key'];//传入KEY
        $express_obj->customer = $config['config']['express_api_customer'];//传入KEY
        $Order = D('Integralorder')->where(array('order_id' => $order_id))->find();//订单
        $Logistics = D('Logistics')->where(array('order_id' => $Order['order_id']))->find();
        $express_obj->company = $Logistics['express_code'];//传入快递编号
        $express_obj->num = $Order['express_number'];//传入快递单号
        $data = $express_obj->getContent();//获取数组
        return $data;
    }
	
	
	
	//菜市场
	public function get_market_express($order_id){
		import('ORG.Util.Express');//引入类
			
		$config = D('Setting')->fetchAll();
			
			
		$express_obj = new Express();
		$express_obj->type = $config['config']['express_api_type'];//传入查询类型
		$express_obj->keys = $config['config']['express_api_key'];//传入KEY
		$express_obj->customer = $config['config']['express_api_customer'];//传入KEY
		$Order = D('Marketorder')->where(array('order_id' => $order_id))->find();//订单
		$Logistics = D('Logistics')->where(array('express_id' => $Order['express_id']))->find();
		$express_obj->company = $Logistics['express_code'];//传入快递编号
		$express_obj->num = $Order['express_number'];//传入快递单号
		$data = $express_obj->getContent();//获取数组
		return $data;
	}
	
	
	//会员兑换礼品
	public function get_order_exchange($order_id){
		import('ORG.Util.Express');//引入类
			
		$config = D('Setting')->fetchAll();
			
			
		$express_obj = new Express();
		$express_obj->type = $config['config']['express_api_type'];//传入查询类型
		$express_obj->keys = $config['config']['express_api_key'];//传入KEY
		$express_obj->customer = $config['config']['express_api_customer'];//传入KEY
		$Order = D('ExchangeOrder')->where(array('order_id' => $order_id))->find();//订单
		$Logistics = D('Logistics')->where(array('express_id' => $Order['express_id']))->find();
		$express_obj->company = $Logistics['express_code'];//传入快递编号
		$express_obj->num = $Order['express_number'];//传入快递单号
		$data = $express_obj->getContent();//获取数组
		return $data;
	}
	
	
	
	public function getCompany()
	{
		$company = array(
			'0'=>array(
				'express_name'=>'邮政包裹',
				'express_code'=>'youzhengguonei'
			),
			'1'=>array(
				'express_name'=>'EMS',
				'express_code'=>'ems'
			),
			'2'=>array(
				'express_name'=>'顺丰',
				'express_code'=>'shunfeng'
			),
			'3'=>array(
				'express_name'=>'申通',
				'express_code'=>'shentong'
			),
			'4'=>array(
				'express_name'=>'圆通',
				'express_code'=>'youzhengguonei'
			),
			'5'=>array(
				'express_name'=>'中通',
				'express_code'=>'zhongtong'
			),
			'6'=>array(
				'express_name'=>'汇通',
				'express_code'=>'huitongkuaidi'
			),
			'7'=>array(
				'express_name'=>'韵达',
				'express_code'=>'yunda'
			),
			'8'=>array(
				'express_name'=>'天天',
				'express_code'=>'tiantian'
			),
			'9'=>array(
				'express_name'=>'德邦',
				'express_code'=>'debangwuliu'
			),
			'10'=>array(
				'express_name'=>'国通',
				'express_code'=>'guotongkuaidi'
			),
			'11'=>array(
				'express_name'=>'增益',
				'express_code'=>'zengyisudi'
			),
			'12'=>array(
				'express_name'=>'速尔',
				'express_code'=>'suer'
			),
			'13'=>array(
				'express_name'=>'中铁物流',
				'express_code'=>'ztky'
			),
			'14'=>array(
				'express_name'=>'中铁快运',
				'express_code'=>'zhongtiewuliu'
			),
			'15'=>array(
				'express_name'=>'能达',
				'express_code'=>'ganzhongnengda'
			),
			'16'=>array(
				'express_name'=>'优速',
				'express_code'=>'youshuwuliu'
			),
			'17'=>array(
				'express_name'=>'全峰',
				'express_code'=>'quanfengkuaidi'
			),
			'18'=>array(
				'express_name'=>'京东',
				'express_code'=>'jd'
			),
			'19'=>array(
				'express_name'=>'北京EMS',
				'express_code'=>'bjemstckj'
			)
		);
		return $company;
	}

}