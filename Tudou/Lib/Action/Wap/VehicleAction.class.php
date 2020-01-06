<?php       
class VehicleAction extends CommonAction{
     protected $lifecate = array();
     private $authenticati=array('end_time','user_id','handphoto','positivephoto','recommend','backphoto','businessphoto','personname','persontel','address','type','deposit','management');

    public function _initialize(){

        parent::_initialize();

        if(empty($this->_CONFIG['operation']['life'])){
            $this->error('分类信息功能已关闭');die;
        }

        //判断卖房
        $this->lifecate = M('LifeCate')->select();
        $this->lifechannel = M('LifeClass')->where(array('channel_id'=>2))->select();
        $this->assign('lifecate', $this->lifecate);
        $this->assign('channel', $this->lifechannel);
        //判断类型为房的
        $this->cates = M('LifeClass')->where(array('channel_id'=>2))->select();
        $this->assign('cates', $this->cates);
        //判断类型为1的
        $this->cate_name=M('LifeClass')->where(array('channel_id'=>2,'cate_id'=>$this->_param('cat')))->getField('cate_name');
        $this->assign('cate_name', $this->cate_name);
        $life_code = session('life_code');

    }
    //卖车
    public function index(){
   
        $map = $linkArr = array();
        $linkArr = array('channel' => $channel, 'area' => 0, 's1' => 0, 's2' => 0, 's3' => 0, 's4' => 0, 's5' => 0);
        
        if($channel = (int) $this->_param('channel')){
            $cates_ids = array();
            foreach($this->lifecate as $val){
                if($val['channel_id'] == $channel) {
                    $cates_ids[] = $val['cate_id'];
                }
            }
            if(!empty($cates_ids)){
                $map['cate_id'] = array('IN', $cates_ids);
            }
            $this->assign('cates_ids', $cates_ids);
            $this->assign('channel_id', $channel);
            $linkArr['channel'] = $channel;
        }
        
        if($cate_id = (int) $this->_param('cat')){
             $cate=M('LifeClass')->where(array('channel_id'=>2,'cate_id'=>$cate_id))->find();
            if($cate){
                $map['cate_id'] = $cate_id;
                $this->seodatas['cate_name'] = $cate['cate_name'];
                $this->assign('cat', $cate_id);
                $linkArr['cat'] = $cate_id;
                $this->assign('cate', $cate);
                $attrs = D('Lifeclassattr')->getAttrs($cate_id);
                for($i = 1; $i <= 5; $i++){
                    if (!empty($cate['select' . $i])){
                        $s[$i] = (int) $this->_param('s' . $i);
                        if($attrs['select' . $i][$s[$i]]) {
                            $map['select' . $i] = $s[$i];
                            $this->assign('s' . $i, $s[$i]);
                            $linkArr['s' . $i] = $s[$i];
                        }
                    }
                }
                $this->assign('attrs', $attrs);
            }
        }
        

        $attrs = D('Lifeclassattr')->getAttrs($cat);
        for($i = 1; $i <= 5; $i++){
            if(!empty($cate['select' . $i])){
                $s[$i] = (int) $this->_param('s' . $i);
                if($attrs['select' . $i][$s[$i]]){
                    $map['select' . $i] = $s[$i];
                    $this->assign('s' . $i, $s[$i]);
                    $linkArr['s' . $i] = $s[$i];
                }
            }
        }
        $area = (int) $this->_param('area');
        if(!empty($area)){
            $linkArr['area'] = $area;
            $this->assign('area', $area);
        }
        
        $tag_id = (int) $this->_param('tag_id');
        if(!empty($tag_id)){
            $linkArr['tag_id'] = $tag_id;
            $this->assign('tag_id', $tag_id);
        }
        
        
        $order = (int) $this->_param('order');
        if (!empty($order)) {
            $linkArr['order'] = $order;
            $this->assign('order', $order);
        }
        
        
        $this->assign('linkArr', $linkArr);
        $linkArr['p'] = '0000';
        $this->assign('nextpage', U('vehicle/load', $linkArr));
        
        $this->assign('list',$channel);
        $this->display();
    }

    //新版一级分类惰性加载
    public function load(){
        $Life = D('Lifessell');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'city_id' => $this->city_id, 'closed' => 0);
        
        $areas = D('Area')->fetchAll();
        if($area_id = (int) $this->_param('area')){
            $map['area_id'] = $area_id;
            $this->assign('area', $area_id);
            $this->seodatas['area_name'] = $areas[$area_id]['area_name'];
            $linkArr['area'] = $area_id;
        }
        $chl = D('Lifetype')->select();
        if($channel = (int) $this->_param('channel')){
            $cates_ids = array();
            foreach($this->cates as $val){
                if($val['channel_id'] == $channel) {
                    $cates_ids[] = $val['cate_id'];
                }
            }
            if(!empty($cates_ids)){
                $map['cate_id'] = array('IN', $cates_ids);
            }
            $this->assign('channel', $channel);
            $linkArr['channel'] = $channel;
            $this->seodatas['channel_name'] = $chl[$channel];
        }

