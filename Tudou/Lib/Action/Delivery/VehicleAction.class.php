<?php
class VehicleAction extends CommonvehicleAction{

public function _initialize() {
    //var_dump($this->uid);die;
        parent::_initialize();
        $Delivery = D('Userspinche')->where(array('user_id'=>$this->uid)) -> find();
        $this->assign('delivery', $Delivery);
        $config = D('Setting')->fetchAll();
        $this->assign('config',$config);
    }

    //登录判断s
    public function vehicleindexs(){
        if(empty($this->uid)){
            redirect('delivery/passport/vehiclelogin');
        }else{
            redirect("https://" . $_SERVER['HTTP_HOST'] . "/delivery/vehicle/vehicleindex.html");
        }
    }

     //抢单app
    public function vehicleindex(){

        //$id=$this->id;


         $deliver = D('Userspinche')->where(array('user_id'=>$this->uid))->find();
 
       $delivery_id=$deliver['user_id'];
        
        // var_dump($deliver);die;
         $this->assign('DELIVERY',$deliver);
        
         $this->assign('user_face',$user_face = D('Users') -> where(array('user_id'=>$deliver['user_id'])) -> find());//查询用户头像

        // //未配送订单
         $do = D('Runningvehicle');

         $today = strtotime(date('Y-m-d'));
         $today_p = $do-> where('update_time >='.$today.' and cid ='.$delivery_id.' and status =2'.' and closed =0') -> count();//今日配送
        // //var_dump($today_p);die;        
         $this->assign('today_p',$today_p);//今日完成

         $today_ok = $do-> where('update_time >='.$today.' and cid ='.$delivery_id.' and status =8'.' and closed =0') -> count();
         $this->assign('today_ok',$today_ok);//总计完成

         $all_ok = $do -> where('cid ='.$delivery_id.' and status =8'.' and closed =0') -> count();
         $this->assign('all_ok',$all_ok); //抢新单
        
        $new1 = $do-> where('status =1 and cid =0'.' and closed =0') ->count();
        
         $this->assign('new',$new1);//网站的新单子
            
         $ing = $do-> where('status = 2 and cid ='.$delivery_id.' and closed =0') -> count();
         //var_dump($delivery_id);die;
         $this->assign('ing',$ing);  //已完成
         $ed = $do-> where('status = 8 and cid ='.$delivery_id.' and closed =0') -> count();
         $this->assign('ed',$ed);
         $this->assign('msg', $msg = (int) D('Msg')->where(array('cate_id' => 5, 'views' => 0, 'running_id' => $this->delivery_id))->count());

        $this->display();
       
    }
    

   //跑腿众包开始
	public function index( ){
        $aready = (int) $this->_param('aready');
        $cid = $this->delivery_id;
        $this->assign('cid',$cid);
        $this->assign('aready', $aready);
        $this->assign('id',$id);
        $this->assign('nextpage', LinkTo('vehicle/loaddata', array('aready' => $aready, 't' => NOW_TIME,'p' => '0000')));
        $this->display(); 
	
	
	}
	public function loaddata( ){
//        if (empty($this->delivery_id)){
//
//            header('Location:'.U('vehicle/vehicleindex'));
//        }
        $cid = $this->delivery_id; 
        $obj=D('Userspinche')->where(array('id'=>$cid))->find();
        $running = D('Runningvehicle');
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


        $config = D('Vehicle')->select();

        foreach ( $list as $k => $val ){
          $list[$k]['d'] = getdistance( $lat, $lng, $val['lat'], $val['lng'] );
          $list[$k]['freight']-=$config[0]['vehiclechajia'];
          $list[$k]['need_pay']-=$config[0]['vehiclechajia'];
        }
        //var_dump($list);
        $this->assign('list', $list);
		$this->assign('page', $show); 
        $this->display( );
    }
	
	
	//跑腿众包结束
	public function state($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Runningvehicle")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Userspinche')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign("detail", $detail);
        $this->display();
    }
	
	public function detail($running_id){
        $running_id = (int) $running_id;
        if (empty($running_id) || !($detail = D("Runningvehicle")->find($running_id))) {
            $this->error("该跑腿不存在");
        }
		$thumb = unserialize($detail['thumb']);
        $this->assign('thumb', $thumb);
		$this->assign('deliverys', D('Userspinche')->where(array('user_id'=>$detail['cid']))->find());
        $this->assign("detail", $detail);
        $this->display();
    }

   

	
   //强跑腿开始
	public function qiang(){
        if ( IS_AJAX ){
            $running_id = i('running_id', 0,'trim,intval');
            $running = D('Runningvehicle');
            $open=M('users_pinche')->where(['user_id'=>$this->delivery_id])->find();
            $config = D('Setting')->fetchAll();
            $dan=$config['running']['jiedan_num'];
            $today=strtotime(date('Y-m-d 00:00:00'));
            $datas['cid']=$this->delivery_id;
            $datas['update_time'] = array('egt',$today);
            $sum=$running->where($datas)->count();
            if($open['shenghe']!=1){
                if($sum>=$dan){
                    $this->ajaxReturn(array('status' =>'error','message' =>'您未上传车身广告，接单数量有限!!!请上传！！！' ));
                }
            }
            if($open['is_open']==0){
                $this->ajaxReturn(array('status' =>'error','message' =>'您已关闭接单状态!' ));
            }
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
				if($running->Running_Confirm_Qiangs($running_id,$cid)){
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

            $running = D('Runningvehicle');
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
				if ($running->Running_Confirm_Completes($running_id,$cid)){
					$this->ajaxReturn( array('status' =>'success','message' =>'恭喜您完成订单' ) );
				}else{
                    $this->ajaxReturn( array('status' =>'error','message' =>'操作失败' ) );
                }
               
            }
        }
    }
	//跑腿确认结束


