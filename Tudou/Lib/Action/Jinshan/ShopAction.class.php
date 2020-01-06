<?php		
class ShopAction extends CommonAction{
    private $create_fields = array('user_id','is_manage','is_display', 'cate_id', 'grade_id', 'city_id', 'area_id', 'business_id', 'shop_name', 'logo', 'mobile', 'photo', 'addr', 'tel', 'extension', 'contact', 'tags', 'near',  'business_time','express_price',  'delivery_time', 'orderby', 'lng', 'lat', 'price', 'recognition','panorama_url','auth_id');
    private $edit_fields = array('user_id','is_register','is_kefu','is_manage','is_display', 'cate_id','grade_id','city_id', 'area_id', 'business_id', 'shop_name', 'mobile', 'logo', 'photo', 'addr', 'tel', 'extension', 'contact', 'tags', 'near', 'business_time', 'delivery_time',  'orderby', 'lng', 'lat', 'price', 'is_ding', 'recognition','is_tuan_pay','is_hotel_pay','panorama_url', 'apiKey', 'mKey', 'partner', 'machine_code', 'service', 'service_audit', 'is_ele_print', 'is_tuan_print', 'is_goods_print', 'is_booking_print','is_appoint_print','service_audit','express_price','commission','bg_date', 'end_date');
    private $edit_fields_jurisdiction = array('is_mall', 'is_tuan', 'is_ele','is_market','is_store','is_news', 'is_hotel', 'is_booking', 'is_farm', 'is_appoint', 'is_huodong', 'is_coupon', 'is_life', 'is_jifen', 'is_cloud','is_book','is_stock','is_edu','is_zhe','is_ktv','is_decorate','is_decorate_num', 'is_mall_num', 'is_tuan_num', 'is_ele_num','is_market_num','is_store_num','is_news_num', 'is_hotel_num', 'is_booking_num', 'is_farm_num', 'is_appoint_num', 'is_huodong_num', 'is_coupon_num', 'is_life_num', 'is_jifen_num', 'is_cloud_num', 'is_book_num', 'is_stock_num', 'is_edu_num', 'is_zhe_num', 'is_ktv_num');
	
	public function _initialize(){
        parent::_initialize();
		$this->assign('cates', D('Shopcate')->fetchAll());
		$this->end_dates = D('Shop')->getEndDate();
        $this->assign('end_dates',$this->end_dates);
        $this->assign('grades',$grades = D('Shopgrade')->where(array('closed'=>0))->select());//增加商家等级
    }
	
	//批量试生产商家二维码
	public function buildqrcode($admin_id){
		$list = M('Shop')->where(array('audit'=>'1','closed'=>'0'))->select();
		$i= 0;
		foreach($list as $k => $val) {
            
			if($val['qrcode'] == ''){
				$i++;
				D('Shop')->buildShopQrcode($val['shop_id'],15);
			}
        }
		
		if($i){
			$explain = '生成'.$i.'个商家二维码';
		}else{
			$explain = '没有可生成的二维码或者操作失败';
		}
		
		$arr['admin_id'] = $admin_id;
		$arr['type'] = 2;
		$arr['intro'] = $explain;
		$arr['create_time'] = NOW_TIME;
		$arr['create_ip'] = get_client_ip();
		M('AdminActionLogs')->add($arr);  
        $this->tuSuccess($explain, U('index/main'));
    }
	
	//删除试生产商家二维
	public function delqrcode($admin_id){
		$list = M('Shop')->where(array('audit'=>'1','closed'=>'0'))->select();
		$i= 0;
		foreach($list as $k => $val){
			if($val['qrcode']){
				$i++;
				M('Shop')->where(array('shop_id'=>$val['shop_id']))->save(array('qrcode'=>''));
			}
        }
		
		if($i){
			$explain = '成功删除'.$i.'个商家二维码';
		}else{
			$explain = '没有可删除的二维码或者操作失败';
		}
		
		$arr['admin_id'] = $admin_id;
		$arr['type'] = 2;
		$arr['intro'] = $explain;
		$arr['create_time'] = NOW_TIME;
		$arr['create_ip'] = get_client_ip();
		M('AdminActionLogs')->add($arr);  
        $this->tuSuccess('成功删除'.$i.'个商家二维码', U('index/main'));
    }
	
	
	
