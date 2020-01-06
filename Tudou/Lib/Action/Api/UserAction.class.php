<?php

class UserAction extends CommonAction{
	
	
	
	
	
	//获取openid重新开始做
	public function Openid(){
		  $code = I('code','','trim,htmlspecialchars');
		  $config = D('Setting')->fetchAll();
		  $appid = $config['wxapp']['appid'];
		  $secret = $config['wxapp']['appsecret'];
		  $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
		  $res = $this->httpRequest($url);
		  print_r($res);
	}
	  
	//httpRequest请求数据
	public function httpRequest($url,$data = null){
		  $curl = curl_init();
		  curl_setopt($curl, CURLOPT_URL, $url);
		  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		  if(!empty($data)){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		  }
		  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		  $output = curl_exec($curl);
		  curl_close($curl);
		  return $output;
	}
	
	//保存formid
	public function SaveFormid(){
		$data['user_id'] = I('user_id','','trim');
		$data['form_id'] = I('form_id','','trim');
		$data['openid'] = I('openid','','trim');
		$data['time']=date('Y-m-d H:i:s');
		$res = M('UserFormid')->add($data);
		if($res){
		  echo  '1';
		}else{
		  echo  '2';
		}
	  }
	 //获取用户的formid
	  public function GetFormid(){
		$user_id = I('user_id','','trim');
		$res = M('UserFormid')->where(array('user_id'=>$user_id))->find();
		echo json_encode($res);
	  } 
	  
	  
	//删除formid
	  public function DelFormid(){
		$user_id = I('user_id','','trim');
		$form_id = I('form_id','','trim');
		$res = M('UserFormid')->where(array('user_id'=>$user_id,'form_id'=>$form_id))->delete();
		if($res){
		  echo  '1';
		}else{
		  echo  '2';
		}
	  }
	  
  
		
	//查看是否拉黑
	public function GetUserInfo(){
	  $user_id = I('user_id','','trim');
	  $is_lock = D('Users')->where(array('user_id'=>$user_id))->getField('is_lock');
	  if($is_lock == 0){
		 $res['state'] = 1; 
	  }else{
		$res['state'] = 0;   
	  }
	  echo json_encode($res);
	}
	 
	//登录用户信息
	public function Login(){
		$res['openid'] = I('openid','','trim,htmlspecialchars');
		$res['session_key'] = I('session_key','','trim,htmlspecialchars');
		$res['face'] = I('img','','trim,htmlspecialchars');
		$res['nickname'] = I('name','','trim,htmlspecialchars');
		
		$result = $this->wxappRegister($res); //返回用户信息列表
		echo json_encode($result);
	}
	 
	public function wxappRegister($res){
		$Connect = D('Connect')->getConnectByOpenid('weixin',$res['openid']);
		$data['open_id'] = $res['openid'];
        $data['type'] = 'weixin';
		$data['session_key'] = $res['session_key'];
		$data['rd_session'] = $rd_session = md5(time().mt_rand(1,999999999));
		
		if(!$Connect){
			$data['create_time'] = time();
            $data['create_ip'] = get_client_ip();
			
			$connect_id = D('Connect')->add($data);//新建表
			
            $arr = array(
               'account' => 'wxapp'.$connect_id, 
               'password' => rand(1000, 9999), 
               'nickname' => $res['nickname'], 
               'face' => $res['face'], 
               'ext0' => $res['nickname'], 
               'create_time' => NOW_TIME, 
               'create_ip' => get_client_ip()
            );
            $user_id = D('Passport')->register($arr,$fid = '',$type = '1');
			D('Connect')->save(array('connect_id' =>$connect_id, 'uid' =>$user_id));
			$Users = D('Users')->find($user_id);
			$Users['id'] = $Users['user_id'];//兼容小程序
			return $Users;
		}else{
			D('Connect')->where(array('connect_id'=>$Connect['connect_id']))->save($data);
			$Users = D('Users')->find($Connect['uid']);
			$Users['id'] = $Users['user_id'];//兼容小程序
			return $Users;
			
		}
		return true;
	}
	

