<?php
class EnvelopeAction extends CommonAction{
	
	public function _initialize(){
        parent::_initialize();
        $this->assign('types', D('Envelope')->getType());
		$this->assign('orderTypes', D('Envelope')->getOrderType());
        $config = D('Setting')->fetchAll();
        $this->assign('CONFIGN',$config);
    }
	

	
	
	public function index(){
        $obj = D('Envelope');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id);
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
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
		if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
		if(isset($_GET['closed']) || isset($_POST['closed'])){
            $closed = (int) $this->_param('closed');
            if($closed != 999){
                $map['closed'] = $closed;
            }
            $this->assign('closed', $closed);
        }else{
            $this->assign('closed', 999);
        }
		
		//创建红包类型
		if(isset($_GET['type']) || isset($_POST['type'])){
            $type = (int) $this->_param('type');
            if($type != 999){
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
		
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('envelope_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	public function logs(){
        $obj = D('EnvelopeLogs');
        import('ORG.Util.Page');
        $map = array('shop_id' => $this->shop_id);
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
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
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
		
		if($envelope_id = (int) $this->_param('envelope_id')){
            $map['envelope_id'] = $envelope_id;
            $this->assign('envelope_id', $envelope_id);
        }
		
		//创建红包类型
		if(isset($_GET['type']) || isset($_POST['type'])){
            $type = (int) $this->_param('type');
            if($type != 999){
                $map['type'] = $type;
            }
            $this->assign('type', $type);
        }else{
            $this->assign('type', 999);
        }
		
		//原始订单记录
		if(isset($_GET['orderType']) || isset($_POST['orderType'])){
            $orderType = (int) $this->_param('orderType');
            if($orderType != 999){
                $map['orderType'] = $orderType;
            }
            $this->assign('orderType', $orderType);
        }else{
            $this->assign('orderType', 999);
        }
		
        $count = $obj->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $list = $obj->where($map)->order(array('log_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $key => $val){
            $list[$key]['shop'] = M('Shop')->find($val['shop_id']);
			$list[$key]['envelope'] = M('Envelope')->find($val['envelope_id']);
			$list[$key]['user'] = M('Users')->find($val['user_id']);
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
	
		  
	
    public function create(){
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('shop_id','title','intro','prestore','single','ratio','bg_date', 'type','end_time','use_time'));
            //var_dump($data);die;
            $data['shop_id'] = $this->shop_id;
			if(empty($data['shop_id'])){
				$this->tuError('商家ID必须选择');
			}
			if(!$shop = M('Shop')->find($data['shop_id'])){
				$this->tuError('商家不存在');
			}
			//新增,判断商家是否购买了红包广告费
            $time=NOW_TIME;
            $hong=D('BuyenvelopesLogs')->where(array('shop_id'=>$data['shop_id'],'is_show'=>0))->find();
            if(empty($hong)){
                $this->tuError('当前未购买红包广告，请购买后再发红包');
            }
            if($time>$hong['end_time']){
                $this->tuError('当前未红包广告已过期，请购买后再发红包');
            }

           // $ele = M('Ele')->find($data['shop_id']);
           // if($ele['is_user_envelope'] ==0){
                //$this->tuError('暂未开启红包折扣,无法添加红包');
           // }
            //红包类型分为三种，每一种最多只能存在一个未关闭的红包
            $res = M('envelope')->where(array('shop_id'=>$data['shop_id'],'closed'=>'0', 'type' => $data['type']))->find();
            if($res){
                $types = array(1 => '普通红包', 2 => '订单红包', 3 => '引流红包');
                $this->tuError('当前商家有一个' . $types[$data['type']] . '红包【'.$res['title'].'】正在进行，暂时无法添加');
            }


			$data['title'] = htmlspecialchars($data['title']);
			if(empty($data['title'])){
				$this->tuError('红包标题不能为空');
			}
			$data['intro'] = htmlspecialchars($data['intro']);
			if(empty($data['intro'])){
				$this->tuError('红包说明不能为空');
			}
			$data['prestore'] = (float) ($data['prestore']);
			if(empty($data['prestore'])){
				$this->tuError('红包总额不能为空');
			}

			if($data['type']==2 && $data['prestore']<50){
                $this->tuError('拼团红包总金额最低50元');
            }
			$data['single'] = (float) ($data['single']);
			if($data['type']==3 && empty($data['single'])){
                    $this->tuError('单个红包金额不能为空');
            }
			if(!$user = M('Users')->find($shop['user_id'])){
				$this->tuError('该商家未绑定会员');
			}
			if($data['prestore'] > $user['money']){
				$this->tuError('当前商家绑定的会员余额小于您填写的红包总额');
			}

			$data['ratio'] = $data['ratio'];
			if(empty($data['ratio'])){
				$this->tuError('红包发放比例不能为空');
			}
			if($data['ratio'] >= 1000){
				$this->tuError('红包发放百分比例不正确，建议填写1-10');
			}
			$data['bg_date'] = htmlspecialchars($data['bg_date']);
			if(empty($data['bg_date'])){
				$this->tuError('开始时间不能为空');
			}
			 // print_r($data);die;

            //新增
            $data['use_time'] = htmlspecialchars($data['use_time']);
            if(empty($data['use_time'])){
                $this->tuError('红包作废时间不能为空');
            }
           
            $data['end_time'] = htmlspecialchars($data['end_time']);
            if(empty($data['end_time'])){
                $this->tuError('红包结束时间不能为空');
            }

            $data['status'] = 0;
            $data['is_shop'] = 1;
			$data['closed'] = 0;
			$data['create_time'] = NOW_TIME;
			$data['create_ip'] = get_client_ip();

            if($envelope_id = D('Envelope')->add($data)){
				D('Users')->addMoney($shop['user_id'],-$data['prestore'],'红包ID【'.$envelope_id.'】扣除预存资金');
                $this->tuSuccess('添加成功，扣除会员资金【'.round($data['prestore'],2).'】元', U('envelope/index'));
            }
           $this->tuError('操作失败');
        }else{
            $shop=D('Shop')->where(array('shop_id'=>$this->shop_id))->find();
            $this->display(); 
        }
    }

	
	//完成红包
    public function closed($envelope_id = 0){
		if(!($detial = M('Envelope')->find($envelope_id))){
           $this->tuError('红包不存在');
        }
		if($detial['closed'] != 0){
			$this->tuError('当前红包状态不正确');
		}
		if($detial['shop_id'] != $this->shop_id){
			$this->tuError('非法操作');
		}
		if(!($shop = M('Shop')->find($detial['shop_id']))){
           $this->tuError('商家不存在');
        }
		if(M('Envelope')->save(array('envelope_id' => $envelope_id,'closed' => 1))){
			if($detial['prestore'] > 0 && $detial['type'] == 2){
				D('Users')->addMoney($shop['user_id'],$detial['prestore'],'红包ID【'.$envelope_id.'】结束退还剩余资金');
			}
            if($detial['type']==2){
                $ele=D('Ele')->where(['shop_id'=>$detial['shop_id']])->find();
                $store=D('Store')->where(['shop_id'=>$detial['shop_id']])->find();
                $market=D('Market')->where(['shop_id'=>$detial['shop_id']])->find();
                $goods=D('Shop')->where(['shop_id'=>$detial['shop_id']])->find();
            if(!empty($ele)){
                D('Eleproduct')->where(['shop_id'=>$ele['shop_id']])->save(['is_tuan'=>0]);
            }elseif(!empty($store)){
                D('Storeproduct')->where(['shop_id'=>$store['shop_id']])->save(['is_tuan'=>0]);
            }elseif(!empty($market)){
                D('Marketproduct')->where(['shop_id'=>$market['shop_id']])->save(['is_tuan'=>0]);
            }elseif(!empty($goods)){
                D('Goods')->where(['shop_id'=>$goods['shop_id']])->save(['is_pintuan'=>0]);
            }

        }
			$this->tuSuccess('操作成功', U('envelope/index'));
		}else{
			$this->tuError('操作失败');
		}
    }

    //购买红包广告
    public function buy(){
	    $user=$this->uid;
	    $hongbao=D('Buyenvelopes');
     if($this->ispost()){
         $id=I('post.id_id');
         if($id==0){
             $this->tuError('请选择');
         }
         $row=$hongbao->where(array('id'=>$id))->find();
         $shop=D('Shop')->where(array('shop_id'=>$this->shop_id,'user_id'=>$user))->find();
         $us=D('Users')->where(array('user_id'=>$shop['user_id']))->find();
         if($us['gold']<$row['buy_money']){
             $this->tuError('当前商户资金不足，请充值后购买！');
         }
         $need_pay=$row['buy_money']*($row['discount']/100);
         $time=NOW_TIME;
         //判断当前红包广告费是否过期
         $lo=D('BuyenvelopesLogs')->where(array('shop_id'=>$this->shop_id))->find();
         if($lo['end_time']>$time){
             $this->tuError('当前购买的红包广告费还未过期！');
         }

         //判断如果是月
         if($row['identification']==1){
             $dan=date("Y-m-d H:i:s",$time);
             $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."months",strtotime($dan)));
             $times = strtotime($yue);
         //判断如果是年
         }elseif($row['identification']==2){
             $dan=date("Y-m-d H:i:s",$time);
             $yue=date("Y-m-d H:i:s",strtotime("+".$row['num']."years",strtotime($dan)));
             $times = strtotime($yue);
         }
         $logs=array(
          'shop_id'=>$this->shop_id,
          'id_id'=>$id,
          'time'=>$row['buy_time'],
          'money'=>$need_pay,
          'discount'=>$row['discount'],
          'shenhe'=>1,
          'create_time'=>NOW_TIME,
         'end_time'=>$times
        );

         if($chenggo = D('BuyenvelopesLogs')->add($logs)){
             D('Users')->addGold($user,-$need_pay,'购买红包广告【'.$id.'】扣除预存资金');
             $this->tuSuccess('添加成功，扣除商户资金【'.round($need_pay,2).'】元', U('envelope/buy'));
         }
         $this->tuError('操作失败');

    }else{
        $buy_hongbao=$hongbao->where(array('close'=>0))->select();
        $this->assign('hongbao',$buy_hongbao);
	    $this->display();
     }
    }

    //购买红包广告记录
   public function buylogs(){
       $obj = D('BuyenvelopesLogs');
       import('ORG.Util.Page');
       $map = array('shop_id' => $this->shop_id,'shop_close'=>0);
       $count = $obj->where($map)->count();
       $Page = new Page($count, 25);
       $show = $Page->show();
       $list = $obj->where($map)->order(array('end_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
       $this->assign('list', $list);
       $this->assign('page', $show);
       $this->display();
   }

   //删除红包记录
   public function delbuy($eid){
       $detial =D('BuyenvelopesLogs')->where(array('eid'=>$eid,'shop_id'=>$this->shop_id))->find();
       if(empty($detial)){
           $this->tuError('记录不存在');
       }
       $time= NOW_TIME;
       if($time<$detial['end_time']){
           $this->tuError('该使用记录还未到期');
       }

       if(D('BuyenvelopesLogs')->save(array('eid' => $eid,'shop_close' => 1))){
           $this->tuSuccess('操作成功', U('envelope/buylogs'));
       }else{
           $this->tuError('操作失败');
       }
   }



}
