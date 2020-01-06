<?php
class ZeroelementspecModel extends CommonModel {

    protected $pk = 'id';
    protected $tableName = 'zero_element_spec';

    //判断规格下面
    public function judge_goods_item($id){
        $count = D('Zeroelementspecitem')->where(array('spec_id'=>$id))->count();
        return $count;
    }

}