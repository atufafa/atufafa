<?php
class ExchangeSpecModel extends CommonModel {

    protected $pk = 'id';
    protected $tableName = 'tp_spec';
	
	//判断规格下面
    public function judge_goods_item($id){
	   $count = M('ExchangeSpecItem')->where(array('spec_id'=>$id))->count();  
	   return $count;
    } 

}