        //条件筛选
        if($s1 = (int) $this->_param('s1')){
            $screen=M('LifeClassAttr')->where(array('attr_id'=>$s1))->getField('attr_id');
           
            $map['select1']=$screen;
        }
        if($s2 = (int) $this->_param('s2')){
            $screen=M('LifeClassAttr')->where(array('attr_id'=>$s2))->getField('attr_id');
            
            $map['select2']=$screen;
        }
        if($s3 = (int) $this->_param('s3')){
            $screen=M('LifeClassAttr')->where(array('attr_id'=>$s3))->getField('attr_id');
            
            $map['select3']=$screen;
        }
        if($s4 = (int) $this->_param('s4')){
            $screen=M('LifeClassAttr')->where(array('attr_id'=>$s4))->getField('attr_id');
           
            $map['select4']=$screen;
        }
        if($s5 = (int) $this->_param('s5')){
            $screen=M('LifeClassAttr')->where(array('attr_id'=>$s5))->getField('attr_id');
           
            $map['select5']=$screen;
        }
        if($cate_id = (int) $this->_param('cat')){
                $map['cate_id'] = $cate_id;
                $this->seodatas['cate_name'] = $cate['cate_name'];
                $this->assign('cat', $cate_id);
                $linkArr['cat'] = $cate_id;
                $this->assign('cate', $cate);
                $this->assign('channel', $cate['channel_id']);
                $attrs = D('Lifeclassattr')->getAttrs($cate_id);
                for($i = 1; $i <= 5; $i++) {
                    if(!empty($cate['select' . $i])){
                        $s[$i] = (int) $this->_param('s' . $i);
                        if($attrs['select' . $i][$s[$i]]){
                            $map['select' . $i] = $s[$i];
                            $this->assign('s' . $i, $s[$i]);
                            $linkArr['s' . $i] = $s[$i];
                        }
                    }
                }
                $this->assign('attrs', $attrs);
        }
        
        
        if($business_id = (int) $this->_param('business')){
            $map['business_id'] = $business_id;
            $this->assign('business', $business_id);
            $this->seodatas['business_name'] = $this->bizs[$business_id]['business_name'];
            $linkArr['business'] = $business_id;
        }
        
        $order = (int) $this->_param('order');
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        switch($order){
            case 3:
                $orderby = array('create_time' => 'desc');
                break;
            case 2:
                $orderby = array('views' => 'desc');
                break;
            default:
                $orderby = array('top_date' => 'desc', 'last_time' => 'desc','create_time' => 'desc');
                break;
        }
        
        
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        
        $tag_id = (int) $this->_param('tag_id');
        
        $list = $Life->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = array();
        
        foreach($list as $key => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $list[$key]['packet'] = $Life->getLifePacket($val['life_id']);
            $list[$key]['pics'] = $this->getListPics($val['life_id']);
            $Lifedetails = D('Lifesdetails')->find($val['life_id']);
            $list[$key]['details'] = $Lifedetails['details'];
            $list[$key]['channel_name'] = $this->getListChannel($val['cate_id']);
            $tags = explode(',',$val['tag']);
            $list[$key]['tags'] = D('Lifeclasstag')->where(array('tag_id'=>array('IN',$tags)))->select();
            
             if(!empty($tag_id)){
                if(!in_array($tag_id,$tags)){
                    unset($list[$key]);
                }
            }
            
        }
                
