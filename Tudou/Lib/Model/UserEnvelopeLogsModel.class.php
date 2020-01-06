<?php
/**
 * 用户红包模型
 * @author pingdan <[<email address>]>
 */
class UserEnvelopeLogsModel extends CommonModel{
    protected $pk = 'log_id';
    protected $tableName = 'user_envelope_logs';

    /**
     * 外卖订单支付写入用户红包日志
     * @author pingdan <[<email address>]>
     * @param  order_id 外卖订单id
     * @return [type] [description]
     */
    public function eleOrderPay($order_id) {
    	$order = D('EleOrder')->where(array('order_id' => $order_id))->find();

    	if ($order) {

    		//红包账户可用红包
    		$user_envelope = D('UserEnvelope')->getUserEnvelope($order['user_id'], $order['shop_id']);

    		//更新商家红包账户
    		if (($update_shop =  $user_envelope['shop_envelope'] - $order['shop_envelope']) >= 0) {
    			D('UserEnvelope')->where(array('user_id' => $order['user_id'], 'shop_id' => $order['shop_id']))->save(array('envelope' => $update_shop));
    		}
    		//更新平台红包账户
    		if (($update_any =  $user_envelope['any_envelope'] - $order['any_envelope']) >= 0) {
    			D('UserEnvelope')->where(array('user_id' => $order['user_id'], 'shop_id' => 0))->save(array('envelope' => $update_any));
    		}

    		//写入日志
    		$intro = '外卖订单ID：'. $order['order_id'] . '支出(商家红包-' .$order['shop_envelope']. '元，'. '平台红包-' . $order['any_envelope']. '元)';
    		$data = array(
    			'type' => 2,
    			'user_id' => $order['user_id'],
    			'order_id' => $order['order_id'],
    			'envelope' => $order['shop_envelope'] + $order['any_envelope'],
    			'intro' => $intro,
    			'create_time' => time(),
    			'create_ip' => get_client_ip(),
    		);
    		return $this->add($data);
    	
    	}
    	return false;
    }

}