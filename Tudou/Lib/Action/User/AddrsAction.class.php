<?php
class AddrsAction extends CommonAction {
	
	private $create_fields = array('type','province_id','order_id','user_id','city_id','area_id','business_id','name','mobile','addr','info','lng','lat');
    private $edit_fields = array('type','province_id','order_id','user_id','city_id','area_id','business_id','name','mobile','addr','info','lng','lat');
	
	

	public function index() {
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = I('order_id', 0, 'trim,intval');
        $list = D('UserAddr')->where(array('user_id'=>$this->uid,'closed'=>0))->select();
		foreach($list as $k => $v){
		    if($Province=D('province')->find($v['province_id'])){
                $list[$k]['province'] = $Province;
            }
            if($City = D('City')->find($v['city_id'])){
                $list[$k]['city'] = $City;
            }
			if($Area = D('Area')->find($v['area_id'])){
                $list[$k]['area'] = $Area;
            }
			if($Business = D('Business')->find($v['business_id'])){
                $list[$k]['business'] = $Business;
            }
        }
        $this->assign('list',$list);
		$this->assign('type', $type);
        $this->assign('order_id', $order_id);
		$this->display();
	}
	
	
	//跳转URL
	public function toggle_url($type,$order_id,$addr_id) {
		if($type == 1 && (!empty($order_id))){
			$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功，正在为您跳转到外卖付款页','url'=>U('wap/ele/pay', array('order_id' => $order_id))));
		}if($type == 3 && (!empty($order_id))){
			$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功，正在为您跳转到菜市场付款页','url'=>U('wap/market/pay', array('order_id' => $order_id))));
		}if($type == 4 && (!empty($order_id))){
			$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功，正在为您跳转到便利店付款页','url'=>U('wap/store/pay', array('order_id' => $order_id))));
		}else{
			$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('user/addrs/index')));
		}
	}
	
	
	
	public function create($type = 0,$order_id = 0){
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = I('order_id', 0, 'trim,intval');
        if($this->isPost()){
            $data = $this->createCheck();
            $obj = D('Useraddr');
            if($addr_id = $obj->add($data)){
				$this->toggle_url($data['type'],$data['order_id'],$addr_id);//跳转链接
            }
            $this->ajaxReturn(array('code'=>'0','msg'=>'操作失败'));
        }else{
			$this->assign('type', $type);
			$this->assign('order_id', $order_id);
            $this->assign('province',D('province')->where(['is_open'=>1])->select());
            $this->display();
        }
    }
	
	
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		$data['type'] = htmlspecialchars($data['type']);
		$data['order_id'] = (int) $data['order_id'];
        $data['user_id'] = $this->uid;
        if(empty($data['user_id'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'用户不存在'));
        }

        $data['province_id'] = (int) $data['province_id'];
		$data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];

        if(empty($data['province_id']) || empty($data['city_id']) || empty($data['area_id']) || empty($data['business_id'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请选择地区'));
        }


        $data['name'] = htmlspecialchars($data['name']);
        if(empty($data['name'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'收货人不能为空'));
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if(empty($data['mobile'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号码不能为空'));
        }
        if(!isMobile($data['mobile'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号码格式不正确'));
        }
		
        $data['getAddr'] = htmlspecialchars($data['addr']);
        if(empty($data['getAddr'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'地址不能为空'));
        }
		$data['info'] = htmlspecialchars($data['info']);
		if(empty($data['info'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'请填写到具体门牌号'));
        }
        $data['addr'] = $data['getAddr'].'###'.$data['info'];
		
		$data['lng'] = htmlspecialchars($data['lng']);
		if(empty($data['lng'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'经度不能为空'));
        }
        $data['lat'] = htmlspecialchars($data['lat']);
		if(empty($data['lat'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'纬度不能为空'));
        }
        return $data;
    }
	
	
    public function edit($addr_id = 0,$type = 0,$order_id = 0){
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = I('order_id', 0, 'trim,intval');
        if($addr_id = (int) $addr_id){
            $obj = D('Useraddr');
            if(!($detail = $obj->find($addr_id))) {
				$this->ajaxReturn(array('code'=>'0','msg'=>'地址不存在'));
            }
            if($this->isPost()){
                $data = $this->editCheck();
                $data['addr_id'] = $addr_id;
                // print_r($data);die;
                $url = "http://api.map.baidu.com/geocoder?address=".$data['getAddr']."&output=json&key=37492c0ee6f924cb5e934fa08c6b1676";
				$arr = file_get_contents($url);
        		$arr = json_decode($arr);
        		$address = $arr->result->location;
        		// $lat = $arr->result->location['lat'];
        		// print_r($address);
        		$data['lng'] = $address->lng;
        		$data['lat'] = $address->lat;
        		// print_r($data);die;
        		// die;
                if(false !== $obj->where(['addr_id'=>$addr_id])->save($data)){
					$this->toggle_url($data['type'],$data['order_id'],$data['addr_id']);//跳转链接
                }
				$this->ajaxReturn(array('code'=>'0','msg'=>'操作失败'));
            }else{
                $this->assign('province',D('province')->where(['is_open'=>1])->select());
				$this->assign('type', $type);
				$this->assign('order_id', $order_id);
				$this->assign('address', $address = explode('###',$detail['addr']));
                $this->assign('detail', $detail);
                $this->display();
            }
        }else{
			$this->ajaxReturn(array('code'=>'0','msg'=>'请选择要编辑的地址'));
        }
    }
	
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
		$data['type'] = htmlspecialchars($data['type']);
		$data['order_id'] = (int) $data['order_id'];
        $data['user_id'] = $this->uid;
        if(empty($data['user_id'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'用户不存在'));
        }
        $data['province_id'] = (int) $data['province_id'];
		$data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];

        if(empty($data['province_id']) || empty($data['city_id']) || empty($data['area_id']) || empty($data['business_id'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请选择地区'));
        }

        $data['name'] = htmlspecialchars($data['name']);
        if(empty($data['name'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'收货人不能为空'));
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if(empty($data['mobile'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号码不能为空'));
        }
        if(!isMobile($data['mobile'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'手机号码格式不正确'));
        }
		
        $data['getAddr'] = htmlspecialchars($data['addr']);
        if(empty($data['getAddr'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'地址不能为空'));
        }
		$data['info'] = htmlspecialchars($data['info']);
		if(empty($data['info'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'请填写到具体门牌号'));
        }
        $data['addr'] = $data['getAddr'].'###'.$data['info'];
		
		$data['lng'] = htmlspecialchars($data['lng']);
		if(empty($data['lng'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'经度不能为空'));
        }
        $data['lat'] = htmlspecialchars($data['lat']);
		if(empty($data['lat'])){
			$this->ajaxReturn(array('code'=>'0','msg'=>'纬度不能为空'));
        }
        return $data;
    }
	
	
	
	 public function delete($addr_id = 0){
        $addr_id = (int)$this->_param('addr_id');
		if($addr_id){
			if(!$detail = D('Useraddr')->find($addr_id)){
				$this->ajaxReturn(array('status'=>'error','msg'=>'地址不存在'));
			}
			if($detail['user_id'] != $this->uid){
				$this->ajaxReturn(array('status'=>'error','msg'=>'不要操作别人的地址'));
			}
			if(D('Useraddr')->where(array('addr_id'=>$addr_id))->save(array('closed'=>1))){
				$this->ajaxReturn(array('status'=>'success','msg'=>'恭喜您删除成功'));
			}
		}else{
			$this->ajaxReturn(array('status'=>'error','msg'=>'ID不存在'));
		}
        
    }
	
	 public function update_addr($type = 0, $order_id = 0){
		$type = I('type', '', 'trim,htmlspecialchars');
		$order_id = I('order_id', 0, 'trim,intval');
        $addr_id = I('addr_id',0, 'trim,intval');
        if(!$addr_id){
			$this->ajaxReturn(array('status'=>'error','msg'=>'ID不存在'));
        }else{
            $res = D('Useraddr')->where(array('user_id'=>$this->uid))->setField('is_default', 0);
            $res1 = D('Useraddr')->where(array('addr_id'=>$addr_id))->setField('is_default', 1);
            if($type == 1){
				$this->ajaxReturn(array('code'=>'1','msg'=>'切换外卖地址成功','url'=>U('wap/ele/pay', array('order_id' => $order_id))));
            }else{
				$this->ajaxReturn(array('code'=>'1','msg'=>'操作成功','url'=>U('user/addrs/index')));
            }
        }
		$this->assign('type', $type);
        $this->assign('order_id',$order_id);
		
    }
  
}