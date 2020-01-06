<?php
class ApplyadminAction extends CommonAction{
		private $delivery_create_fieldss = array('sq_name', 'end_time','sq_userid','sq_tel', 'sq_photo', 'sq_license','recommend', 'dj_id','sq_address','lng','lat');
    
		//显示
		public function index(){
			if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
		$obj = D('Applicationmanagement');
		$gly=$obj->where(['user_id'=>$this->uid])->find();
        if(!empty($gly) && $gly['is_pay']==0){
            $obj->where(['user_id'=>$this->uid,'sq_id'=>$gly['sq_id']])->delete();
        }

        $level = D('Deliveryadmin')->select();
        $this->assign('level', $level);
        $this->assign('payment', D('Payment')->getPayments(true));
		$peison = $obj->where(array('user_id' => $this->uid))->find();
		if($peison['sq_delete'] !=0){
			$this->tuMsg('非法错误');
		}
        if($this->isPost()){
            $data = $this->delivery_createCheck();
            if ($obj->add($data)){
                $this->tuMsg('恭喜您申请成功', U('user/member/index'));
            }else{
				$this->tuMsg('申请失败');
			}
        }else{
            //查询协议
            $xueyi=D('Agreementlist')->where(['x_id'=>4])->find();
            $this->assign('title',$xueyi['title']);
            $this->assign('content',$xueyi['details']);
			$this->assign('peison', $peison);
            $this->display();
        }
		}

	private function delivery_createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->delivery_create_fieldss);
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
        $data['dj_id'] = I('post.sq_level');
        $data['auth_id'] = htmlspecialchars($data['grade_id']);
        $data['recommend'] = (int) $data['recommend'];
        if(!empty($data['recommend'])){
            if(false==D('Users')->where(['user_id'=>$data['recommend']])->find()){
                $this->tuMsg('该推荐人ID不存在，请输入有效推荐人ID');
            }
        }
	
	
        return $data;
    }

		 //配送员支付
    public function psy(){
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
        $config= D('Setting')->fetchAll();
        $jieshu=$config['site']['pend_time'];
        $now = date('Y-m-d H:i:s',time());
        $code = $this->_post('code', 'htmlspecialchars');
        $data = $this->checkFields($this->_post('data', false), $this->delivery_create_fieldss);
        $data['user_id'] = $this->uid;
        if(!empty($jieshu) || $jieshu!=0){
            $data['end_time']=date("Y-m-d",strtotime("+".$jieshu."years",strtotime($now)));
        }
        $data['sq_photo'] = htmlspecialchars($data['sq_photo']);
       
        if(empty($data['sq_photo'])){
            $this->tuMsg('请上传身份证');
        }
        if(!isImage($data['sq_photo'])){
            $this->tuMsg('身份证格式不正确');
        }

         $data['sq_license'] = htmlspecialchars($data['sq_license']);
        if(empty($data['sq_license'])){
            $this->tuMsg('请上传营业执照');
        }
        if(!isImage($data['sq_license'])){
            $this->tuMsg('营业执照格式不正确');
        }

        $data['sq_name'] = htmlspecialchars($data['sq_name']);
       
        if(empty($data['sq_name'])){
            $this->tuMsg('姓名不能为空');
        }
        $data['sq_tel'] = htmlspecialchars($data['sq_tel']);
        
        if(empty($data['sq_tel'])){
            $this->tuMsg('手机号不能为空');
        }
        if(!isPhone($data['sq_tel']) && !isMobile($data['sq_tel'])){
            $this->tuMsg('手机号格式不正确');
        }
        $data['sq_address'] = htmlspecialchars($data['sq_address']);
        $data['lng'] = htmlspecialchars($data['lng']);
        $data['lat'] = htmlspecialchars($data['lat']);
        if(empty($data['sq_address'])){
            $this->tuMsg('地址不能为空');
        }
        $data['dj_id'] = (int) $data['dj_id'];
        $moneys = D('Deliveryadmin')->where(array('dj_id' => $data['dj_id']))->find();
        $money = (float) ($moneys['price']);
        $data['sq_level'] = $num;
        $code = $this->_post('code', 'htmlspecialchars');
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
            'type' => 'administrators',
            'code' => $code,
            'order_id' => 0,
            'psy' => 1,
            'need_pay' => $money,
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'deposit'=>$money
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];
        if($logs['log_id']){
            $obj = D('Applicationmanagement');

            if ($obj->add($data)){
                $this->tuMsg('提交成功',U('wap/payment/payment', array('log_id' => $logs['log_id'])));
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

    //配送管理员代理中心
    public function core(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        //查找用户代理等级
        $cityagentname = D('Applicationmanagement')->where(array('user_id'=>$this->uid))->find();
        $this->assign('cityagentname', $cityagentname);
        $dj=D('Deliveryadmin')->where(['dj_id'=>$cityagentname['dj_id']])->find();
        $ry=D('Deliveryahonor')->where(['ry_id'=>$cityagentname['ry_id']])->find();
        //查询每月获得分成
        $sql = "select sum(money) as money from tu_delivery_divide where date_format(time,'%Y-%m')=date_format(now(),'%Y-%m') and status= 1 and sq_id=".$this->uid;
        $sql2 = M()->query($sql);
        //查询每月取消的分成
        $sql3 = "select sum(money) as money from tu_delivery_divide where date_format(time,'%Y-%m')=date_format(now(),'%Y-%m') and status= 2 and sq_id=".$this->uid;
        $sql4 = M()->query($sql3);
        $this->assign('profit_ok',$sql2[0]['money']);
        $this->assign('profit_cancel',$sql4[0]['money']);
        $this->assign('dj',$dj);
        $this->assign('ry',$ry);
        $this->display();

    }


    //分成
    public function profit(){
        $status = (int) $this->_param('status');
        $this->assign('status', $status);
        $this->assign('nextpage', LinkTo('applyadmin/profitloaddata',array('status'=>$status,'t' => NOW_TIME, 'p' => '0000')));
        $this->display(); // 输出模板
    }

    public function profitloaddata(){
        $status = (int) $this->_param('status');
        if (!in_array($status, array(1, 2))) {
            $status = 1;
        }
        $model = D('DeliveryDivide');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'status' => $status);
        $count = $model->where($map)->count();
        $Page = new Page($count, 8);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $orderby = array('id' => 'DESC');
        $list = $model->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('status', $status);
        $this->display();
    }

    //上级
    public function superior()
    {
        $user = D('Users');
        if ($this->member['fuid1']) {
            $fuser = $user->find($this->member['fuid1']);
        }
        $this->assign('fuser', $fuser);
        $this->display();
    }





}