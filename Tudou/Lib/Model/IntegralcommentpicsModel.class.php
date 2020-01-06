<?php
class IntegralcommentpicsModel extends CommonModel
{
    protected $pk = 'pic_id';
    protected $tableName = 'integral_comment_pics';


    public function upload($exchange_id, $photos,$goods_id)
    {
        $exchange_id = (int) $exchange_id;
        $this->delete(array("where" => array('order_id' => $exchange_id)));
        foreach ($photos as $val) {
            $this->add(array('pic' => $val, 'order_id' => $exchange_id,'goods_id'=>$goods_id));
        }
        return true;
    }
    public function getPics($exchange_id)
    {
        $exchange_id = (int) $exchange_id;
        return $this->where(array('order_id' => $exchange_id))->select();
    }

}