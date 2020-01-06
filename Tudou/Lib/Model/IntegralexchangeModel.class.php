<?php
class IntegralexchangeModel extends CommonModel
{
    protected $pk = 'exchange_id';
    protected $tableName = 'integral_exchange';

    protected $types = array(
        0 => '等待付款',
        1 =>'用户已收货',
        2 => '等待发货',
        3 => '仓库已捡货',
        4 => '申请退款中', //待开发
        5 => '已退款', //待开发
        6 => '申请售后中', //待开发
        7 => '已完成售后', //待开发
        8 => '已完成配送'
    );

    public function getType(){
        return $this->types;
    }
    public function getError(){
        return $this->error;
    }


    //PC端输入物流单号发货
    public function pc_expre_deliver($exchange_id){
        D('Integralexchange')->save(array('audit' => 3), array("where" => array('exchange_id' => $exchange_id)));
        return true;
    }

    //最终确认收货，不按照类目结算价按照订单用户实际金额扣点结算
    public function overOrders($exchange_id){
        $config = D('Setting')->fetchAll();
        if($detail = $this->find($exchange_id)){
            if($detail['audit'] != 3 && $detail['audit'] != 1) {
                return false;
            }else{

                if($this->save(array('audit' => 8, 'exchange_id' => $exchange_id))){
                    $Shop = D('Shop')->find($detail['shop_id']);
                    list($settlement_price,$intro) = $this->get_order_settlement_price_intros($detail);//获取结算价封装

                    if($detail['is_daofu'] == 0){
                        D('Shopmoney')->insertData($exchange_id,$id = '0',$detail['shop_id'],$settlement_price,$type ='goods',$intro);//结算给商家
                        D('Users')->integral_restore_user($detail['user_id'],$exchange_id, $id = '0',$settlement_price,$type ='goods');//商城购物返利积分
                        D('Users')->return_integral($Shop['user_id'], $detail['integral'] , '商城用户积分兑换返还给商家');//商城用户积分兑换返还给商家
                        if($config['prestige']['is_goods']){
                            D('Users')->reward_prestige($detail['user_id'], (int)($settlement_price),'商城购物返'.$config['prestige']['name']);//返威望
                        }
                    }
                    D('Weixinmsg')->weixinTmplOrderMessage($exchange_id,$cate = 1,$type = 2,$status = 8);
                    D('Weixinmsg')->weixinTmplOrderMessage($exchange_id,$cate = 2,$type = 2,$status = 8);
                    return true;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }   

    }

//返回结算价格，返回结算说明，顺便把配送员的运费给结算了
    public function get_order_settlement_price_intros($detail){
        $shop = D('Shop')->find($detail['shop_id']);

        if($shop['commission'] == 0 || $shop['commission'] < 0){
            $commission = '未设置佣金';
            $estimated_price = $detail['money'];
        }else{
            //开通第三方配送佣金不含配送费
            if($shop['is_goods_pei'] == 1){
                $need_pay = $detail['money'];//积分商城没有运费
                $commission = (int)(($need_pay * $shop['commission'])/10000);//计算佣金
                $estimated_price = (int)($detail['money'] - $commission);//实际结算给商家价格
            }else{
                $commission = (int)(($detail['money'] * $shop['commission'])/10000);//佣金
                $estimated_price = (int)($detail['money'] - $commission);
            }

        }

        if($estimated_price > 0){
            if($shop['is_goods_pei'] == 1){
                $express_price = isset($shop['express_price']) ? (int)$shop['express_price'] : 10;//商家自己配置的默认运费
                if($detail['express_price'] < $express_price){
                    $settlement_price = $estimated_price - $express_price;
                    $express_price = $express_price;
                    $intro .='状态：【已开通配送状态，用户支付运费小于商家默认配送费】---';
                    $intro .='结算金额：结算价'.round($detail['money'],2).'-商家默认配送费'.round($express_price,2).'元'.'-商城结算佣金'.round($commission,2).'元】---';
                    $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
                }else{
                    $settlement_price = $estimated_price - $detail['express_price'];
                    $express_price = $detail['express_price'];
                    $intro .='状态：【已开通配送状态，用户支付运费大于商家默认配送费】---';
                    $intro .='结算金额：结算价'.round($detail['money'],2).'-用户支付运费'.round($detail['express_price'],2).'元'.'-商城结算佣金'.round($commission,2).'元】---';
                    $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
                }
                D('Runningmoney')->add_express_price($detail['order_id'],$express_price,2);//配送员结算
            }else{
                //商家自主配送不结算给配送员，结算价 = 扣除佣金后价格
                $settlement_price = $estimated_price;
                $intro .='状态：【商家自主配送】---';
                $intro .='结算金额：结算价'.round($detail['money'],2).'-佣金'.round($commission,2).'元】---';
                $intro .='当前佣金费率：【'.round($shop['commission'],2).'%】';
            }
            return array($settlement_price,$intro);
        }else{
            return true;//错误不管
        }
    }

    //更新购物表的状态
    public function del_order_goods_closeds($exchange_id) {
        $exchange_id = (int) $exchange_id;
        $ordergoods = D('Integralexchange')->where(array('exchange_id' => $exchange_id))->select();
        foreach ($ordergoods as $k => $v){
            D('Integralexchange')->save(array('exchange_id' => $v['exchange_id'], 'colse' => 1));
        }
        return TRUE;
    }

    //更新退款库存
    public function del_goods_nums($exchange_id) {
        $exchange_id = (int) $exchange_id;
        $ordergoods = D('Integralexchange')->where('exchange_id =' . $exchange_id)->select();
        foreach ($ordergoods as $k => $v) {
            D('Integralgoods')->updateCount($v['goods_id'], 'num', $v['num']);
        }
        return TRUE;
    }


























}