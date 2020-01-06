<?php
class EnvelopeAction extends CommonAction{
	
	public function _initialize(){
        parent::_initialize();
		$this->assign('types', D('Envelope')->getType());
		$this->assign('orderTypes', D('Envelope')->getOrderType());
    }
	public function index(){
		$this->display();
	}
	
	public function loaddata(){
        $obj = D('EnvelopeLogs');
        import('ORG.Util.Page');
        $map = array('user_id'=>$this->uid);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
			$list[$key]['envelope'] = M('Envelope')->find($val['envelope_id']);
			$list[$key]['user'] = M('Users')->find($val['user_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //新增红包日志
    public function logs()
    {
        $this->display();
    }

    public function logs_loaddata()
    {
        $obj = D('UserEnvelopeLogs');
        import('ORG.Util.Page');
        $map = array('user_id'=>$this->uid);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
            $list[$key]['user'] = M('Users')->find($val['user_id']);
            if($val['type'] ==1){
                $list[$key]['type'] = '订单红包';
            }else{
                $list[$key]['type'] = '引流红包';
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //@pingdan begin
	
    /**
     * 普通红包\引流红包领取
     * @param  [type] $envelope_id [description]
     * @return [type]              [description]
     */
	public function receive($envelope_id) {
        if ($envelope_id < 0) {
            return $this->error('红包ID异常，领取失败');
        }
        $envelope = D('Envelope')->find($envelope_id);
        $where = array('user_id' => $this->uid, 'shop_id' => $envelope['shop_id']);
        $user_envelope = D('UserEnvelope')->where($where)->find();
        //如果当前有红包过期，则剔除 --新增部分
        if($user_envelope['envelope_id'] == $envelope['envelope_id']){
            if($envelope['use_time'] < 
                date('Y-m-d H:i:s',NOW_TIME)){
                M('UserEnvelope')->where(['envelope_id'=>$user_envelope['envelope_id']])->save(['close'=>1]);
            } 
        }
        if(!$envelope || $envelope['closed'] == 1) {
            return $this->error('当前红包不存在或已失效');
        }

        /*  红包竞价  */
        /*if($envelope['is_tui'] == 1){
            D('Envelope')->check_price($envelope['shop_id'],$this->uid,$envelope['jingjia']/100,$envelope_id);
        }*/
        if($envelope['type']==3 && $envelope['num']>0){
            D('Envelope')->check_price($envelope['shop_id'],$this->uid,$envelope['num'],$envelope_id);
        }

        //如果已经有该商家的红包且还未过期则直接提示消费 --新增部分
        if(M('UserEnvelope')->where(['shop_id'=>$envelope['shop_id'],'user_id'=>$this->uid,'close'=>2])->find()){ 
            return $this->success('已经有一个红包，即将进入商家页面', U('wap/shop/detail', array('shop_id' => $envelope['shop_id'])));
        }
        $bg_time = strtotime(TODAY);
        if($EnvelopeLogs = M('EnvelopeLogs')->where(array('user_id'=>$this->uid,'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time)),'envelope_id'=>$envelope_id))->find()){
            if ($envelope['is_shop']) {
                //今天已经领取过，跳转至商家详情页
                return $this->success('已经领取过，即将进入商家页面', U('wap/shop/detail', array('shop_id' => $envelope['shop_id'])));
            } else {
                //平台红包今天已经领取过
                return $this->success('已经领取过', U('wap/index/hongbao'));
            }
        }

        if($envelope['prestore'] < $envelope['single']){
            M('Envelope')->where(array('envelope_id'=>$envelope_id))->save(array('closed'=>1)); //关闭返还
            //如果是商家红包
            if($envelope['is_shop'] == 1){
                $shop = M('Shop')->find($envelope['shop_id']);
                D('Users')->addMoney($shop['user_id'],$envelope['prestore'],'用户兑换的金额大于红包剩余余额，自动关闭该红包');
            }
            return $this->error('当前红包金额不足无法领取');
        }
        //新增部分
        $who = $envelope['is_shop'] ?  '商家': '平台';
        $intro = '【' . $who . '红包】ID【'.$envelope_id.'】用户领取红包【'.round($envelope['single'],2).'】';
        $arr['type'] = $envelope['type'];
        $arr['envelope_id'] = $envelope_id;
        $arr['shop_id'] = $envelope['shop_id'] ? $envelope['shop_id'] : '0';
        $arr['user_id'] = $this->uid;
        $arr['order_id'] = 1;
        $arr['money'] = $envelope['single'];
        $arr['surplus_prestore'] = $envelope['prestore'] - $envelope['single'];
        $arr['intro'] = $intro;
        $arr['create_time'] = time();
        $arr['create_ip'] = get_client_ip();


        if(M('EnvelopeLogs')->add($arr)){
           
            //查询用户红包如果存在，金额累加，否则新增一行 --新增部分
            
            // if ($user_envelope) {
            //     D('UserEnvelope')->where($where)->save(array('envelope' => $user_envelope['envelope'] + $envelope['single'], ));
            // } else {
                $where['envelope'] = $envelope['single'];
                $where['envelope_id'] = $envelope['envelope_id'];
                $where['create_time'] = NOW_TIME;
                D('UserEnvelope')->add($where);
            // }

            D('Envelope')->where(array('envelope_id'=>$envelope_id))->setDec('prestore',$envelope['single']); 
            if ($envelope['is_shop']) {
                //领取成功，跳转至商家详情页
                return $this->success(round($envelope['single'],2) . '元商家红包领取成功，可在指定商家使用', U('wap/shop/detail', array('shop_id' => $envelope['shop_id'])));
            } else {
                //平台红包领取成功
                return $this->success('平台红包领取成功，可以在全平台使用', U('wap/index/hongbao'));
            }        
        }else{
            return $this->error('领取失败');
        }
        
    
    }

    //领取平台通用红包
    public function currency($envelope_id){
        if ($envelope_id < 0) {
            return $this->error('红包ID异常，领取失败');
        }

        $envelope = D('Envelope')->find($envelope_id);

         //如果当前有红包过期，则剔除 --新增部分
        if($user_envelope['envelope_id'] == $envelope['envelope_id']){
            if($envelope['use_time'] < 
                date('Y-m-d H:i:s',NOW_TIME)){
                M('UserEnvelope')->where(['envelope_id'=>$user_envelope['envelope_id']])->save(['close'=>1]);
            } 
        }
        if(!$envelope || $envelope['closed'] == 1) {
            return $this->error('当前红包不存在或已失效');
        }

        $bg_time = strtotime(TODAY);
        if($EnvelopeLogs = M('EnvelopeLogs')->where(array('user_id'=>$this->uid,'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time)),'envelope_id'=>$envelope_id))->find()){
                //平台红包今天已经领取过
                return $this->success('已经领取过', U('wap/index/hongbao'));
        }
        if($this->ispost()){
            $data=array();
            $data['num']=I('post.num');
            $data['money']=I('post.money');
                 //新增部分
            $arr=array();
            $who ='平台';
            $intro = '【' . $who . '通用红包】ID【'.$envelope_id.'】用户领取红包【'.($data['money']).'】领取个数【'.$data['num'].'】';
            $arr['type'] = $envelope['type'];
            $arr['envelope_id'] = $envelope_id;
            $arr['shop_id'] =0;
            $arr['user_id'] = $this->uid;
            $arr['order_id'] = 1;
            $arr['money'] = $data['money'];
            $arr['num']=$data['num'];
            $arr['surplus_prestore'] =$data['money'];
            $arr['intro'] = $intro;
            $arr['create_time'] = time();
            $arr['create_ip'] = get_client_ip();


        if(M('EnvelopeLogs')->add($arr)){
                $where['user_id'] = $this->uid;
                $where['shop_id'] = 0;
                $where['envelope'] = $data['money'];
                $where['num']= $data['num'];
                $where['envelope_id'] = $envelope['envelope_id'];
                $where['create_time'] = NOW_TIME;
                D('UserEnvelope')->add($where);

                //平台红包领取成功
                return $this->success('平台红包领取成功，可以在全平台使用', U('user/envelope/index'));
             
        }else{
            return $this->error('领取失败');
        }

    }

    }

    /**
     * 订单红包领取
     * @param  int $order_id 外卖订单id
     * @return
     */
    public function order($order_id) {

        if ($order_id < 0) {
            return $this->error('订单号异常，领取失败');
        }

        $order = D('EleOrder')->find($order_id);
        if ($order['envelope_status'] == 1) {
            return $this->error('该订单红包已领过，请勿再次操作');
        }

        //匹配红包日志表，为用户入账
        $envelope_logs = D('EnvelopeLogs')->where(array('order_id' => (int)$order_id, 'user_id' => $this->uid))->find();
        if ($order['order_envelope'] == $envelope_logs['money']) {
            $map = array('user_id' => $this->uid, 'shop_id' => $order['shop_id']);
            $user_envelope = D('UserEnvelope')->where($map)->find();

            if ($user_envelope) {
                //存在商家红包记录，金额累计
                D('UserEnvelope')->where($map)->save(array('envelope' => $user_envelope['envelope'] + $order['order_envelope']));
            } else {
                //写入一行
                $user_envelope_id = D('UserEnvelope')->add(array('user_id' => $this->uid, 'shop_id' => $order['shop_id'], 'envelope' => $order['order_envelope'],'envelope_id'=>$envelope_logs['envelope_id'],'create_time'=>NOW_TIME));
            }

            $result = D('EleOrder')->where(array('order_id' => $order['order_id']))->save(array('envelope_status' => 1));
            if ($result) {
                return $this->success('红包领取成功');
            }
        } else {
            return $this->error('订单红包数据异常，领取失败');
        }
    }
    //@pingdan end

    public function hongbaologs(){
        $obj = D('UserEnvelopeLogs');
        import('ORG.Util.Page');
        $map = array('user_id'=>$this->uid);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
            $list[$key]['envelope'] = M('Envelope')->find($val['envelope_id']);
            $list[$key]['user'] = M('Users')->find($val['user_id']);
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


}