    /**
     *
     *提现
     * 
     */
    public function cash(){
        $start =date('Y-m-d', mktime(0,0,0,date('m')-1,$t,date('Y')));
        $end =date('Y-m-d', mktime(0,0,0,date('m')-1,1,date('Y')));
        $Users = D('Users');

        if(!$detail = $Users->find($this->uid)){
            $this->error('会员信息不存在');
        }elseif($detail['is_lock'] == 1){
            $this->error('您的账户已被锁，暂时无法提现');
        }
        
        $Connect = D('Connect')->where(array('uid' => $this->uid,'type'=>weixin,))->find();

        
        if(IS_POST){
            $vehiclemoney = abs((float) ($_POST['vehiclemoney']));
            if($vehiclemoney == 0) {
                $this->tuMsg('提现金额不合法');
            }
            if($vehiclemoney > $detail['vehiclemoney'] || $detail['vehiclemoney'] == 0) {
                $this->tuMsg('余额不足，无法提现');
            }
            
            
            if(!($code = $this->_post('code'))){
                $this->tuMsg('请选择提现方式');
            }
            
            if($code == 'bank'){
                if(!($data['bank_name'] = htmlspecialchars($_POST['bank_name'])) || $data['bank_name'] =='请输入开户银行'){
                    $this->tuMsg('开户行不能为空');
                }
                if(!($data['bank_num'] = htmlspecialchars($_POST['bank_num']))){
                    $this->tuMsg('银行账号不能为空');
                }
                if(!is_numeric($data['bank_num'])){
                    $this->tuMsg('银行账号只能为数字');
                }
                if(strlen($data['bank_num']) < 15){
                    $this->tuMsg('银行账号格式不正确');
                }
                if(!($data['bank_realname'] = htmlspecialchars($_POST['bank_realname']) || $data['bank_realname'] =='输入开户名' || $data['bank_realname'] ==1)){
                    $this->tuMsg('开户姓名不能为空');
                }
                $data['bank_branch'] = htmlspecialchars($_POST['bank_branch']);
                
            }

            if(empty($Connect['open_id'])){
                if($code == weixin){
                    $this->tuMsg('您非微信登录，暂时不能选择微信提现方式');
                }
            }
            
            if($code == weixin){
                 if (!($data['re_user_name'] = htmlspecialchars($_POST['re_user_name'])) || $_POST['re_user_name'] =='') {
                    $this->tuMsg('请填写真实姓名');
                }
            } 
            // print_r($_POST);die;
            if (!($data['re_user_name'] = htmlspecialchars($_POST['re_user_name'])) || $_POST['re_user_name'] =='') {
                    $this->tuMsg('请填写真实姓名');
                }
            
            if($code == 'alipay'){
                if(!($data['alipay_account'] = htmlspecialchars($_POST['alipay_account']))){
                    $this->tuMsg('支付宝账户');
                }
                if(!($data['alipay_real_name'] = htmlspecialchars($_POST['alipay_real_name']))){
                    $this->tuMsg('支付宝真实姓名不能为空');
                }
            }
            
            $data['user_id'] = $this->uid;
       
            $arr = array();
            $arr['user_id'] = $this->uid;
            $arr['apply_money'] = $vehiclemoney; //申请提现的金额 @author pingdan
            $arr['vehiclemoney'] = $vehiclemoney;
            //$arr['commission'] = $commission;
            $arr['type'] = user;
            $arr['addtime'] = NOW_TIME;
            $arr['account'] = $detail['account'];
            $arr['bank_name'] = $data['bank_name'];
            $arr['bank_num'] = $data['bank_num'];
            $arr['bank_realname'] = $data['bank_realname'];
            $arr['bank_branch'] = $data['bank_branch'];
            $arr['alipay_account'] = $data['alipay_account'];
            $arr['alipay_real_name'] = $data['alipay_real_name'];
            $arr['re_user_name'] = $_POST['re_user_name'];
            $arr['code'] = $code;
          
            if($cash_id = D('Vehiclecash')->add($arr)){
            //  if($Users->addMoney($detail['user_id'], -$money,$intro)){
                    D('Usersex')->save($data);
                    D('Weixintmpl')->weixin_cash_user($this->uid,1);//申请提现：1会员申请，2商家同意，3商家拒绝
                    $this->tuMsg('申请成功，请等待管理员审核', U('vehicle/cashlog'));
            //  }else{
            //      $this->tuMsg('抱歉，提现扣余额失败');
            //  }
            }else{
                $this->tuMsg('抱歉，提现操作失败');
            }   
           
        }
        $this->assign('connect', $Connect);
        $this->assign('vehiclemoney', $detail['vehiclemoney']);
        $this->assign('detail', $detail);
        $this->assign('info', D('Usersex')->getUserex($this->uid));
        $this->display();
    }

