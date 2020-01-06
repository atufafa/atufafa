<?php
class UsersauxAction extends CommonAction{
    private $create_fields = array('user_id','province_id','city_id', 'area_id', 'business_id','team_id', 'jury_id', 'group_id', 'card_photo', 'name', 'mobile','card_id','addr_str', 'addr_info', 'guarantor_name', 'guarantor_mobile');
	private $edit_fields = array('user_id','province_id','city_id', 'area_id', 'business_id', 'team_id', 'jury_id', 'group_id','card_photo', 'name', 'mobile','card_id','addr_str', 'addr_info', 'guarantor_name', 'guarantor_mobile');
    private $pinche_fields = array('recommend','end_time','user_id','province_id','vehicle_type','city_id', 'area_id', 'business_id','team_id', 'jury_id', 'group_id', 'card_photo', 'name', 'mobile','card_id','addr_str', 'addr_info', 'guarantor_name', 'guarantor_mobile','jia_photo','bao_photo','car_photo','xing_photo','yun_photo','yajin','guanli');
    private $rz=array('ying_photo','fa_photo','money','moneys','create_time','closed','end_time');


    public function index(){
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
        if ($this->isPost()) {
            $data = $this->createCheck();
            $obj = D('Usersaux');
            if ($user_id = $obj->add($data)) {
                $this->tuMsg('申请实名认证成功', U('usersaux/index'));
            }else{
				$this->tuMsg('申请失败');
			}
        } else {
            $this->assign('province',D('province')->where(['is_open'=>1])->select());
			$this->assign('detail',$detail = D('Usersaux')->find($this->uid));
			$this->assign('citys', D('City')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->display();
        }
    }
    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
		$data['user_id'] = $this->uid;
		$data['guide_id'] = (int) $data['guide_id'];
        $data['card_photo'] = htmlspecialchars($data['card_photo']);
        if (empty($data['card_photo'])) {
            $this->tuMsg('请上传身份证');
        }
        if (!isImage($data['card_photo'])) {
            $this->tuMsg('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuMsg('真实名字不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('手机号不能为空');
        }
		$data['card_id'] = (int) $data['card_id'];
        if (empty($data['card_id'])) {
            $this->tuMsg('身份证号码不能为空');
        }
	
		if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuMsg('手机号码格式不正确');
        }
		$data['province_id'] = (int) $data['province_id'];
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['province_id']) || empty($data['city_id']) || empty($data['area_id']) || empty($data['business_id'])) {
            $this->tuMsg('请选择所在地区');
        }
		
		
		// $data['team_id'] = (int) $data['team_id'];
  //       if (empty($data['team_id'])) {
  //           $this->tuMsg('队伍不能为空');
  //       }
  //       $data['jury_id'] = (int) $data['jury_id'];
  //       if (empty($data['jury_id'])) {
  //           $this->tuMsg('团队不能为空');
  //       }
  //       $data['group_id'] = (int) $data['group_id'];
  //       if (empty($data['group_id'])) {
  //           $this->tuMsg('群不能为空');
  //       }
		
		
		$city = D('City')->find($data['city_id']);
		$area = D('Area')->find($data['area_id']);
		$Busines = D('Business')->find($data['business_id']);
		$data['addr_str'] = $city['name'] . " " . $area['area_name'] . " " . $Busines['business_name'];
        $data['addr_info'] = htmlspecialchars($data['addr_info']);
        if (empty($data['addr_info'])) {
            $this->tuMsg('详细地址不能为空');
        }
		$data['guarantor_name'] = htmlspecialchars($data['guarantor_name']);
        if (empty($data['guarantor_name'])) {
            $this->tuMsg('担保人姓名不能为空');
        }
		$data['guarantor_mobile'] = htmlspecialchars($data['guarantor_mobile']);
        if (empty($data['guarantor_mobile'])) {
            $this->tuMsg('担保人电话不能为空');
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
	
	//实名编辑
	public function edit($user_id = 0){
        if ($user_id = (int) $user_id) {
            $obj = D('Usersaux');
				if (!($detail = $obj->find($user_id))) {
					$this->error('该认证不存在');
				}
				if ($detail['closed'] != 0) {
					$this->error('该认证已被删除');
				}
				if ($this->isPost()) {
					$data = $this->editCheck();
					if (false !== $obj->save($data)) {
						$this->tuMsg('编辑操作成功', U('usersaux/index'));
					}else{
						$this->tuMsg('操作失败');
					}
					
            } else {
                $this->assign('province',D('province')->where(['is_open'=>1])->select());
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {

            $this->error('参数错误');
        }
    }
	//编辑
    private function editCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['user_id'] = $this->uid;
		$data['guide_id'] = (int) $data['guide_id'];
        $data['card_photo'] = htmlspecialchars($data['card_photo']);
        if (empty($data['card_photo'])) {
            $this->tuMsg('请上传身份证');
        }
        if (!isImage($data['card_photo'])) {
            $this->tuMsg('身份证格式不正确');
        }
        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuMsg('真实名字不能为空');
        }
		$data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('手机号不能为空');
        }
		$data['card_id'] = (int) $data['card_id'];
        if (empty($data['card_id'])) {
            $this->tuMsg('身份证号码不能为空');
        }
		
		if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuMsg('手机号码格式不正确');
        }
        $data['province_id'] = (int) $data['province_id'];
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['province_id']) || empty($data['city_id']) || empty($data['area_id']) || empty($data['business_id'])) {
            $this->tuMsg('请选择所在地区');
        }
		
		$data['team_id'] = (int) $data['team_id'];
        if (empty($data['team_id'])) {
            $this->tuMsg('队伍不能为空');
        }
        $data['jury_id'] = (int) $data['jury_id'];
        if (empty($data['jury_id'])) {
            $this->tuMsg('团队不能为空');
        }
        $data['group_id'] = (int) $data['group_id'];
        if (empty($data['group_id'])) {
            $this->tuMsg('群不能为空');
        }
		
		$city = D('City')->find($data['city_id']);
		$area = D('Area')->find($data['area_id']);
		$Busines = D('Business')->find($data['business_id']);
		$data['addr_str'] = $city['name'] . " " . $area['area_name'] . " " . $Busines['business_name'];
        $data['addr_info'] = htmlspecialchars($data['addr_info']);
        if (empty($data['addr_info'])) {
            $this->tuMsg('详细地址不能为空');
        }
		$data['guarantor_name'] = htmlspecialchars($data['guarantor_name']);
        if (empty($data['guarantor_name'])) {
            $this->tuMsg('担保人姓名不能为空');
        }
		$data['guarantor_mobile'] = htmlspecialchars($data['guarantor_mobile']);
        if (empty($data['guarantor_mobile'])) {
            $this->tuMsg('担保人电话不能为空');
        }
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        return $data;
    }
	
	function is_idcard( $id ) { 
	  $id = strtoupper($id); 
	  $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/"; 
	  $arr_split = array(); 
	  if(!preg_match($regx, $id)) { 
		return FALSE; 
	  } 
	  if(15==strlen($id)) { 
		$regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/"; 
	  
		@preg_match($regx, $id, $arr_split); 
		//检查生日日期是否正确 
		$dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 
		if(!strtotime($dtm_birth))  { 
		  return FALSE; 
		} else { 
		  return TRUE; 
		} 
	  } 
	  else { 
		$regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/"; 
		@preg_match($regx, $id, $arr_split); 
		$dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4]; 
		if(!strtotime($dtm_birth)) { 
		  return FALSE; 
		} 
		else{ 
		  //检验18位身份证的校验码是否正确。 
		  //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。 
		  $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2); 
		  $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'); 
		  $sign = 0; 
		  for ( $i = 0; $i < 17; $i++ ) 
		  { 
			$b = (int) $id{$i}; 
			$w = $arr_int[$i]; 
			$sign += $b * $w; 
		  } 
		  $n = $sign % 11; 
		  $val_num = $arr_ch[$n]; 
		  if ($val_num != substr($id,17, 1)) 
		  { 
			return FALSE; 
		  } 
		  else
		  { 
			return TRUE; 
		  } 
		} 
	  } 
	} 	
    //拼车司机资料上传   ---- 新增
    public function pincheCrad()
    {
        if (empty($this->uid)) {
            header("Location:" . U('passport/login'));
            die;
        }
       

        
        // if ($this->isPost()) {
        //     $data = $this->pincheCrad_check();
        //     $obj = M('UsersPinche');
        //     if ($user_id = $obj->add($data)) {
        //         $this->tuMsg('申请拼车司机认证成功', U('usersaux/pincheCrad'));
        //     }else{
        //         $this->tuMsg('申请失败');
        //     }
        // } else {

            //查询协议
            $xueyi=D('Agreementlist')->where(['x_id'=>3])->find();
            $this->assign('title',$xueyi['title']);
            $this->assign('content',$xueyi['details']);
            $config = D('Setting')->fetchAll();
            $this->assign('money',$config['site']['pinche_money']);
            $this->assign('moneys',$config['site']['pinche_moneys']);
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->assign('detail',$detail = D('Userspinche')->where(array('user_id'=>$this->uid))->find());
            $this->assign('citys', D('City')->fetchAll());
            $this->assign('business', D('Business')->fetchAll());
            $this->assign('province',D('province')->where(['is_open'=>1])->select());
            $this->display();
       // }
    }

    //资料检验 ---- 新增
    public function pincheCrad_check()
    {
        $code = $this->_post('code', 'htmlspecialchars');
        $data = $this->checkFields($this->_post('data', false), $this->pinche_fields);
        $data['user_id'] = $this->uid;
        $data['guide_id'] = (int) $data['guide_id'];
        $data['card_photo'] = htmlspecialchars($data['card_photo']);
        if (empty($data['card_photo'])) {
            $this->tuMsg('请上传身份证');
        }
        if (!isImage($data['card_photo'])) {
            $this->tuMsg('身份证格式不正确');
        }
        $data['jia_photo'] = htmlspecialchars($data['jia_photo']);
        if (empty($data['jia_photo'])) {
            $this->tuMsg('请上传驾驶证');
        }
        if (!isImage($data['jia_photo'])) {
            $this->tuMsg('驾驶证格式不正确');
        }

        $data['bao_photo'] = htmlspecialchars($data['bao_photo']);
        if (empty($data['bao_photo'])) {
            $this->tuMsg('请上传车辆保险凭证');
        }
        if (!isImage($data['bao_photo'])) {
            $this->tuMsg('车辆保险凭证格式不正确');
        }

        $data['car_photo'] = htmlspecialchars($data['car_photo']);
        if (empty($data['car_photo'])) {
            $this->tuMsg('请上传车辆和人照片');
        }
        if (!isImage($data['car_photo'])) {
            $this->tuMsg('车辆和人照片格式不正确');
        }

        $data['xing_photo'] = htmlspecialchars($data['xing_photo']);
        if (empty($data['xing_photo'])) {
            $this->tuMsg('请上传行驶证');
        }
        if (!isImage($data['xing_photo'])) {
            $this->tuMsg('行驶证格式不正确');
        }

        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuMsg('真实名字不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('手机号不能为空');
        }
        $data['card_id'] = (int) $data['card_id'];
        if (empty($data['card_id'])) {
            $this->tuMsg('身份证号码不能为空');
        }
    
        if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuMsg('手机号码格式不正确');
        }
        $data['province_id'] = (int) $data['province_id'];
        $data['city_id'] = (int) $data['city_id'];
        $data['area_id'] = (int) $data['area_id'];
        $data['business_id'] = (int) $data['business_id'];

        if (empty($data['province_id']) || empty($data['city_id']) || empty($data['area_id']) || empty($data['business_id'])) {
            $this->tuMsg('请选择地区');
        }
        $city = D('City')->find($data['city_id']);
        $area = D('Area')->find($data['area_id']);
        $Busines = D('Business')->find($data['business_id']);
        $data['addr_str'] = $city['name'] . " " . $area['area_name'] . " " . $Busines['business_name'];
        $data['addr_info'] = htmlspecialchars($data['addr_info']);
        if (empty($data['addr_info'])) {
            $this->tuMsg('详细地址不能为空');
        }
        $data['guarantor_name'] = htmlspecialchars($data['guarantor_name']);
        if (empty($data['guarantor_name'])) {
            $this->tuMsg('担保人姓名不能为空');
        }
        $data['guarantor_mobile'] = htmlspecialchars($data['guarantor_mobile']);
        if (empty($data['guarantor_mobile'])) {
            $this->tuMsg('担保人电话不能为空');
        }
        $data['yun_photo'] = htmlspecialchars($data['yun_photo']);


        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();


         $data['yajin'] = htmlspecialchars($data['yajin']);
         $data['guanli'] = htmlspecialchars($data['guanli']);
        
         $code = $this->_post('code', 'htmlspecialchars');
       
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
        }
        $logs = array(
            'user_id' => $this->uid,
            'type' => 'money',
            'code' => $code,
            'order_id' => 1,
            'psy' => 1,
            'need_pay' => $data['yajin']+ $data['guanli'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip()
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];
        //var_dump($data);die;
        if($logs['log_id']){
            $obj = M('UsersPinche');
            if ($obj->add($data)){
                //$this->tuMsg('申请拼车司机认证成功', U('usersaux/pincheCrad'));
            }else{
                D('Paymentlogs')->delete($logs['log_id']);
                $this->error('请重新输入');
            }
        }

        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('money', $money);
        $this->assign('logs', $logs);
        $this->display();

       // return $data;
    }

    public function life()
    {
        if ($this->isPost()) {
            $users = D('Users')->find($this->uid);
            $data['money'] = $_POST['money'];
            $data['ying_photo'] = $_POST['ying_photo'];
            $data['fa_photo'] = $_POST['fa_photo'];
            if($users['money'] < $data['money']){
                $this->tuMsg('余额不足，请充值后再次尝试',U('money/index'));
            }
            if (empty($data['ying_photo'])) {
                $this->tuMsg('请上传营业执照');
            }
            if (!isImage($data['ying_photo'])) {
                $this->tuMsg('营业执照格式不正确');
            }
            if (empty($data['fa_photo'])) {
                $this->tuMsg('请上传法人手持证件照');
            }
            if (!isImage($data['fa_photo'])) {
                $this->tuMsg('法人手持证件照格式不正确');
            }
            $data['audit'] = 0;
            $data['create_time'] = NOW_TIME;
            $data['is_pay'] = 1;
            $data['user_id'] = $this->uid;
            $data['closed'] = 0;
            if(M('LifeAudit')->add($data)){
                if(false == D('Users')->addMoney($data['user_id'],-$data['money'],'提交公司认证扣除押金'.$data['money'])){
                    $this->tuMsg('押金扣除失败，,请联系管理员处理');
                }else{
                    $this->tuMsg('提交认证成功',U('usersaux/life'));
                }
            }else{
                $this->tuMsg('提交认证失败');
            }
        } else {
            $config = D('Setting')->fetchAll();
            $this->assign('money',$config['site']['life_money']);
            $this->assign('moneys',$config['site']['life_moneys']);
            $detail = M('LifeAudit')->find($this->uid);
            $this->assign('detail',$detail);
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->display();
        }
    }
    public function life_edit($user_id)
    {
        if ($user_id = (int) $user_id) {
            $obj = M('LifeAudit');
                if (!($detail = $obj->find($user_id))) {
                    $this->error('该认证不存在');
                }
                if ($detail['closed'] != 0) {
                    $this->error('该认证已被删除');
                }
                if ($this->isPost()) {
                    $data['ying_photo'] = $_POST['ying_photo'];
                    $data['fa_photo'] = $_POST['fa_photo'];
                    if (empty($data['ying_photo'])) {
                        $this->tuMsg('请上传营业执照');
                    }
                    if (!isImage($data['ying_photo'])) {
                        $this->tuMsg('营业执照格式不正确');
                    }
                    if (empty($data['fa_photo'])) {
                        $this->tuMsg('请上传法人手持证件照');
                    }
                    if (!isImage($data['fa_photo'])) {
                        $this->tuMsg('法人手持证件照格式不正确');
                    }
                    $data['audit'] = 0;
                    $data['create_time'] = NOW_TIME;
                    $data['is_pay'] = 1;
                    $data['closed'] = 0;
                    if (false !== $obj->where(['user_id'=>$user_id])->save($data)) {
                        $this->tuMsg('编辑操作成功', U('usersaux/life'));
                    }else{
                        $this->tuMsg('操作失败');
                    }
                    
            } else {
                $this->assign('detail', $detail);
                $this->display();
            }
        } else {
            $this->tuMsg('参数错误');
        }
    }


    public function rez(){
            $config = D('Setting')->fetchAll();
            $this->assign('money',$config['site']['life_money']);
            $this->assign('moneys',$config['site']['life_moneys']);
            $detail = M('LifeAudit')->find($this->uid);
            $this->assign('detail',$detail);
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->display();
    }


    //公司认证支付方式
    public function psy(){
         if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        $code = $this->_post('code', 'htmlspecialchars');
        $data = $this->checkFields($this->_post('data', false), $this->rz);
        $data['user_id'] = $this->uid;
        $data['ying_photo'] = htmlspecialchars($data['ying_photo']);
        if(empty($data['ying_photo'])){
            $this->tuMsg('请上传营业执照');
        }
        if(!isImage($data['ying_photo'])){
            $this->tuMsg('营业执照格式不正确');
        }
        $data['fa_photo'] = htmlspecialchars($data['fa_photo']);
        if(empty($data['fa_photo'])){
            $this->tuMsg('请上传法人手持证件照');
        }

         if(!isImage($data['fa_photo'])){
            $this->tuMsg('法人手持证件照格式不正确');
        }

        $config = $config = D('Setting')->fetchAll();
        $jieshu=$config['site']['send_time'];
        if(!empty($jieshu) || $jieshu!=0){
            $now = date('Y-m-d H:i:s',time());
            $data['end_time']=date("Y-m-d",strtotime("+".$jieshu."years",strtotime($now)));
        }


         $data['create_time'] = NOW_TIME;
         $data['closed'] = 0;

         //$num = $this->_post('money');
        
         $data['money'] = htmlspecialchars($data['money']);
         $data['moneys'] = htmlspecialchars($data['moneys']);
        
         $code = $this->_post('code', 'htmlspecialchars');
       
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->tuMsg('该支付方式不存在');
        }
        $logs = array(
            'user_id' => $this->uid,
            'type' => 'xinxi',
            'code' => $code,
            'order_id' => $order_id,
            'psy' => 0,
            'need_pay' => $data['money']+ $data['moneys'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'deposit'=>$data['money']
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];
        //var_dump($data);die;
        if($logs['log_id']){
            $obj = M('LifeAudit');
            if ($obj->add($data)){
                $this->tuMsg('正在为您跳入付款页面',U('wap/payment/payment', array('log_id' => $logs['log_id'])));
            }else{
                D('Paymentlogs')->delete($logs['log_id']);
                $this->tuMsg('请重新输入');
            }
        }

        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('money', $money);
        $this->assign('logs', $logs);
        $this->display();
        
        }

    //司机认证支付方式
    public function pay(){
         if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }

        $peison=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>1))->find();
        $paotui=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>2))->find();
        $siji=D('Userspinche')->where(array('user_id'=>$this->uid))->find();
        $peisonadmin=D('Applicationmanagement')->where(array('user_id'=>$this->uid))->find();
        $daili=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>1))->find();
        $chenshi=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>2))->find();
        if(!empty($paotui) || !empty($peison) || !empty($siji) || !empty($peisonadmin) || !empty($daili) || !empty($chenshi)){
            $this->tuMsg('您已注册配送员或跑腿、司机、配送管理员、代理、城市合伙人，请重新换号注册！');
        }



        $code = $this->_post('code', 'htmlspecialchars');

        $data = $this->checkFields($this->_post('data', false), $this->pinche_fields);
        $data['user_id'] = $this->uid;
        $data['guide_id'] = (int) $data['guide_id'];

        $data['card_photo'] = htmlspecialchars($data['card_photo']);
        if (empty($data['card_photo'])) {
            $this->tuMsg('请上传身份证');
        }
        if (!isImage($data['card_photo'])) {
            $this->tuMsg('身份证格式不正确');
        }
        $data['jia_photo'] = htmlspecialchars($data['jia_photo']);
        if (empty($data['jia_photo'])) {
            $this->tuMsg('请上传驾驶证');
        }
        if (!isImage($data['jia_photo'])) {
            $this->tuMsg('驾驶证格式不正确');
        }

        $data['bao_photo'] = htmlspecialchars($data['bao_photo']);
        if (empty($data['bao_photo'])) {
            $this->tuMsg('请上传车辆保险凭证');
        }
        if (!isImage($data['bao_photo'])) {
            $this->tuMsg('车辆保险凭证格式不正确');
        }

        $data['car_photo'] = htmlspecialchars($data['car_photo']);
        if (empty($data['car_photo'])) {
            $this->tuMsg('请上传车辆和人照片');
        }
        if (!isImage($data['car_photo'])) {
            $this->tuMsg('车辆和人照片格式不正确');
        }

        $data['xing_photo'] = htmlspecialchars($data['xing_photo']);
        if (empty($data['xing_photo'])) {
            $this->tuMsg('请上传行驶证');
        }
        if (!isImage($data['xing_photo'])) {
            $this->tuMsg('行驶证格式不正确');
        }

        $data['recommend'] = (int) $data['recommend'];
        if(!empty($data['recommend'])){
            if(false==D('Users')->find($data['recommend'])){
                $this->tuMsg('该推荐人不存在');
            }
        }

        $data['name'] = htmlspecialchars($data['name']);
        if (empty($data['name'])) {
            $this->tuMsg('真实名字不能为空');
        }
        $data['mobile'] = htmlspecialchars($data['mobile']);
        if (empty($data['mobile'])) {
            $this->tuMsg('手机号不能为空');
        }
        $data['card_id'] = (int) $data['card_id'];
        if (empty($data['card_id'])) {
            $this->tuMsg('身份证号码不能为空');
        }
    
        if (!isPhone($data['mobile']) && !isMobile($data['mobile'])) {
            $this->tuMsg('手机号码格式不正确');
        }
        
        $data['city_id'] = (int) $data['city_id'];
        if (empty($data['city_id'])) {
            $this->tuMsg('城市不能为空');
        }
        $data['area_id'] = (int) $data['area_id'];
        if (empty($data['area_id'])) {
            $this->tuMsg('地区不能为空');
        }
        $data['business_id'] = (int) $data['business_id'];
        if (empty($data['business_id'])) {
            $this->tuMsg('商圈不能为空');
        }

        $city = D('City')->find($data['city_id']);
        $area = D('Area')->find($data['area_id']);
        $Busines = D('Business')->find($data['business_id']);
        $data['addr_str'] = $city['name'] . " " . $area['area_name'] . " " . $Busines['business_name'];
        $data['addr_info'] = htmlspecialchars($data['addr_info']);
        if (empty($data['addr_info'])) {
            $this->tuMsg('详细地址不能为空');
        }
        $data['guarantor_name'] = htmlspecialchars($data['guarantor_name']);
        if (empty($data['guarantor_name'])) {
            $this->tuMsg('担保人姓名不能为空');
        }
        $data['guarantor_mobile'] = htmlspecialchars($data['guarantor_mobile']);
        if (empty($data['guarantor_mobile'])) {
            $this->tuMsg('担保人电话不能为空');
        }
        $data['yun_photo'] = htmlspecialchars($data['yun_photo']);
        $data['vehicle_type'] = (int) $data['vehicle_type'];
        if(empty($data['vehicle_type'])){
            $this->tuMsg('请选择车辆类型');
        }
        $config = $config = D('Setting')->fetchAll();
        $jieshu=$config['site']['pinche_time'];
        $now = date('Y-m-d H:i:s',time());
        $data['end_time']=date("Y-m-d",strtotime("+".$jieshu."years",strtotime($now)));
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        
        $data['money'] = htmlspecialchars($data['money']);
        $data['moneys'] = htmlspecialchars($data['moneys']);
        
       // $code = $this->_post('code', 'htmlspecialchars');
       
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->tuMsg('该支付方式不存在');
        }

        $xueyi = $this->_post('checkbox');
        if(empty($xueyi)){
            $this->tuMsg('请勾选同意协议');
        }

        $logs = array(
            'user_id' => $this->uid,
            'type' => 'pingche',
            'code' => $code,
            'order_id' => $order_id,
            'psy' => 1,
            'need_pay' => $data['yajin']+ $data['guanli'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'deposit'=> $data['yajin']
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];
        //var_dump($data);die;
        if($logs['log_id']){
            $obj = M('UsersPinche');
            if ($obj->add($data)){
                $this->tuMsg('提交成功',U('wap/payment/payment', array('log_id' => $logs['log_id'])));
            }else{
                D('Paymentlogs')->delete($logs['log_id']);
                $this->tuMsg('请重新输入');
            }
        }

       $this->assign('button', D('Payment')->getCode($logs));
//        $this->assign('money', $money);
//        $this->assign('logs', $logs);
        $this->display();
        
        }

}