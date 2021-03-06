<?php
class CommonAction extends Action{
    protected $uid = 0;
    protected $member = array();
    protected $seodatas = array();
    protected $_CONFIG = array();
    protected $citys = array();
    protected $city_id = 0;
    protected $city = array();
    protected function _initialize(){
        
        $this->_CONFIG = D('Setting')->fetchAll();
        $this->citys = D('City')->fetchAll();
        define('TU_DOMAIN', $this->_CONFIG['site']['hostdo']);
		define('IS_MOBILE', is_mobile());
		define('TU_HOST_PREFIX', $this->_CONFIG['site']['host_prefix']);
		
        //设置根域名
        $this->assign('citys', $this->citys);
        $this->city_id = cookie('city_id');
$this->_CONFIG['site']['https']=0;
header('Upgrade-Insecure-Requests: 0');
	    if($this->_CONFIG['site']['https'] == 1){
			define('__HOST__', 'https://' . $_SERVER['HTTP_HOST']);
		}else{
			define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
		}

		$http = ($this->_CONFIG['site']['https'] == 1) ? 'https' : 'http';
		if(IS_MOBILE){
			if($this->_CONFIG['site']['https'] == 1){
			   if($_SERVER['HTTPS'] == ''){
				   //header('Location: https://'.TU_HOST_PREFIX.'.'.TU_DOMAIN.$_SERVER['REQUEST_URI']);
				   //die;
			   }
			}else{
					
				if($_SERVER['HTTP_HOST'] == TU_DOMAIN || $_SERVER['HTTP_HOST'] != TU_HOST_PREFIX .'.'. TU_DOMAIN){
				   // header('Location: http://'.TU_HOST_PREFIX.'.'.TU_DOMAIN.$_SERVER['REQUEST_URI']);
				   // die;
			   }
			}
		}
		
		
		
		
        if(empty($this->city_id)){
            import('ORG/Net/IpLocation');
            $IpLocation = new IpLocation('UTFWry.dat');// 实例化类 参数表示IP地址库文件
            $result = $IpLocation->getlocation($_SERVER['REMOTE_ADDR']);
            foreach ($this->citys as $val){
                if(strstr($result['country'], $val['name'])){
                    $city = $val;
                    $this->city_id = $val['city_id'];
                    break;
                }
            }
            if(empty($city)){
                $this->city_id = $this->_CONFIG['site']['city_id'];
                $city = $this->citys[$this->_CONFIG['site']['city_id']];
            }
        }else{
            $city = $this->citys[$this->city_id];
        }
		
		
        $this->city = $city;
        define('IN_MOBILE', true);
        $ctl = strtolower(MODULE_NAME);
        $act = strtolower(ACTION_NAME);
        $is_weixin = is_weixin();
        $is_weixin = $is_weixin == false ? false : true;
        define('IS_WEIXIN', $is_weixin);
        searchWordFrom();
        $this->uid = getUid();
        if(!empty($this->uid)){
            $member = $MEMBER = $this->member = D('Users')->find($this->uid);//客户端缓存会员数据
            $member['password'] = '';
            $member['token'] = '';
            cookie('member', $member, 86000);//cookie保存时间，建议后台设置，暂时这样修改
        }
        if($ctl == 'member'){
            if (empty($this->uid)) {
                header('Location: ' . U('passport/login'));
                die;
            }
        }
		
		
        //三级分销开始
        $fuid = (int) $this->_param('fuid');
        if(!empty($fuid)){
            $profit_expire = (int) $this->_CONFIG['profit']['profit_expire'];
            if($profit_expire){
                cookie('fuid', $fuid, $profit_expire * 60 * 60);
            }else{
                cookie('fuid', $fuid);
            }
        }
		$controller = $this->_param('controller');
        $action = $this->_param('action');
		
		
		if($fuid && $controller && $action){
			D('WeixinShare')->update($fuid,$this->uid,$controller,$action);
		}
		
		
		
        $this->_CONFIG = D('Setting')->fetchAll();
        define('__HOST__', $this->_CONFIG['site']['host']);
        if (!empty($city['name'])) {
            $this->_CONFIG['site']['cityname'] = $city['name'];
        }
		
		//统计PC导航
		if ($nav_id = (int) $this->_param('nav_id')) {
            D('Navigation')->navigation_click($nav_id);
		}
	
		
        
		//新版微信自动登录，非常有用
		
		if($is_weixin){
			if (empty($this->uid)){
				if ($this->_CONFIG['weixin']['user_auto'] == 1){
					if($is_weixin && !empty($this->_CONFIG['weixin']['appid'])){
						if(!$this->uid && $act != 'wxstart'){
							$state = md5(uniqid(rand(), TRUE));
							session('state', $state);
							if(!empty($_SERVER['REQUEST_URI'])){
								$backurl = $_SERVER['REQUEST_URI'];
							}else{
								$backurl = U('index/index');
							}
							session('backurl', $backurl);
							cookie('backurl', $backurl);
							$login_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->_CONFIG['weixin']['appid'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/wxstart')) . '&response_type=code&scope=snsapi_userinfo&state=' . $state . '#wechat_redirect';
							header("location:{$login_url}");
							echo $login_url;
							die;
						}
					}
				}
			}
		}
	
		
        $local = D('Near')->GetLocation();
        $this->assign('local', $local);
        $this->assign('CONFIG', $this->_CONFIG);
        $this->assign('MEMBER', $this->member);
		
 
        $this->assign('today', TODAY);//兼容模版的其他写法
        $this->assign('nowtime', NOW_TIME);
        $this->assign('ctl', strtolower(MODULE_NAME));//主要方便调用
		$this->assign('host',__HOST__);
		
		
        $this->assign('act', ACTION_NAME);
        $this->assign('is_weixin', IS_WEIXIN);
        $this->assign('city_name', $city['name']);//您当前可能在的城市
        $this->assign('city_id', $this->city_id);
        $city_ids = array('0', $this->city_id);
        $city_ids = join(',', $city_ids);
        $this->assign('city_ids', $city_ids);
		

		$this->assign('lat',$lat = cookie('lat'));
		$this->assign('lng',$lat = cookie('lng'));
	    $this->assign('address',$lat = cookie('address'));
		$this->assign('index_mask_show',$index_mask_show = cookie('index_mask_show'));//首页广告cookie
		
        $goods = session('goods');
        $this->assign('cartnum', (int) array_sum($goods));
        $this->assign('footer', $footer = $this->_CONFIG['other']['footer']);
		$this->assign('color', $color = $this->_CONFIG['other']['color']);
		
        import("@/Net.Jssdk");
        $jssdk = new JSSDK($this->_CONFIG['weixin']["appid"],$this->_CONFIG['weixin']["appsecret"]);
        $signPackage = $jssdk->GetSignPackage();
        $this->signPackage = $signPackage;
		
		
		
		
		$this->assign('url',$_SERVER['REQUEST_URI']);
		$this->assign('nav_footer',$nav_footer = D('Navigation') ->where(array('status' => 1,'closed'=>0))->order(array('orderby' => 'asc'))->limit(0,6)->select());
		
		
		
		//检测是否有店铺
        $this->assign('is_shop', $is_shop = D('Shop')->find(array('where' => array('user_id' => $this->uid))));
        if ($this->_CONFIG['site']['web_close'] == 0) {
            $this->display('public:web_close');
            die;
        }
	
    }
    private function seo(){
        $this->assign('mobile_title', $this->mobile_title);
        $this->assign('mobile_keywords', $this->mobile_keywords);
        $this->assign('mobile_description', $this->mobile_description);
    }
	
	
    protected function check_mobile(){
        $u = D('Users');
        $m = $u->where('user_id =' . $this->uid)->getField('mobile');
        if($m == null || !isMobile($m)){
            $mobile_open = 0;
        }else{
            $mobile_open = 1;
        }
        $this->assign('mobile_open', $mobile_open);
    }


	//绑定手机
    protected function mobile(){
        if($this->isPost()){
			if(!$this->uid){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请先登录后再操作'));
            }
            $mobile = $this->_post('mobile');
            $yzm = $this->_post('yzm');
            if(empty($mobile) || empty($yzm)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请填写正确的手机及手机收到的验证码'));
            }
            $s_mobile = session('mobile');
            $s_code = session('code');
            if($mobile != $s_mobile){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '手机号码和收取验证码的手机号不一致'));
            }
            if($yzm != $s_code){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '验证码不正确'));
            }
            $data = array('user_id' => $this->uid,'mobile' => $mobile);
            if(D('Users')->save($data)){
                D('Users')->integral($this->uid,'mobile');
                D('Users')->prestige($this->uid,'mobile');
                $this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜您通过手机认证'));
            }
            $this->ajaxReturn(array('status' => 'error', 'msg' => '更新失败'));
        }else{
            $this->display();
        }
    }
	
    //删除分类信息获取验证码
    protected function life_yzm(){
		if(!$this->uid){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请先登录后再操作'));
        }
        if(!($mobile = $this->_post('mobile'))){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请输入手机号码'));
        }
        if(!isMobile($mobile)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '手机号码格式不正确'));
        }
        session('mobile', $mobile);
		$randstring = session('life_code');
		if(!empty($randstring)){
			session('life_code',null);
		}
        $randstring = rand_string(4,1);
        session('life_code', $randstring);
        D('Sms')->sms_yzm($mobile, $randstring);//发送短信
        $this->ajaxReturn(array('status' => 'success', 'msg' => '短信发送成功，请留意收到的手机短信', 'code' => session('code')));
    }
	
	
    protected function mobile2(){
        if($this->isPost()){
			if(!$this->uid){
				$this->ajaxReturn(array('status' => 'error', 'msg' => '请先登录后再操作'));
			}
            $mobile = $this->_post('mobile');
            $yzm = $this->_post('yzm');
            if(empty($mobile) || empty($yzm)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请填写正确的手机及手机收到的验证码'));
            }
            $s_mobile = session('mobile');
            $s_code = session('code');
            if($mobile != $s_mobile){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '手机号码和收取验证码的手机号不一致'));
            }
            if($yzm != $s_code){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '验证码不正确'));
            }
            $data = array('user_id' => $this->uid, 'mobile' => $mobile);
            if(D('Users')->save($data)){
                $this->ajaxReturn(array('status' => 'success', 'msg' => '恭喜您成功更换绑定手机号'));
            }
            $this->ajaxReturn(array('status' => 'error', 'msg' => '更新手机号失败'));
        }
    }
	
	
	
    protected function sendsms(){
        if(!($mobile = $this->_post('mobile'))){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请输入正确的手机号码'));
        }
        if(!isMobile($mobile)){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '手机号码格式不正确'));
        }
        if($user = D('Users')->where(array('mobile' => $mobile))->find()) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '手机号码已经存在'));
        }
        session('mobile', $mobile);
		$randstring = session('code');
		if(!empty($randstring)){
			session('code',null);
		}
        $randstring = rand_string(4,1);
        session('code', $randstring);
	
        D('Sms')->sms_yzm($mobile, $randstring);//发送短信
        $this->ajaxReturn(array('status' => 'success', 'msg' => '短信发送成功，请留意收到的手机短信', 'code' => session('code')));
    }
	
	
	
    protected function msg(){
        $msgs = D('Msg')->where(array('user_id' => array('IN', array(0, $this->uid))))->limit(0, 20)->select();
        $this->assign('msgs', $msgs);
        $msg_ids = array();
        foreach ($msgs as $val) {
            $msg_ids[] = $val['msg_id'];
        }
        if (!empty($this->uid)) {
            $reads = D('Msgread')->where(array('user_id' => $this->uid, 'msg_id' => array('IN', $msg_ids)))->select();
            $messagenum = count($msgs) - count($reads);
            $messagenum = $messagenum > 9 ? 9 : $messagenum;
            $readids = array();
            foreach ($reads as $val) {
                $readids[$val['msg_id']] = $val['msg_id'];
            }
            $this->assign('readids', $readids);
            $this->assign('messagenum', $messagenum);
        } else {
            $this->assign('messagenum', 0);
        }
    }
    private function tmplToStr($str, $datas)
    {
        preg_match_all('/{(.*?)}/', $str, $arr);
        foreach ($arr[1] as $k => $val) {
            $v = isset($datas[$val]) ? $datas[$val] : '';
            $str = str_replace($arr[0][$k], $v, $str);
        }
        return $str;
    }
	
    public function show($templateFile = ''){
        $this->seo();

        parent::display($templateFile);
		
    }
	
    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = ''){
        $this->seo();
				ob_start(function($str){
			$str=str_replace('"/static/','"/static/',$str);
			return $str;});
        parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content = '', $prefix = '');
		ob_end_flush();
    }
	
    private function parseTemplate($template = ''){
        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);
        $theme = $this->getTemplateTheme();
        define('NOW_PATH', BASE_PATH . '/themes/' . $theme . 'Wap/');
        define('THEME_PATH', BASE_PATH . '/themes/default/Wap/');
        define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Wap/');
        if ('' == $template) {
            $template = strtolower(MODULE_NAME) . $depr . strtolower(ACTION_NAME);
        } elseif (false === strpos($template, '/')) {
            $template = strtolower(MODULE_NAME) . $depr . strtolower($template);
        }
        $file = NOW_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
        if (file_exists($file)) {
            return $file;
        }
        return THEME_PATH . $template . C('TMPL_TEMPLATE_SUFFIX');
    }
	
    private function getTemplateTheme(){
        define('THEME_NAME', 'default');
        if ($this->theme) {
            $theme = $this->theme;
        } else {
            $theme = D('Template')->getDefaultTheme();
            if (C('TMPL_DETECT_THEME')) {
                $t = C('VAR_TEMPLATE');
                if (isset($_GET[$t])) {
                    $theme = $_GET[$t];
                } elseif (cookie('think_template')) {
                    $theme = cookie('think_template');
                }
                if (!in_array($theme, explode(',', C('THEME_LIST')))) {
                    $theme = C('DEFAULT_THEME');
                }
                cookie('think_template', $theme, 864000);
            }
        }
        return $theme ? $theme . '/' : '';
    }
	
    protected function ajaxLogin(){
        $str = '<script>';
        $str .= 'parent.ajaxLogin();';
        $str .= '</script>';
        die($str);
    }
	
    protected function checkFields($data = array(), $fields = array()){
        foreach ($data as $k => $val) {
            if (!in_array($k, $fields)) {
                unset($data[$k]);
            }
        }
        return $data;
    }
    protected function ipToArea($_ip)
    {
        return IpToArea($_ip);
    }
	
    protected function tuMsg($message, $jumpUrl = '', $time = 3000){
        $str = '<script>';
        $str .= 'parent.boxmsg("' . $message . '","' . $jumpUrl . '","' . $time . '");';
        $str .= '</script>';
        die($str);
    }
	
    protected function tuErrorJump($message, $jumpUrl = '', $time = 3000){
        $str = '<script>';
        $str .= 'parent.error("' . $message . '",' . $time . ',\'jumpUrl("' . $jumpUrl . '")\');';
        $str .= '</script>';
        die($str);
    }
    protected function tuAlert($message, $url = '')
    {
        $str = '<script>';
        $str .= 'parent.alert("' . $message . '");';
        if (!empty($url)) {
            $str .= 'parent.location.href="' . $url . '";';
        }
        $str .= '</script>';
        die($str);
    }
    protected function tuLoginSuccess()
    {
        //异步登录
        $str = '<script>';
        $str .= 'parent.parent.LoginSuccess();';
        $str .= '</script>';
        die($str);
    }
    protected function tuError($message, $time = 2000, $yzm = false, $parent = true)
    {
        $parent = $parent ? "parent." : "";
        $str = "<script>";
        if ($yzm) {
            $str .= $parent . "error(\"" . $message . "\"," . $time . ",\"verify()\");";
        } else {
            $str .= $parent . "error(\"" . $message . "\"," . $time . ");";
        }
        $str .= "</script>";
        exit($str);
    }
    protected function tuSuccess($message, $jumpUrl = "", $time = 2000, $parent = true)
    {
        $parent = $parent ? "parent." : "";
        $str = "<script>";
        $str .= $parent . "success(\"" . $message . "\"," . $time . ",'jump(\"" . $jumpUrl . "\")');";
        $str .= "</script>";
        exit($str);
    }

    //红包
    public function hongbaos($uid,$order_id,$envelope_id){

        if(!empty($envelope_id)){

        $envelope=D('UserEnvelope')->where(array('user_envelope_id'=>$envelope_id,'user_id'=>$uid))->find();
        if(!empty($envelope['shop_id'])){
            D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
            $arr=array(
                'user_id'=>$uid,
                'envelope'=>$envelope['envelope'],
                'intro'=>'使用商家红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
            );

        }else{
            $ss =$envelope['num']-1;
            D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('num'=>$ss));
            $cha=D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->find();

            if($cha['num']==0){
                D('UserEnvelope')->where(array('user_id'=>$uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
            }
            $arr=array(
                'user_id'=>$uid,
                'envelope'=>$envelope['envelope'],
                'intro'=>'使用通用红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
            );
        }
        D('UserEnvelopeLogs')->add($arr);
        }
        return $arr;
    }












}