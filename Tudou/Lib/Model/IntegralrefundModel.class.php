
<?php
class IntegralrefundModel extends CommonModel
{

    protected $pk = 'id';
    protected $tableName = 'integral_refund';

    /**
     * 售后类型数组
     * @author pingdan
     * @var array
     */
    protected $refund_type = array(
        1 => '仅退款',
        2 => '退货退款',
        3 => '换货',
    );

    /**
     * 售后状态数组
     * @author pingdan
     * @var array
     */
    protected $refund_status = array(
        0 => '待审核',
        1 => '已通过',
        2 => '已拒绝',
        3 => '商家已同意,等待买家退货', //退款退货
        4 => '买家已发货,等待商家确认收货', //退款退货
        5 => '商家已确认收货', //退款退货
        6 => '商家已发货,等待买家确认收货', //换货
        7 => '买家已确认收货', //换货
        8 => '订单已完成',
    );




    //支付宝退款
    public function alipay_refund($payinfo,$wx_price,$wx_pdr_id,$wx_replace_sn_id)
    {
        //payinfo 配置信息 wx_price 退款金额 wx_pdr_id 支付订单ID    wx_replace_sn_id  商家订单ID
        Vendor('Alipay.aop.AopClient');
        Vendor('Alipay.aop.request.AlipayTradeRefundRequest');
        $aop = new \AopClient();
        $aliConfig = C('ALI_CONFIG');
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $payinfo['alipay_app_id'];//appid不用多说
        $aop->rsaPrivateKey = $payinfo['alipay_private_key'];//商户私钥
        $aop->alipayrsaPublicKey= $payinfo['alipay_rsa_public_key'];//支付宝公钥
        $aop->apiVersion = '1.0';//固定
        $wx_replace_sn_id= $wx_replace_sn_id;
        $wx_pdr_id = $wx_pdr_id;
        $wx_price = $wx_price; //退款金额 此处请不要随意修改
        $aop->signType = 'RSA2';//固定
        $aop->postCharset='utf-8';//固定
        $aop->format='json';//固定
        $request = new \AlipayTradeRefundRequest();
        $request->setBizContent("{" .
            "\"out_trade_no\":\"$wx_replace_sn_id\"," .
            "\"trade_no\":\"$wx_pdr_id\"," .
            "\"refund_amount\":$wx_price," .
            "\"refund_reason\":\"正常退款\"," .
            "\"out_request_no\":\"HZ01RF001\"," . //此处的标识为退款标识，唯一值，但可以不一样
            "\"operator_id\":\"OP001\"," .
            "\"store_id\":\"NJ_S_001\"," .
            "\"terminal_id\":\"NJ_T_001\"" .
            "  }");
        // print_r($request);die;
        $result = $aop->execute($request);//这里会返回一个对象
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code; //返回这次的状态码，10000为成功
        $return_trade = $result->$responseNode->trade_no;//这个是支付宝生成的订单号
        if(!empty($resultCode)&&$resultCode == 10000){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取售后类型数组
     * @return array
     */
    public function getRefundType() {
        return $this->refund_type;
    }

    /**
     * 售后状态数组
     * @return array
     */
    public function getRefundStatus() {
        return $this->refund_status;
    }








}