<?php
class StoreAction extends CommonAction{
    private $create_fields = array('shop_id','province_id','business_id','is_carry','is_logistics','logistics_money', 'distribution', 'is_open','is_fan', 'fan_money', 'is_new', 'full_money', 'new_money', 'logistics', 'since_money', 'sold_num', 'month_num', 'intro', 'orderby');
    protected $store;
    public function _initialize(){
        parent::_initialize();
		if(empty($this->_CONFIG['operation']['store'])) {
            $this->error('便利店功能已关闭');
            die;
        }
        $getStoreCate = D('Store')->getStoreCate();
        $this->assign('getStoreCate', $getStoreCate);
		$getEleCateIds = D('Store')->getEleCateIds();
        $this->assign('getEleCateIds', $getEleCateIds);
		
        $this->store = D('Store')->find($this->shop_id);
        if (empty($this->store) && ACTION_NAME != 'apply') {
            $this->error('您还没有入住便利店频道', U('store/apply'));
        }
        if (!empty($this->store) && $this->store['audit'] == 0) {
            $this->error('亲，您的申请正在审核中');
        }
        $this->assign('store', $this->store);
    }
    public function index(){
		$file['small_file'] = D('Store')->get_file_Code($this->shop_id,8);//生成二维码
		$file['middle_file'] = D('Store')->get_file_Code($this->shop_id,15);//生成二维码
		$file['big_file'] = D('Store')->get_file_Code($this->shop_id,100);//生成二维码
        $this->assign('file', $file);
        $this->display();
    }
    public function open(){
        $is_open = (int) $_POST['is_open'];
		$is_coupon = (int) $_POST['is_coupon'];
		$is_new = (int) $_POST['is_new'];
		$full_money = (float) ($_POST['full_money']);
		$new_money = (float) ($_POST['new_money']);
		$is_yuyue = (int) $_POST['is_yuyue'];//新增预约
        $is_carry = (int) $_POST['is_carry'];//新增到店自取
		$is_full = (int) $_POST['is_full'];
		$is_logistics = (int) $_POST['is_logistics'];//新增，物流
        $logistics_money = (int) $_POST['logistics_money'];
        if($is_logistics==1 && empty($logistics_money)){
            $this->tuError('请输入物流运费');
        }
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
		if(false !==($shop =D('Store')->find()) && $shop['is_pei'] == 0){
            $logistics = $this->_CONFIG['site']['store_pei'];   
        }else{
            $logistics = (float) ($_POST['logistics']);
        }
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
		$is_refresh = (int) $_POST['is_refresh'];
		$is_refresh_second = $_POST['is_refresh_second'];
		$tags = $_POST['tags'];

        if($is_yuyue==0){
            D('Storeproduct')->where(array('shop_id'=>array('IN',$this->shop)))->save(array('is_yuyue'=>0,'closed'=>1));
        }
        D('Store')->save(array(
			'shop_id' => $this->shop_id, 
			'is_open' => $is_open,
			'is_coupon' => $is_coupon, 
			'is_new' => $is_new,
			'is_yuyue'=>$is_yuyue,
			'full_money' => $full_money, 
			'new_money' => $new_money, 
			'is_full' => $is_full,
			'is_carry'=>$is_carry,
			'is_logistics'=>$is_logistics,
			'logistics_money'=>$logistics_money,
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
			'tags' => $tags
		));
        $this->tuSuccess('操作成功', U('store/index'));
    }
    public function apply(){
//        $this->assign('area', D('Area')->fetchAll());
//        $this->assign('city', D('City')->fetchAll());
        $this->assign('province', D('province')->order('province_id asc')->select());
        $this->assign('area', D('Area')->fetchAll());
        $this->assign('city', D('City')->order('first_letter desc')->fetchAll());

        if($this->isPost()){
            $data = $this->applyCheck();
            $obj = D('Store');
            $cate = $this->_post('cate', false);
            // $cate = implode(',', $cate);
            $data['cate'] = $cate;
            if($obj->add($data)){
                $this->tuSuccess('添加成功', U('store/index'));
            }
            $this->tuError('操作失败');
        }else{
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
        $data['logistics'] = $this->_CONFIG['site']['store_pei'];  
        $data['shop_name'] = $shop['shop_name'];
        $data['lng'] = $shop['lng'];
        $data['lat'] = $shop['lat'];
        $data['province_id'] = (int) $data['province_id'];
        if(empty($data['province_id'])){
            $this->tuError('请选择所在区域');
        }

        $data['area_id'] = $shop['area_id'];
        $data['city_id'] = $shop['city_id'];
        $data['business_id'] =(int) $data['business_id'];
        $data['is_open'] = (int) $data['is_open'];
        $data['is_fan'] = (int) $data['is_fan'];
        $data['fan_money'] = (float) ($data['fan_money']);
        $data['is_new'] = (int) $data['is_new'];
        $data['full_money'] = (float) ($data['full_money'] );
        $data['new_money'] = (float) ($data['new_money']);
         $data['logistics'] = (float) ($data['logistics'] );
        $data['since_money'] = (float) ($data['since_money']);
        $data['distribution'] = (int) $data['distribution'];
        $data['intro'] = htmlspecialchars($data['intro']);
        if (empty($data['intro'])) {
            $this->tuError('说明不能为空');
        }
        return $data;
    }

    //便利店
    public function store(){
        $Marketdianping = D('Storedianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $Marketdianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Marketdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $order_ids[$val['order_id']] = $val['order_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($order_ids)) {
            $this->assign('pics', D('StoreDianpingPics')->where(array('order_id' => array('IN', $order_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['order_id'], $order_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //便利店点评回复
    public function storereply($dianping_id){
        $dianping_id = (int) $dianping_id;
        $detail = D('StoreDianping')->where(array('dianping_id'=>$dianping_id))->find();
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->tuError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('reply' => $reply);
                if (D('StoreDianping')->where(array('dianping_id'=>$dianping_id))->save($data)) {
                    $this->tuSuccess('回复成功', U('store/store'));
                }
            }
            $this->tuError('请填写回复');
        } else {
            $this->assign('detail', $detail);
            $this->display();
        }
    }
    //点评删除
    public function comment_delete($comment_id =0)
    {
        if ($comment_id = (int) $comment_id){
            $obj = D('Storedianping');
            $detail = D('Storedianping')->where(array('dianping_id' => $comment_id, 'shop_id' => $this->shop_id))->find();
            if (!$detail){
                $this->tuError('点评记录不存在');
            }
            if($obj->delete($comment_id)){
                $this->tuSuccess('删除成功', U('store/store'));
            }
            $this->tuError('操作失败');
        } else {
            $this->tuError('请选择要删除的点评');
        }
    }
}