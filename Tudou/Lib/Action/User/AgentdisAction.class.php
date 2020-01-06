<?php
/*
* 代理分成控制器
* @resource
*/

 class AgentdisAction extends CommonAction{
	
	public function _initialize(){
        parent::_initialize();
    }

 
    public function index(){
        //判断用户是否登录
        if (empty($this->uid)) {
            header("Location: " . U('Wap/passport/login'));
            die;
        }
        //查找用户代理等级
        $cityagent = D('UsersAgentApply')->field('agent_id,level,end_time')->where(array('user_id'=>$this->uid))->find();
        if(!empty($cityagent['level'])&&$cityagent['level']==1){
            $obj = D('Citypromo');
        }else{
            $obj = D('Cityagent');
        }
        $this->assign('times',$cityagent);
        $cityagentname = $obj->field('agent_name')->where(array('agent_id'=>$cityagent['agent_id'],'level'=>$cityagent['level']))->find();

        $daili=D('UsersAgentApply')->where(['user_id'=>$this->uid])->find();
        $objs = D('Cityagent')->where(['agent_id'=>$daili['agent_id']])->find();
        $this->assign('dengji',$daili);
        $this->assign('name',$objs['agent_name']);
        //$profit_ok = D('Cityagentprofitlogs')->where(array('user_id' => $this->uid,'is_separate' =>1))->sum('money');
       
        //$profit_ok = D('Cityagentprofitlogs')->where(array('agent_id'=>$cityagent['agent_id'],'level'=>$cityagent['level'],'is_separate'=>0))->find();
        //$profit_ok = D('Cityagentfenclogs')->where(array('log_id'=>$profit_ok['log_id']))->sum('money');



        //print_r($profit_ok); die; 
         // $profit_cance = 
        //$profit_cancel = D('Cityagentprofitlogs')->where(array('user_id' => $this->uid,'is_separate' =>2))->sum('money');

        //print_r($profit_ok); die; 
        $this->assign('cityagentname', $cityagentname);
		$this->assign('profit_ok', $profit_ok);
		$this->assign('profit_cancel',$profit_cancel);
        $this->display();
    }

    public function profit(){
		$status = (int) $this->_param('status');
		$this->assign('status', $status);
		$this->assign('nextpage', LinkTo('agentdis/profitloaddata',array('status'=>$status,'t' => NOW_TIME, 'p' => '0000')));
        $this->mobile_title = '优惠买单';
		$this->display(); // 输出模板		
    }

	public function profitloaddata(){
        $status = (int) $this->_param('status');
        if (!in_array($status, array(0, 1, 2, 3))) {
            $status = 1;
        }
        $model = D('Userprofitlogs');
        import('ORG.Util.Page');
        $map = array('user_id' => $this->uid, 'is_separate' => $status);
        $count = $model->where($map)->count();
        $Page = new Page($count, 8);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
			die('0');
		}
        $orderby = array('log_id' => 'DESC');
        $list = $model->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$user_ids = $shop_ids = array();
        foreach ($list as $k => $val) {
            $user_ids[$val['user_id']] = $val['user_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
			$type_name= $model->get_money_type($val['type']);
            $list[$k]['type_name'] = $type_name;
        }
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('status', $status);
		$this->display();
	}
	
    public function subordinate(){
		$level = (int) $this->_param('level');
		$this->assign('level', $level);
		$this->assign('nextpage', LinkTo('distribution/subordinateloaddata',array('level'=>$level,'t' => NOW_TIME, 'p' => '0000')));
        $this->mobile_title = '优惠买单';
		$this->display(); // 输出模板		
    }
	
	public function subordinateloaddata(){
		$level = (int) $this->_param('level');
        if (!in_array($level, array(1, 2, 3))) {
            $level = 1;
        }
        $user = D('Users');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'fuid' . $level => $this->uid);
        $count = $user->where($map)->count();
        $Page = new Page($count, 8);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
		$p = $_GET[$var];
		if ($Page->totalPages < $p) {
			die('0');
		}
        $orderby = array('user_id' => 'DESC');
        $list = $user->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('level', $level);
        $this->display();
	}
    
    public function poster()
    {
        $token = 'fuid_' . $this->uid;
        $url = U('Wap/passport/register', array('fuid' => $this->uid));
        $file = tuQrCode($token, $url);
        $this->assign('file', $file);
        $this->display();
    }
    public function superior()
    {
        $user = D('Users');
        if ($this->member['fuid1']) {
            $fuser = $user->find($this->member['fuid1']);
        }
        $this->assign('fuser', $fuser);
        $this->display();
    }


    //我的下级订单
    public function goods($user_id){
        //当前ID
        $user_id = (int) $this->_param('user_id');
        //查询下级
        $child = D('UsersAgentApply')->field('user_id')->where(array('user_guide_id'=>$user_id))->select();
        if(count($child)< 1){
            $this->error('下级代理不存在！');
        }
        //获取下级ID
        foreach($child as $v){
            foreach($v as $m){
                $r[]=$m;
            }
            $child = $r;
        }
       
        $linkArr['user_id'] = $child;
        $is_top = (int) $this->_param('is_top');
        $linkArr['is_top'] = $is_top;
        
        $is_tuijian = (int) $this->_param('is_tuijian');
        $linkArr['is_tuijian'] = $is_tuijian;

        $this->assign('nextpage', LinkTo('agentdis/loaddata',$linkArr, array('p'=>'0000')));
        $this->assign('is_top', $is_top);
        $this->assign('is_tuijian', $is_tuijian);
        $this->assign('keyword', $keyword);
        $this->assign('users', $Users);
        $this->assign('user_id', $user_id);
        $this->display();
    } 

     //商品详情页
     public function loaddata(){
        $obj = D('SellerGoods');
        import('ORG.Util.Page');
        $map['closed'] = 0;
        $user_id = (int) $this->_param('user_id');
         
        $map['user_id'] = $user_id;


        
        $linkArr['user_id'] = $user_id;
        
        $is_top = (int) $this->_param('is_top');
        if($is_top){
            $map['is_top'] = $is_top;
            $linkArr['is_top'] = $is_top;
        }
        
        $is_tuijian = (int) $this->_param('is_tuijian');
        if($is_tuijian){
            $map['is_tuijian'] = $is_tuijian;
            $linkArr['is_tuijian'] = $is_tuijian;
        }
        
        $count = $obj->where($map)->count();
        //print_r($count);
        $Page = new Page($count, 10); 
        $show = $Page->show(); 
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $obj->where($map)->order(array('create_time' =>'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $val){
            if($val['type_id'] == 1){
                if($Goods = D('Goods')->find($val['id'])){
                   $val['goods'] = $Goods;
                   $list[$k] = $val;
                }
            }
            if($val['type_id'] == 2){
                if($Tuan = D('Tuan')->find($val['id'])){
                   $val['tuan'] = $Tuan;
                   $list[$k] = $val;
                }
            }if($val['type_id'] == 3){
                if($Shop = D('Shop')->find($val['id'])){
                   $val['shop'] = $Shop;
                   $list[$k] = $val;
                }
            }   
        }
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

}