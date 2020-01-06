<?php
class DepositmanagementModel extends CommonModel{
    protected $pk   = 'deposit_id';
    protected $tableName =  'deposit_management';

    //添加押金
    public function addyajin($user_id,$type,$deposit,$code){
        $data=array(
            'user_id'=>$user_id,
            'type'=>$type,
            'money'=>$deposit,
            'pay_type'=>$code,
            'create_time'=>NOW_TIME,
            'nowmoney'=>$deposit
        );
        if(false!==D('Depositmanagement')->add($data)){
            return true;
        }
        return false;
    }

    //押金充值
    public function  save_deposit($log_id){
        $detail = M('PaymentLogs')->where(array('log_id'=>$log_id))->find();
        // print_r($detail);die;
        $shop = $this->where(['user_id'=>$detail['user_id']])->find();
        // print_r($shop);die;
        if (!empty($shop)) {
            // if (M('PaymentLogs')->where(array('log_id'=>$log_id))->save(array('log_id' => $log_id, 'order_status' => '1'))){
            $shop['nowmoney'] +=$detail['need_pay'];
            //var_dump($detail['need_pay']);var_dump($shop['money']);die;
            D('Depositmanagement')->where(array('user_id'=>$shop['user_id'],'deposit_id'=>$detail['deposit_id']))->save(['nowmoney'=>$shop['nowmoney']]);
            D('Depositmanagement')->where(array('user_id'=>$shop['user_id'],'deposit_id'=>$detail['deposit_id']))->save(['koumoney'=>0]);
            return TRUE;
            // }
        }else{
            return TRUE;
            //由于支付回调，直接忽略报错 return false;
        }
    }


    //用户押金
    public  function yaJin($user_id, $num, $intro = ''){
            return D('Usermoneylogs')->add(array(
                'user_id' => $user_id,
                'money' => $num,
                'intro' => $intro,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip()
            ));

        return false;
    }


}