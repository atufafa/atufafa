<?php

class RunningAction extends CommonAction {
	
	public function _initialize() {
        parent::_initialize();
		$this->assign('areas', $areas = D('Area')->fetchAll());
		$this->assign('bizs', $biz = D('Business')->fetchAll());
		
    }
	
	//跑腿众包开始
	public function index( ){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->assign('nextpage', LinkTo('running/loaddata', array('aready' => $aready, 't' => NOW_TIME,'p' => '0000')));
        $this->display(); 
	
	
	}
	public function loaddata( ){
        if (empty($this->delivery_id)){
            header('Location:'.U('index/index'));
        }
        $cid = $this->delivery_id; 
        $running = D('Running');
		import('ORG.Util.Page'); 
        $map = array();
        if (isset($_GET['aready']) || isset($_POST['aready'])) {
			$aready = (int) $this->_param('aready');
			if ($aready == 1) {
				$map['status'] = $aready;
			}elseif($aready == 2 || $aready == 3){
                $map['status'] = $aready;
                $map['cid']=$cid;
            }
			$this->assign('aready', $aready);
		} else {
			$this->assign('aready', 999);
		}
			
		$count = $running->where($map)->count(); 
		$Page = new Page($count, 10); 
		$show = $Page->show(); 
	
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
			die('0');
		}
			
        $lat = addslashes( cookie('lat' ) );
        $lng = addslashes( cookie('lng' ) );
        if ( empty( $lat ) || empty( $lng ) ){
           $lat = $this->city['lat'];
           $lng = $this->city['lng'];
        }
        $orderby = "(ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc";
		
        $list = $running->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $config = D('Setting')->fetchAll();
        foreach ( $list as $k => $val ){
          $list[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
          $list[$k]['freight']-=$config['running']['chajia'];
          $list[$k]['need_pay']-=$config['running']['chajia'];
        }

    //var_dump($list);



        $this->assign('config',$config);
        $this->assign('user',D('Users')->select());
        $this->assign('list', $list);
		$this->assign('page', $show); 
        $this->display( );
    }
	
	
	//跑腿众包结束
	public function state($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Running")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Delivery')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign("detail", $detail);
        $this->display();
    }
	
	public function detail($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Running")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Delivery')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign("detail", $detail);
        $this->display();
    }
	
   //强跑腿开始
	public function qiang(){
        if ( IS_AJAX ){
            $running_id = i('running_id', 0,'trim,intval');
            $running = D('Running');
            if (empty($this->delivery_id)){
                $this->ajaxReturn(array('status' =>'error','message' =>'您还没有登录或登录超时!' ));
            }
            else{
                $detail = $running->find($running_id);
                if (!$detail){
                    $this->ajaxReturn(array('status' =>'error','message' =>'跑腿不存在!'));
                }
                if($detail['status'] != 1 || $detail['closed'] != 0){
                    $this->ajaxReturn( array('status' =>'error','message' =>'该跑腿状态不支持抢单!'));
                }
                $cid = $this->delivery_id; //获取配送员ID
				if($running->Running_Confirm_Qiang($running_id,$cid)){
					$this->ajaxReturn(array('status' =>'success','message' =>'恭喜您！接单成功！请尽快进行配送！'));
				}else{
                   $this->ajaxReturn(array('status' =>'error','message' =>'接单失败！错误！'));
                }
				
				
            }
        }
    }

	//跑腿确认
	public function running_ok(){
        if (IS_AJAX){
            $running_id = i('running_id', 0,'trim,intval');
            $running = D('Running');
            if (empty($this->delivery_id)){
                $this->ajaxReturn( array('status' =>'error','message' =>'您还没有登录或登录超时!' ));
            }
            else{
                $detail = $running->find($running_id);
                if (!$detail){
                    $this->ajaxReturn( array('status' =>'error','message' =>'跑腿不存在!' ));
                }
                if ($detail['status'] != 2 || $detail['closed'] != 0){
                    $this->ajaxReturn( array('status' =>'error','message' =>'该跑腿状态不能完成!' ));
                }
                $cid = $this->delivery_id; //获取配送员ID
                if ( $detail['cid'] != $cid ){
                    $this->ajaxReturn( array('status' =>'error','message' =>'不能操作别人的跑腿!' ));
                }
				if ($running->Running_Confirm_Complete($running_id,$cid)){
					$this->ajaxReturn( array('status' =>'success','message' =>'恭喜您完成订单' ) );
				}else{
                    $this->ajaxReturn( array('status' =>'error','message' =>'操作失败' ) );
                }
               
            }
        }
    }
	//跑腿确认结束
    //导航图
   public function gps($running_id, $type = '0', $gps_type = 'shop'){
        
        if (!is_numeric($running_id) || !in_array($type, ['0', '1', '2']) || !in_array($gps_type, ['shop', 'buyer'])) { 
            $this->error('该'. ($gps_type == 'shop' ? '商家' : '买家'). '信息有误');
        }
        
        
        if ($gps_type == 'buyer') { //如果买家, 则额外添加字段 shop_name
        //送达地址
        $shop = D('Running')->where(array('running_id'=>I('running_id')))->find(); 
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lng'],$shop['lat']));
            $user=D('Users')->find($shop['user_id']);
            $shop['shop_name']=$user['nickname'];
            $peitype=1;
        }else{
        //购买地址
        $shop = D('Running')->where(array('running_id'=>I('running_id')))->find($running_id);
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lngs'],$shop['lats']));
            $shop['shop_name']='购买地址';
            $peitype=2;
        }
       $this->assign('pei',$peitype);
        $this->assign('dingwei',$gps_type);
        $this->assign('shop', $shop);
        $this->assign('running_id', $running_id);
        $this->assign('gps_type', $gps_type);
        $this->assign('type', $type);
        
        $this->display();
    } 

    //BD-09(百度) 坐标转换成  GCJ-02(火星，高德) 坐标
      //@param bd_lon 百度经度
      //@param bd_lat 百度纬度
      public function bd_decrypt($bd_lon,$bd_lat){
            $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
            $x = $bd_lon - 0.0065;
            $y = $bd_lat - 0.006;
            $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
            $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
            $data['gg_lon'] = $z * cos($theta);
            $data['gg_lat'] = $z * sin($theta);
            return $data;
        }

}