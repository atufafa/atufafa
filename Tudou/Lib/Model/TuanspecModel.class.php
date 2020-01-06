<?php
class TuanspecModel extends CommonModel {

    protected $pk = 'id';
    protected $tableName = 'tuan_spec';

    //判断规格下面
    public function judge_goods_item($id){
        $count = D('Tuanspecitem')->where(array('spec_id'=>$id))->count();
        return $count;
    }

}