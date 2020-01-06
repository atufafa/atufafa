<?php


class FarmOrderModel extends CommonModel{
    
    protected $pk   = 'order_id';
    protected $tableName =  'farm_order';

    public function cancel($order_id){

        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            if($detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>-1))){
                if($detail['is_fan'] == 1){
                    D('Users')->addMoney($detail['user_id'],(int)$detail['amount'],'农家乐订单取消,ID:'.$order_id.'，返还余额');
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 5,$status = 11);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 5,$status = 11);
                }
                return true;
            }else{
                return false;
            }
            
        }  
    }
    
    //新版农家乐结算
    public function complete($order_id){
        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            if($detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }
			$farm = D('Farm')->where(array('farm_id'=>$detail['farm_id']))->find();
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>2))){
                if($detail['is_fan'] == 1){
					$info = '农家乐【'.$farm['farm_name'].'】订单ID：'.$order_id.'完成，结算金额：'.$detail['jiesuan_amount'].'元';
					D('Shopmoney')->insertData($order_id,$id ='0',$farm['shop_id'],$detail['jiesuan_amount'],$type ='farm',$info);//结算给商家
                    D('Users')->getProit($farm['user_id'],$type='farm',$detail['jiesuan_amount'],$farm['shop_id'],$order_id); //家政分销订单结算   ----新增
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 5,$status = 8);
					// D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 5,$status = 8);
					return true;
                 }
                return true;
            }else{
                return false;
            }
            
        }  
    }

    //红包获取部分
    public function GetuseEnvelope($uid,$shop_ids)
    {

        $shop=D('UserEnvelope')->where(array('shop_id'=>array('IN',$shop_ids),'user_id'=>$uid,'close'=>2,'is_use'=>0))->select();

        //判断是否有商家红包
        if(!empty($shop)){
            $map=array('user_id'=>$uid,'close'=>2,'shop_id'=>array('IN',$shop_ids),'is_use'=>0);

            $UsersEnvelope=D('UserEnvelope')->where($map)->select();
        }else{
            $map=array('user_id'=>$uid,'close'=>2,'shop_id'=>0,'is_use'=>0);
            $UsersEnvelope=D('UserEnvelope')->where($map)->select();
        }

        $useEnvelope = array();
        foreach ($UsersEnvelope as $key => $value) {
            $useEnvelope[$key]['type'] = $value['type'];
            $useEnvelope[$key]['envelope']+=$value['envelope'];
            $useEnvelope[$key]['useEnvelope_id'] = $value['user_envelope_id'];
        }

        //红包重组
        foreach ($useEnvelope as $k => $val) {
            switch ($val['type']) {
                case '1':
                    $val['type'] = '订单红包折扣';
                    break;

                case '2':
                    $val['type'] = '引流红包折扣';
                    break;

                case '3':
                    $val['type']='平台通用红包';
            }
            $useEnvelope[$k]['type'] = $val['type'];
        }
        // print_r($useEnvelope);die;
        return $useEnvelope;
    }
     
}