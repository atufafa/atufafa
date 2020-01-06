<?php
class HotelorderModel extends CommonModel{
    protected $pk   = 'order_id';
    protected $tableName =  'hotel_order';
    
	
	public function getError() {
        return $this->error;
    }
	
    public function cancel($order_id){
        if(!$order_id = (int)$order_id){
            return false;
        }elseif(!$detail = $this->find($order_id)){
            return false;
        }else{
            if($detail['online_pay'] == 1&&$detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }
            $room = D('Hotelroom')->find($detail['room_id']);
            if(!$room['is_cancel']){
                return false;
            }
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>-1))){
                if($detail['is_fan'] == 1){
                    D('Users')->addMoney($detail['user_id'],(int)$detail['amount'],'酒店订单取消,ID:'.$order_id.'，返还余额');
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 6,$status = 11);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 6,$status = 11);
                }
                D('Hotelroom')->updateCount($detail['room_id'],'sku',$detail['num']);
                return true;
            }else{
                return false;
            }
            
        }  
    }
     
    public function plqx($hotel_id){
        if($hotel_id = (int)$hotel_id){
            $ntime = date('Y-m-d',NOW_TIME);
            $map['stime'] = array('LT',$ntime);
            $map['hotel_id'] = $hotel_id;
            $order = $this->where($map)->select();
            foreach ($order as $k=>$val){
                $this->cancel($val['order_id']);
            }
            return true;
        }else{
            return false;
        }
    }
	
	
    //酒店结算
    public function complete($order_id){
		$order_id = (int)$order_id;
		if(empty($order_id)){
			 $this->error = '必要的参数order_id没有传入';
			 return false;
		}
		$detail = $this->find($order_id);
		if(!empty($detail)){
			$Hotel = D('Hotel')->find($detail['hotel_id']);
            if($detail['online_pay'] == 1&&$detail['order_status'] == 1){
                $detail['is_fan'] = 1;
            }
            $room = D('Hotelroom')->find($detail['room_id']);
			$shop = D('Shop')->find($detail['shop_id']);
			$commission = (int)(($detail['amount'] * $shop['commission']));
			//echo $commission;
			//19980
			//echo $shop['commission'];
			//1000
            //echo $detail['amount'];
            //1998
			if(!$commission){
				$this->error = '商户佣金设置不正确';
			   	return false;
			}
			$jiesuan_amount = $detail['amount'] - $commission;
			//echo $jiesuan_amount;
			//17982
            if(false !== D('Hotelorder')->save(array('order_id'=>$order_id,'order_status'=>2))){

				//?? 佣金比例1000
                //if($detail['is_fan'] == 1 && $jiesuan_amount > 1){
                if($detail['is_fan'] == 1){
					$info = '酒店订单号【'.$order_id.'】结算，房间名称【'.$room['title'].'】当前结算佣金比例【'.$shop['commission'].'%】订单总价【'.$detail['amount'].'】元';
					D('Shopmoney')->insertData($order_id,$id ='0',$Hotel['shop_id'],$jiesuan_amount,$type ='hotel',$info);//结算给商家
                    D('Users')->getProit($Hotel['user_id'],$type='hotel',$jiesuan_amount,$Hotel['shop_id'],$order_id); //酒店分销订单结算   ----新增
                    /*暂时屏蔽*/
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 6,$status = 8);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 6,$status = 8);
                }else{
					 $this->error = '订单状态不正确';
			   		 return false;
				}
            }else{
                $this->error = '更新酒店订单已完成数据库操作失败';
			   	return false;
            }
		}else{
			$this->error = '没有找到该订单详情';
			return false;
		}
    }  
	//酒店退款给用户封装
    public function hotel_refund_user($order_id){
		$order_id = (int)$order_id;
		if(empty($order_id)){
			 return false;
		}
		$detail = $this->find($order_id);
		if(!empty($detail)){
            if(false !== $this->save(array('order_id'=>$order_id,'order_status'=>4))){
				// D('Sms')->sms_hotel_refund_user($order_id);//酒店退款通知用户手机
				$info = '酒店订单号：【'.$order_id.'】，申请退款，退资金'.$detail['amount'].'元';
                D('Users')->addMoney($detail['user_id'], $detail['amount'], $info);//给用户增加金额
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 6,$status = 4);
			    // D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 6,$status = 4);
                return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
    }

    /**
     * 超时批量取消订单
     * @author pingdan <[<email address>]>
     * @param  array  $ids [description]
     * @return [type]      [description]
     */
    public function cancelByIds($ids = array()) {

        if (is_array($ids) && $ids) {
            foreach ($ids as $order_id) {
                $this->cancel($order_id);
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