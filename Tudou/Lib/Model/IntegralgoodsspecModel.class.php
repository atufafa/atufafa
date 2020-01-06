<?php
class IntegralgoodsspecModel extends CommonModel {

    protected $pk = 'id';
    protected $tableName = 'integral_goods_spec';

    //判断规格下面
    public function judge_goods_item($id){
        $count = D('Integralspecitem')->where(array('spec_id'=>$id))->count();
        return $count;
    }

}