    //修改用户信息
    public function  updUser(){

        if(IS_POST){
            $data['rd_session'] = $this->_param('rd_session');
            $data['nickname'] =$res['nickname'] =  $this->_param('nickname');
            $data['headimgurl'] =$res['face'] =  $this->_param('headimgurl');
            if(empty($data['nickname'])||empty($data['headimgurl'])||empty($data['rd_session']))
            exit(json_encode(array('status'=>-1,'msg'=>'要求的参数不能为空，请检查所传参数','data'=>'')));


            $user = $this->checkLogin($data['rd_session']);

            //更新数据库
            $r = D('Connect')->where('connect_id='.$user['connect_id'])->save($data);
            D('Users')->where('user_id='.$user['uid'])->save($res);

            $json_arr = array('status'=>1,'msg'=>'更新用户信息成功','data'=>$data);         
            $json_str = json_encode($json_arr); 
            exit($json_str);

        }else{
            exit(json_encode(array('status'=>-1,'msg'=>'请使用POST请求方式','data'=>'')));
        }
      }
      //修改授课状态
    public function UserUpdateClassStatus(){
	    $order_id=I('post.order_id');
	    $user_id=I('post.user_id');
	    $shop_id=I('post.shop_id');
	    $class_status=I('post.class_status');
	    $class_id=I('post.class_id');
	    $insert_data=array(
	        'order_id'=>$order_id,
	        'user_id'=>$user_id,
	        'shop_id'=>$shop_id,
	        'type'=>2,
	        'create_time'=>date('Y-m-d H:i:s',time())
        );
	    $Model=M();
        $Model->startTrans();
        $res1=M('edu_order')->where(['order_id'=>$order_id])->save(['class_status'=>$class_status]);
	    if($class_status==2){
            $class_id=M('edu_class_record')->add($insert_data);
            $res3=M('edu_order')->where(['order_id'=>$order_id])->save(['class_id'=>$class_id]);
            if($res1 && $class_id && $res3){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'申请成功']);
            }
        }elseif($class_status==3 && $class_id>0){
            $res2=M('edu_class_record')->where(array('id'=>$class_id))->save(['start_time'=>time()]);
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'确认成功']);
            }
        }elseif($class_status==4 && $class_id>0){
            $end_time=time();
            $class_record=M('edu_class_record')->where(array('id'=>$class_id))->find();
                if($class_record['start_time']>0){
                    $minutes=ceil(($end_time-$class_record['start_time'])/60);
                }
                $update=[
                    'end_time'=>$end_time,
                    'minutes'=>$minutes,
                ];
            $res2=M('edu_class_record')
                ->where(array('id'=>$class_id))
                ->save($update);
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'结束成功']);
            }
        }elseif($class_status==5 && $class_id>0){
            $res2=M('edu_class_record')->where(['id'=>$class_id])->delete();
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'取消授课成功']);
            }
        }
        $Model->rollback();
	    echoJson(['code'=>0,'msg'=>'操作失败']);
    }

    //修改授课状态
    public function ShopUpdateClassStatus(){
        $order_id=I('post.order_id');
        $user_id=I('post.user_id');
        $shop_id=I('post.shop_id');
        $class_status=I('post.class_status');
        $class_id=I('post.class_id');
        $insert_data=array(
            'order_id'=>$order_id,
            'user_id'=>$user_id,
            'shop_id'=>$shop_id,
            'type'=>1,
            'create_time'=>date('Y-m-d H:i:s',time())
        );
        $Model=M();
        $Model->startTrans();
        $res1=M('edu_order')->where(['order_id'=>$order_id])->save(['class_status'=>$class_status]);
        if($class_status==1){
            $class_id=M('edu_class_record')->add($insert_data);
            $res3=M('edu_order')->where(['order_id'=>$order_id])->save(['class_id'=>$class_id]);
            if($res1 && $class_id && $res3){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'发布成功']);
            }
        }elseif($class_status==3 && $class_id>0){
            $res2=M('edu_class_record')->where(array('id'=>$class_id))->save(['start_time'=>time()]);
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'确认成功']);
            }
        }elseif($class_status==4 && $class_id>0){
            $end_time=time();
            $class_record=M('edu_class_record')->where(array('id'=>$class_id))->find();
            if($class_record['start_time']>0){
                $minutes=ceil(($end_time-$class_record['start_time'])/60);
            }
            $update=[
                'end_time'=>$end_time,
                'minutes'=>$minutes,
            ];
            $res2=M('edu_class_record')
                ->where(array('id'=>$class_id))
                ->save($update);
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'结束成功']);
            }
        }elseif($class_status==5 && $class_id>0){
            $res2=M('edu_class_record')->where(['id'=>$class_id])->delete();
            if($res1 && $res2){
                $Model->commit();
                echoJson(['code'=>200,'msg'=>'取消授课成功']);
            }
        }
        $Model->rollback();
        echoJson(['code'=>0,'msg'=>'操作失败']);
    }

   }
    