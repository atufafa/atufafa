<?php
class StoreproductModel extends CommonModel{
    protected $pk = 'product_id';
    protected $tableName = 'store_product';
	
	
		
	public function gauging_tableware_price($tableware_price,$settlement_price){
		$config = D('Setting')->fetchAll();
		if($config['store']['tableware_price_max']){
			if($tableware_price > $config['store']['tableware_price_max']){
			   $this->error = '餐具价格最高不能高于'.$config['store']['tableware_price_max'].'元';
				return false;
			}
		}
		if($config['store']['tableware_price_mini']){
			if($tableware_price < $config['store']['tableware_price_mini']){
			   $this->error = '餐具价格最低不能低于'.$config['store']['tableware_price_mini'].'元';
				return false;
			}
		}
		
		if($config['store']['tableware_price_max'] || $config['store']['tableware_price_mini']){
			if($tableware_price >= $settlement_price){
				$this->error = '餐具价格不能大于等于结算价：【'.$settlement_price.'】元';
				return false;
			}
		}
		return true;
	}
	
	
	
	
	
	
}