    public function index(){
        $Shop = D('Shop');
        import('ORG.Util.Page');
		$p = (int) $this->_param('p');
        $map = array('closed' => 0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date) + 86400;
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
                $end_time = strtotime($end_date) + 86400;
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if($city_id = (int) $this->_param('city_id')){
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if($area_id = (int) $this->_param('area_id')){
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
		
		if($endDate = (int) $this->_param('endDate')){
			if ($endDate != 999) {
				if($endDate == 1){
					$min = date("Y-m-d",strtotime("-1 day"));
					$max = date("Y-m-d",strtotime("+30 day"));
				}elseif($endDate == 2){
					$min = date("Y-m-d",strtotime("+31 day"));
					$max = date("Y-m-d",strtotime("+60 day"));
				}elseif($endDate == 3){
					$min = date("Y-m-d",strtotime("+61 day"));
					$max = date("Y-m-d",strtotime("+90 day"));
				}elseif($endDate == 4){
					$min = date("Y-m-d",strtotime("+91 day"));
					$max = date("Y-m-d",strtotime("+3600 day"));
				}
				$map['end_date'] = array('between', $min.','.$max);
				$this->assign('endDate', $endDate);
			}
		}else{
			$this->assign('endDate', 999);
		}

            if($isregister = (int) $this->_param('isregister')){
                if ($isregister != 999) {
                    if($isregister == 1){
                        $map['is_register'] = $isregister;
                    }elseif($isregister == 2){
                        $map['is_register'] = $isregister;
                    }
                    $this->assign('isregister', $isregister);
                }
            }else{
                $this->assign('isregister', 999);
            }
		
        $count = $Shop->where($map)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $list = $Shop->order(array('shop_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $grade_ids =  $ids = array();
        foreach ($list as $k => $val){
            $list[$k]['province'] = D('province')->find($val['province_id']);
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
			$list[$k]['sms'] = (int)M('SmsShop')->where(array('type' => shop,'shop_id' =>$val['shop_id'],'status'=>0))->sum('num');
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
			$grade_ids[$val['grade_id']] = $val['grade_id'];
            $money = D('Paymentlogs')->where(['type'=>'shop','user_id'=>$val['user_id'],'is_paid'=>1])->find();
            $list[$k]['shop_apply_prrice'] = $money['need_pay'];
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
		$this->assign('grade', D('Shopgrade')->itemsByIds($grade_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
		$this->assign('p', $p);
        $this->display();
    }
	
	
	//推荐人
	public function guide(){
        $obj = D('Shopguide');
        import('ORG.Util.Page');
        $map = array('closed' => 0);
		 if(($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
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
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->order(array('guide_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = array();
        foreach($list as $k => $val){
             $user_ids[$val['user_id']] = $val['user_id'];
			 $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
		$this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function rate(){
        $guide_id = (int) $this->_get('guide_id');
		$obj = D('Shopguide');
        if(empty($guide_id)){
            $this->tuError('请选择推荐ID');
        }
		if(!($detail = $obj->find($guide_id))){
           $this->tuError('请选择要编辑的推荐人');
        }
        if($this->isPost()){
			$user_id = (int) $this->_post('user_id');
            if($user_id == 0){
                $this->tuError('请选择会员');
            }
            $rate = (int) $this->_post('rate');
            if($rate == 0){
                $this->tuError('请输入正确的费率');
            }
			if($rate >= 300){
                $this->tuError('输入费率太高了，不超过300');
            }
            $intro = $this->_post('intro', 'htmlspecialchars');
			if (empty($intro)) {
                $this->tuError('备注不能为空');
            }
			if(D('Shopguide')->where(['guide_id' => $guide_id,'user_id' => $user_id])->save(array('rate' => $rate,'intro' => $intro))){
				$this->tuSuccess('操作成功', U('shop/guide'));
			}else{
				$this->tuError('操作失败');
			}
        }else{
			$this->assign('detail', $detail);
			$this->assign('user', D('Users')->find($detail['user_id']));
            $this->assign('guide_id', $guide_id);
            $this->display();
        }
    }
	
	
    public function apply(){
        $Shop = D('Shop');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'audit' => 0);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($city_id = (int) $this->_param('city_id')){
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if($area_id = (int) $this->_param('area_id')){
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        $count = $Shop->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Shop->order(array('shop_id' => 'asc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach($list as $k => $val){
            if($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
    public function create(){
        if($this->isPost()){
            $data2 = $data = $this->createCheck();
            $obj = D('Shop');
            $details = $this->_post('details', 'SecurityEditorHtml');
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->tuError('商家介绍含有敏感词：' . $words);
            }
            $bank = $this->_post('bank', 'htmlspecialchars');
            unset($data['near'], $data['price'], $data['business_time'], $data['delivery_time']);
            //商家默认权限写入
            $auth = D('ShopgradeAuth')->where(array('grade_id'=>$data['auth_id']))->find();
            // print_r($auth);die;
            unset($auth['grade_name'],$auth['create_time']);
            $auth['create_time'] = NOW_TIME;
            $auth['create_ip'] = get_client_ip();
            $auth['user_id'] = $data['user_id'];
            D('ShopauthUser')->add($auth);
            // D('')->add();
            // die;
            if($shop_id = $obj->add($data)){
                $wei_pic = D('Weixin')->getCode($shop_id, 1);
                $ex = array('wei_pic' =>$wei_pic,'details'=>$details,'bank'=>$bank,'near'=>$data2['near'],'price' =>$data2['price'],'business_time'=>$data2['business_time'],'delivery_time'=>$data2['delivery_time']);
                D('Shopdetails')->upDetails($shop_id, $ex);
				D('Shop')->buildShopQrcode($shop_id,15);//生成商家二维码
                $this->tuSuccess('添加成功', U('shop/apply'));
            }
            $this->tuError('操作失败');
        }else{
            $detailss = D('ShopgradeAuth')->select();
            // dump($detailss);die;
            $this->assign('detailss',$detailss);
            $this->assign('cates', D('Shopcate')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->display();
        }
    }
	
	
    public function select(){
        $Shop = D('Shop');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'audit' => 1);
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($city_id = (int) $this->_param('city_id')){
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if($area_id = (int) $this->_param('area_id')){
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        $count = $Shop->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $list = $Shop->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach($list as $k => $val){
            if($val['user_id']){
                $ids[$val['user_id']] = $val['user_id'];
            }
			$list[$k]['city'] = D('City')->find($val['city_id']);
			$list[$k]['area'] = D('Area')->find($val['area_id']);
			$list[$k]['business'] = D('Business')->find($val['business_id']);
        }
		
	
		
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        // print_r($data);die;
        $data['user_id'] = (int) $data['user_id'];
        if(empty($data['user_id'])){
            $this->tuError('管理者不能为空');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])){
            $this->tuError('分类不能为空');
        }
		$data['grade_id'] = (int) $data['grade_id'];
        if(empty($data['grade_id'])){
            $this->tuError('商家等级不能为空');
        }
        $data['auth_id'] = (int) $data['auth_id'];
        if(empty($data['auth_id'])){
            $this->tuError('商家权限不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        if(empty($data['area_id'])){
            $this->tuError('所在区域不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if(empty($data['business_id'])){
            $this->tuError('所在商圈不能为空');
        }
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if(empty($data['shop_name'])){
            $this->tuError('商铺名称不能为空');
        }
        $data['logo'] = htmlspecialchars($data['logo']);
        if(empty($data['logo'])){
            $this->tuError('请上传商铺LOGO');
        }
        if(!isImage($data['logo'])){
            $this->tuError('商铺LOGO格式不正确');
        }
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuError('请上传店铺缩略图');
        }
        if(!isImage($data['photo'])){
            $this->tuError('店铺缩略图格式不正确');
        }
        $data['addr'] = htmlspecialchars($data['addr']);
        if(empty($data['addr'])){
            $this->tuError('店铺地址不能为空');
        }
        $data['tel'] = htmlspecialchars($data['tel']);
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if(empty($data['tel']) && empty($data['mobile'])){
            $this->tuError('店铺电话不能为空');
        }
        $data['extension'] = htmlspecialchars($data['extension']);
        $data['contact'] = htmlspecialchars($data['contact']);
        $data['tags'] = str_replace(',', '，', htmlspecialchars($data['tags']));
        $data['near'] = htmlspecialchars($data['near']);
        $data['business_time'] = htmlspecialchars($data['business_time']);
        $data['orderby'] = (int) $data['orderby'];
		$data['panorama_url'] = htmlspecialchars($data['panorama_url']);
        $data['price'] = (int) $data['price'];
        $data['is_manage'] = (int) $data['is_manage'];
        $data['recognition'] = (int) $data['recognition'];
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
    /*权限部分*/
        public function edit_jurisdiction($user_id = 0){
        if ($user_id = (int) $user_id) {

            $obj = D('ShopauthUser');
            // dump($shop);die;
            if (!($detail = $obj->where(array("user_id"=>$user_id))->find())) {
                
                $this->tuError('请选择要编辑的商家');
            }
            if ($this->isPost()) {
                $data = $this->editCheck_jurisdiction();
                $data['user_id'] = $user_id;
                if (false !== $obj->where(array("user_id"=>$user_id))->save($data)) {
                    $obj->cleanCache();
                    $this->tuSuccess('操作成功', U('Shop/index'));
                }
                $this->tuError('操作失败');
            } else {
                // print_r($user_id);die;
                $grade = D('ShopgradeAuth')->where(array("grade_id"=>$detail['grade_id']))->find();
                // echo D('ShopgradeAuth')->getlastSql();die;
                $detail['grade_name'] = $grade['grade_name'];
                $detail['content'] = $grade['content'];
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的商家等级');
        }
    }
    
     private function editCheck_jurisdiction(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields_jurisdiction);
        $data['is_mall'] = (int) $data['is_mall'];
        $data['is_tuan'] = (int) $data['is_tuan'];
        $data['is_ele'] = (int) $data['is_ele'];
        $data['is_market'] = (int) $data['is_market'];
        $data['is_store'] = (int) $data['is_store'];
        $data['is_news'] = (int) $data['is_news'];
        $data['is_hotel'] = (int) $data['is_hotel'];
        $data['is_booking'] = (int) $data['is_booking'];
        $data['is_farm'] = (int) $data['is_farm'];
        $data['is_appoint'] = (int) $data['is_appoint'];
        $data['is_huodong'] = (int) $data['is_huodong'];
        $data['is_coupon'] = (int) $data['is_coupon'];
        $data['is_life'] = (int) $data['is_life'];
        $data['is_jifen'] = (int) $data['is_jifen'];
        $data['is_cloud'] = (int) $data['is_cloud'];
        $data['is_book'] = (int) $data['is_book'];
        $data['is_stock'] = (int) $data['is_stock'];
        $data['is_edu'] = (int) $data['is_edu'];
        $data['is_zhe'] = (int) $data['is_zhe'];
        $data['is_ktv'] = (int) $data['is_ktv'];
        $data['is_decorate'] = (int) $data['is_decorate'];

        $data['is_decorate_num'] = (int) $data['is_decorate_num'];
        $data['is_mall_num'] = (int) $data['is_mall_num'];
        $data['is_tuan_num'] = (int) $data['is_tuan_num'];
        $data['is_ele_num'] = (int) $data['is_ele_num'];
        $data['is_market_num'] = (int) $data['is_market_num'];
        $data['is_store_num'] = (int) $data['is_store_num'];
        $data['is_news_num'] = (int) $data['is_news_num'];
        $data['is_hotel_num'] = (int) $data['is_hotel_num'];
        $data['is_booking_num'] = (int) $data['is_booking_num'];
        $data['is_farm_num'] = (int) $data['is_farm_num'];
        $data['is_appoint_num'] = (int) $data['is_appoint_num'];
        $data['is_huodong_num'] = (int) $data['is_huodong_num'];
        $data['is_coupon_num'] = (int) $data['is_coupon_num'];
        $data['is_life_num'] = (int) $data['is_life_num'];
        $data['is_jifen_num'] = (int) $data['is_jifen_num'];
        $data['is_cloud_num'] = (int) $data['is_cloud_num'];
        $data['is_book_num'] = (int) $data['is_book_num'];
        $data['is_stock_num'] = (int) $data['is_stock_num'];
        $data['is_edu_num'] = (int) $data['is_edu_num'];
        $data['is_zhe_num'] = (int) $data['is_zhe_num'];
        $data['is_ktv_num'] = (int) $data['is_ktv_num'];
        return $data;
    }

    public function edit($shop_id = 0){
        if($shop_id = (int) $shop_id) {
            $obj = D('Shop');
            if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())){
                $this->tuError('请选择要编辑的商家11');
            }
              	$zhizhao = D('Audit')->where('shop_id='.$shop_id)->find();
          	if($zhizhao){
          		$this->assign('zhizhao',$zhizhao);
          	}
         //     print_r($zhizhao);exit;
          
            if($this->isPost()){
                $data = $this->editCheck($shop_id);
                $data['shop_id'] = $shop_id;
                $details = $this->_post('details', 'SecurityEditorHtml');
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuError('商家介绍含有敏感词：' . $words);
                }
                $bank = $this->_post('bank', 'htmlspecialchars');
                $shopdetails = D('Shopdetails')->find($shop_id);
              
       
              
                $ex = array('details' => $details, 'bank' => $bank, 'near' => $data['near'], 'price' => $data['price'], 'business_time' => $data['business_time']);
                if(!empty($shopdetails['wei_pic'])) {
                    if(true !== strpos($shopdetails['wei_pic'], 'https://mp.weixin.qq.com/')){
                        $wei_pic = D('Weixin')->getCode($shop_id, 1);
                        $ex['wei_pic'] = $wei_pic;
                    }
                }else{
                    $wei_pic = D('Weixin')->getCode($shop_id, 1);
                    $ex['wei_pic'] = $wei_pic;
                }
                unset($data['near'], $data['price'], $data['business_time']);
                if(false !== $obj->where(['shop_id'=>$shop_id])->save($data)) {
                    //没有逻辑操作
                    $data1 =$this->_post('data', false);
                    $data1[user_name] = $data1[user_name];
                    $data1[mobile] = $data1[mobile];
                    $data1[zhucehao] = $data1[zhucehao];
                    $data1[zuzhidaima] = $data1[zuzhidaima];
                    $data1[pic] = $data1[pic];
                    $data1[picfan] = $data1[picfan];
                    $data1[frontcard] = $data1[frontcard];
                    $data1[backcard] = $data1[backcard];
                    $data1[weisheng] = $data1[weisheng];
                    $data1[yingye] = $data1[yingye];
                    D('Audit')->where(array("audit_id"=>$zhizhao[audit_id]))->save($data1);
                    D('Shopdetails')->upDetails($shop_id, $ex);
					D('Shop')->buildShopQrcode($shop_id,15);//生成商家二维码
                    $this->tuSuccess('操作成功', U('shop/index'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('province',D('province')->where(['is_open'=>1])->select());
                $this->assign('cates', D('Shopcate')->fetchAll());
                $this->assign('ex', D('Shopdetails')->find($shop_id));
                $this->assign('user', D('Users')->find($detail['user_id']));
                $this->assign('detail', $detail);
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商家22');
        }
    }
	
	
    private function editCheck($shop_id){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['user_id'] = (int) $data['user_id'];
        if (empty($data['user_id'])) {
            $this->tuError('管理者不能为空');
        }
		
        $shop = D('Shop')->find(array('where' => array('user_id' => $data['user_id'])));
        if (!empty($shop) && $shop['shop_id'] != $shop_id) {
            $this->tuError('该管理者已经拥有商铺了');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('分类不能为空');
        }
		$data['grade_id'] = (int) $data['grade_id'];
        if (empty($data['grade_id'])) {
            $this->tuError('商家等级不能为空');
        }
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->tuError('所在区域不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->tuError('所在商圈不能为空');
        }
        $data['shop_name'] = htmlspecialchars($data['shop_name']);
        if (empty($data['shop_name'])) {
            $this->tuError('商铺名称不能为空');
        }
//        $data['logo'] = htmlspecialchars($data['logo']);
//        if (empty($data['logo'])) {
//            $this->tuError('请上传商铺LOGO');
//        }
//        if (!isImage($data['logo'])) {
//            $this->tuError('商铺LOGO格式不正确');
//        }
//        $data['photo'] = htmlspecialchars($data['photo']);
//        if (empty($data['photo'])) {
//            $this->tuError('请上传店铺缩略图');
//        }
//        if (!isImage($data['photo'])) {
//            $this->tuError('店铺缩略图格式不正确');
//        }
//        $data['addr'] = htmlspecialchars($data['addr']);
//        if (empty($data['addr'])) {
//            $this->tuError('店铺地址不能为空');
//        }
//        $data['tel'] = htmlspecialchars($data['tel']);
//        $data['mobile'] = htmlspecialchars($data['mobile']);
//        if (empty($data['tel']) && empty($data['mobile'])) {
//            $this->tuError('店铺电话不能为空');
//        }
//        $data['contact'] = htmlspecialchars($data['contact']);
//        $data['tags'] = htmlspecialchars($data['tags']);
//        $data['near'] = htmlspecialchars($data['near']);
//        $data['business_time'] = htmlspecialchars($data['business_time']);
//		$data['express_price'] = (float) ($data['express_price']);
//		if (empty($data['express_price'])) {
//            $this->tuError('配送费必须设置');
//        }
//		if ($data['express_price'] < 10) {
//            $this->tuError('配送费必须大于0.1元');
//        }
		$data['commission'] = (float) ($data['commission']);
		if($data['commission'] < 0) {
            $this->tuError('结算佣金不能小于0可以等于0');
        }
		if($data['commission'] >= 10000 ){
            $this->tuError('结算佣金设置错误');
        }
        $data['orderby'] = (int) $data['orderby'];
		$data['panorama_url'] = htmlspecialchars($data['panorama_url']);
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
		$data['is_display'] = (int) $data['is_display'];
		$data['is_tuan_pay'] = (int) $data['is_tuan_pay'];
		$data['is_hotel_pay'] = (int) $data['is_hotel_pay'];
		
		
        $data['price'] = (int) $data['price'];
        $data['apiKey'] = htmlspecialchars($data['apiKey']);
        $data['mKey'] = htmlspecialchars($data['mKey']);
        $data['partner'] = htmlspecialchars($data['partner']);
        $data['machine_code'] = htmlspecialchars($data['machine_code']);
        $data['service'] = htmlspecialchars($data['service']);
        $data['service_audit'] = (int) $data['service_audit'];
        $data['is_ele_print'] = (int) $data['is_ele_print'];
        $data['is_tuan_print'] = (int) $data['is_tuan_print'];
        $data['is_goods_print'] = (int) $data['is_goods_print'];
        $data['is_booking_print'] = (int) $data['is_booking_print'];
		$data['is_appoint_print'] = (int) $data['is_appoint_print'];
		$data['is_kefu'] = (int) $data['is_kefu'];
		$data['is_register'] = (int) $data['is_register'];
 		$data['is_manage'] = (int) $data['is_manage'];

		$data['bg_date'] = htmlspecialchars($data['bg_date']);
        if(!empty($data['bg_date'])) {
           if (!isDate($data['bg_date'])) {
				$this->tuError('开始时间格式不正确');
			} 
        }
		
		$data['end_date'] = htmlspecialchars($data['end_date']);
        if(!empty($data['end_date'])){
            if(!isDate($data['end_date'])) {
				$this->tuError('结束时间格式不正确');
			}
        }
        
        return $data;
    }
    public function delete($shop_id = 0){
        if(is_numeric($shop_id) && ($shop_id = (int) $shop_id)){
            $obj = D('Shop');
            $obj->where(['shop_id' => $shop_id])->save(array('closed' => 1));
            $this->tuSuccess('删除成功', U('shop/index'));
        }else{
            $shop_id = $this->_post('shop_id', false);
            if(is_array($shop_id)){
                $obj = D('Shop');
                foreach($shop_id as $id){
                    $obj->where(['shop_id' => $id])->save(array('closed' => 1));
                }
                $this->tuSuccess('删除成功', U('shop/index'));
            }
            $this->tuError('请选择要删除的商家');
        }
    }
	
	
    public function audit($shop_id = 0){
        $shop_id = (int) $shop_id;
		if($shop_id){
            $obj = D('Shop');
            // $auth['user_id'] = 
			if(false != $obj->shop_audit($shop_id)){
				$obj->where(['shop_id' => $shop_id])->save(array('audit' => 1));
                $result = $obj->where(array('shop_id'=>$shop_id))->find();
                // echo $obj->getlastSql();die;
                $data = D("ShopgradeAuth")->where(array("grade_id"=>$result['auth_id']))->find();
                unset($data['content']);
                unset($data['grade_name']);
                $data['user_id'] = (int)$shop_id;
                $data['create_ip'] =get_client_ip();
                $data['create_time'] = (int)time();
                // print_r($data);print_r($result);die;
                D('ShopauthUser')->add($data);//审核商户后写入对应的权限
                D('Users')->where(['user_id'=>$result['user_id']])->save(array('auth_id'=>$result['auth_id']));//同步用户权限
                // echo D('Users')->getlastSql();die;
                $password=M('users')->where(array('user_id'=>$result['user_id']))->getField('password');
                $post_data=array(
                    'action'=>'register',
                    'user_type'=>2,
                    'token'=>md5(C('CHAT_TOKEN')),
                    'mobile'=>$result['tel'],
                    'password'=>$password,
                );
                $res=Post(C('CHAT_URL'),$post_data);
                $chat=json_decode($res,true);
                //聊天用户注册成功才注册商城用户
                if($chat['code']==0 && !empty($chat['data']['uid'])){
                    $obj->where(['shop_id' => $shop_id])->save(array('chat_user_id' =>$chat['data']['uid']));//保存聊天的用户id
                    $this->tuSuccess('审核成功', U('shop/apply'));
                }else{
                    $this->tuError($obj->getError());
                }

			}else{
				$this->tuError($obj->getError());
			}
        }else{
			$this->tuError('商家不存在');
		}
	}

    public function login($shop_id){
        $obj = D('Shop');
        if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())){
            $this->error('请选择要编辑的商家');
        }
        if (empty($detail['user_id'])) {
            $this->error('该用户没有绑定管理者');
        }
        setUid($detail['user_id']);
        header('Location:' . U('Merchant/index/index'));
        die;
    }
   
    public function biz($shop_id,$p = 0){
        $obj = D('Shop');
        if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_biz' => 0);
        if ($detail['is_biz'] == 0) {
            $data['is_biz'] = 1;
        }
        $obj->where([ 'shop_id' => $shop_id])->save($data);
        $this->tuSuccess('操作成功', U('shop/index',array('p'=>$p)));
    }
    public function profit($shop_id,$p = 0){
        $obj = D('Shop');
        if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_profit' => 0);
        if ($detail['is_profit'] == 0) {
            $data['is_profit'] = 1;
        }
        $obj->where(['shop_id' => $shop_id])->save($data);
        $this->tuSuccess('操作成功', U('shop/index',array('p'=>$p)));
    }
	
	//设置农村电商
	public function online($shop_id,$p = 0){
		
        $obj = D('Shop');
        if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())){
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_online' => 0);
		$intro = '关闭农电商成功';
        if($detail['is_online'] == 0) {
            $data['is_online'] = 1;
			$intro = '开启农电商成功';
        }
        $obj->where(['shop_id' => $shop_id])->save($data);
        $this->tuSuccess($intro,U('shop/index',array('p'=>$p)));
    }

	
	//新版开启外卖配送
    public function is_ele_pei($shop_id,$p = 0){
        $obj = D('Shop');
        if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        if($detail['is_ele_pei'] == 1){
			$do = D('DeliveryOrder')->where(array('shop_id' =>$detail['shop_id'],'type' => 1,'closed' =>0,'status' => array('IN',array(1,2))))->find();
            if($do){
                $this->tuError('您还有未完成的外卖配送订单');
            }
            $obj->where(['shop_id' => $shop_id])->save(array('is_ele_pei' =>0));
        }else{
            if($detail['is_ele_pei'] == 0){
				$Eleorder = D('Eleorder')->where(array('shop_id' =>$detail['shop_id'],'closed' =>0,'status' => array('IN',array(1,2))))->find();
				if($Eleorder){
					$this->tuError('该商家外卖订单号【'.$Eleorder['order_id'].'】没处理完毕，暂时无法强制开通配送');
				}
                $obj->where(['shop_id' => $shop_id])->save(array( 'is_ele_pei' =>1));
            }
        }
        $this->tuSuccess('外卖配送操作成功', U('shop/index',array('p'=>$p)));
    }
	
	//新版开启菜市场配送
    public function is_market_pei($shop_id,$p = 0){
        $obj = D('Shop');
        if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        if($detail['is_market_pei'] == 1){
			$do = D('DeliveryOrder')->where(array('shop_id' =>$detail['shop_id'],'type' =>3,'closed' =>0,'status' => array('IN',array(1,2))))->find();
            if($do){
                $this->tuError('您还有未完成的菜市场配送订单');
            }
            $obj->where(['shop_id' => $shop_id])->save(array( 'is_ele_pei' =>0));
        }else{
            if($detail['is_market_pei'] == 0){
				$Marketorder = D('Marketorder')->where(array('shop_id' =>$detail['shop_id'],'closed' =>0,'status' => array('IN',array(1,2))))->find();
				if($Marketorder){
					$this->tuError('该商家菜市场订单号【'.$Marketorder['order_id'].'】没处理完毕，暂时无法强制开通配送');
				}
                $obj->where(['shop_id' => $shop_id])->save(array('is_market_pei' =>1));
            }
        }
        $this->tuSuccess('菜市场配送操作成功', U('shop/index',array('p'=>$p)));
    }
	
	//新版开启便利店配送
    public function is_store_pei($shop_id,$p = 0){
        $obj = D('Shop');
        if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        if($detail['is_store_pei'] == 1){
			$do = D('DeliveryOrder')->where(array('shop_id' =>$detail['shop_id'],'type' =>4,'closed' =>0,'status' => array('IN',array(1,2))))->find();
            if($do){
                $this->tuError('您还有未完成的外卖配送订单');
            }
            $obj->where(['shop_id' => $shop_id])->save(array('is_store_pei' =>0));
        }else{
            if($detail['is_store_pei'] == 0){
				$Storeorder = D('Storeorder')->where(array('shop_id' =>$detail['shop_id'],'closed' =>0,'status' => array('IN',array(1,2))))->find();
				if($Storeorder){
					$this->tuError('该商家便利店订单号【'.$Storeorder['order_id'].'】没处理完毕，暂时无法强制开通配送');
				}
                $obj->where(['shop_id' => $shop_id])->save(array( 'is_store_pei' =>1));
            }
        }
        $this->tuSuccess('便利店配送操作成功', U('shop/index',array('p'=>$p)));
    }
	
	
	
	//新版开启商城配送
	public function is_goods_pei($shop_id,$p = 0){
        $obj = D('Shop');
        if(!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        if($detail['is_goods_pei'] == 1) {
			$do = D('DeliveryOrder')->where(array('shop_id' =>$detail['shop_id'],'type' =>0,'closed' =>0,'status' => array('IN',array(1,2))))->find();
            if($do){
                $this->tuError('您还有未完成的商城配送订单');
            }
            $obj->where(['shop_id' => $shop_id])->save(array('is_goods_pei' =>0));
        }else{
            if($detail['is_goods_pei'] == 0){
				$order = D('Order')->where(array('shop_id' =>$detail['shop_id'],'closed' =>0,'status' => array('IN',array(1,2))))->find();
				if($order){
					$this->tuError('该商家商城订单号【'.$order['order_id'].'】没处理完毕，暂时无法强制开通配送');
				}
                $obj->where(['shop_id' => $shop_id])->save(array('is_goods_pei' =>1));
            }
        }
        $this->tuSuccess('商城配送操作成功', U('shop/index',array('p'=>$p)));
    }
	
	//开启商城推手
	public function is_goods_backers($shop_id,$p = 0){
        $obj = D('Shop');
        if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_goods_backers' => 0);
        if ($detail['is_goods_backers'] == 0) {
            $data['is_goods_backers'] = 1;
        }
        $obj->where(['shop_id' => $shop_id])->save($data);
        $this->tuSuccess('商家商城推手设置成功', U('shop/index',array('p'=>$p)));
    }
	
	//开启外卖推手
	public function is_ele_backers($shop_id,$p = 0){
        $obj = D('Shop');
        if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
            $this->error('请选择要编辑的商家');
        }
        $data = array('is_ele_backers' => 0);
        if ($detail['is_ele_backers'] == 0) {
            $data['is_ele_backers'] = 1;
        }
        $obj->where(['shop_id' => $shop_id])->save($data);
        $this->tuSuccess('商家外卖推手设置成功', U('shop/index',array('p'=>$p)));
    }
	
    public function recovery(){
        $Shop = D('Shop');
        import('ORG.Util.Page');
        $map = array('closed' => 1);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($city_id = (int) $this->_param('city_id')) {
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        $count = $Shop->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Shop->order(array('shop_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('citys', D('City')->fetchAll());
        $this->assign('areas', D('Area')->fetchAll());
        $this->assign('cates', D('Shopcate')->fetchAll());
        $this->assign('business', D('Business')->fetchAll());
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //认领开始
    public function recognition(){
        $Shop = D('Shop');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'recognition' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['shop_name|tel'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($city_id = (int) $this->_param('city_id')) {
            $map['city_id'] = $city_id;
            $this->assign('city_id', $city_id);
        }
        if ($area_id = (int) $this->_param('area_id')) {
            $map['area_id'] = $area_id;
            $this->assign('area_id', $area_id);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Shopcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        $count = $Shop->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $Shop->order(array('shop_id' => 'desc'))->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $ids = array();
        foreach ($list as $k => $val) {
            if ($val['user_id']) {
                $ids[$val['user_id']] = $val['user_id'];
            }
        }
        $this->assign('users', D('Users')->itemsByIds($ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //认领结束
    public function recovery2($shop_id = 0){
        if (is_numeric($shop_id) && ($shop_id = (int) $shop_id)) {
            $obj = D('Shop');
            $obj->where(['shop_id' => $shop_id])->save(array('closed' => 0));
            $this->tuSuccess('恢复商家成功', U('shop/index'));
        } else {
            $shop_id = $this->_post('shop_id', false);
            if (is_array($shop_id)) {
                $obj = D('Shop');
                foreach ($shop_id as $id) {
                    $obj->where(['shop_id' => $shop_id])->save(array('closed' => 0));
                }
                $this->tuSuccess('恢复商家成功', U('shop/index'));
            }
            $this->tuError('请选择要恢复的商家');
        }
    }
    public function delete2($shop_id = 0){
        $shop_id = (int) $shop_id;
        if (!empty($shop_id)) {
            $goods = D('Goods')->where(array('shop_id' => $shop_id))->select();
            foreach ($goods as $k => $value) {
                D('Goods')->where(['goods_id' => $value['goods_id']])->save(array( 'closed' => 1));
            }
            $coupon = D('Coupon')->where(array('shop_id' => $shop_id))->select();
            foreach ($coupon as $k => $value) {
                D('Tuan')->where(['coupon_id' => $value['coupon_id']])->save(array('closed' => 1));
            }
            $tuan = D('Tuan')->where(array('shop_id' => $shop_id))->select();
            foreach ($goods as $k => $value) {
                D('Tuan')->where(['tuan_id' => $value['tuan_id']])->save(array( 'closed' => 1));
            }
            D('Ele')->where(['shop_id' => $value['shop_id']])->save(array('audit' => 0));
            D('Shop')->delete($shop_id);
            $this->tuSuccess('彻底删除成功', U('shop/recovery'));
        } else {
            $this->tuError('操作失败');
        }
    }
  
  public function artlist(){
  	$Article = D('Article')->where('article_id=999999')->find();
 //     print_r($Article);
      $this->assign('art',$Article);
//    print_r($Article);
      $this->display();
    
  }

  //销售额与订单数
  public function saleadnorder($shop_id = 0){
      if($shop_id = (int) $shop_id) {
          $obj = D('Shop');
          if (!($detail = $obj->where(['shop_id'=>$shop_id])->find())) {
              $this->tuError('请选择要查看的商家');
          }
          $Shopmoney = D('Shopmoney');
          import('ORG.Util.Page');
          $count = $Shopmoney->tjmonthCount("",$shop_id);
          $Page = new Page($count, 15);
          $show = $Page->show();
          $list = $Shopmoney->tjmonth("", $shop_id, $Page->firstRow, $Page->listRows);
          $this->assign('list', $list);
          $this->assign('page', $show);
      }else{

      }
      $this->display();
  }




}