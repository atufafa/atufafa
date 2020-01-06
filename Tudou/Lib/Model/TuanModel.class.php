<?php
class TuanModel extends CommonModel{

    protected $pk = 'tuan_id';
    protected $tableName = 'tuan';
	
	
	public function getError() {
        return $this->error;
    }
	
    public function _format($data){
        $data['save'] = round(($data['price'] - $data['tuan_price']) , 2);
        $data['price'] = round($data['price'], 2);
        $data['tuan_price'] = round($data['tuan_price'] , 2);
        $data['mobile_fan'] = round($data['mobile_fan'] , 2);
        $data['settlement_price'] = round($data['settlement_price'], 2);
        $data['discount'] = round($data['tuan_price'] * 10 / $data['price'], 1);
        return $data;
    }
    public function getParentsId($id){
        $data = $this->fetchAll();
        $parent_id = $data[$id]['parent_id'];
        $parent_id2 = $data[$parent_id]['parent_id'];
        if ($parent_id2 == 0) {
            return $parent_id;
        }
        return $parent_id2;
    }
	
	public function check_add_use_integral($use_integral,$settlement_price){
        $config = D('Setting')->fetchAll();
        $integral = $config['integral']['buy'];
		if($integral == 0){
			if ($use_integral % 100 != 0) {
				$this->error = '积分必须为100的倍数';
				return false;
			}
			if ($use_integral > $settlement_price) {
				$this->error = '积分兑换数量必须小于'.$settlement_price.','.'并是100的倍数';
				return false;
			}
		}elseif($integral == 10){
			if ($use_integral % 10 != 0) {
				$this->error = '积分必须为10的倍数';
			}
			if ($use_integral*10 > $settlement_price) {
				$this->error = '积分兑换数量必须小于'.($settlement_price/10).','.'并是10的倍数';
				return false;
			}
		}elseif($integral == 100){
			if ($use_integral % 1 != 0) {
				$this->error = '积分必须为1的倍数';
				return false;
			}
			
			if ($use_integral > $settlement_price) {
				$this->error = '积分兑换数量必须小于'.($settlement_price).','.'并是1的倍数';
				return false;
			}	
		}else{
			$this->error = '后台设置的消费抵扣积分比例不合法';
			return false;
		}
		return true;
    }
	
	
    public function CallDataForMat($items){
        //专门针对CALLDATA 标签处理的
        if (empty($items)) {
            return array();
        }
        $obj = D('Shop');
        $shop_ids = array();
        foreach ($items as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shops = $obj->itemsByIds($shop_ids);
        foreach ($items as $k => $val) {
            $val['shop'] = $shops[$val['shop_id']];
            $items[$k] = $val;
        }
        return $items;
    }


    //商品推广
    public function top_time($goods_id,$check_price,$check_price_money,$end_time){
        $config = D('Setting')->fetchAll();
        $goods = $this->find($goods_id);
        $shop = D('Shop')->find($goods['shop_id']);
        if(!$shop){
            $this->error = '没找到商家';
            return false;
        }
        $Users = D('Users')->find($shop['user_id']);
        if(!$Users){
            $this->error = '会员状态不正常';
            return false;
        }
        if(($goods['start_time'] +3600)>NOW_TIME){
            $min = 60 - (int)((NOW_TIME -$goods['start_time'] )/60);
            $this->error ='当前以出价，请'.$min.'分钟后再次尝试';
            return false;
        }
        if($check_price){
            if(($shop['bid_money'])<$check_price){
                $this->error = '您的竞价余额不足，请先充值后操作';
                return false;
            }else{
                if(empty($check_price_money)){
                    $intro ='商品'.$goods['title'].'参与竞价排行，并出价单次点击'.$check_price.'元';
                    $check_price_money = 0;
                }else{
                    $intro ='商品'.$goods['title'].'参与竞价排行，并出价单次点击'.$check_price.'元,限额'.$check_price_money.'元';
                }

                D('TuanShopJingjia')->add(array(
                    'shop_id' => $shop['shop_id'],
                    'intro' => $intro,
                    'create_time' => NOW_TIME,
                    'get_ip' => get_client_ip(),
                    'check_price' => $check_price,
                    'check_money' => abs($check_price_money),
                    'type'=>1,
                    'goods_id'=>$goods_id
                ));
                D('Tuan')->where(['tuan_id'=>$goods_id])->save(['check_price'=>$check_price,'check_price_money'=>$check_price_money,'start_time'=>NOW_TIME,'end_time'=>NOW_TIME+86400,'is_tui'=>1]);
                return true;
            }
        }else{
            $this->error = '请输入竞价';
            return false;
        }
    }


    //竞价扣款部分逻辑
    public function check_price($goods_id)
    {
        $goods = D('Tuan')->find($goods_id);

        if($goods['check_price_money']>0){//如果限额


        }else{
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where= " goods_id =".$goods_id." and create_time between ".$beginToday." and ".$endToday." and type =2 and get_ip ='".get_client_ip()."'";
            $now_ip = D('TuanShopJingjia')->where($where)->select();
            $count = count($now_ip);
            //查询IP限制
            $limit = M('Jingjia')->where(['id'=>1])->find();
            if($limit['check_num']<$count){//如果超过限制，不扣费
                //写入浏览数据
                D('TuanShopJingjia')->add(array(
                    'check_price'=>0,
                    'get_ip'=>get_client_ip(),
                    'type'=>2,
                    'intro'=>'浏览商品'.$goods['title'].'，超过扣费次数，不扣费',
                    'create_time'=>NOW_TIME,
                    'shop_id'=>$goods['shop_id'],
                    'goods_id'=>$goods_id
                ));
                //写入商品数据
                $check_num = $goods['check_num'] +1 ;
                D('Tuan')->where(['tuan_id'=>$goods_id])->save(array('check_num'=>$check_num));
            }else{
                $shop =D('Shop')->where(['shop_id'=>$goods['shop_id']])->find();
                if($shop['bid_money'] <$goods['check_price']){
                    //竞价余额不足，踢出竞价排行
                    D('Tuan')->where(['tuan_id'=>$goods_id])->save(['check_price'=>0,'is_tui'=>0]);
                }
                //扣款操作
                $shop['bid_money'] -= $goods['check_price'];
                D('Shop')->where(['shop_id'=>$goods['shop_id']])->save(array('bid_money'=>$shop['bid_money']));
                //写入浏览数据
                D('TuanShopJingjia')->add(array(
                    'check_price'=>$goods['check_price'],
                    'get_ip'=>get_client_ip(),
                    'type'=>2,
                    'intro'=>'浏览商品'.$goods['title'].'，消费竞价金额￥'.$goods['check_price'],
                    'create_time'=>NOW_TIME,
                    'shop_id'=>$goods['shop_id'],
                    'goods_id'=>$goods_id
                ));
                //写入商品数据
                $check_num = $goods['check_num'] +1 ;
                D('Tuan')->where(['tuan_id'=>$goods_id])->save(array('check_money'=>($goods['check_money'] + $goods['check_price']),'check_num'=>$check_num));
            }
        }
    }












}