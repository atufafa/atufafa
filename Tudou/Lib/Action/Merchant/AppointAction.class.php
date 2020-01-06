<?php
class  AppointAction extends CommonAction{
	 
	 
	private $card_create_fields = array('title', 'fullMoney', 'cardMoney', 'bg_date', 'end_date', 'num');
	private $create_fields = array('shop_id','cate_id','city_id','area_id','business_id','lng','lat','price', 'one_full_money','title', 'intro', 'unit','gongju', 'photo','thumb', 'user_name','user_mobile', 'biz_time','end_date','contents');
    private $edit_fields = array('shop_id','cate_id','city_id','area_id','business_id','lng','lat','price','one_full_money', 'title', 'intro', 'unit','gongju', 'photo','thumb', 'user_name','user_mobile', 'biz_time','end_date','contents');
	 
	private $worker_edit_fields = array('appoint_id','cate_id','city_id','area_id','business_id','star','date_id','price','photo','name','office','height','age','zodiac','constellatory','culture','working_age','household','mandarin','mobile','is_recommend','intro','skill','content','audit');
	
	public function _initialize(){
        parent::_initialize();
		if($this->_CONFIG['operation']['appoint'] == 0){
            $this->error('此功能已关闭');die;
        }
		$this->assign('orderTypes', $orderTypes = D('Appoint')->getAppoinOrderType());
		$this->assign('stars', $stars = D('Appoint')->getAppoinStar());
		$this->assign('dates', $dates = D('Appoint')->getAppoinDate());
		$this->assign('cates', D('Appointcate')->fetchAll());
        $this->assign('types', $types = D('Appointorder')->getType());
		$this->assign('zodiacs', $zodiacs = D('Appoint')->getAppoinZodiac());
		$this->assign('constellatorys', $constellatorys = D('Appoint')->getAppoinConstellatory());
		$this->assign('mandarins', $mandarins = D('Appoint')->getAppoinMandarin());
		
		
		$this->assign('states',$state = D('AppointCard')->getstate());//优惠卡订单状态
		$this->assign('statuss',D('AppointCard')->getStatus());//优惠卡订单状态
		
		
		
    }
	
	
	
