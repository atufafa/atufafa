<?php
class IntegralexchangeModel extends CommonModel
{
    protected $pk = 'exchange_id';
    protected $tableName = 'integral_exchange';

    protected $types = array(
        0 => '�ȴ�����',
        1 =>'�û����ջ�',
        2 => '�ȴ�����',
        3 => '�ֿ��Ѽ��',
        4 => '�����˿���', //������
        5 => '���˿�', //������
        6 => '�����ۺ���', //������
        7 => '������ۺ�', //������
        8 => '���������'
    );

    public function getType(){
        return $this->types;
    }
    public function getError(){
        return $this->error;
    }


    //PC�������������ŷ���
    public function pc_expre_deliver($exchange_id){
        D('Integralexchange')->save(array('audit' => 3), array("where" => array('exchange_id' => $exchange_id)));
        return true;
    }

    //����ȷ���ջ�����������Ŀ����۰��ն����û�ʵ�ʽ��۵����
    public function overOrders($exchange_id){
        $config = D('Setting')->fetchAll();
        if($detail = $this->find($exchange_id)){
            if($detail['audit'] != 3 && $detail['audit'] != 1) {
                return false;
            }else{

                if($this->save(array('audit' => 8, 'exchange_id' => $exchange_id))){
                    $Shop = D('Shop')->find($detail['shop_id']);
                    list($settlement_price,$intro) = $this->get_order_settlement_price_intros($detail);//��ȡ����۷�װ

                    if($detail['is_daofu'] == 0){
                        D('Shopmoney')->insertData($exchange_id,$id = '0',$detail['shop_id'],$settlement_price,$type ='goods',$intro);//������̼�
                        D('Users')->integral_restore_user($detail['user_id'],$exchange_id, $id = '0',$settlement_price,$type ='goods');//�̳ǹ��ﷵ������
                        D('Users')->return_integral($Shop['user_id'], $detail['integral'] , '�̳��û����ֶһ��������̼�');//�̳��û����ֶһ��������̼�
                        if($config['prestige']['is_goods']){
                            D('Users')->reward_prestige($detail['user_id'], (int)($settlement_price),'�̳ǹ��ﷵ'.$config['prestige']['name']);//������
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

//���ؽ���۸񣬷��ؽ���˵����˳�������Ա���˷Ѹ�������
    public function get_order_settlement_price_intros($detail){
        $shop = D('Shop')->find($detail['shop_id']);

        if($shop['commission'] == 0 || $shop['commission'] < 0){
            $commission = 'δ����Ӷ��';
            $estimated_price = $detail['money'];
        }else{
            //��ͨ����������Ӷ�𲻺����ͷ�
            if($shop['is_goods_pei'] == 1){
                $need_pay = $detail['money'];//�����̳�û���˷�
                $commission = (int)(($need_pay * $shop['commission'])/10000);//����Ӷ��
                $estimated_price = (int)($detail['money'] - $commission);//ʵ�ʽ�����̼Ҽ۸�
            }else{
                $commission = (int)(($detail['money'] * $shop['commission'])/10000);//Ӷ��
                $estimated_price = (int)($detail['money'] - $commission);
            }

        }

        if($estimated_price > 0){
            if($shop['is_goods_pei'] == 1){
                $express_price = isset($shop['express_price']) ? (int)$shop['express_price'] : 10;//�̼��Լ����õ�Ĭ���˷�
                if($detail['express_price'] < $express_price){
                    $settlement_price = $estimated_price - $express_price;
                    $express_price = $express_price;
                    $intro .='״̬�����ѿ�ͨ����״̬���û�֧���˷�С���̼�Ĭ�����ͷѡ�---';
                    $intro .='����������'.round($detail['money'],2).'-�̼�Ĭ�����ͷ�'.round($express_price,2).'Ԫ'.'-�̳ǽ���Ӷ��'.round($commission,2).'Ԫ��---';
                    $intro .='��ǰӶ����ʣ���'.round($shop['commission'],2).'%��';
                }else{
                    $settlement_price = $estimated_price - $detail['express_price'];
                    $express_price = $detail['express_price'];
                    $intro .='״̬�����ѿ�ͨ����״̬���û�֧���˷Ѵ����̼�Ĭ�����ͷѡ�---';
                    $intro .='����������'.round($detail['money'],2).'-�û�֧���˷�'.round($detail['express_price'],2).'Ԫ'.'-�̳ǽ���Ӷ��'.round($commission,2).'Ԫ��---';
                    $intro .='��ǰӶ����ʣ���'.round($shop['commission'],2).'%��';
                }
                D('Runningmoney')->add_express_price($detail['order_id'],$express_price,2);//����Ա����
            }else{
                //�̼��������Ͳ����������Ա������� = �۳�Ӷ���۸�
                $settlement_price = $estimated_price;
                $intro .='״̬�����̼��������͡�---';
                $intro .='����������'.round($detail['money'],2).'-Ӷ��'.round($commission,2).'Ԫ��---';
                $intro .='��ǰӶ����ʣ���'.round($shop['commission'],2).'%��';
            }
            return array($settlement_price,$intro);
        }else{
            return true;//���󲻹�
        }
    }

    //���¹�����״̬
    public function del_order_goods_closeds($exchange_id) {
        $exchange_id = (int) $exchange_id;
        $ordergoods = D('Integralexchange')->where(array('exchange_id' => $exchange_id))->select();
        foreach ($ordergoods as $k => $v){
            D('Integralexchange')->save(array('exchange_id' => $v['exchange_id'], 'colse' => 1));
        }
        return TRUE;
    }

    //�����˿���
    public function del_goods_nums($exchange_id) {
        $exchange_id = (int) $exchange_id;
        $ordergoods = D('Integralexchange')->where('exchange_id =' . $exchange_id)->select();
        foreach ($ordergoods as $k => $v) {
            D('Integralgoods')->updateCount($v['goods_id'], 'num', $v['num']);
        }
        return TRUE;
    }


























}