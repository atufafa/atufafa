<?php
class TuanrefundModel extends CommonModel{
    protected $pk = 'id';
    protected $tableName = 'tuan_refund';
	
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