	 public function index(){
        $Appoint = D('Appoint');
        import('ORG.Util.Page'); 
        $map = array('closed' => 0,'shop_id'=>$this->shop_id);
        $keyword = $this->_param('keyword', 'htmlspecialchars');
		
        if ($keyword) {
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }  
		
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Appointcate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
		
        $count = $Appoint->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $Appoint->where($map)->order(array('appoint_id' => 'asc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		$shop_ids = array();
        foreach ($list as $key => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
			$list[$key]['num'] = D('Appointworker')->where(array('appoint_id'=>$val['appoint_id'],'closed'=>0))->count();
        }		
		
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->display(); 
    }

	//添加家政
	public function create() {
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Appoint');
            if ($obj->add($data)) {
                $this->tuSuccess('添加成功', U('Appoint/index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->display();
        }
    }
	//添加验证
	 private function createCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		
			$data['shop_id'] = $this->shop_id;//商家ID
			if (empty($data['shop_id'])) {
            $this->tuError('请您选择商家');
     	    } 
			$shop = D('Shop')->find($data['shop_id']);
			if (empty($shop)) {
				$this->tuError('请选择正确的商家');
			}
			$data['city_id'] = $shop['city_id'];
			$data['area_id'] = $shop['area_id'];
			$data['business_id'] = $shop['business_id'];
			$data['lng'] = $shop['lng'];
			$data['lat'] = $shop['lat'];
        	$data['cate_id'] = (int) $data['cate_id'];//ID
			if (empty($data['cate_id'])) {
				$this->tuError('类型ID不能为空');
			}
			$Appointcate = D('Appointcate')->where(array('cate_id' => $data['cate_id']))->find();
			$parent_id = $Appointcate['parent_id'];
			if ($parent_id == 0) {
			$this->tuError('请选择二级分类');
			}
			
            $data['title'] = htmlspecialchars($data['title']);
			if (empty($data['title'])) {
            $this->tuError('请您填写服务标题');
     	    }
			if ($words = D('Sensitive')->checkWords($data['title'])) {
            $this->tuError('标题内容含有敏感词：' . $words);
			}
			$data['intro'] = htmlspecialchars($data['intro']);//标题名字
			if (empty($data['intro'])) {
            $this->tuError('请您填写服务建议');
     	    }
			if ($words = D('Sensitive')->checkWords($data['intro'])) {
				$this->tuError('服务建议含有敏感词：' . $words);
			}
          
			
			$data['price'] = (float)($data['price']);
			if (empty($data['price'])) {
            	$this->tuError('价格不能为空');
            }
			$data['one_full_money'] = (float)($data['one_full_money']);
			if($data['one_full_money']){
				if($data['one_full_money']  >= $data['price']){
					$this->tuError('首单减金额不能大于等于价格');
				}
			}
			
            $data['unit']  = htmlspecialchars($data['unit']);
            $data['gongju']  = htmlspecialchars($data['gongju']);
			$data['user_name'] = htmlspecialchars($data['user_name']);
			if (empty($data['user_name'])) {
            $this->tuError('请您填写姓名');
     	    }
			$data['user_mobile'] = htmlspecialchars($data['user_mobile']);
			if (empty($data['user_mobile'])) {
            $this->tuError('请您手机号码');
     	    }
			if (!isPhone($data['user_mobile']) && !isMobile($data['user_mobile'])) {
            $this->tuError('联系电话格式不正确');
            }
			
            $data['photo']  = htmlspecialchars($data['photo']);
			if (empty($data['photo'])) {
            $this->tuError('请您上传图片');
            }
			
			$thumb = $this->_param('thumb', false);
			foreach ($thumb as $k => $val) {
				if (empty($val)) {
					unset($thumb[$k]);
				}
				if (!isImage($val)) {
					unset($thumb[$k]);
				}
			}
			$data['thumb'] = serialize($thumb);
            $data['biz_time']  = htmlspecialchars($data['biz_time']);
			$data['end_date'] = htmlspecialchars($data['end_date']);
			if (empty($data['end_date'])) {
				$this->tuError('结束时间不能为空');
			}
			if (!isDate($data['end_date'])) {
				$this->tuError('结束时间格式不正确');
			}
			$data['contents'] = SecurityEditorHtml($data['contents']);
			if (empty($data['contents'])) {
            $this->tuError('家政内容不能为空');
			}
			if ($words = D('Sensitive')->checkWords($data['contents'])) {
				$this->tuError('家政简介含有敏感词：' . $words);
			}
			$data['create_time'] = time();
			$data['create_ip'] = get_client_ip();
        	return $data;
    }
	
	 public function edit($appoint_id = 0){
        if ($appoint_id = (int) $appoint_id) {
            $obj = D('Appoint');
            if (!($detail = $obj->find($appoint_id))){
                $this->tuError('请选择要编辑的活动');
            }
            if($this->isPost()){
                $data = $this->editCheck();
                $data['appoint_id'] = $appoint_id;
                if (false !== $obj->save($data)) {
                    $this->tuSuccess('操作成功', U('appoint/index'));
                }
                $this->tuError('操作失败');
            }else{
                $thumb = unserialize($detail['thumb']);
				$this->assign('thumb', $thumb);
				$this->assign('shops', D('Shop')->find($detail['shop_id']));
				$this->assign('detail', $detail);
				$this->display();
            }
        }else{
            $this->tuError('请选择要编辑的家政');
        }
    }
    
	
	 private function editCheck(){
			$data = $this->checkFields($this->_post('data', false), $this->edit_fields);
			$data['shop_id'] =  $this->shop_id;//商家ID
			if (empty($data['shop_id'])) {
            $this->tuError('请您选择商家');
     	    } 
			$shop = D('Shop')->find($data['shop_id']);
			if (empty($shop)) {
				$this->tuError('请选择正确的商家');
			}
			$data['city_id'] = $shop['city_id'];
			$data['area_id'] = $shop['area_id'];
			$data['business_id'] = $shop['business_id'];
			$data['lng'] = $shop['lng'];
			$data['lat'] = $shop['lat'];
        	$data['cate_id'] = (int) $data['cate_id'];//ID
			if (empty($data['cate_id'])) {
				$this->tuError('类型ID不能为空');
			}
			$Appointcate = D('Appointcate')->where(array('cate_id' => $data['cate_id']))->find();
			$parent_id = $Appointcate['parent_id'];
			if ($parent_id == 0) {
			$this->tuError('请选择二级分类');
			}
			
            $data['title'] = htmlspecialchars($data['title']);
			if (empty($data['title'])) {
            $this->tuError('请您填写服务标题');
     	    }
			if ($words = D('Sensitive')->checkWords($data['title'])) {
            $this->tuError('标题内容含有敏感词：' . $words);
			}
			$data['intro'] = htmlspecialchars($data['intro']);//标题名字
			if (empty($data['intro'])) {
            $this->tuError('请您填写服务建议');
     	    }
			if ($words = D('Sensitive')->checkWords($data['intro'])) {
				$this->tuError('服务建议含有敏感词：' . $words);
			}
            $data['price'] = (float)($data['price']);
			if (empty($data['price'])) {
            	$this->tuError('价格不能为空');
            }
			$data['one_full_money'] = (float)($data['one_full_money'] );
			if($data['one_full_money']){
				if($data['one_full_money']  >= $data['price']){
					$this->tuError('首单减金额不能大于等于价格');
				}
			}
            $data['unit']  = htmlspecialchars($data['unit']);
            $data['gongju']  = htmlspecialchars($data['gongju']);
			$data['user_name'] = htmlspecialchars($data['user_name']);
			if (empty($data['user_name'])) {
            $this->tuError('请您填写姓名');
     	    }
			$data['user_mobile'] = htmlspecialchars($data['user_mobile']);
			if (empty($data['user_mobile'])) {
            $this->tuError('请您手机号码');
     	    }
			if (!isPhone($data['user_mobile']) && !isMobile($data['user_mobile'])) {
            $this->tuError('联系电话格式不正确');
            }
			
            $data['photo']  = htmlspecialchars($data['photo']);
			if (empty($data['photo'])) {
            $this->tuError('请您上传图片');
            }
			
			$thumb = $this->_param('thumb', false);
			foreach ($thumb as $k => $val) {
				if (empty($val)) {
					unset($thumb[$k]);
				}
				if (!isImage($val)) {
					unset($thumb[$k]);
				}
			}
			$data['thumb'] = serialize($thumb);
            $data['biz_time']  = htmlspecialchars($data['biz_time']);
			$data['end_date'] = htmlspecialchars($data['end_date']);
			if (empty($data['end_date'])) {
				$this->tuError('结束时间不能为空');
			}
			if (!isDate($data['end_date'])) {
				$this->tuError('结束时间格式不正确');
			}
            $data['contents'] = SecurityEditorHtml($data['contents']);
			if (empty($data['contents'])) {
            $this->tuError('家政内容不能为空');
			}
			if ($words = D('Sensitive')->checkWords($data['contents'])) {
				$this->tuError('家政简介含有敏感词：' . $words);
			}
        	return $data;
    }
	
   //家政删除
	public function delete($appoint_id = 0) {
        if (is_numeric($appoint_id) && ($appoint_id = (int) $appoint_id)) {
            $obj = D('Appoint');
            $obj->save(array('appoint_id' => $appoint_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('appoint/index'));
        } else {
            $appoint_id = $this->_post('appoint_id', false);
            if (is_array($appoint_id)) {
                $obj = D('Appoint');
                foreach ($appoint_id as $appoint_id) {
                    $obj->save(array('appoint_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('批量删除成功', U('appoint/index'));
            }
            $this->tuError('请选择要删除的预约项目');
        }
    }
	
	
	
	//技师列表，由于数量不多，暂时不做分页
	public function worker($appoint_id = 0) {
        if ($appoint_id = (int) $appoint_id) {
			$Appoint = D('Appoint');
            $Appointworker = D('Appointworker');
			
			$map = array('closed' => 0);
			$map = array('appoint_id' => $appoint_id);
			
			$keyword = $this->_param('keyword', 'htmlspecialchars');
			if($keyword){
				$map['name|office|intro|skill|content|mobile'] = array('LIKE', '%' . $keyword . '%');
				$this->assign('keyword', $keyword);
			}
		
			
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
		
		
			if(isset($_GET['star']) || isset($_POST['star'])){
				$star = (int) $this->_param('star');
				if($star != 999){
					$map['star'] = $star;
				}
				$this->assign('star', $star);
			}else{
				$this->assign('star', 999);
			}
		
			$detail = $Appoint->find($appoint_id);
			$this->assign('detail', $detail);
			$this->assign('list', $list = $Appointworker->where($map)->select());
			$this->display();
        }else{
            $this->error('请选择要设置的技师');
        }
    }
	

	
	
    //添加技师
	public function worker_create($appoint_id){
		if ($appoint_id = (int) $appoint_id){
			$Appoint = D('Appoint');
            $Appointworker = D('Appointworker');
            if(!$detail = $Appoint->find($appoint_id)){
                $this->error('请选择要设置的家政');
            }
            if($detail['closed'] != 0) {
                $this->error('该家政已被删除');
            }
			if($data = $this->_post()){
				$data = $this->worker_editCheck();
				
				$data['appoint_id'] = $appoint_id;
				
				$data['city_id'] = $detail['city_id'];
				$data['area_id'] = $detail['area_id'];
				$data['business_id'] = $detail['business_id'];
				
				$date_ids = $this->_post('date_id', false);
				$date_id = implode(',', $date_ids);
				$data['date_id'] = $date_id;
				
				if($Appointworker->add($data)) {
					$this->tuSuccess('操作成功', U('appoint/worker',array('appoint_id'=>$detail['appoint_id'])));
				}
				$this->tuError('操作失败');
			}else{
				$this->assign('detail', $detail);
				$this->display();
			}

		} else {
            $this->error('请选择要设置的家政');
        }
	}
	

	
	
    //编辑技师
	public function worker_edit($worker_id){
		if ($worker_id = (int) $worker_id) {
			$Appoint = D('Appoint');
            $Appointworker = D('Appointworker');
			if(!$worker = $Appointworker->find($worker_id)){
                $this->tuError('修改的内容不存在');
            }
			$appoint_id = $worker['appoint_id'];
            if(!$detail = $Appoint->find($appoint_id)){
                $this->tuError('请选择要设置的家政');
            }
            if($detail['closed'] != 0) {
                $this->tuError('该家政已被删除');
            }	
			
			if ($this->isPost()) {
                $data = $this->worker_editCheck();
				$data['worker_id'] = $worker_id;
				$data['appoint_id'] = $appoint_id;
				$data['city_id'] = $detail['city_id'];
				$data['area_id'] = $detail['area_id'];
				$data['business_id'] = $detail['business_id'];
				
				$date_ids = $this->_post('date_id', false);
				$date_id = implode(',', $date_ids);
				$data['date_id'] = $date_id;
				
				
				if(false !==  $Appointworker->save($data)) {
					$this->tuSuccess('操作成功', U('appoint/worker',array('appoint_id'=>$detail['appoint_id'])));
				}else{
					$this->tuError('操作失败');
				} 
            }else{
				$this->assign('date_ids', $date_ids = explode(',', $worker['date_id']));
                $this->assign('worker', $worker);//输出
				$this->assign('detail', $detail);
				$this->display();
            }
		}else{
            $this->tuError('修改的内容不存在');
        }
	}
	
	
	 private function worker_editCheck(){
			$data = $this->checkFields($this->_post('data', false), $this->worker_edit_fields);
			
			$data['cate_id'] = (int) $data['cate_id'];
			if(empty($data['cate_id'])){
				$this->tuError('类型ID不能为空');
			}
			$Appointcate = D('Appointcate')->where(array('cate_id' => $data['cate_id']))->find();
			if($Appointcate['parent_id'] == 0){
				$this->tuError('请选择二级分类');
			}
			
			$data['star'] = (int)$data['star'];
			if(empty($data['star'])){
				$this->tuError('技师星级不能为空');
			}
		
            $data['price'] = htmlspecialchars($data['price']);
			if (empty($data['price'])) {
            	$this->tuError('请填写价格');
     	    }
			$data['photo']  = htmlspecialchars($data['photo']);
			if (empty($data['photo'])) {
            	$this->tuError('请您上传头像');
            }
			$data['name'] = htmlspecialchars($data['name']);
			if (empty($data['name'])) {
            	$this->tuError('请填写姓名');
     	    }
			$data['office'] = htmlspecialchars($data['office']);
			if (empty($data['office'])) {
            	$this->tuError('请填写职位');
     	    }
			
			//新加入开始
			$data['height'] = (int)($data['height']);
			if(empty($data['height'])){
            	$this->tuError('请填写身高');
     	    }
			$data['age'] = (int)($data['age']);
			if(empty($data['age'])){
            	$this->tuError('请填写身高');
     	    }
			$data['zodiac'] = (int)($data['zodiac']);
			if(empty($data['zodiac'])){
            	$this->tuError('请选择生肖');
     	    }
			$data['constellatory'] = (int)($data['constellatory']);
			if(empty($data['constellatory'])){
            	$this->tuError('请选择星座');
     	    }
			$data['culture'] = htmlspecialchars($data['culture']);
			if(empty($data['culture'])) {
            	$this->tuError('请您填写文化水平');
     	    }
			$data['working_age'] = htmlspecialchars($data['working_age']);
			if(empty($data['working_age'])) {
            	$this->tuError('请您填写工作年限');
     	    }
			$data['household'] = htmlspecialchars($data['household']);
			$data['mandarin'] = (int)($data['mandarin']);
			//新加入结束
			$data['mobile'] = htmlspecialchars($data['mobile']);
			if (empty($data['mobile'])) {
            $this->tuError('请您手机号码');
     	    }
			if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuError('联系电话格式不正确');
            }
			$data['is_recommend'] = (int)$data['is_recommend'];
			
			
			$data['intro'] = htmlspecialchars($data['intro']);//标题名字
			if(empty($data['intro'])) {
            	$this->tuError('请您填技师风采');
     	    }
			if($words = D('Sensitive')->checkWords($data['intro'])) {
				$this->tuError('技师风采含有敏感词：' . $words);
			}
			
			$data['skill'] = htmlspecialchars($data['skill']);//标题名字
			if(empty($data['skill'])) {
            	$this->tuError('请您填写技能简介');
     	    }
			if($words = D('Sensitive')->checkWords($data['skill'])) {
				$this->tuError('技能简介含有敏感词：' . $words);
			}
			$data['content'] = htmlspecialchars($data['content']);//标题名字
			if(empty($data['content'])){
            	$this->tuError('请您填写简介');
     	    }
			if($words = D('Sensitive')->checkWords($data['content'])) {
				$this->tuError('简介含有敏感词：' . $words);
			}
			$data['audit'] = 1;
        	return $data;
    }
	
	
	

	//删除技师
	 public function type_delete($worker_id = 0) {
            $worker_id = (int) $worker_id;
			if(!empty($worker_id)){
				$obj = D('Appointworker');
				if (!$worker = $obj->find($worker_id)) {
					$this->tuError('修改的内容不存在');
				}
				if (!$detail = D('Appoint')->find($worker['appoint_id'])) {
					$this->tuError('请选择要设置的家政');
				}
				if ($detail['closed'] != 0) {
					$this->tuError('该家政已被删除');
				}
				$obj->save(array('worker_id' => $worker_id, 'closed' => 1));
				$this->tuSuccess('操作成功', U('appoint/worker',array('appoint_id'=>$detail['appoint_id'])));
			 }else{
				$this->tuError('操作错误');	
			}
           
       
	 }
	
	//家政订单列表
    public function order(){
		$Appoint = D('Appoint');
        $Appointorder = D('Appointorder');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id,'closed'=>0);
		$st = (int) $this->_param('st');
		if ($st == 1) {
			$map['status'] = 1;
		}elseif ($st == 2) {
			$map['status'] = 2;
		}elseif ($st == 3) {
			$map['status'] = 3;
		}elseif ($st == 4) {
			$map['status'] = 4;
		}elseif ($st == 8) {
			$map['status'] = 8;
		}else{
			$map['status'] = 0;
		}
		$this->assign('st', $st);
		
		if(isset($_GET['orderType']) || isset($_POST['orderType'])){
            $orderType = (int) $this->_param('orderType');
            if($orderType != 999){
                $map['orderType'] = $orderType;
            }
            $this->assign('orderType', $orderType);
        }else{
            $this->assign('orderType', 999);
        }
		
		$keyword = $this->_param('keyword', 'htmlspecialchars');
        if ($keyword) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }  
		
        $count = $Appointorder->where($map)->count();
        $Page = new Page($count, 5);
        $show = $Page->show();
        $list = $Appointorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $appoint_ids = array();
        foreach ($list as $k => $val) {
            $appoint_ids[$val['appoint_id']] = $val['appoint_id'];
        }
        // dump($list);die;
        $this->assign('appoints', $Appoint->itemsByIds($appoint_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    
  
  
	
   //管理员取消订单
    public function cancel($order_id = 0){
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Appointorder');
            if (!($detial = $obj->find($order_id))) {
                $this->tuError('该订单不存在');
            }elseif(false == D('Appointorder')->Appoint_order_Distribution($order_id,$type =0)){
				$this->tuError('检测到家政配送状态有误');
			}elseif($appoint_order['status'] != 0 ||$appoint_order['status'] != 4){
				$this->tuError('该订单暂时不能取消');
			}elseif($detail['shop_id'] != $this->shop_id){
				$this->tuError('请不要操作他人的订单');
			}else{
				if ($obj->save(array('order_id' => $order_id, 'closed' => 1))) {
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 11);
					D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 11);
					$this->tuSuccess('您已成功删除家政订单', U('appoint/index', array('st' => 1)));
				}else{
					$this->tuError('操作失败');
				}
			}
		}
    }

	//接单
    public function confirm(){
        $order_id = I('order_id', 0, 'trim,intval');
        $Appointorder = D('Appointorder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
		if (!($detial = $Appointorder->find($order_id))) {
                $this->tuError('该订单不存在');
        }elseif($appoint_order['status'] != 1){
				$this->tuError('订单状态不正确，但是无法发货');
		}elseif($detial['shop_id'] != $this->shop_id){
				$this->tuError('请不要操作其他商铺的订单');
		}else{
			if ($Appointorder->save(array('order_id' => $order_id, 'status' => 2))) {
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 1,$type = 3,$status = 2);
				D('Weixinmsg')->weixinTmplOrderMessage($order_id,$cate = 2,$type = 3,$status = 2);
				$this->tuSuccess('您已成功接单', U('appoint/index', array('st' => 2)));
			}else{
				$this->tuError('操作失败');
			}
		}
		
    }
	
	
				
				
	//同意退款操作
    public function agree_refund(){
        $order_id = I('order_id', 0, 'trim,intval');
        $Appointorder = D('Appointorder');
        $appoint_order = $Appointorder->where('order_id =' . $order_id)->find();
		if (!($detial = $Appointorder->find($order_id))) {
                $this->tuError('该订单不存在');
        }elseif($appoint_order['status'] != 3){
				$this->tuError('订单状态不正确，无法退款');
		}elseif($detial['shop_id'] != $this->shop_id){
				$this->tuError('请不要操作其他商铺的订单');
		}else{
			if (false == $Appointorder->refund_user($order_id)) {//退款操作
				$this->tuError('非法操作');
			}else{
				$this->tuSuccess('已成功退款',U('appoint/index', array('st' => 4)));	
			}
		}
    }

    /**
     * 优惠卡列表
     * @author pingdan <[<email address>]>
     * @return [type] [description]
     */
   	public function card() {
   		$AppointCard = D('AppointCard');
        import('ORG.Util.Page'); 
        $map = array('shop_id'=>$this->shop_id);
    
        $count = $AppointCard->where($map)->count(); 
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $list = $AppointCard->where($map)->order(array('create_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
		
		$ids = array();
		foreach ($list as $val) {
			if ($val['user_id']) {
				$ids[] = $val['user_id'];
			}
		}
		$users = D('Users')->getUserByIds(array_unique($ids));

		foreach ($list as &$value) {
			if ($value['user_id']) {
				$value['account'] = $users[$value['user_id']]['account'];
			}
		}

        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
   	}
	
	/**
	 * 添加家政优惠卡
	 * @author pingdan <[<email address>]>
	 * @return [type] [description]
	 */
	public function card_create() {
        if ($this->isPost()) {
            $data = $this->_card_create_check();
            $obj = D('AppointCard');

            $data['state'] = 0;
            $data['shop_id'] = $this->shop_id;
			$data['create_time'] = time();
        	$data['create_ip'] = get_client_ip();
        	$data['cardMoney'] = $data['cardMoney'];
        	$data['fullMoney'] = $data['fullMoney'];
			
			$i = 0;
			for($k=1; $k<= $data['num']; $k++ ){
				$i++;
				$data['cardNumber'] = D('AppointCard')->getCardNumber();
				$obj->add($data);
			}
			
			if($i){
				$this->tuSuccess('添加成功【'.$i.'】条', U('appoint/card'));
			}else{
				$this->tuError('操作失败');
			}
            
        } else {
            $this->display();
        }
    }

    /**
     * 添加优惠卡表单验证
     * @return [type] [description]
     */
    private function _card_create_check() {
    	$data = $this->checkFields($this->_post('data', false), $this->card_create_fields);

    	if (empty($data['title'])) {
    		$this->tuError('请输入优惠卡名称');
    	}
    	$data['cardMoney'] = (int) $data['cardMoney'];
    	if ($data['cardMoney'] < 1) {
    		$this->tuError('请输入优惠卡面值，大于1的整数');
    	}
    	$data['fullMoney'] = (int) $data['fullMoney'];
    	if ($data['fullMoney'] < 1) {
    		$this->tuError('请输入满减金额，大于1的整数');
    	}
    	$data['num'] = (int) $data['num'];
    	if ($data['num'] < 1) {
    		$this->tuError('请输入添加张数，大于1的整数');
    	}
    	$data['bg_date']  = htmlspecialchars($data['bg_date']);
		if (empty($data['bg_date'])) {
			$this->tuError('开始时间不能为空');
		}
		if (!isDate($data['bg_date'])) {
			$this->tuError('开始时间格式不正确');
		}

		$data['end_date'] = htmlspecialchars($data['end_date']);
		if (empty($data['end_date'])) {
			$this->tuError('结束时间不能为空');
		}
		if (!isDate($data['end_date'])) {
			$this->tuError('结束时间格式不正确');
		}
		if($data['bg_date'] >= $data['end_date']){
				$this->tuError('开始时间不能大于或者等于结束时间');
			}
		return $data;
    }
    
    /**
     * 删除优惠卡
     * @author pingdan <[<email address>]>
     * @return [type] [description]
     */
    public function card_delete($card_id = 0){
    	$card_id = (int) $card_id;
    	$map = array('card_id' => $card_id);
    	$card = D('AppointCard')->where($map)->find();
		if (!$card) {
			$this->tuError('优惠卡不存在');
		}
		if (D('AppointCard')->where($map)->delete()) {
			$this->tuSuccess('删除成功', U('appoint/card'));
		} else {
			$this->tuError('删除失败');
		}
    }

    //家政点评OEDER_ID主键
    public function appoint(){
        $Appointdianping = D('AppointDianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'shop_id' => $this->shop_id);
        $count = $Appointdianping->where($map)->count();
        $Page = new Page($count, 15);
        $show = $Page->show();
        $list = $Appointdianping->where($map)->order(array('dianping_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $order_ids = array();
        foreach ($list as $k => $val) {
            $list[$k] = $val;
            $user_ids[$val['user_id']] = $val['user_id'];
            $dianping_ids[$val['dianping_id']] = $val['dianping_id'];
        }
        if (!empty($user_ids)) {
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if (!empty($dianping_ids)) {
            $this->assign('pics', D('AppointDianpingPics')->where(array('dianping_id' => array('IN', $dianping_ids)))->select());
        }
        foreach ($list as $key => $v) {
            if (in_array($v['dianping_id'], $dianping_ids)) {
                $list[$key]['pichave'] = 1;
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //家政点评回复
    public function appointreply($dianping_id){
        $dianping_id = (int) $dianping_id;
        $detail = D('AppointDianping')->where(array('dianping_id'=>$dianping_id))->find();
        if (empty($detail) || $detail['shop_id'] != $this->shop_id) {
            $this->tuError('没有该内容');
        }
        if ($this->isPost()) {
            if ($reply = $this->_param('reply', 'htmlspecialchars')) {
                $data = array('reply' => $reply);
                if (D('AppointDianping')->where(array('dianping_id'=>$dianping_id))->save($data)) {
                    $this->tuSuccess('回复成功', U('appoint/appoint'));
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
        exit;
        if ($comment_id = (int) $comment_id){
            $obj = D('AppointDianping');
            $detail = D('AppointDianping')->where(array('dianping_id' => $comment_id, 'shop_id' => $this->shop_id))->find();
            if (!$detail){
                $this->tuError('点评记录不存在');
            }
            if($obj->delete($comment_id)){
                $this->tuSuccess('删除成功', U('appoint/appoint'));
            }
            $this->tuError('操作失败');
        } else {
            $this->tuError('请选择要删除的点评');
        }
    }
}