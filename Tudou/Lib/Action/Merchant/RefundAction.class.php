<?php
class RefundAction extends CommonAction{
    public function index(){
        $Tuanorder = M('OrderRefund');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id, 'closed' => 0);
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        $count = $Tuanorder->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Tuanorder->where($map)->order(array('id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $tuan_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k]['info'] = $this->get_Refund_info($val['id']);
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        } 
        // dump($list);die;
        // $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        // $this->assign('tuan', D('Tuan')->itemsByIds($tuan_ids));
        // $this->assign('dianping', D('Tuandianping')->itemsByIds($order_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //同意退款 -- 直接打款
    public function do_refund($order_id)
    {
        if(empty($order_id)){
            $this->tuError('请选择退款订单');
        }
        if(false ==($detail = M('OrderRefund')->where(['id'=>$order_id])->find())){
            $this->tuError('未找到该退款订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('无权限操作');
        }
        if($detail['status'] !=0){
            $this->tuError('该订单状态异常，无法操作');
        }
        //查询当前订单信息
        $order=D('Eleorder')->where(array('order_id'=>$detail['goods_id']))->find();


        //将下单时间进行日期化
        $is_pay=date("Y-m-d H:i:s",$order['pay_time']);
        //将日期化的日期进行多加一分钟计算
        $ends=date('Y-m-d H:i:s',strtotime( '+1 Minute', strtotime($is_pay)));
        //计算好的时间转为时间戳
        $mm=strtotime($ends);

        if($mm>$detail['create_time']){
            //如果申请时间小于一分钟就自动退款
            D('Refund')->getRefund_Pay($detail['goods_id'],$detail['type']);
        }
        //后台设置的
        $times=time();
        $tui=$this->CONFIG['site']['refund'];
        $daoji=date("Y-m-d H:i:s",$detail['create_time']);
        $daoshi=date('Y-m-d H:i:s',strtotime('+2 hours', strtotime($daoji)));
        $zhuan=strtotime($daoshi);
        //如果用户申请退款商家没有操作，过几个小时后自动退款到用户余额中
        if(!empty($detail['goods_id']) && $detail['status']==0 && $times==$zhuan){
            D('Refund')->getRefund_Pay($detail['goods_id'],$detail['type']);
        }

        if(false !==(D('Refund')->getRefund_Pay($detail['goods_id'],$detail['type']))){
              $this->tuSuccess('退款成功',U('refund/index'));  
        }else{
            $this->tuError('退款失败');
        }
    }

    //拒绝退款  --  取消退款
    public function miss_refund($order_id)
    {
        $detail = M('OrderRefund')->where(['id'=>$order_id])->find();
        if(empty($order_id)){
            $this->tuError('请选择退款订单');
        }
        if(false ==$detail){
            $this->tuError('未找到该退款订单');
        }
        if($detail['shop_id'] != $this->shop_id){
            $this->tuError('无权限操作');
        }
        if($detail['status'] !=0){
            $this->tuError('该订单状态异常，无法操作');
        }

//        $peis=D('DeliveryOrder')->where(array('type_order_id'=>$order_id))->find();
//        if($peis['status']!=8){
//            $this->tuError('配送员正在配送，无法操作');
//        }

        $Model = M();
        $Model->startTrans();
        $res1=M('OrderRefund')->where(array('id'=>$order_id))->setInc('refund_count');
        $res2=M('AppointOrder')->where(array('order_id'=>$detail['goods_id']))->save(array('status'=>5));
        $detail=M('OrderRefund')->where(array('id'=>$order_id))->save(array('status'=>2));
        if($detail && $res1 && $res2){
            $Model->commit();
            $this->tuSuccess('操作成功',U('refund/index'));
        }else{
            $Model->rollback();
            $this->tuError('操作失败');
        }

    }
    //获取退款订单详细信息
    public function get_Refund_info($id)
    {
        $refund = M('OrderRefund')->where(['id'=>$id])->find();

        switch ($refund['type']) {
        // 退款订单类型（1家政2商城3外卖4农家乐5便利店6菜市场7教育8酒店9KTV0订座）
            case '1':
                $where = " Apporder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('AppointOrder')
                        ->alias('Apporder')
                        ->join('tu_appoint AS app ON Apporder.shop_id = app.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');        
                // echo M('AppointOrder')->getLastSql();
                // die;
                return $info;
                break;
            case '2':
                break;
            case '3':
                $where = " eleorder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('EleOrder')
                        ->alias('eleorder')
                        ->join('tu_ele AS ele ON eleorder.shop_id = ele.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                return $info;
                break;
            case '4':
                $where = " Apporder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('FarmOrder')
                        ->alias('farmorder')
                        ->join('tu_farm AS farm ON farmorder.farm_id = farm.farm_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                return $info;
                // echo M('AppointOrder')->getLastSql();
                // die;
                break;
            case '5':
                $where = " Apporder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('AppointOrder')
                        ->alias('Apporder')
                        ->join('tu_appoint AS app ON Apporder.shop_id = app.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                return $info;
                // echo M('AppointOrder')->getLastSql();
                // die;
                break;
            case '6':
                $where = " Apporder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('AppointOrder')
                        ->alias('Apporder')
                        ->join('tu_appoint AS app ON Apporder.shop_id = app.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                return $info;
                // echo M('AppointOrder')->getLastSql();
                // die;
                break;
            case '7':
                $where = " eduorder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('EduOrder')
                        ->alias('eduorder')
                        ->join('tu_edu AS edu ON eduorder.shop_id = edu.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                return $info;
                // echo M('AppointOrder')->getLastSql();
                // die;
                break;
            case '8':
                $where = " hotelorder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('HotelOrder')
                        ->alias('hotelorder')
                        ->join('tu_hotel AS hotel ON hotelorder.hotel_id = hotel.hotel_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                // echo M('AppointOrder')->getLastSql();
                // die;
                break;
            case '9':
                $where = " Apporder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('AppointOrder')
                        ->alias('Apporder')
                        ->join('tu_appoint AS app ON Apporder.shop_id = app.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                // echo M('AppointOrder')->getLastSql();
                // die;
                return $info;
                break;
            case '0':
                $where = " bookingorder.order_id = ".$refund['goods_id'];
                // print($where);die;
                $info = M('BookingOrder')
                        ->alias('bookingorder')
                        ->join('tu_booking AS booking ON bookingorder.shop_id = booking.shop_id')   
                        ->where($where)->find();
                $info['user_name'] = M('Users')->where(['user_id'=>$info['user_id']])->getField('nickname');
                // echo M('AppointOrder')->getLastSql();
                // die;
                return $info;
                break;
            // default:
            //     $where = " Apporder.order_id = ".$refund['goods_id'];
            //     // print($where);die;
            //     $info = M('AppointOrder')
            //             ->alias('Apporder')
            //             ->join('tu_appoint AS app ON Apporder.shop_id = app.shop_id')   
            //             ->where($where)->find();
            //     // echo M('AppointOrder')->getLastSql();
            //     // die;
            //     return $info;
                break;
        }
    }

}