    //检测银行卡
        public function getbankinfo(){
            $card = I('card');
            if(!$card){
               $this->ajaxReturn(array('code' => '0', 'msg' => '请输入银行卡号'));
            }
            if(!is_numeric($card)){
                $this->ajaxReturn(array('code' => '0', 'msg' => '银行账号只能为数字'));
            }
            if(strlen($card) < 15) {
                $this->ajaxReturn(array('code' => '0', 'msg' => '银行账号格式不正确'));
            }
            $res = D('BankList')->getBankInfo($card);
            if($res){
                $this->ajaxReturn(array('code' => '1', 'msg' => $res));
            }else{
                $this->ajaxReturn(array('code' => '0', 'msg' => '操作错误'));
            }
        }
    
    //提现日志
    public function cashlog(){

        $this->display();
    }

    //日志显示
    public function cashlogloaddata(){
        $Userscash = D('Vehiclecash');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'type' => user);
        $count = $Userscash->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Userscash->where($map)->order(array('cash_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //评价
    public function pjlist($user_id){
        $obj=D('Userspinche')->where(array('user_id'=>$user_id))->find();
        //var_dump($obj);die; 
       $psy=D('Vehicledianping');
      //$detail = D('Delivery')->find($id);
      
      $list = $psy->where(array('che_id'=>$obj['id']))->order('create_time desc')->select();

        $this->assign('detail',$detail= D('Userspinche')->where(array('user_id'=>$user_id))->find());
        $this->assign('list',$list);
        $this->display();
    }
    
   


    //投诉
    public function tslist($user_id){

    $obj=D('Userspinche')->where(array('user_id'=>$user_id))->find();
        $psy=D('Vehiclets');
      //$detail = D('Delivery')->find($id);
      
      $list = $psy->where(array('che_id'=>$obj['id'],'status'=>1))->order('time desc')->select();

        $this->assign('detail',$detail= D('Userspinche')->find($user_id));
        $this->assign('list',$list);

        $this->display();
    }


     //导航图
   public function gps($running_id, $type = '0', $gps_type = 'shop'){
        
        if (!is_numeric($running_id) || !in_array($type, ['0', '1', '2']) || !in_array($gps_type, ['shop', 'buyer'])) { 
            $this->error('该'. ($gps_type == 'shop' ? '商家' : '买家'). '信息有误');
        }
        
        
        if ($gps_type == 'buyer') { //如果买家, 则额外添加字段 shop_name
        //送达地址
        $shop = D('Runningvehicle')->where(array('running_id'=>I('running_id')))->find($running_id);
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lngs'],$shop['lats']));
        $user=D('Users')->find($shop['user_id']);
        $shop['shop_name']=$user['nickname'];
            $peitype=1;
        }else{
        //搬家地址
        $shop = D('Runningvehicle')->where(array('running_id'=>I('running_id')))->find(); 
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lng'],$shop['lat']));
         $shop['shop_name']='搬家地址';
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



    //配送app下载（司机）
    public function appxzs  (){
        $this->display();
    }


    //相册
    public function photo($type=0){
        if($this->ispost()){
            $left_photo=I('post.left_photo');
            $right_photo=I('post.right_photo');
            $back_photo=I('post.back_photo');
            if(empty($left_photo)){
                $this->tuMsg('请上传司机带车左侧图片');
            }
            if(!isImage($left_photo)){
                $this->tuMsg('司机带车左侧格式不正确');
            }
            if(empty($right_photo)){
                $this->tuMsg('请上传司机带车右侧图片');
            }
            if(!isImage($right_photo)){
                $this->tuMsg('司机带车右侧格式不正确');
            }
            if(empty($back_photo)){
                $this->tuMsg('请上传司机带车后面图片');
            }
            if(!isImage($back_photo)){
                $this->tuMsg('司机带车后面格式不正确');
            }

            if(false !== M('users_pinche')->where(['user_id'=>$this->delivery_id])->save(['left_photo'=>$left_photo,'right_photo'=>$right_photo,'back_photo'=>$back_photo])){
                M('users_pinche')->where(['user_id'=>$this->delivery_id])->save(['shenghe'=>3]);
                $this->tuMsg('上传成功，请等待审核',U('vehicle/vehicleindex'));
            }else{
                $this->tuMsg('上传失败，请稍后再试。。。。。');
            }

        }else{
            if($type==1){
                M('users_pinche')->where(['user_id'=>$this->delivery_id])->save(['shenghe'=>null]);
            }
            $open=M('users_pinche')->where(['user_id'=>$this->delivery_id])->find();
            $this->assign('open',$open);
            $this->display();
        }
    }


}