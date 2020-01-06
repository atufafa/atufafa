<?php
class PindanModel extends CommonModel{

    protected $pk = 'tuan_id';
    protected $tableName = 'pindan';
	
	
	public function getError() {
        return $this->error;
    }

     public function _format($data){
        $data['save'] = round(($data['price'] - $data['tuan_price']), 2);
        $data['price'] = round($data['price'], 2);
        $data['tuan_price'] = round($data['tuan_price'] , 2);
        $data['mobile_fan'] = round($data['mobile_fan'], 2);
        $data['settlement_price'] = round($data['settlement_price'], 2);
        $data['discount'] = round($data['tuan_price'] / $data['price'], 1);
        return $data;
    }
	
	 //��Ʒ�ƹ�
    public function top_time($goods_id,$check_price,$check_price_money,$end_time){
        $config = D('Setting')->fetchAll();
        $goods = $this->find($goods_id);
        $shop = D('Shop')->find($goods['shop_id']);
        if(!$shop){
            $this->error = 'û�ҵ��̼�';
            return false;
        }
        $Users = D('Users')->find($shop['user_id']);
        if(!$Users){
            $this->error = '��Ա״̬������';
            return false;
        }
        if(($goods['start_time'] +3600)>NOW_TIME){
            $min = 60 - (int)((NOW_TIME -$goods['start_time'] )/60);
            $this->error ='��ǰ�Գ��ۣ���'.$min.'���Ӻ��ٴγ���';
            return false;
        }
        if($check_price){
            if(($shop['bid_money'])<$check_price){
                $this->error = '���ľ������㣬���ȳ�ֵ�����';
                return false;
            }else{
                if(empty($check_price_money)){
                    $intro ='��Ʒ'.$goods['title'].'���뾺�����У������۵��ε��'.$check_price.'Ԫ';
                    $check_price_money = 0;
                }else{
                    $intro ='��Ʒ'.$goods['title'].'���뾺�����У������۵��ε��'.$check_price.'Ԫ,�޶�'.$check_price_money.'Ԫ';
                }
               
                D('PingdanShopJingjia')->add(array(
                    'shop_id' => $shop['shop_id'], 
                    'intro' => $intro, 
                    'create_time' => NOW_TIME, 
                    'get_ip' => get_client_ip(),
                    'check_price' => $check_price,
                    'check_money' => abs($check_price_money),
                    'type'=>1,
                    'goods_id'=>$goods_id
                ));
                D('Pindan')->where(['tuan_id'=>$goods_id])->save(['check_price'=>$check_price,'check_price_money'=>$check_price_money,'start_time'=>NOW_TIME,'end_time'=>NOW_TIME+86400,'is_tui'=>1]);
                return true;
            }   
        }else{
            $this->error = '�����뾺��';
            return false;
        }
   }
	
	
	  //���ۿۿ���߼�
    public function check_price($goods_id)
    {   
        $goods = D('Pindan')->find($goods_id);

        if($goods['check_price_money']>0){//����޶�


        }else{
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where= " goods_id =".$goods_id." and create_time between ".$beginToday." and ".$endToday." and type =2 and get_ip ='".get_client_ip()."'";
            $now_ip = D('PingdanShopJingjia')->where($where)->select();
            $count = count($now_ip);
            //��ѯIP����
            $limit = M('Jingjia')->where(['id'=>1])->find();
            if($limit['check_num']<$count){//����������ƣ����۷�
                //д���������
                D('PingdanShopJingjia')->add(array(
                    'check_price'=>0,
                    'get_ip'=>get_client_ip(),
                    'type'=>2,
                    'intro'=>'�����Ʒ'.$goods['title'].'�������۷Ѵ��������۷�',
                    'create_time'=>NOW_TIME,
                    'shop_id'=>$goods['shop_id'],
                    'goods_id'=>$goods_id
                ));
                //д����Ʒ����
                $check_num = $goods['check_num'] +1 ;
                D('Pindan')->where(['tuan_id'=>$goods_id])->save(array('check_num'=>$check_num));
            }else{
                $shop =D('Shop')->where(['shop_id'=>$goods['shop_id']])->find();
                if($shop['bid_money'] <$goods['check_price']){
                    //�������㣬�߳���������
                    D('Pindan')->where(['tuan_id'=>$goods_id])->save(['check_price'=>0,'is_tui'=>0]);
                }
                //�ۿ����
                $shop['bid_money'] -= $goods['check_price'];
                D('Shop')->where(['shop_id'=>$goods['shop_id']])->save(array('bid_money'=>$shop['bid_money']));
                //д���������
                D('PingdanShopJingjia')->add(array(
                    'check_price'=>$goods['check_price'],
                    'get_ip'=>get_client_ip(),
                    'type'=>2,
                    'intro'=>'�����Ʒ'.$goods['title'].'�����Ѿ��۽�'.$goods['check_price'],
                    'create_time'=>NOW_TIME,
                    'shop_id'=>$goods['shop_id'],
                    'goods_id'=>$goods_id
                ));
                //д����Ʒ����
                $check_num = $goods['check_num'] +1 ;
                D('Pindan')->where(['tuan_id'=>$goods_id])->save(array('check_money'=>($goods['check_money'] + $goods['check_price']),'check_num'=>$check_num));
            }
        }
    }
	
	
	
	
	
	
	
	
	
	
	
	
	
}