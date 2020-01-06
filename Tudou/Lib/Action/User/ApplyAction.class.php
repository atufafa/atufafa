<?php
class ApplyAction extends CommonAction{
    private $create_fields = array('user_id','city_id','photo', 'logo','price','handle','area_id', 'business_id', 'cate_id', 'user_guide_id','tel', 'logo', 'shop_name', 'contact', 'details', 'business_time', 'area_id', 'addr', 'lng', 'lat', 'recognition','is_pei','goods_cate','grade_id');
	private $delivery_create_fields = array('city_id', 'user_id','photo', 'name', 'mobile', 'addr','recommend');
	
    private $creates = array('auth_id','names','province_id','user_id','goods_parent_id','city_id','picfan','frontcard','backcard', 'area_id','parent_id', 'business_id','cate_id', 'user_guide_id','tel', 'logo', 'photo', 'shop_name', 'contact', 'details', 'business_time', 'area_id', 'addr', 'lng', 'lat', 'recognition','is_pei','goods_cate','grade_id','price','handle');

    //修改原来的入驻
    public function index(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }

        $Shop = D('Shop')->where(array('user_id'=>$this->uid))->find();
        $yin=D('Audit')->where(array('shop_id'=>$Shop['shop_id']))->find();
        $this->assign('yin',$yin);
        $cate=D('Shopcate')->where(array('cate_id'=>$Shop['cate_id']))->find();
        $this->assign('cate',$cate);
        $guide_id = I('guide_id');
        $this->assign('guide_id', $guide_id);
        if($this->isPost()){
            $post = $this->_post('data');
            $sh = D('Shop')->where(array('user_id'=>$this->uid))->find();
            $data = $this->createCheck();
            //首先计算入驻费用
            $parent_id = $this->_post('parent_id');
            if(!empty($data['cate_id'])){
                $parent_id = $data['cate_id'];
            }
            $cates = D('Shopcate')->fetchAll();
            $shop_apply_prrice = ((float)$data['price'])+((float)$data['handle']);
			$obj = D('Shop');
            $details = $this->_post('details', 'htmlspecialchars');


            if($words = D('Sensitive')->checkWords($details)){
				$this->ajaxReturn(array('code'=>'0','msg'=>'商家介绍含有敏感词：' . $words));
            }
          
			
			if($shop_apply_prrice > 0){
				if(!($code = $_POST['code'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'请选择支付方式'));
				}
			}

			if($code=='money'){
			    $use=D('Users')->find($this->uid);
			    if($use['money']<$shop_apply_prrice){
                    $this->ajaxReturn(array('code'=>'0','msg'=>'您的余额不足！'));
                }
            }
            if($sh['parent_id']==100){
                $data['is_pay'] = 1;
            }
            $data['deposit'] = $data['handle'];
			$data['administration'] = $data['price'];
            unset($data['near'], $data['price'], $data['business_time']);
            $data['business_type'] = $post['business_type'];
            $data['user_guide_id']=$post['user_guide_id'];
            if($shop_id = $obj->where(array('shop_id'=>$Shop['shop_id']))->save($data)){
                $data['addr'] = $post['address'];
                $data['name'] = $post['name'];
              	$data['zhucehao'] = $post['zhucehao'];
                $data['end_date'] = $post['end_date'];
                $data['zuzhidaima'] = $post['zuzhidaima'];
                $data['user_name'] = $post['user_name'];
                $obj = D('Audit')->where(array('shop_id'=>$Shop['shop_id']))->save($data);

                if($shop_apply_prrice > 0){
                    $arr = array(
                        'type' => 'shop',
                        'user_id' => $this->uid,
                        'order_id' => $shop_id,
                        'code' => $code,
                        'need_pay' => $shop_apply_prrice,
                        'create_time' => time(),
                        'create_ip' => get_client_ip(),
                        'is_paid' => 0,
                        'deposit'=>$data['handle']
                    );
                    if($log_id = D('Paymentlogs')->add($arr)){
                        if($sh['parent_id']==100){
                            $this->ajaxReturn(array('code'=>'1','msg'=>'恭喜您申请成功','url'=>U('member/index')));
                        }else{
                            $this->ajaxReturn(array('code'=>'1','msg'=>'正在跳转为您支付','url'=>U('wap/payment/payment', array('log_id' =>$log_id))));
                        }
                    }else{
                        $this->ajaxReturn(array('code'=>'0','msg'=>'设置订单失败'));
                    }

                }else{
                    $this->ajaxReturn(array('code'=>'1','msg'=>'恭喜您申请成功','url'=>U('member/index')));
                }
            }
            $this->ajaxReturn(array('code'=>'0','msg'=>'申请失败'));
        }else{
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if(empty($lat) || empty($lng)) {
                $lat = $this->_CONFIG['site']['lat'];
                $lng = $this->_CONFIG['site']['lng'];
            }

            $this->assign('list',$Shop);
            $this->assign('lat', $lat);
            $this->assign('lng', $lng);
            $this->assign('cates', D('Shopcate')->fetchAll());
            $this->assign('goods', D('Goodscate')->fetchAll());

            $this->assign('auth', D('ShopgradeAuth')->select());
            // dump( D('ShopgradeAuth')->fetchAll());die;
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->display();
        }
    }

    //新入驻申请
    public function shenqin(){
        $guide_id = I('guide_id');
        $this->assign('guide_id', $guide_id);

        if(IS_POST){
            $post = $this->_post('data');
            $data = $this->creates();

            $obj = D('Shop');
            $details = $this->_post('details', 'htmlspecialchars');

            $ex = array('details' => $details,'near' => $data['near'], 'price' => $data['price'], 'handle' => $data['handle'], 'business_time' => $data['business_time']);
            // var_dump($data);exit();
            unset($data['near'], $data['price'], $data['business_time']);
            $data['business_type'] = $post['business_type'];
            $data['user_guide_id']=$post['user_guide_id'];

            if($shop_id = $obj->add($data)) {
                D('Shopdetails')->upDetails($shop_id, $ex);
                D('Shopguide')->upAdd($data['user_guide_id'], $shop_id);//新增到推荐人表
                D('Shop')->buildShopQrcode($shop_id, 15);//生成商家二维码

                $data['shop_id'] = $shop_id;
                $data['yingye'] = $post['yingye'];
                $data['weisheng'] = $post['weisheng'];
                $data['pic'] = $post['pic'];
                $data['picfan']=$post['picfan'];
                $data['frontcard']=$post['frontcard'];
                $data['backcard']=$post['backcard'];
                $data['audit']=1;
                $obj = D('Audit')->add($data);

                $this->ajaxReturn(array('code'=>'1','msg'=>'恭喜您申请成功','url'=>U('member/index')));
            }
			$this->ajaxReturn(array('code'=>'0','msg'=>'申请失败'));
        }else{
            $lat = addslashes(cookie('lat'));
            $lng = addslashes(cookie('lng'));
            if(empty($lat) || empty($lng)) {
                $lat = $this->_CONFIG['site']['lat'];
                $lng = $this->_CONFIG['site']['lng'];
            }
            $this->assign('lat', $lat);
            $this->assign('lng', $lng);
            $this->assign('cates', D('Shopcate')->fetchAll());
            $this->assign('goods', D('Goodscate')->fetchAll());

            $this->assign('auth', D('ShopgradeAuth')->select());
            $this->assign('payment', D('Payment')->getPayments(true));
            //查询地址
            $this->assign('province',D('province')->where(['is_open'=>1])->select());
            //查询协议
            $xueyi=D('Agreementlist')->where(['x_id'=>5])->find();
            $this->assign('title',$xueyi['title']);
            $this->assign('content',$xueyi['details']);
            $this->display();
        }
    }
	
	
    private function createCheck(){
      
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);

		$data['user_id'] = $this->uid;
		$guide_ids = htmlspecialchars($data['user_guide_id']);
		if($guide_ids != '此处填写用户的推广ID，如没有可以不填'){
            $data['user_guide_id'] = explode(',',$guide_ids);
            if($guide_ids){
                if (false == D('Shopguide')->check_user_guide_id($data['user_guide_id'])){
                    $this->tuMsg(D('Shopguide')->getError());
                }
            }
        }
        $data['photo'] = htmlspecialchars($data['photo']);

        if(empty($data['photo'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'请上传商家形象图'));
        }
        if(!isImage($data['photo'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'商家形象图格式不正确'));
        }
		$data['logo'] = htmlspecialchars($data['logo']);
        $data['shop_name'] = htmlspecialchars($data['shop_name']);

        if(empty($data['shop_name'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'店铺名称不能为空'));
        }

        $post = $this->_post('data');

        if($post['business_type'] == 2){
          		if (empty($post['name'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写企业名称'));
                }
          
         		 if (empty($post['address'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写营业地址'));
                }
          
          		if (empty($post['zhucehao'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写注册号'));
                }
          
          		if (empty($post['zuzhidaima'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写组织代码'));
                }
        }
        if(false !== ($cate_ids = D('Shopcate')->where(['cate_id'=>$data['cate_id']])->find()) && $cate_ids['is_weisheng'] == 1){
            if (empty($post['weisheng'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请上传卫生许可证'));
            }     
        }
      
      if (!$post['user_name']) {
           $this->ajaxReturn(array('code'=>'0','msg'=>'请填写个人姓名'));
        }
      
      if (!$post['mobile']) {
           $this->ajaxReturn(array('code'=>'0','msg'=>'请填写个人手机'));
        }

        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        $data['contact'] = htmlspecialchars($data['contact']);
        $data['business_time'] = htmlspecialchars($data['business_time']);
       
        $data['tel'] = htmlspecialchars($data['tel']);
        if(empty($data['tel'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号不能为空'));
        }
        if(!isPhone($data['tel']) && !isMobile($data['tel'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号格式不正确'));
        }
        if(isMobile($data['tel'])){
            $data['mobile'] = $data['tel'];
        }
        $data['auth_id'] = $post['grade_id'];
        $data['recognition'] = 1;
        $data['user_id'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        // print_r($data);die;
        return $data;
    }
//原验证
//    private function createCheck(){
//
//        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
//
//        //     print_r($data);  die;
//
//        $detail = D('Shop')->where(array('user_id' =>$this->uid))->find();
//        if(!empty($detail)){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'您已经是商家了'));
//        }
//        $data['user_id'] = $this->uid;
//        $guide_ids = htmlspecialchars($data['user_guide_id']);
//        if($guide_ids != '此处填写用户的推广ID，如没有可以不填'){
//            $data['user_guide_id'] = explode(',',$guide_ids);
//            if($guide_ids){
//                if (false == D('Shopguide')->check_user_guide_id($data['user_guide_id'])){
//                    $this->tuMsg(D('Shopguide')->getError());
//                }
//            }
//        }
//
//        $data['photo'] = htmlspecialchars($data['photo']);
//
//        if(empty($data['photo'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传商家形象图'));
//        }
//        if(!isImage($data['photo'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'商家形象图格式不正确'));
//        }
//        $data['logo'] = htmlspecialchars($data['logo']);
//        $data['shop_name'] = htmlspecialchars($data['shop_name']);
//        if(empty($data['shop_name'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'店铺名称不能为空'));
//        }
//        //$data['grade_id'] = htmlspecialchars($data['grade_id']);
//        $data['grade_id'] = $data['grade_id'];
//        $gradename = D("Shopgradeauth")->where("grade_id=".$data['grade_id'])->field("grade_name")->find();
//        if($gradename[grade_name]=='商城'){
//            $data['cate_id'] = (int) $data['cate_id'];
//            if(empty($data['cate_id'])){
//                $this->ajaxReturn(array('code'=>'0','msg'=>'分类不能为空'));
//            }
//        }
//        $data['city_id'] = (int) $data['city_id'];
//        if(empty($data['city_id'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'城市不能为空'));
//        }
//
//        if(empty($data['addr'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'店铺地址不能为空'));
//        }
//
//        if(empty($data['contact'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'店铺联系人不能为空'));
//        }
//        $post = $this->_post('data');
//        //print_r($post);die;
//        if($post['business_type'] == 2){
//            $photo = $post['yingye'];
//
//            if (empty($post['yingye'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请上传营业执照'));
//            }
//            if (empty($post['name'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写企业名称'));
//            }
//
//            if (empty($post['address'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写营业地址'));
//            }
//
//            if (empty($post['zhucehao'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写注册号'));
//            }
//
//            if (empty($post['zuzhidaima'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请填写组织代码'));
//            }
//        }
//        if(false !== ($cate_ids = D('Shopcate')->where(['cate_id'=>$data['cate_id']])->find()) && $cate_ids['is_weisheng'] == 1){
//            if (empty($post['weisheng'])) {
//                $this->ajaxReturn(array('code'=>'0','msg'=>'请上传卫生许可证'));
//            }
//        }
//        $pic = $post['pic'];
//        if (!$pic) {
//            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传身份证，需个人手持身份证'));
//        }
//        if (!isImage($pic)) {
//            $this->ajaxReturn(array('code'=>'0','msg'=>'所上传的身份证格式不正确'));
//        }
//
//        if (!$post['user_name']) {
//            $this->ajaxReturn(array('code'=>'0','msg'=>'请填写个人姓名'));
//        }
//
//        if (!$post['mobile']) {
//            $this->ajaxReturn(array('code'=>'0','msg'=>'请填写个人手机'));
//        }
//
//        $data['area_id'] = (int) $data['area_id'];
//        $data['business_id'] = (int) $data['business_id'];
//        $data['lng'] = htmlspecialchars($data['lng']);
//        $data['lat'] = htmlspecialchars($data['lat']);
//        $data['contact'] = htmlspecialchars($data['contact']);
//        $data['business_time'] = htmlspecialchars($data['business_time']);
//        $data['addr'] = htmlspecialchars($data['addr']);
//        $data['tel'] = htmlspecialchars($data['tel']);
//        if(empty($data['tel'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'手机号不能为空'));
//        }
//        if(!isPhone($data['tel']) && !isMobile($data['tel'])){
//            $this->ajaxReturn(array('code'=>'0','msg'=>'手机号格式不正确'));
//        }
//        if(isMobile($data['tel'])){
//            $data['mobile'] = $data['tel'];
//        }
//        $data['auth_id'] = $post['grade_id'];
//        $data['recognition'] = 1;
//        $data['user_id'] = $this->uid;
//        $data['create_time'] = NOW_TIME;
//        $data['create_ip'] = get_client_ip();
//        // print_r($data);die;
//        return $data;
//    }

    private function creates(){

        $data = $this->checkFields($this->_post('data', false), $this->creates);

        //     print_r($data);  die;

        $detail = D('Shop')->where(array('user_id' =>$this->uid))->find();
        if(!empty($detail)){
            $this->ajaxReturn(array('code'=>'0','msg'=>'您已经是商家了'));
        }
        $data['user_id'] = $this->uid;
        $guide_ids = htmlspecialchars($data['user_guide_id']);
        if($guide_ids != '填写用户的推广ID，如没有可以不填'){
            $data['user_guide_id'] = explode(',',$guide_ids);
            if($guide_ids){
                if (false == D('Shopguide')->check_user_guide_id($data['user_guide_id'])){
                    $this->tuMsg(D('Shopguide')->getError());
                }
            }
        }
        $data['parent_id'] = (int) $data['parent_id'];
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if(empty($data['shop_name'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'店铺名称不能为空'));
        }

        $data['names'] = htmlspecialchars($data['names']);
        if(empty($data['names'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'公司或个体名称不能为空'));
        }


        $data['grade_id'] = $data['grade_id'];
        $gradename = D("Shopgradeauth")->where("grade_id=".$data['grade_id'])->field("grade_name")->find();
        if($gradename[grade_name]=='商城'){
            $data['cate_id'] = (int) $data['cate_id'];
            if(empty($data['cate_id'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'分类不能为空'));
            }
        }
        $data['goods_parent_id'] = (int) $data['goods_parent_id'];
        $data['province_id'] = (int) $data['province_id'];
        if(empty($data)){
            $this->ajaxReturn(array('code'=>'0','msg'=>'省份不能为空'));
        }

        $data['city_id'] = (int) $data['city_id'];
        if(empty($data['city_id'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'城市不能为空'));
        }


        if(empty($data['contact'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'店铺联系人不能为空'));
        }
        $post = $this->_post('data');

        if(false !== ($cate_ids = D('Shopcate')->where(['cate_id'=>$data['cate_id']])->find()) && $cate_ids['is_weisheng'] == 1){
            if (empty($post['weisheng'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'请上传卫生许可证'));
            }
        }
        $pic = $post['pic'];
        if (!$pic) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传身份证，需个人手持身份证正面'));
        }
        if (!isImage($pic)) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'所上传的身份证格式不正确'));
        }

        $pics = $post['picfan'];
        if (!$pic) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传身份证，需个人手持身份证背面'));
        }
        if (!isImage($pics)) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'所上传的身份证格式不正确'));
        }

        $frontcard = $post['frontcard'];
        if (!$frontcard) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传身份证正面'));
        }
        if (!isImage($frontcard)) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'所上传的身份证格式不正确'));
        }

        $backcard = $post['backcard'];
        if (!$backcard) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传身份证背面'));
        }
        if (!isImage($backcard)) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'所上传的身份证格式不正确'));
        }
        
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        $data['contact'] = htmlspecialchars($data['contact']);
        $data['tel'] = htmlspecialchars($data['tel']);
        if(empty($data['tel'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'手机号不能为空'));
        }
        if(!isPhone($data['tel']) && !isMobile($data['tel'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'手机号格式不正确'));
        }
        if(isMobile($data['tel'])){
            $data['mobile'] = $data['tel'];
        }
        $data['auth_id']=htmlspecialchars($data['auth_id']);
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        $data['addr'] = htmlspecialchars($data['addr']);
        $data['recognition'] = 1;
        $data['user_id'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }

	
	public function delivery(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }

        $p=D('Delivery')->where(array('user_id'=>$this->uid))->find();
        if($p['is_pay']!=1 && !empty($p)){
            D('Delivery')->where(['user_id'=>$this->uid,'id'=>$p['id']])->delete();
        }

        $peison=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>1))->find();
        $paotui=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>2))->find();
        $siji=D('Userspinche')->where(array('user_id'=>$this->uid))->find();
        $peisonadmin=D('Applicationmanagement')->where(array('user_id'=>$this->uid))->find();
        $daili=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>1))->find();
        $chenshi=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>2))->find();
        if(!empty($paotui) || !empty($peison) || !empty($siji) || !empty($peisonadmin) || !empty($daili) || !empty($chenshi)){
            $this->error('您已注册配送员或跑腿、司机、配送管理员、代理、城市合伙人，请重新换号注册！');
        }
        

		$obj = D('Delivery');
        $level = D('DeliveryLevel')->select();
        $this->assign('level', $level);
        $this->assign('payment', D('Payment')->getPayments(true));
		$user_delivery = $obj->where(array('user_id' => $this->uid))->find();
		if($user_delivery['closed'] !=0){
			$this->error('非法错误');
		}
        if($this->isPost()){
            $xue=I('post.checkbox');
            if(empty($xue)){
                $this->tuMsg('请勾选同意协议');
            }
            $data = $this->delivery_createCheck();
            if ($obj->add($data)){
                $this->tuMsg('恭喜您申请成功', U('user/member/index'));
            }else{
				$this->tuMsg('申请失败');
			}
        }else{
            //查询协议
            $xueyi=D('Agreementlist')->where(['x_id'=>2])->find();
            $this->assign('title',$xueyi['title']);
            $this->assign('content',$xueyi['details']);
			$this->assign('user_delivery', $user_delivery);
            $this->display();
        }
    }
	
	 private function delivery_createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->delivery_create_fields);
		$data['user_id'] = $this->uid;
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuMsg('请上传身份证');
        }
        if(!isImage($data['photo'])){
            $this->tuMsg('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if(empty($data['name'])){
            $this->tuMsg('姓名不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if(empty($data['mobile'])){
            $this->tuMsg('手机号不能为空');
        }
        if(!isPhone($data['mobile']) && !isMobile($data['mobile'])){
            $this->tuMsg('手机号格式不正确');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if(empty($data['addr'])){
            $this->tuMsg('地址不能为空');
        }        
        $data['auth_id'] = htmlspecialchars($data['grade_id']);
         $data['recommend'] = htmlspecialchars($data['recommend']);

         if(!empty($data['recommend'])){
             if(false == D('Users')->where(['user_id'=>$data['recommend']])->find()){
                 $this->tuMsg('该推荐人不存在');
             }
         }

        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
	
	
	
	public function worker(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
		$obj = D('Shopworker');
		$worker = $obj->where(array('user_id' => $this->uid))->find();
		if($worker['closed'] ==1){
			$this->error('非法错误');
		}
        if($this->isPost()) {
            $data = $this->checkFields($this->_post('data', false),array('shop_id','name','tel','mobile','qq','weixin','work','addr'));
			$data['user_id'] = $this->uid;
			$data['shop_id'] = $data['shop_id'];
			if(empty($data['shop_id'])){
				$this->tuMsg('商家ID不能为空');
			}
			$data['name'] = htmlspecialchars($data['name']);
			if (empty($data['name'])){
				$this->tuMsg('姓名不能为空');
			}
			$data['mobile'] = $data['mobile'];
			if(empty($data['mobile'])){
				$this->tuMsg('手机号码不能为空');
			}
			$data['work'] = htmlspecialchars($data['work']);
			if(empty($data['work'])){
				$this->tuMsg('员工职务不能为空');
			}
			$data['addr'] = htmlspecialchars($data['addr']);
			if(empty($data['addr'])){
				$this->tuMsg('联系地址不能为空');
			}
			
			
			//$obj->add($data);
			//p($obj->getLastSql());die;
            if($obj->add($data)){
				
                $this->tuMsg('恭喜您申请店员成功', U('user/member/index'));
            }else{
				$this->tuMsg('申请失败');
			}
        }else{
			$this->assign('worker', $worker);
            $this->display();
        }
    }

	public function artlist(){
		$id = $_GET['id'];
		if($id){
			$Article = D('Article')->where('article_id='.$id)->find();
		}else{
			$Article = D('Article')->where('article_id=999999')->find();
		}
 //     print_r($Article);
      $this->assign('art',$Article);
      $this->display();
    }

    public function goods_cat(){
        if(IS_AJAX){
            $cate_id=$_POST['codeid'];
            $goods=M('goods_cate')->where(['parent_id'=>$cate_id])->select();
            if(!empty($goods)){
                echoJson(['ret'=>1,'data'=>$goods]);
            }
        }

    }















}