<?php
/**
 * 用户红包模型
 * @author pingdan <[<email address>]>
 */
class UserEnvelopeModel extends CommonModel{

	protected $pk   = 'user_envelope_id';
    protected $tableName =  'user_envelope';

    /**
     * 获取用户可用红包
     * @author  pingdan <[<email address>]>
     * @param  [type] $user_id [description]
     * @param  [type] $shop_id [description]
     * @return [type]          [description]
     */
    public function getUserEnvelope($user_id, $shop_id) {
    	$envelope = array();
    	//当前商家红包
    	$shop_envelope = $this->where(array('user_id' => $user_id, 'shop_id' => $shop_id))->find();
    	if ($shop_envelope) {
    		$envelope['shop_envelope'] = $shop_envelope['envelope'];
    	}

    	//平台红包
    	$any_envelope = $this->where(array('user_id' => $user_id, 'shop_id' => 0))->find();
    	if ($any_envelope) {
    		$envelope['any_envelope'] = $any_envelope['envelope'];
    	}
    	return $envelope;

    }	

    /**
     * 获取订单可使用的红包金额
     * @author pingdan <[<email address>]>
     * @param int $order_id 订单id
     * @return [type] [description]
     */
    public function getOrderCanUseEnvelope($order_id) {
        $envelope = array(
            'use_shop_envelope' => 0,
            'use_any_envelope' => 0,
        );
        $order = D('Eleorder')->find($order_id);
        if ($order) {
	        $user_envelope = $this->getUserEnvelope($order['user_id'], $order['shop_id']);
	        if ($user_envelope['shop_envelope'] > 0) {
	            $envelope['use_shop_envelope'] = min($user_envelope['shop_envelope'], $order['need_pay']);
	        }
	        $rest = $order['need_pay'] - $envelope['use_shop_envelope'];
	        if ($user_envelope['any_envelope'] > 0 && $rest > 0) {
	            $envelope['use_any_envelope'] = min($user_envelope['any_envelope'], $rest);
	        }
        }
        return $envelope;
    }

}
