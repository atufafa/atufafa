<?php



class alipay {

    public function getCode($logs, $setting) {
        
        $config = D('Setting')->fetchAll();

        $real_method = $setting['service'];
        switch ($real_method) {
            case '0':
                $service = 'trade_create_by_buyer';
                break;
            case '1':
                $service = 'create_partner_trade_by_buyer';
                break;
            case '2':
                $service = 'create_direct_pay_by_user';
                break;
            
        }
        $parameter = array(
            'service' => $service,
            'partner' => $setting['alipay_partner'],
            '_input_charset' => 'utf-8',
            'notify_url' => $config['site']['host'] . U( 'Home/payment/respond', array('code' => 'alipay')),
            'return_url' => $config['site']['host'] . U( 'Home/payment/respond', array('code' => 'alipay')),
            /* 业务参数 */
            'subject' => $logs['subject'],
            'out_trade_no' => $this->make_trade_no($logs['logs_id'],$logs['logs_amount']),
            'price' => $logs['logs_amount'],
            'quantity' => 1,
            'payment_type' => 1,
            /* 物流参数 */
            'logistics_type' => 'EXPRESS',
            'logistics_fee' => 0,
            'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
            /* 买卖双方信息 */
            'seller_email' => $setting['alipay_account']
        );

        ksort($parameter);
        reset($parameter);

        $param = '';
        $sign = '';
        foreach ($parameter as $key => $val) {
            $param .= "$key=" . urlencode($val) . "&";
            $sign .= "$key=$val&";
        }

        $param = substr($param, 0, -1);
        $sign = substr($sign, 0, -1) . $setting['alipay_key'];
        $button = '<div style="text-align:center"><input type="button" class="payment" onclick="window.open(\'https://www.alipay.com/cooperate/gateway.do?' . $param . '&sign=' . md5($sign) . '&sign_type=MD5\')" value=" 立刻支付 " /></div>';
         // dump(md5($sign));dump($sign);
        return $button;
    }

    private function make_trade_no($log_id, $order_amount)
    {
        $trade_no = '6';
        $trade_no .= str_pad($log_id, 15, 0, STR_PAD_LEFT);
        $trade_no .= str_pad($order_amount , 10, 0, STR_PAD_LEFT);
        return $trade_no;
    }

    private function parse_trade_no($trade_no)
    {
        $log_id = substr($trade_no, 1, 15);
        return intval($log_id);
    }

    public function respond() {
        
        if (!empty($_POST)) {
            foreach ($_POST as $key => $data) {
                $_GET[$key] = $data;
            }
        }
        $payment = D('Payment')->getPayment($_GET['code']);
        $seller_email = rawurldecode($_GET['seller_email']);
        $logs_id = $this->parse_trade_no($_GET['out_trade_no']);
        $trade_no = $_GET['trade_no']; //支付宝返回订单号
        $seller_id = $_GET['seller_id'];//支付宝返回支付ID
        $notify_time = $_GET['notify_time'];//支付时间
        $intro = $_GET['out_trade_no'];//支付消息
        $logs_id = trim($logs_id);
        /* 检查支付的金额是否相符 */
        if (!D('Payment')->checkMoney($logs_id, $_GET['total_fee'])) {
            return false;
        }
        unset($_GET['_URL_']);
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);
        
        $sign = '';

        foreach ($_GET AS $key => $val) {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code' && $key != 'g' && $key != 'm' && $key != 'a') {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];
       
        //$sign = substr($sign, 0, -1) . ALIPAY_AUTH;
        // if (md5($sign) != $_GET['sign']) {
        //     return false;
        // }
        //  print_r($sign);die;
        //$_GET['trade_status'] == 'WAIT_SELLER_SEND_GOODS' ||
        if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
            /* 改变订单状态 */
            D('Payment')->logsPaid($logs_id,$trade_no,$seller_id,$notify_time,$intro);

            return true;
        } else {
            return false;
        }
    }

}