<?php

class GoodsModel extends CommonModel{
    protected $pk   = 'goods_id';
    protected $tableName =  'goods';
    
    protected $_validate = array(
        array(),
        array(),
        array()
    );
    
    public function getError(){
        return $this->error;
    }

    public function _format($data){
        $data['save'] =  round(($data['price'] - $data['mall_price']),2);
        $data['price'] = round($data['price'],2);
        //多属性开始
        $data['mobile_fan'] = round($data['mobile_fan'],2);
        //多属性结束
        $data['mall_price'] = round($data['mall_price'],2);
        $data['settlement_price'] = round($data['settlement_price'],2);
        $data['commission'] = round($data['commission'],2);
        $data['discount'] = round($data['mall_price'] * 10 / $data['price'],1);
        return $data;
    }
    
    //竞价扣款部分逻辑
    public function check_price($goods_id)
    {   
        $goods = D('Goods')->find($goods_id);
        // $goods['check_price']/100;//出价
        // $goods['check_price_money']/100;//限额
        if($goods['check_price_money']>0){//如果限额
            // if($goods['check_money'] >=$goods['check_price_money']){//如果超过限额

            // }
            // //取当前产品今日点击IP部分
            
            // $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            // $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            // $where= " goods_id =".$goods_id." and create_time between ".$beginToday." and ".$endToday." and type =2";
            // $get_ip = D('Shopbidlogs')->where($where)->select();
            // print_r($gt_ip);
            // echo D('Shopbidlogs')->getLastSql();
            // die;
            // // if(){

            // // }

        }else{
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where= " goods_id =".$goods_id." and create_time between ".$beginToday." and ".$endToday." and type =2 and get_ip ='".get_client_ip()."'";
            $now_ip = D('Shopbidlogs')->where($where)->select();
            $count = count($now_ip);
            //查询IP限制
            $limit = M('Jingjia')->where(['id'=>1])->find();
            if($limit['check_num']<$count){//如果超过限制，不扣费
                //写入浏览数据
                D('Shopbidlogs')->add(array(
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
                D('Goods')->where(['goods_id'=>$goods_id])->save(array('check_num'=>$check_num));
            }else{
                $shop =D('Shop')->where(['shop_id'=>$goods['shop_id']])->find();
                if($shop['bid_money'] <$goods['check_price']){
                    //竞价余额不足，踢出竞价排行
                    D('Goods')->where(['goods_id'=>$goods_id])->save(['check_price'=>0,'is_tui'=>0]);    
                }
                //扣款操作
                $shop['bid_money'] -= $goods['check_price'];
                D('Shop')->where(['shop_id'=>$goods['shop_id']])->save(array('bid_money'=>$shop['bid_money']));
                //写入浏览数据
                D('Shopbidlogs')->add(array(
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
                D('Goods')->where(['goods_id'=>$goods_id])->save(array('check_money'=>($goods['check_money'] + $goods['check_price']),'check_num'=>$check_num));
            }
        }
    }
    
    //获取首页轮播数据
    public function getScroll(){
        $config = D('Setting')->fetchAll();
        $limit = isset($config['goods']['limit']) ? (int)$config['goods']['limit'] : 6;
        $order = isset($config['goods']['order']) ? (int)$config['goods']['order'] : 1;
        switch ($order) {
            case '1':
                $orderby = array('create_time' =>'desc','order_id' =>'desc');
                break;
            case '2':
                $orderby = array('total_price' =>'desc');
                break;
            case '3':
                $orderby = array('order_id' =>'desc');
                break;
        }
        $list = D('Order')->order($orderby)->limit(0,$limit)->select();
        foreach($list as $k => $v){
            if($user = D('Users')->where(array('user_id'=>$v['user_id']))->find()){
                $list[$k]['user'] = $user;
            }
        }
        return $list;
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
               
                D('Shopbidlogs')->add(array(
                    'shop_id' => $shop['shop_id'], 
                    'intro' => $intro, 
                    'create_time' => NOW_TIME, 
                    'get_ip' => get_client_ip(),
                    'check_price' => $check_price*10,
                    'check_money' => abs($check_price_money),
                    'type'=>1,
                    'goods_id'=>$goods_id
                ));
                D('Goods')->where(['goods_id'=>$goods_id])->save(['check_price'=>$check_price,'check_price_money'=>$check_price_money,'start_time'=>NOW_TIME,'end_time'=>NOW_TIME+86400,'is_tui'=>1]);
                return true;
            }   
        }else{
            $this->error = '请输入竞价';
            return false;
        }
   }
    
    //计算用户下单返回多少积分传2个参数，商品id商品类型
    public function get_forecast_integral_restore($id,$type){
        $config = D('Setting')->fetchAll();
        if($config['integral']['is_restore'] == 1){
            if($type == 'goods'){
                $Goods = D('Goods')->find($id);
                if($config['integral']['is_goods_restore'] == 1){
                    if($config['integral']['restore_type'] == 1){
                        $integral = $Goods['mall_price'];
                    }elseif($config['integral']['restore_type'] == 2){
                        $integral = $Goods['settlement_price'];
                    }elseif($config['integral']['restore_type'] == 3){
                        $integral = $Goods['mall_price']- $Goods['settlement_price'];
                    }else{
                        $integral = 0;
                    }
                }else{
                    return false;
                }
            }
            
            if($integral > 0){
                if($config['integral']['restore_points'] > 100){
                    if($config['integral']['restore_points']){
                        $integral = $integral - (($integral * $config['integral']['restore_points']));
                        return int($integral);
                    }else{
                        return false;
                    }
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        
    }
    
    //这里暂时没有判断多属性的问题，后期再判断
    public function check_add_use_integral($use_integral,$mall_price){
        $config = D('Setting')->fetchAll();
        $integral = $config['integral']['buy'];
        if($integral == 0){
            if($use_integral % 100 != 0) {
                $this->error = '积分必须为100的倍数';
                return false;
            }
            if($use_integral >= $mall_price){
                $this->error = '积分兑换数量必须小于'.$mall_price.','.'并是100的倍数';
                return false;
            }
        }elseif($integral == 10){
            if($use_integral % 10 != 0){
                $this->error = '积分必须为10的倍数';
            }
            if($use_integral*10 >= $mall_price){
                $this->error = '积分兑换数量必须小于'.($mall_price).','.'并是10的倍数';
                return false;
            }
        }elseif($integral == 100){
            if($use_integral % 1 != 0){
                $this->error = '积分必须为1的倍数';
                return false;
            }
            if($use_integral*100 >= $mall_price) {
                $this->error = '积分兑换数量必须小于'.($mall_price).','.'并是1的倍数';
                return false;
            }   
        }else{
            $this->error = '后台设置的消费抵扣积分比例不合法';
            return false;
        }
        return true;
    }

}