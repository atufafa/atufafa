<?php
class  DecorateHonorModel extends CommonModel{

    protected $pk   = 'id';
    protected $tableName =  'decorate_honor';

    public  function upload($shop_id,$photos){
        $this->delete(array("where"=>array('shop_id'=>$shop_id)));
        foreach($photos as $val){
            $this->add(array(
                'shop_id' => $shop_id,
                'honorphoto' => htmlspecialchars($val)
            ));
        }
        return true;
    }


    public function getPics($shop_id){
        $shop_id = (int) $shop_id;
        return $this->where(array('shop_id'=>$shop_id))->select();
    }

}