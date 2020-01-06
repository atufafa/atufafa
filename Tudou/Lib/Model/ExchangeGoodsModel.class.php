<?php
class ExchangeGoodsModel extends CommonModel {

    protected $pk = 'goods_id';
    protected $tableName = 'exchange_goods';
	
   public function _format($data){

	$data['price'] = round($data['price'],2);

	return $data;
}

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

}
