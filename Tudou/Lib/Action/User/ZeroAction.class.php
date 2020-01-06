<?php
class ZeroAction extends CommonAction{
    protected function _initialize(){
        parent::_initialize();
        if(empty($this->_CONFIG['operation']['zero'])) {
            $this->error('0元抢购功能已关闭');
            die;
        }
    }

    //列表
    public function index(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    //加载
    public function orderloading(){
        $obj = D('Pindanorder');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'closed' => 0);

        $aready = I('aready', '', 'trim,intval');

        if($aready == 999){
            $map['status'] = array('in',array(1,0,2,3,4,5,6,7,8));
        }elseif($aready == 0){
            $map['status'] = 0;
        }else{
            $map['status'] = $aready;
        }
        $this->assign('aready', $aready);

        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $tuan_ids = array();
        foreach ($list as $k => $val) {
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        }
        $shop_ids = array();
        foreach ($list as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('tuans', D('Pindan')->itemsByIds($tuan_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //详细信息
    public function detail($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D('Pindanorder')->find($order_id))){
            $this->error('该订单不存在');
        }
        if($detail['user_id'] != $this->uid){
            $this->error('请不要操作他人的订单');
        }
        if(!($dianping = D('Pindandianping')->where(array('order_id' => $order_id, 'user_id' => $this->uid))->find())) {
            $detail['dianping'] = 0;
        }else{
            $detail['dianping'] = 1;
        }
        $this->assign('tuans', D('Pindan')->find($detail['tuan_id']));
        $this->assign('detail', $detail);
        $this->display();
    }

    //取消订单
    public function delete($order_id){
        $order_id = I('order_id', 0, 'trim,intval');
        $obj = D('Pindanorder');
        if(!($detail = D('Pindanorder')->find($order_id))){
            $this->tuMsg('抢购不存在', U('zero/index'));
        }
        if($detail['status'] != 0){
            $this->tuMsg('状态不正确', U('zero/index'));
        }elseif($detail['closed'] == 1){
            $this->tuMsg('抢购已关闭', U('zero/index'));
        }elseif($detail['user_id'] != $this->uid){
            $this->tuMsg('不能操作别人的抢购', U('zero/index'));
        }else{
            if($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
                $this->tuMsg('取消订单成功!', U('zero/index'));
            }else{
                $this->tuMsg('操作失败');
            }
        }

    }

    //评价
    public function dianping($order_id){
        $order_id = (int) $order_id;
        if(empty($order_id) || !($detail = D("Pindanorder")->find($order_id))){
            $this->error("该订单不存在");
        }
        if($detail['user_id'] != $this->uid){
            $this->error("请不要操作他人的订单");
        }
        if($detail['is_dianping'] != 0){
            $this->error("您已经点评过了");
        }
        if($this->isPost()){
            $data = $this->checkFields($this->_post("data", FALSE), array("score", "cost", "contents"));
            $data['user_id'] = $this->uid;
            $data['order_id'] = $detail['order_id'];
            $data['shop_id'] = $detail['shop_id'];
            $data['tuan_id'] = $detail['tuan_id'];
            $data['score'] = (int) $data['score'];
            if ($data['score'] <= 0 || 5 < $data['score']) {
                $this->tuMsg("请选择评分");
            }
            $data['cost'] = (int) $data['cost'];
            $data['contents'] = htmlspecialchars($data['contents']);
            if (empty($data['contents'])) {
                $this->tuMsg("不说点什么么");
            }
            $data['create_time'] = NOW_TIME;
            $data_tuan_dianping = $this->_CONFIG['mobile']['data_tuan_dianping'];
            $data['show_date'] = date('Y-m-d', NOW_TIME + $data_tuan_dianping * 86400);
            $data['create_ip'] = get_client_ip();
            $obj = D("Pindandianping");

            if ($dianping_id = $obj->add($data)) {
                $photos = $this->_post("photos", FALSE);
                $local = array();
                foreach ($photos as $val) {
                    if (isimage($val)) {
                        $local[] = $val;
                    }
                }
                if (!empty($local)) {
                    D("Pindandianpingpics")->upload($order_id, $local);
                }
                D("Pindanorder")->save(array("order_id" => $order_id, "is_dianping" => 1));
                D("Shop")->updateCount($detail['shop_id'], "score_num");
                D("Users")->updateCount($this->uid, "ping_num");
                D("Users")->prestige($this->uid, "dianping");
                $this->tuMsg("评价成功", U("zero/index"));
            }
            $this->tuMsg("操作失败");
        } else {
            $this->assign("detail", $detail);
            $tuan = D("Pindan")->find($detail['tuan_id']);
            $this->assign("tuan", $tuan);
            $this->display();
        }
    }

    //投诉
    public function integralcomplaint($order_id){
        if($dc = D('Pindancomplaint')->where(array('order_id'=>$order_id,'user_id'=>$this->uid))->find()){
            $this->ajaxReturn(array('code'=>'0','msg'=>'已经投诉过了！'));
        }
        $shop=D("Pindanorder")->where(array("order_id" => $order_id))->find();
        //查询订单信息
        if($this->_post()){
            //获取页面信息
            $content=I('post.content');
            $photo=I('post.photo');
            $userid=$this->uid;
            $shop_id=$shop["shop_id"];
            $data=array();
            $data['content']=$content;
            if(empty($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉内容不能为空'));
            }
            if($words = D('Sensitive')->checkWords($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'评价内容含有敏感词：' . $words));
            }
            $data['photo']=$photo;
            $data['shop_id']=$shop_id;
            $data['order_id']=$order_id;
            $data['user_id']=$userid;

            //var_dump($tsdata);

            $ts= D('Pindancomplaint')->add($data);

            if($ts>0){
                $this->ajaxReturn(array('code'=>'1','msg'=>'投诉成功！','url'=>U('zero/index')));
            }else{
                $this->ajaxReturn(array('code'=>'0','msg'=>'投诉失败！'));
            }
        }
        $this->assign("sj", $shop);
        $this->display();
    }

    //确认收货
    public function queren($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Pindanorder');
            if (!($detial = $obj->find($order_id))) {
                $this->tuMsg('该订单不存在');
            }
            if ($detial['user_id'] != $this->uid) {
                $this->tuMsg('请不要操作他人的订单');
            }
            //检测配送状态
            $shop = D('Shop')->find($detial['shop_id']);
            if ($shop['is_goods_pei'] == 1) {
                $DeliveryOrder = D('DeliveryOrder')->where(array('type_order_id' => $order_id, 'type' => 0))->find();
                if ($DeliveryOrder['status'] != 8) {
                    $this->tuMsg('配送员还未完成订单');
                }
            }
            if($detial['is_daofu'] == 1) {
                $into = '货到付款确认收货成功';
            }else{
                if ($detial['status'] != 2) {
                    $this->tuMsg('该订单暂时不能确定收货');
                }
                $into = '确认收货成功';
            }
            if ($obj->save(array('order_id' => $order_id, 'status' => 3))) {
                D('Pindanorder')->overOrder($order_id); //确认到账入口
                $this->tuMsg($into, U('zero/index', array('aready' => 8)));
            }else{
                $this->tuMsg('操作失败');
            }
        } else {
            $this->tuMsg('请选择要确认收货的订单');
        }
    }


}