        $this->assign('users',D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

        public function loaddata(){
        $Life = D('Lifessell');
        import('ORG.Util.Page');
        $map = array('city_id' => $this->city_id, 'audit' => 1, 'closed' => 0);
        $cat = (int) $this->_param('cat');
        $cate = $this->lifecate[$cat];
        if (empty($cate)) {
            $this->error('请选择分类');
        }
        $linkArr = array('cat' => $cat, 'area' => 0, 's1' => 0, 's2' => 0, 's3' => 0, 's4' => 0, 's5' => 0);
        $this->assign('cate', $cate);
        $map['cate_id'] = $cat;
        $attrs = D('Lifecateattr')->getAttrs($cat);
        for($i = 1; $i <= 5; $i++){
            if(!empty($cate['select' . $i])){
                $s[$i] = (int) $this->_param('s' . $i);
                if($attrs['select' . $i][$s[$i]]){
                    $map['select' . $i] = $s[$i];
                    $this->assign('s' . $i, $s[$i]);
                    $linkArr['s' . $i] = $map['select' . $i] = $s[$i];
                }
            }
        }
        $area = (int) $this->_param('area');
        if(!empty($area)){
            $map['area_id'] = $area;
            $this->assign('area', $area);
        }
        $count = $Life->where($map)->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Life->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = array();
        foreach($list as $key => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $list[$key]['packet'] = $Life->getLifePacket($val['life_id']);
            $list[$key]['pics'] = $this->getListPics($val['life_id']);
            $list[$key]['details'] = $Lifedetails['details'];
            $list[$key]['channel_name'] = $this->getListChannel($val['cate_id']);
            $tags = explode(',',$val['tag']);
            $list[$key]['tags'] = D('LifeCateTag')->where(array('tag_id'=>array('IN',$tags)))->select();
            
             if(!empty($tag_id)){
                if(!in_array($tag_id,$tags)){
                    unset($list[$key]);
                }
            }
        }
        $this->assign('users',D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('attrs', $attrs);
        $this->assign('linkArr', $linkArr);
        $this->display();
    }
    
        //获取频道
    public function getListChannel($cate_id){
        $Lifecate = D('Llifeclass')->find($cate_id);
        return $this->lifechannel[$Lifecate['channel_id']];
    }
    

    //获取列表图片开始
    public function getListPics($life_id){
        $detail = D('Lifessell')->find($life_id);
        $Lifephoto = D('Lifesphoto')->getPic($life_id);
        if($detail['photo']){
            $arr = array();
            $arr['pic_id'] = '0';
            $arr['life_id'] = $life_id;
            $arr['photo'] = $detail['photo'];
            array_unshift($Lifephoto, $arr);
            $Lifephoto = $Lifephoto;
        }else{
            $Lifephoto = $Lifephoto;
        }
        return $Lifephoto;
    }

    //发布分类信息
    public function release(){
        header("Location:" . U('vehicle/fabu'));
        die;
    }

    //公司认证
    public function recognize(){
        $users_id=$this->uid;

            $config = D('Setting')->fetchAll();
            $this->assign('money',$config['site']['vehicle_money']);
            $this->assign('moneys',$config['site']['vehicle_moneys']);
            $detail = D('Lifesvehicle')->where(array('user_id'=>$users_id))->find();
            //var_dump($detail);die;
            $this->assign('detail',$detail);
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->assign('types',D('Lifetype')->select());

        $this->display();
    }


    public function renz(){
        if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
        $code = $this->_post('code', 'htmlspecialchars');
        $data = $this->checkFields($this->_post('data', false), $this->authenticati);
        $data['user_id'] = $this->uid;
        $data['businessphoto'] = htmlspecialchars($data['businessphoto']);
        if(empty($data['businessphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传营业执照'));
        }
        if(!isImage($data['businessphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'营业执照格式不正确'));
        }
        $data['handphoto'] = htmlspecialchars($data['handphoto']);
        if(empty($data['handphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传法人手持证件照'));
        }

        if(!isImage($data['handphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'法人手持证件照格式不正确'));
        }

        $data['positivephoto'] = htmlspecialchars($data['positivephoto']);
        if(empty($data['positivephoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传法人身份证正面照'));
        }

        if(!isImage($data['positivephoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'法人身份证正面照格式不正确'));
        }

        $data['backphoto'] = htmlspecialchars($data['backphoto']);
        if(empty($data['backphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请上传法人身份证背面照'));
        }

        if(!isImage($data['backphoto'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'法人身份证背面照格式不正确'));
        }

        $data['recommend']=(int) $data['recommend'];
        if(!empty($data['recommend'])){
            if(false == D('Users')->where(['user_id'=>$data['recommend']])->find()){
                $this->ajaxReturn(array('code'=>'0','msg'=>'该推荐人不存在'));
            }
        }
        $data['personname']=htmlspecialchars($data['personname']);
        if(empty($data['personname'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'法人姓名为空'));
        }

        $data['persontel']=htmlspecialchars($data['persontel']);
        if(empty($data['persontel'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'法人联系电话为空'));
        }

        $data['address']=htmlspecialchars($data['address']);
        if(empty($data['address'])){
            $this->ajaxReturn(array('code'=>'0','msg'=>'联系地址为空'));
        }

        $config = $config = D('Setting')->fetchAll();
        $jieshu=$config['site']['cend_time'];
        if(!empty($jieshu) || $jieshu!=0){
            $now = date('Y-m-d H:i:s',time());
            $data['end_time']=date("Y-m-d",strtotime("+".$jieshu."years",strtotime($now)));
        }

        $data['closed'] = 0;
        $data['type']=2;
        $data['times']=NOW_TIME;
        $data['deposit'] = htmlspecialchars($data['deposit']);
        $data['management'] = htmlspecialchars($data['management']);

        $code = $this->_post('code', 'htmlspecialchars');
     
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->ajaxReturn(array('code'=>'0','msg'=>'该支付方式不存在'));
        }
        $logs = array(
            'user_id' => $this->uid,
            'type' => 'vehicle',
            'code' => $code,
            'psy' => 0,
            'need_pay' => $data['deposit']+ $data['management'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip(),
            'deposit'=>$data['deposit']
        );
        if(M('lifes_vehicle')->add($data)){
            $log_id = D('Paymentlogs')->add($logs);
            $this->ajaxReturn(array('code' => '1', 'msg' => '正在跳转到支付页面', 'url' => U('vehicle/psy', array('log_id' => $log_id))));
        }
    }

    //公司认证支付方式
    public function psy($log_id){
        if(empty($this -> uid)) {
            header("Location:" . U('passport/login'));
            die ;
        }
        $log_id = (int)$log_id;
        $logs = D('Paymentlogs') -> find($log_id);
        if(empty($logs) || $logs['user_id'] != $this ->uid || $logs['is_paid'] == 1) {
            $this->error('没有有效的支付记录');
            die ;
        }
        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('types', D('Payment')->getTypes());
        $this->assign('logs', $logs);
        $this->assign('paytype', D('Payment')->getPayments());
        $this->assign('paytypes',$paytype =  D('PaymentLogs')->getcode($logs['code']));
        $this->display();
    }

    //已购买返利卷的预约
    public function yuyue(){
       
         if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }
    
         $data=array();
        $data=session('fanli');
       $data['time']=$this->_post('time','htmlspecialchars');
       $data['life_id']=$this->_post('life_id','htmlspecialchars');
       $exp=D('Lifereserve')->where(['user_id'=>$this->uid,'life_id'=>$data['life_id'],'type'=>2,'is_pay'=>1,'close'=>0])->find();
       if(!empty($exp)){
           $this->tuMsg('您已预约过该商家，请前往个人中心预约列表查看',U('vehicle/details',array('life_id'=>$data['life_id'])));
       }
       if(empty($data['time'])){
            $this->tuMsg('预约时间为空');
       }
       $data['tel'] = $this->_post('tel','htmlspecialchars');
       if(empty($data['tel'])){
           $this->tuMsg('联系方式不能为空');
       }
       $data['sell_user_id']=$data['user_id'];
       $data['user_id']=$this->uid;

       $data['is_pay']=1;
       $data['type']=2;
       $obj = D('Lifereserve');
        if ($obj->add($data)){
              $this->tuMsg('预约成功！请前往个人中心，预约信息列表查看',U('vehicle/index/2'));
            }else{
                $this->tuMsg('操作失败，请重新操作！');
            }
      
    }

    //购买返利卷
    public function pay(){
        $users=$this->uid;
         if(empty($this->uid)){
            header("Location:" . U('passport/login'));
            die;
        }

       $data=array();
       $code = $this->_post('code', 'htmlspecialchars');
       $datas['fanlijuan']=$this->_post('fanlijuan','htmlspecialchars');
       //var_dump($datas['fanlijuan']);die;
       $sess=session('fanli');
       $data['time']=$this->_post('time','htmlspecialchars');
       if(empty($data['time'])){
            $this->error('预约时间为空');
       }
        $data['tel']=$this->_post('tel','htmlspecialchars');
       if(empty($data['tel'])){
           $this->error('联系方式不能为空');
       }
       //var_dump($data['time']);die;
       $data['sell_user_id']=$sess['user_id'];
       $data['user_id']=$this->uid;
       $data['life_id']=$sess['life_id'];
       $data['type']=2;
        // var_dump($data);
       // var_dump($data['user_id']) ;var_dump($data);die;
        $payment = D('Payment')->checkPayment($code);
        if (empty($payment)) {
            $this->error('该支付方式不存在');
        }
         $logs = array(
            'user_id' => $this->uid,
            'type' => 'profit',
            'code' => $code,
            'order_id' => $order_id,
            'psy' => 0,
            'need_pay' =>$datas['fanlijuan'],
            'create_time' => NOW_TIME,
            'create_ip' => get_client_ip()
        );
        $logs['log_id'] = D('Paymentlogs')->add($logs);
        $data['log_id'] = $logs['log_id'];

         if($logs['log_id']){
            $obj = D('Lifereserve');
            $data['log_id'] = $logs['log_id'];
            if ($obj->add($data)){
            }else{
                D('Paymentlogs')->delete($logs['log_id']);
                $this->error('请重新输入');
            }
        }

        $this->assign('button', D('Payment')->getCode($logs));
        $this->assign('logs', $logs);
        $this->display();
    }


    //信息详细列表
   public function detail($life_id){

        if(empty($life_id)){
            $this->error('参数错误');
        }
        if(!($detail = D('Lifessell')->find($life_id))){
            $this->error('该消息不存在或者已经删除');
        }
        if($detail['audit'] != 1){
            $this->error('该消息不存在或者已经删除');
        }
        if($detail['closed'] != 0){
            $this->error('该消息不存在或者已经删除');
        }
        $cate = $this->lifecate[$detail['cate_id']];
        if(empty($cate)){
            $this->error('该信息不能正常显示');
        }
        D('Lifessell')->updateCount($life_id, 'views');
        $this->assign('cate', $cate);
        $this->assign('city_id', $this->city_id);
        
        //测算距离开始
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $detail['d'] = getDistance($lat,$lng,$detail['lat'],$detail['lng']);
        //测算距离结束
        session('fanli',$detail);

        $this->assign('detail', $detail);
        $this->assign('ex', D('Lifesdetails')->find($life_id));
        $this->assign('attrs', $attrs = D('Lifeclassattr')->getAttrs($detail['cate_id']));
        
        $ex = D('Lifesdetails')->find($detail['life_id']);
        $chl = D('Llifeclass')->getChannelM();
        
        
        
        $this->assign('pics', D('Lifesphoto')->getPic($detail['life_id']));
        // $this->assign('buy',$buy = D('Lifesbuy')->where(array('life_id'=>$life_id,'user_id'=>$this->uid))->find());
        //调用图片
        
        $tag_ids = explode(',', $detail['tag']);
        $this->assign('tags',$tags =  D('Lifeclasstag')->order(array('orderby' => 'asc'))->where(array('tag_id' =>array('IN',$tag_ids)))->select());
        
        //输出主分类到模板
        $cates =  D('Llifeclass')->find($cate_id);
        $this->assign('channel_id',$cates['channel_id']);
            
        $this->assign('scroll', $scroll = D('Lifessell')->getScroll());//首页循环播放
        //购买返利卷
        $this->assign('payment', D('Payment')->getPayments(true));

        $config = D('Setting')->fetchAll();
        $this->assign('fanlijuan',$config['site']['fanlijuan']);
        $this->display();
    
}

    //已购买返利卷信息详细列表
   public function details($life_id){

        if(empty($life_id)){
            $this->error('参数错误');
        }
        if(!($detail = D('Lifessell')->find($life_id))){
            $this->error('该消息不存在或者已经删除');
        }
        if($detail['audit'] != 1){
            $this->error('该消息不存在或者已经删除');
        }
        if($detail['closed'] != 0){
            $this->error('该消息不存在或者已经删除');
        }
        $cate = $this->lifecate[$detail['cate_id']];
        if(empty($cate)){
            $this->error('该信息不能正常显示');
        }
        D('Lifessell')->updateCount($life_id, 'views');
        $this->assign('cate', $cate);
        $this->assign('city_id', $this->city_id);
        
        //测算距离开始
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $detail['d'] = getDistance($lat,$lng,$detail['lat'],$detail['lng']);
        //测算距离结束
        session('fanli',$detail);

        $this->assign('detail', $detail);
        $this->assign('ex', D('Lifesdetails')->find($life_id));
        $this->assign('attrs', $attrs = D('Lifeclassattr')->getAttrs($detail['cate_id']));
        
        $ex = D('Lifesdetails')->find($detail['life_id']);
        $chl = D('Llifeclass')->getChannelM();
        
        
        
        $this->assign('pics', D('Lifesphoto')->getPic($detail['life_id']));
        // $this->assign('buy',$buy = D('Lifesbuy')->where(array('life_id'=>$life_id,'user_id'=>$this->uid))->find());
        //调用图片
        
        $tag_ids = explode(',', $detail['tag']);
        $this->assign('tags',$tags =  D('Lifeclasstag')->order(array('orderby' => 'asc'))->where(array('tag_id' =>array('IN',$tag_ids)))->select());
        
        //输出主分类到模板
        $cates =  D('Llifeclass')->find($cate_id);
        $this->assign('channel_id',$cates['channel_id']);
            
        $this->assign('scroll', $scroll = D('Lifessell')->getScroll());//首页循环播放
        //购买返利卷
        $this->assign('payment', D('Payment')->getPayments(true));

        $config = D('Setting')->fetchAll();
        $this->assign('fanlijuan',$config['site']['fanlijuan']);
        $this->display();
    
}
    //发布信息
    public function fabu(){
         if(empty($this->uid)){
            $this->error('请登录', U('passport/login'));
        }

        $users =D('Users')->where(['user_id'=>$this->uid])->find();
        if($users['is_aux'] !=1){
            $this->error('未实名认证，请实名认证后再次尝试发布信息！',U('user/usersaux/index'));
        }
        
        //判断类型等于二手、车辆、房屋、培训、招聘、服务、兼职
            $users_life=D('Lifesvehicle')->where(array('user_id'=>$this->uid))->find();
            $room=D('Lifesauthentication')->where(array('user_id'=>$this->uid))->find();

            if(!empty($room['user_id'])){
               $this->error('该账号已进行卖房公司认证了，如有需要请换一个账号进行卖车公司认证');

             }
            if($users_life['examine']!=1){
               $this->error('未通过公司认证，请认证过后再次发布',U('vehicle/recognize'));
            }

            if($users_life['close']==1){
                 $this->error('该账号已被冻结！');
            }

        
        if($this->isPost()){
         
            $data = $data = $this->checkFields($this->_post('data', false),array('title','city_id','cate_id','area_id','business_id','text1','text2','text3','text4','text5','num1','num2','select1','select2','select3','select4','select5','tag','contact','mobile','qq','addr','money','lng','lat','urgent_num','urgent_switch_num','top_num','top_switch_num'));
            
            $details = $this->_post('details', 'SecurityEditorHtml');
            if(empty($details)){
                $this->ajaxReturn(array('code'=>'0','msg'=>'说点什么吧'));
            }
            if(strlen($details) > 500){
                $this->ajaxReturn(array('code'=>'0','msg'=>'您输入的文字太多了'));
            }
            if($words = D('Sensitive')->checkWords($details)){
                $this->ajaxReturn(array('code'=>'0','msg'=>'商家介绍含有敏感词：' . $words));
            }
            $data['cate_id'] = (int) $data['cate_id'];
            if(empty($data['cate_id'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'分类必须选择'));
            }
            
            if(!$res = D('Lifecate')->find($data['cate_id'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'分类不存在'));
            }
            
            $data['title'] = tu_msubstr($details,0,30,false);//标题截取信息内容前半部分
            if(empty($data['title'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'标题不能为空'));
            }
            $data['city_id'] = (int) $data['city_id'];
            if(empty($data['city_id'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'城市不能为空'));
            }
            $data['area_id'] = (int) $data['area_id'];
            $data['business_id'] = (int) $data['business_id'];
            
            $data['lng'] = htmlspecialchars(trim($data['lng']));
            $data['lat'] = htmlspecialchars(trim($data['lat']));
            $data['text1'] = htmlspecialchars($data['text1']);
            $data['text2'] = htmlspecialchars($data['text2']);
            $data['text3'] = htmlspecialchars($data['text3']);
            $data['text4'] = htmlspecialchars($data['text4']);
            $data['text5'] = htmlspecialchars($data['text5']);
            $data['num1'] = (int) $data['num1'];
            $data['num2'] = (int) $data['num2'];
            $data['select1'] = (int) $data['select1'];
            $data['select2'] = (int) $data['select2'];
            $data['select3'] = (int) $data['select3'];
            $data['select4'] = (int) $data['select4'];
            $data['select5'] = (int) $data['select5'];
        
            
            $tag = $this->_post('tag', false);
            $tag = implode(',', $tag);
            $data['tag'] = $tag;
            
        
        
            //加急信息  
            $urgent_switch_num = I('urgent_switch_num','','trim');//加急开关    

            if($urgent_switch_num == 1){
                $urgent_num = I('urgent_num','','trim');//加急天数
                
                $urgent_peice = ($urgent_num * $this->_CONFIG['life']['urgent']);//加急费用
            }
            //置顶信息
            $top_switch_num = I('top_switch_num','','trim');//置顶开关
            if($top_switch_num == 1){
                $top_num = I('top_num','','trim');//置顶天数
                $top_peice = ($top_num * $this->_CONFIG['life']['top']);//置顶费用
            }   
            
                
            $data['urgent_date'] = date('Y-m-d', NOW_TIME + $urgent_num * 86400);//最后的加急天数
            $data['top_date'] = date('Y-m-d', NOW_TIME + $top_num * 86400);//最后的置顶天数
            
            $money = $res['price'] + $top_peice + $urgent_peice;//客户应该付款总费用
            
            $data['contact'] = htmlspecialchars($data['contact']);
            if(empty($data['contact'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'联系人不能为空'));
            }
            $data['mobile'] = htmlspecialchars($data['mobile']);
            if(empty($data['mobile'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'电话不能为空'));
                $this->tuMsg('电话不能为空');
            }
            if(!isMobile($data['mobile']) && !isPhone($data['mobile'])) {
                $this->ajaxReturn(array('code'=>'0','msg'=>'电话格式不正确'));
            }
            $data['qq'] = htmlspecialchars($data['qq']);
            $data['addr'] = htmlspecialchars($data['addr']);
            $data['views'] = (int) $data['views'];
            $data['money'] = $money;
            $data['audit'] = $this->_CONFIG['site']['fabu_life_audit'];
            $data['create_time'] = NOW_TIME;
            $data['last_time'] = NOW_TIME + 86400 * 30;
            $data['create_ip'] = get_client_ip();
            
            if($Shop = D('Shop')->where(array('user_id' => $this->uid,'closed' => 0,'audit' => 1))->find()){
                $data['is_shop'] = 1;
            }
            $data['user_id'] = $this->uid;
        
            if($life_id = D('Lifessell')->add($data)){
                
                //传图
                $photo = $this->_post('photo', false);
                if($photo){
                    D('Lifessell')->where(array('life_id'=>$life_id))->save(array('photo'=>$photo['0']));
                }
                
                $photos = array_splice($photo,1,9); 
                
                
                $arr = '';
                if($photos){
                    D('Lifesphoto')->upload($life_id, $photos);//更新更多详情图
                    foreach($photos as $val){
                        if($val != ''){
                            $arr .= '<img src='. config_img($val) .'>';
                        }
                    }
                }
            
                $data['details'] = $details .'<br/>'. $arr;//信息加上图片
                
                //插入详情
                if($data['details']){
                    D('Lifesdetails')->updateDetailss($life_id, $data['details']);
                }
                if($data['lng']=='' || $data['lat']==''){
                    $this->ajaxReturn(array('code'=>'0','msg'=>'请选择地址'));
                }
                // print_r($data);die;
                
                //去付款，这里的费用已经计算了
                if($money > 0){
                    //如果用户有余额
                    if($this->member['money'] >= $money){
                        D('Users')->addMoney($this->uid,-$money,'发布分类信息ID【'.$life_id.'】扣除余额');
                        D('Lifessell')->save(array('life_id'=>$life_id,'price'=>$money,'status'=>1));//改变订单状态
                        $this->ajaxReturn(array('code'=>'1','msg'=>'发布信息成功扣费【'.round($money,2).'】元','url'=>U('wap/vehicle/index')));
                    }else{
                        $code = IS_WEIXIN ? 'weixin' : 'alipay'; //智能选择支付方式
                        $logs = array(
                            'type'=>'life',
                            'user_id'=>$this->uid,
                            'order_id'=>$life_id,
                            'code' =>$code,
                            'need_pay'=>$money,
                            'create_time'=>NOW_TIME,
                            'create_ip'=>get_client_ip(),
                            'is_paid' =>0
                        );
                        $log_id = D('Paymentlogs')->add($logs);
                        $this->ajaxReturn(array('code'=>'1','msg'=>'正在跳转到支付页面','url'=>U('payment/payment', array('log_id'=>$log_id))));  
                    }
                }else{
                    //后台没配置分类需要扣费
                    $this->ajaxReturn(array('code'=>'1','msg'=>'发布信息成功，通过审核后将会显示','url'=>U('wap/vehicle/index'))); 
                }
            }
            $this->ajaxReturn(array('code'=>'0','msg'=>'发布信息失败'));
        }else{
            
            //获取地址
            $this->assign('addr', $addr = D('Useraddr')->where(array('is_default' =>'1','user_id'=>$this->uid,'closed'=>'0'))->find());
            if($addr['lat'] && $addr['lng']){
                $lat = $addr['lat'];
                $lng = $addr['lng'];
            }else{
                $lat = cookie('lat');
                $lng = cookie('lng');
                if(empty($lat) || empty($lng)){
                    $lat = $this->_CONFIG['site']['lat'];
                    $lng = $this->_CONFIG['site']['lng'];
                }   
            }
            $this->assign('lng', $lng);
            $this->assign('lat', $lat);
            
            
            $this->assign('cates', D('Llifeclass')->fetchAll());
            $type= D('Lifetype')->select();

            $this->assign('channelmeans',$type);
            
            //获取ID信息
            $cate_id = I('cate_id',0,'trim,intval');
            $channel_id = I('channel_id',0,'trim,intval');
            $this->assign('cat',$cat = D('Llifeclass')->where(array('channel_id'=>$channel_id))->select());
            $this->assign('cate_id',$cate_id);
            $this->assign('channel_id',$channel_id);

        $this->display();
    }
}

  //前台获取商圈
    public function channels($channel_id = '0'){
        if(!$channel_id = (int)$channel_id){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请选择频道'));
        }
        $datas = D('Llifeclass')->order(array('orderby' => 'asc'))->where(array('channel_id' => $channel_id))->select();
        $str = '<option value="0">请选择子分类</option>';
        foreach($datas as $val){
            if($val['channel_id'] == $channel_id){
                $str .= '<option value="' . $val['cate_id'] . '">' . $val['cate_name'] . '</option>';
            }
        }
        echo $str;
        die;
    }

    //根据分类调用数据
    public function ajax($cate_id,$life_id=0){

        if(!$cate_id = (int)$cate_id){
            $this->error('请选择正确的分类');
        }
        if(!$detail = D('Llifeclass')->where(array('cate_id'=>$cate_id))->find()){
            $this->error('请选择正确的分类');
        }
        //var_dump($detail);die;
        $this->assign('cate',$detail);
        $att=D('Lifeclassattr')->order(array('orderby'=>'asc'))->where(array('cate_id'=>$cate_id))->select();
        $this->assign('attrs',$att);
        //var_dump($att);die;
        $this->assign('tags', D('Lifeclasstag')->order(array('orderby' => 'asc'))->where(array('cate_id' => $cate_id))->select());
        if($life_id){
            $this->assign('detail',D('Lifessell')->find($life_id));
            $this->assign('maps',D('Lifeclassattr')->getAttrs($life_id));
            $this->assign('tag', D('Lifeclasstag')->getTag($life_id));
        }
        $this->display();
    }

        //根据分类获取价格
    public function getAttrPrice($cate_id){
        if(!$cate_id = (int)$cate_id){
            $this->ajaxReturn(array('code'=>'0','msg'=>'ID不存在'));
        }
        if(!$detail = D('Llifeclass')->find($cate_id)){
            $this->ajaxReturn(array('code'=>'0','msg'=>'请选择正确的分类'));
        }
        $this->ajaxReturn(array('code'=>'1','msg'=>'当前分类发布价'.round($detail['price'],2).'元','price'=>$detail['price']));
    }

    //gps导航
    public function gps($life_id,$type = '0'){
        $type = (int) $this->_param('type');
        $detail = D('Lifessell')->find($life_id);
        $this->assign('detail', $detail);
        $this->assign('type', $type);
        $this->assign('amap', $amap= $this->bd_decrypt($shop['lng'],$shop['lat']));
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

    //新版举报
    public function report($life_id){
        if(empty($this->uid)){
            $this->error('请先登陆后操作', U('Wap/passport/login'));
        }
        if(!($detail = D('Lifessell')->find($life_id))){
            $this->error('该信息不存在');
        }
        if(D('Lifereport')->check($life_id, $this->uid)){
            $this->error('您已经举报过了');
        }
        if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false),array('life_id','photo','type','content','shop_id'));
             $shop=session('fanli');
             $data['shop_id']=$shop['user_id'];
            $data['city_id'] = $this->city_id;
            $data['life_id'] = $life_id;
            $data['user_id'] = $this->uid;
            $data['photo'] = htmlspecialchars($data['photo']);
            if(!empty($data['photo']) && !isImage($data['photo'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'缩略图格式不正确'));
            }
            $data['type'] = (int) $data['type'];
            if(empty($data['type'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'类型不能为空'));
            }
            $data['content'] = SecurityEditorHtml($data['content']);
            if(empty($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'详细内容不能为空'));
            }
            if($words = D('Sensitive')->checkWords($data['content'])){
                $this->ajaxReturn(array('code'=>'0','msg'=>'详细内容含有敏感词：' . $words));
            }
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            if(false !== D('Lifereport')->add($data)) {
               $this->ajaxReturn(array('code'=>'1','msg'=>'举报成功','url'=>U('vehicle/index')));  
            }
            $this->ajaxReturn(array('code'=>'0','msg'=>'操作失败'));
        }else{
           $this->assign('detail', $detail);
           $this->display();
        }
    }

    //查找
     public function search(){
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $cat = (int) $this->_param('cat');
        $this->assign('cat', $cat);
        $order = (int) $this->_param('order');
        $this->assign('order', $order);
        $area_id = (int) $this->_param('area_id');
        $this->assign('area_id', $area_id);
        $this->assign('nextpage', LinkTo('life/searchload', array('keyword' => $keyword, 'cat' => $cat, 'order' => $order, 't' => NOW_TIME, 'p' => '0000')));
        $this->display();
    }
    //加载查找
    public function searchload(){
        $keyword = $this->_param('keyword');
        if ($keyword) {
            $map['qq|mobile|contact|title|num1|num2'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        $Life = D('Lifessell');
        import('ORG.Util.Page');
        $count = $Life->where(array('audit' => 1, 'city_id' => $this->city_id, 'closed' => 0))->count();
        $Page = new Page($count, 25);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $Life->where($map)->order(array('top_date' => 'desc', 'last_time' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


}