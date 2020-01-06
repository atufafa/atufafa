<?php
class CityagentAction extends CommonAction {
	private $create_fields = array('name','end_time','mobile', 'intro', 'agent_id', 'price', 'type','photo_positive', 'photo_back', 'photo_ying', 'photo_shou', 'photo_shen', 'photo_shop');//提交数据字段

	   /*前台提交数据入口
	    *	
	    **/
		public function index(){
            $ds=M('user_agent_applys')->where(['user_id'=>$this->uid])->find();
            if(!empty($ds) && $ds['is_pay'] ==0){
                M('user_agent_applys')->where(['user_id'=>$this->uid,'apply_id'=>$ds['apply_id']])->delete();
            }
		    $daili=M('user_agent_applys')->where(['user_id'=>$this->uid])->find();
			if($this->isPost()){

                $peison=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>1))->find();
                $paotui=D('Delivery')->where(array('user_id'=>$this->uid,'level'=>2))->find();
                $siji=D('Userspinche')->where(array('user_id'=>$this->uid))->find();
                $peisonadmin=D('Applicationmanagement')->where(array('user_id'=>$this->uid))->find();
                $daili=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>1))->find();
                $chenshi=D('UsersAgentApply')->where(array('user_id'=>$this->uid,'level'=>2))->find();
                if(!empty($paotui) || !empty($peison) || !empty($siji) || !empty($peisonadmin) || !empty($daili) || !empty($chenshi)){
                    $this->ajaxReturn(array('code'=>'0','msg'=>'您已注册配送员或跑腿、司机、配送管理员、代理、城市合伙人，请重新换号注册！'));
                }

				$data = $this->checkFields($this->_post('data', false), $this->create_fields);
				if(empty($data['mobile'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'申请手机号不能为空'));
				}
				if(empty($data['name'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'申请姓名不能为空'));
				}
				if(empty($data['intro'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'申请说明不能为空'));
				}
				if(empty($data['agent_id'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'申请代理类型不能为空'));
				}
				if(empty($data['photo_positive'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'身份证正面不能为空'));
				}
				if(!isImage($data['photo_positive'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'身份证正面照片格式不正确'));
		        }
				if(empty($data['photo_back'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'身份证反面不能为空'));
				}
				if(!isImage($data['photo_back'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'身份证反面照片格式不正确'));
		        }
		        if(empty($data['photo_ying'])){
		        	$this->ajaxReturn(array('code'=>'0','msg'=>'营业执照不能为空'));
				}
				if(!isImage($data['photo_ying'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'营业执照格式不正确'));
		        }
		        if(empty($data['photo_shou'])){
		        	$this->ajaxReturn(array('code'=>'0','msg'=>'手持身份证照片不能为空'));
				}
				if(!isImage($data['photo_shou'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'手持身份证照片格式不正确'));
		        }
		        if(empty($data['photo_shop'])){
		        	$this->ajaxReturn(array('code'=>'0','msg'=>'商家照片不能为空'));
				}
				if(!isImage($data['photo_shop'])){
					$this->ajaxReturn(array('code'=>'0','msg'=>'商家照片格式不正确'));
		        }
                $config = $config = D('Setting')->fetchAll();
                $jieshu=$config['site']['dlend_time'];
                if(!empty($jieshu) || $jieshu!=0){
                    $now = date('Y-m-d H:i:s',time());
                    $data['end_time']=date("Y-m-d",strtotime("+".$jieshu."years",strtotime($now)));
                }
				$data['create_time'] = NOW_TIME;
				$data['create_ip'] = get_client_ip();
				$data['closed'] = 0;
				$data['audit'] =0;
				$data['user_id'] =$this->uid;

				//地址1（下拉选项）
				$row = I("post.data",array());

				//推荐人ID
				$data['user_guide_id'] = ($row['user_guide_id'])?$row['user_guide_id']:0;
                $province = D('province')->field('name')->where(['province_id'=>$row['province_id']])->find();
				$city = D('City')->field('name')->where(array('city_id'=>$row['city_id']))->find();
               	$area = D('Area')->field('area_name')->where(array('area_id'=>$row['area_id']))->find();
               	$business = D('Business')->field('business_name')->where(array('business_id'=>$row['business_id']))->find();

				if(!empty($province['name'])&&!empty($city['name'])&&!empty($area['area_name'])&&!empty($business['business_name'])){
					$data['addr1'] =$province['name'].'-'.$city['name'].'-'.$area['area_name'].'-'.$business['business_name'];
				}elseif(!empty($city['name'])&&!empty($area['area_name'])){
					$data['addr1'] = $city['name'].'-'.$area['area_name'];
				}else{
					$data['addr1'] = $city['name'];
				}

				//地址2（手动输入）
				$data['addr2'] = $row['addr'];

				//地址3(百度坐标)
				$data['addr3'] = $row['lng'].','.$row['lat'];
				
				//注册类型
				if(isset($_COOKIE['type'])){
					$data['level'] = $_COOKIE['type'];
				}
				
				//支付类型
				$code = I("post.code");
				$price = $row['price'];
				$data['type'] = (!empty($code)&& $code=='alipay') ? 1 : 2;

				if($agent_id = D('UsersAgentApply')->add($data)){

					if(!empty($price)&&$price>0){
						$arr = array(
							'type' => 'agent', //支付来源
							'user_id' => $this->uid, 
							'order_id' => $agent_id, 
							'code' => $code, 
							'need_pay' => $price,
							'create_time' => time(), 
							'create_ip' => get_client_ip(), 
							'is_paid' => 0
						);
						if($log_id = D("Paymentlogs")->add($arr)){
							$this->ajaxReturn(array('code'=>'1','msg'=>'正在跳转为您支付','url'=>U('wap/payment/payment', array('log_id' =>$log_id))));
						}else{
							$this->ajaxReturn(array('code'=>'0','msg'=>'设置订单失败'));
						}
					}
					$this->ajaxReturn(array('code'=>'1','msg'=>'恭喜您申请成功','url'=>U('member/index')));
				}else{
					$this->ajaxReturn(array('code'=>'0','msg'=>'申请失败'));
				}
			}else{
                $this->assign('daili',$daili);
                $this->assign('province',D('province')->where(['is_open'=>1])->select());
                //查询协议
                $xueyi=D('Agreementlist')->where(['x_id'=>6])->find();
                $this->assign('title',$xueyi['title']);
                $this->assign('content',$xueyi['details']);

				$level = (isset($_COOKIE['type'])&&!empty($_COOKIE['type']))?$_COOKIE['type']:1;
				$agent = D('Cityagent')->where(array('level'=>$level))->select();
				$this->assign('agent',$agent);
				$this->assign('payment', D('Payment')->getPayments(true));
				$this->display();
			}
		}

		public function upload($file='')
		{
			import('ORG.Net.UploadFile');
            $upload = new UploadFile(); //
            $upload->maxSize = 3145728; // 设置附件上传大小
            $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg'); // 设置附件上传类型
            $name = date('Y/m/d', NOW_TIME);
            $dir = BASE_PATH . '/attachs/user/' . $name . '/';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $upload->savePath = $dir; // 设置附件上传目录
            if (!$upload->upload()) {// 上传错误提示错误信息
                $this->error($upload->getErrorMsg());
            } else {// 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
            }
            return $dir.$info[0]['savename'];
		}


		//改变选项(价格等级)
		public function changePromo(){
			$rel = array();
			$get_level = I("post.level");
			$pid = I("post.pid");
			if(isset($pid)&& (int)$pid >0){
				$pricef= D('Cityagent')->field('price')->where(array('agent_id'=>$pid))->find();
				$this->ajaxReturn(array('code'=>'0','msg'=>'请求成功','agent'=>$pricef));
			}else{
				$agent =D('Cityagent')->where(array('level'=>$get_level))->select();
				$this->ajaxReturn(array('code'=>'0','msg'=>'请求成功','agent'=>$agent));
			}
		} 		
}