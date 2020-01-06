<?php
class EnvelopeAction extends CommonAction{
	
	public function _initialize(){
        parent::_initialize();
        $this->assign('types', D('Envelope')->getType());
		$this->assign('range', D('Envelope')->getRange());
		$this->assign('orderTypes', D('Envelope')->getOrderType());
    }
	

	
	
	public function index(){
        $obj = D('Envelope');
        import('ORG.Util.Page');
        $map = array();
        if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))){
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        }else{
            if($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
		if(isset($_GET['closed']) || isset($_POST['closed'])){
            $closed = (int) $this->_param('closed');
            if($closed != 999){
                $map['closed'] = $closed;
            }
            $this->assign('closed', $closed);
        }else{
            $this->assign('closed', 999);
        }
		
		//创建红包类型
		if(isset($_GET['type']) || isset($_POST['type'])){
            $type = (int) $this->_param('type');
            if($type != 999){
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
		
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('envelope_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function logs(){
        $obj = D('EnvelopeLogs');
        import('ORG.Util.Page');
        $map = array();
        if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))){
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        }else{
            if($bg_date = $this->_param('bg_date', 'htmlspecialchars')){
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if($end_date = $this->_param('end_date', 'htmlspecialchars')){
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($user_id = (int) $this->_param('user_id')){
            $map['user_id'] = $user_id;
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
        }
		
		if($envelope_id = (int) $this->_param('envelope_id')){
            $map['envelope_id'] = $envelope_id;
            $this->assign('envelope_id', $envelope_id);
        }
		
		//创建红包类型
		if(isset($_GET['type']) || isset($_POST['type'])){
            $type = (int) $this->_param('type');
            if($type != 999){
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
		
		//原始订单记录
		if(isset($_GET['orderType']) || isset($_POST['orderType'])){
            $orderType = (int) $this->_param('orderType');
            if($orderType != 999){
                $map['orderType'] = $orderType;
            }
            $this->assign('orderType', $orderType);
        }else{
            $this->assign('orderType', 999);
        }
		
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
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
	
	
		
	//@pingdan 去除商家红包添加
    public function create(){
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('shop_id','title','intro','prestore','single','bg_date','use_time','end_time'));

			$data['title'] = htmlspecialchars($data['title']);
			if(empty($data['title'])){
				$this->tuError('红包标题不能为空');
			}
			$data['intro'] = htmlspecialchars($data['intro']);
			if(empty($data['intro'])){
				$this->tuError('红包说明不能为空');
			}
			$data['prestore'] = (float) ($data['prestore']);
			if(empty($data['prestore'])){
				$this->tuError('红包总额不能为空');
			} 
            //随机产生红包数
            $data['single'] = (float) ($data['single']);
			if(empty($data['single'])){
				$this->tuError('单个红包金额不能为空');
			}

			// $data['ratio'] = $data['ratio'];
			// if(empty($data['ratio'])){
			// 	$this->tuError('红包发放比例不能为空');
			// }
			// if($data['ratio']*100 >= 1000){
			// 	$this->tuError('红包发放百分比例不正确，建议填写1-10');
			// }
			$data['bg_date'] = htmlspecialchars($data['bg_date']);
			if(empty($data['bg_date'])){
				$this->tuError('开始时间不能为空');
			}
			 //新增
            $data['use_time'] = htmlspecialchars($data['use_time']);
            if(empty($data['use_time'])){
                $this->tuError('红包作废时间不能为空');
            }
           
            $data['end_time'] = htmlspecialchars($data['end_time']);
            if(empty($data['end_time'])){
                $this->tuError('红包结束时间不能为空');
            }
            $data['type'] = 1;
            $data['status'] = 1;
            $data['is_shop'] = 0;
			$data['closed'] = 0;
			$data['create_time'] = NOW_TIME;
			$data['create_ip'] = get_client_ip();

            if($envelope_id = D('Envelope')->add($data)){

                $intro = '添加平台红包成功';

                $this->tuSuccess($intro, U('envelope/index'));
            }
           $this->tuError('操作失败');
        }else{
            $this->display(); 
        }
    }
	
	
	//完成红包
    public function closed($envelope_id = 0){
		if(!($detial = M('Envelope')->find($envelope_id))){
           $this->tuError('红包不存在');
        }
		if($detial['closed'] != 0){
			$this->tuError('当前红包状态不正确');
		}
		
		//如果是商家红包验证商家
		if($detial['type'] == 2){
			if(!($shop = M('Shop')->find($detial['shop_id']))){
			   $this->tuError('商家不存在');
			}
		}
		
		if(M('Envelope')->save(array('envelope_id' => $envelope_id,'closed' => 1))){
			if($detial['prestore'] > 0 && $detial['type'] == 2){
				D('Users')->addMoney($shop['user_id'],$detial['prestore'],'红包ID【'.$envelope_id.'】结束退还剩余资金');
			}
			$this->tuSuccess('操作成功', U('envelope/index'));
		}else{
			$this->tuError('操作失败');
		}
	
		
    }
	
    /**
     * 引流红包审核通过操作
     * @param  integer $envelope_id [description]
     * @return [type]               [description]
     */
    public function confirm($envelope_id = 0) {
        if(!($detial = M('Envelope')->find($envelope_id))){
           $this->tuError('红包不存在');
        }
        if($detial['closed'] != 0){
            $this->tuError('当前红包状态不正确');
        }

        //如果商家发布拼团红包，平台审核通过后，将现价修改为商家填写的拼团红包最低价
        $good=D('Goods');
        $config = D('Setting')->fetchAll();
        $times=$config['shop']['xiajia'];
        $time=date('Y-m-d H:i:s',$detial['create_time']);
        $end_time=date($time,strtotime('+'.$times.'hour'));
        $lower_shelf=strtotime($end_time);
        $arr=array(
            'ping_money'=>$detial['goods_money'],
            'is_pintuan'=>1,
            'is_reight'=>1,
            'is_vs5'=>1
        );
        if($detial['type']==2){
            $row=$good->where(array('goods_id'=>$detial['goods_id'],'shop_id'=>$detial['shop_id']))->save($arr);
            M('Envelope')->save(array('envelope_id' => $envelope_id,'lower_shelf' => $lower_shelf));
        }
        if(M('Envelope')->save(array('envelope_id' => $envelope_id,'status' => 1))){
            $this->tuSuccess('操作成功', U('envelope/index'));
        }else{
            $this->tuError('操作失败');
        }
    }
    /**
     * 引流红包审核拒绝操作
     * @param  integer $envelope_id [description]
     * @return [type]               [description]
     */
    public function confirm_del($envelope_id = 0)
    {
        if(!($detial = M('Envelope')->find($envelope_id))){
           $this->tuError('红包不存在');
        }
        if($detial['closed'] != 0){
            $this->tuError('当前红包状态不正确');
        }
        if(false !==(M('Envelope')->save(array('envelope_id' => $envelope_id,'status' => 2,'closed'=>1)))){
            $envelope = M('Envelope')->where(['envelope_id' => $envelope_id])->find();
            $user = D('Shop')->where(['shop_id'=>$envelope['shop_id']])->find();
            D('Users')->addMoney($user['user_id'],$envelope['prestore'],'引流红包申请被拒绝，返还金额￥'.$envelope['prestore']);
            $this->tuSuccess('操作成功', U('envelope/index'));
        }else{
            $this->tuError('操作失败');
        }
    }

   
}
