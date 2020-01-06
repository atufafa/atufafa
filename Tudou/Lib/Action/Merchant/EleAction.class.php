<?php
class EleAction extends CommonAction{
    private $create_fields = array('shop_id','province_id','business_id','distribution', 'is_open', 'is_carry', 'is_fan', 'fan_money', 'is_new', 'full_money', 'new_money', 'logistics', 'since_money', 'sold_num', 'month_num', 'intro', 'orderby');
    protected $ele;
    public function _initialize(){
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['ele'])) {
            $this->error('外卖功能已关闭');
            die;
        }
        $getEleCate = D('Ele')->getEleCate();
        $this->assign('getEleCate', $getEleCate);
		
        $getEleCates = D('Ele')->getEleCates();
        $this->assign('getEleCates', $getEleCates);
        $this->ele = D('Ele')->find($this->shop_id);
        if (empty($this->ele) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住外卖频道', U('ele/apply'));
        }
        if (!empty($this->ele) && $this->ele['audit'] == 0) {
            $this->error('亲，您的申请正在审核中');
        }
        $this->assign('ele', $this->ele);
    }
    public function index(){
		$file['small_file'] = D('Ele')->get_file_Code($this->shop_id,8);//生成二维码
		$file['middle_file'] = D('Ele')->get_file_Code($this->shop_id,15);//生成二维码
		$file['big_file'] = D('Ele')->get_file_Code($this->shop_id,100);//生成二维码
        $this->assign('file', $file);
        $this->display();
    }
    public function open(){
        $is_open = (int) $_POST['is_open'];
		$is_coupon = (int) $_POST['is_coupon'];
		$is_new = (int) $_POST['is_new'];
		$full_money = (float) ($_POST['full_money']);
		$new_money = (float) ($_POST['new_money']);
        $is_hongbao = (int) $_POST['is_hongbao']; //新增订单红包开关
        $is_yuyue = (int) $_POST['is_yuyue'];
		$is_carry = (int) $_POST['is_carry'];
		$is_full = (int) $_POST['is_full'];
		$order_price_full_1 = (float) ($_POST['order_price_full_1']);
		$order_price_reduce_1 = (float) ($_POST['order_price_reduce_1']);
		$order_price_full_2 = (float) ($_POST['order_price_full_2']);
		$order_price_reduce_2 = (float) ($_POST['order_price_reduce_2']);
		if($is_full){
			if($order_price_full_1 == 0 || $order_price_reduce_1 == 0){
				$this->tuError('满多少1或者减多少1必填或者填写错误');
			}
			if($order_price_reduce_1 >= $order_price_full_1){
				$this->tuError('减去多少1不能大于满多少1');
			}
			if($order_price_full_2){
				if($order_price_full_2 == 0 || $order_price_reduce_2 == 0){
					$this->tuError('满多少1或者减多少1必填或者填写错误');
				}
				if($order_price_reduce_2 >= $order_price_full_2){
					$this->tuError('减去多少1不能大于满多少2');
				}
				if($order_price_full_1 >= $order_price_full_2){
					$this->tuError('满多少1不能大于满多少2');
				}
			}
		}
		
		$logistics = (float) ($_POST['logistics']);
		$logistics_full = (float) ($_POST['logistics_full']);
		$busihour = $_POST['busihour'];
        $is_radius = $_POST['is_radius'];
		$given_distribution = $_POST['given_distribution'];
		if($given_distribution !=0){
			if (!($Deliver = D('Delivery')->where(array('id'=>$given_distribution))->find())) {
				$this->tuError('不存在配送员ID');
			}
	    }
		$is_voice = (int) $_POST['is_voice'];
        $is_user_envelope = (int) $_POST['is_user_envelope'];
        $y_bili = (float) ($_POST['y_bili']);
		$is_refresh = (int) $_POST['is_refresh'];
		$is_refresh_second = $_POST['is_refresh_second'];
		$tags = $_POST['tags'];
        if($is_yuyue==0){
            D('Eleproduct')->where(array('shop_id'=>array('IN',$this->shop_id)))->save(array('is_yuyue'=>0,'closed'=>1));
            D('Shop')->where(['shop_id'=>$this->shop_id])->save(['is_yue'=>0]);
        }

        if($is_yuyue==1){
            D('Shop')->where(['shop_id'=>$this->shop_id])->save(['is_yue'=>1]);
        }

        D('Ele')->save(array(
			'shop_id' => $this->shop_id, 
			'is_open' => $is_open,
			'is_coupon' => $is_coupon, 
			'is_new' => $is_new,
			'is_yuyue'=>$is_yuyue,
			'full_money' => $full_money, 
			'new_money' => $new_money, 
			'is_full' => $is_full,
            'is_carry'=>$is_carry,
            'is_hongbao' => $is_hongbao, //新增订单红包开关
			'order_price_full_1' => $order_price_full_1, 
			'order_price_reduce_1' => $order_price_reduce_1, 
			'order_price_full_2' => $order_price_full_2, 
			'order_price_reduce_2' => $order_price_reduce_2, 
			'logistics' => $logistics, 
			'logistics_full' => $logistics_full, 
			'busihour' => $busihour, 
			'is_radius' => $is_radius,
			'given_distribution' => $given_distribution,
			'is_print_deliver' => $is_print_deliver,
			'is_voice' => $is_voice,
			'is_refresh' => $is_refresh,
			'is_refresh_second' => $is_refresh_second,
			'tags' => $tags,
            'y_bili' => $y_bili,
            'is_user_envelope' => $is_user_envelope
		));
        $this->tuSuccess('操作成功', U('ele/index'));
    }
    public function apply(){
//        $this->assign('area', D('Area')->fetchAll());
//        $this->assign('city', D('City')->fetchAll());
        $this->assign('province', D('province')->order('province_id asc')->select());
        $this->assign('area', D('Area')->fetchAll());
        $this->assign('city', D('City')->order('first_letter desc')->fetchAll());
        if ($this->isPost()) {
            $data = $this->applyCheck();
            $obj = D('Ele');
            $cate = $this->_post('cate', false);
            $cate = implode(',', $cate);
            $data['cate'] = $cate;
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('ele/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }
    private function applyCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);

        $data['shop_id'] = $this->shop_id;
        if (empty($data['shop_id'])) {
            $this->tuError('ID不能为空');
        }
        if (!($shop = D('Shop')->find($data['shop_id']))) {
            $this->tuError('商家不存在');
        }
        if($shop['is_pei'] == 0){
            $data['logistics'] = $this->_CONFIG['site']['delivery_pei'];
        }else{
            $data['logistics'] = (float) ($data['logistics']);
        }
        $data['shop_name'] = $shop['shop_name'];
        $data['lng'] = $shop['lng'];
        $data['lat'] = $shop['lat'];
        $data['province_id'] = (int) $data['province_id'];
        if(empty($data['province_id'])){
            $this->tuError('请选择区域');
        }
        $data['area_id'] = $shop['area_id'];
        $data['city_id'] = $shop['city_id'];
        $data['business_id'] =(int) $data['business_id'];
        $data['is_open'] = (int) $data['is_open'];
        $data['is_fan'] = (int) $data['is_fan'];
        $data['fan_money'] = (float) ($data['fan_money'] );
        $data['is_new'] = (int) $data['is_new'];
        $data['full_money'] = (float) ($data['full_money']);
        $data['new_money'] = (float) ($data['new_money'] );
        $data['logistics'] = (float) ($data['logistics'] );
        $data['since_money'] = (float) ($data['since_money']);
        $data['distribution'] = (int) $data['distribution'];
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('说明不能为空');
        }
        return $data;
    }

    //奖励规则
    public function system(){
        $obj=D('Elereward');
        if($this->ispost()){
            $cunzai=$obj->where(array('shop_id'=>$this->shop_id,'audit'=>0))->find();
            if(!empty($cunzai)){
                $this->tuError('您的申请正在核实中，请勿重复提交！');
            }
            $shuomin=I('post.shuomin');
            if(empty($shuomin)){
                $this->tuError('说明不能为空');
            }
            $arr=array();
            $arr['shop_id']=$this->shop_id;
            $arr['reward']=$shuomin;
            $arr['create_time']=NOW_TIME;
            $arr['create_ip']=get_client_ip();
            if($obj->add($arr)){
                $this->tuSuccess('操作成功', U('ele/systemlist'));
            }
            $this->tuError('操作失败');
        }else{
            $config = D('Setting')->fetchAll();
            $this->assign('config',$config);
        }
        $this->display();
    }

    //申请列表
    public function systemlist(){
        $obj = D('Elereward');
        import('ORG.Util.Page');
        //获取当前商家id
        $map = array('shop_id' => $this->shop_id,'closed'=>0);
        $count = $obj->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $obj->where($map)->order('id  desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $this->assign('list',$list);
        $this->assign('page', $show);
        $this->display();
    }
}