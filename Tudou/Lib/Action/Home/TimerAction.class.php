<?php
/**
 * 定时器控制器，处理自动取消订单等操作
 * @author pingdan <[<email address>]>
 */
class TimerAction extends Action {

	/**
	 * 超时时间
	 * @author pingdan <[<email address>]>
	 * @var array
	 */
	private $timeout = array(
		'ele' => 100, //半小时
		'hotel' => 1800,
	);

	/**
	 * 初始化方法
	 * @author pingdan
	 * @return [type] [description]
	 */
	protected function _initialize() {
		//echo '<pre>';
	}

	/**
	 * 定时入口
	 * @author pingdan <[<email address>]>
	 * @return [type] [description]
	 */
	public function index() {
		$this->ele(); //外卖订单超时操作
		$this->hotel(); //酒店订单超时操作
	}

	/**
	 * 外卖订单超时操作
	 * @author pingdan
	 * @return [type] [description]
	 */
	public function ele() {
		$map = array(
			'create_time' => array('lt', $_SERVER['REQUEST_TIME'] - $this->timeout['ele']),
			'status' => 0,
		);
		$orders = D('EleOrder')->where($map)->select();
		if (!$orders) {
			return;
		}
		$ids = array();
		foreach ($orders as $order) {
			$ids[] = $order['order_id'];
		}
		if (count($ids)) {
			$ids = array_unique($ids);
		}
		//超时批量取消订单
		$result = D('Eleorder')->cancelByIds($ids);
	}

	/**
	 * 酒店订单超时操作
	 * @author pingdan
	 * @return [type] [description]
	 */
	public function hotel() {
		$map = array(
			'create_time' => array('lt', $_SERVER['REQUEST_TIME'] - $this->timeout['hotel']),
			'order_status' => -1,
		);
		$orders = D('HotelOrder')->where($map)->select();
		if (!$orders) {
			return;
		}
		$ids = array();
		foreach ($orders as $order) {
			$ids[] = $order['order_id'];
		}
		if (count($ids)) {
			$ids = array_unique($ids);
		} 
		//超时批量取消订单
		$result = D('Hotelorder')->cancelByIds($ids);
	}

}