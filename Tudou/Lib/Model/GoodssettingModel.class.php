<?php

class GoodssettingModel extends CommonModel{
    protected $pk   = 'id';
    protected $tableName =  'jingjia';
	
	protected $_validate = array(
        array(),
        array(),
        array()
    );
	
	public function getError(){
        return $this->error;
    }

    public function _format($data){
        $data['save'] =  round(($data['price'] - $data['mall_price']),2);
        $data['price'] = round($data['price'],2);
		//多属性开始
		$data['mobile_fan'] = round($data['mobile_fan'],2);
		//多属性结束
        $data['mall_price'] = round($data['mall_price'],2); 
        $data['settlement_price'] = round($data['settlement_price'],2); 
        $data['commission'] = round($data['commission'],2); 
        $data['discount'] = round($data['mall_price'] * 10 / $data['price'],1);
        return $data;
    }
	
}