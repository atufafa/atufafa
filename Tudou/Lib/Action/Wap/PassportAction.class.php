<?php
class PassportAction extends CommonAction{
    private $create_fields = array('account', 'password', 'nickname');
    public function register(){
        if ($this->isPost()) {
            if (isMobile(htmlspecialchars($_POST['account']))) {
                if (!($scode = trim($_POST['scode']))) {
                    $this->tuMsg('请输入短信验证码');
                    //echoJson(['code'=>-1,'msg'=>'请输入短信验证码','data'=>'']);
                }
                $scode2 = session('scode');
                if (empty($scode2)) {
                    $this->tuMsg('请获取短信验证码');
                    //echoJson(['code'=>-1,'msg'=>'请获取短信验证码','data'=>'']);
                }
                if ($scode != $scode2) {
                    $this->tuMsg('请输入正确的短信验证码');
                    //echoJson(['code'=>-1,'msg'=>'请输入正确的短信验证码','data'=>'']);
                }
            }
            $data = $this->createCheck();
            $invite_id = (int) cookie('invite_id');
            if (!empty($invite_id)) {
                $data['invite_id'] = $invite_id;
            }
			if (empty($data['password'])) {
                $this->tuMsg('请输入密码');
                //echoJson(['code'=>-1,'msg'=>'请输入密码','data'=>'']);
            }
            $wap_register_password2 = $this->_CONFIG['register']['wap_register_password2'];
            if ($wap_register_password2 == 1) {
                $password2 = $this->_post('password2');
                if ($password2 !== $data['password']) {
                    $this->tuMsg('两次密码不一致');
                    //echoJson(['code'=>-1,msg=>'两次密码不一致','data'=>'']);
                }
            }
            $post_data=array(
                'action'=>'register',
                'user_type'=>1,
                'token'=>md5(C('CHAT_TOKEN')),
                'mobile'=>$_POST['account'],
                'password'=>md5($_POST['password']),
            );
            $res=Post(C('CHAT_URL'),$post_data);
            echo $res;
            $chat=json_decode($res,true);
            //聊天用户注册成功才注册商城用户
            if($chat['code']==0 && !empty($chat['data']['uid'])){
                $data['chat_user_id']=$chat['data']['uid'];//保存聊天的用户id
                //var_dump($data);die;
                //开始其他的判断了
                if (true == D('Passport')->register($data)) {
                    $this->tuMsg('恭喜您，注册成功!',U('passport/download'));
                   // echoJson(['code'=>0,'msg'=>'恭喜您，注册成功','url'=>U('passport/appDownload')]);
                }else{
                    $this->tuMsg('注册失败,请稍后再试!');
                    //echoJson(['code'=>-1,'msg'=>'注册失败,请稍后再试!','data'=>'']);
                }
            }
            $this->tuMsg($chat['msg']);
            //echoJson(['code'=>-1,msg=>$chat['msg'],'data'=>'']);
        } else {
            //分销开始
            $fuid = (int) cookie('fuid');
            if ($fuid) {
                $profit_min_rank_id = (int) $this->_CONFIG['profit']['profit_min_rank_id'];
                $fuser = D('Users')->find($fuid);
                if ($fuser) {
                    $flag = false;
                    if ($profit_min_rank_id) {
                        $modelRank = D('Userrank');
                        $rank = $modelRank->find($profit_min_rank_id);
                        $userRank = $modelRank->find($fuser['rank_id']);
                        if ($rank) {
                            if ($userRank && $userRank['prestige'] >= $rank['prestige']) {
                                $flag = true;
                            } else {
                                $flag = false;
                            }
                        } else {
                            $flag = false;
                        }
                    } else {
                        $flag = true;
                    }
                    $fuser['nickname'] = empty($fuser['nickname']) ? $fuser['account'] : $fuser['nickname'];
                    if ($flag) {
                        $this->assign('fuser', $fuser);
                    }
                }
            }
            //查询协议
            $xueyi=D('Agreementlist')->where(['x_id'=>1])->find();
            $this->assign('title',$xueyi['title']);
            $this->assign('content',$xueyi['details']);
            //分销结束
            $this->display();
        }
    }

     //微信app下载
      public function download(){

       $this->display();

    }
     //浏览器app下载appdownload
      public function appdownload(){

       $this->display();

    }


    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['fuid1'] = htmlspecialchars($_POST['fuid1']);
        //微信自动分享
        $data['account'] = htmlspecialchars($_POST['account']);
        if(empty($data['account'])){
            $this->tuMsg('请输入手机号!');
            //echoJson(['code'=>-1,'msg'=>'请输入手机号!','data'=>'']);
        }
        if (!isMobile($data['account'])) {
            session('verify', null);
            $this->tuMsg('只允许手机号注册，请检查');
            //echoJson(['code'=>-1,'msg'=>'','data'=>'']);
        }
        $data['password'] = $_POST['password'];
        //整合UC的时候需要
        $register_password = $this->_CONFIG['register']['register_password'];
        if (empty($data['password']) || strlen($data['password']) < $register_password) {
            session('verify', null);
            $this->tuMsg('请输入正确的密码!密码长度必须要在' . $register_password . '个字符以上');
            //echoJson(['code'=>-1,'msg'=>'请输入正确的密码!密码长度必须要在' . $register_password . '个字符以上','data'=>'']);
        }
        $data['nickname'] = $data['account'];
        $data['token'] = $data['token'];
        $data['ext0'] = $data['account'];
        //兼容UCENTER
        $data['mobile'] = $data['account'];
        $data['reg_ip'] = get_client_ip();
        $data['reg_time'] = NOW_TIME;
        return $data;
    }
	
	
    public function sendsms(){
        if(!($mobile = htmlspecialchars($_POST['mobile']))) {
            $this->tuMsg('请输入正确的手机号码');
            //die('请输入正确的手机号码');
        }
        if(!isMobile($mobile)) {
            $this->tuMsg('请输入正确的手机号码');
            //die('请输入正确的手机号码');
        }
        if($user = D('Users')->getUserByAccount($mobile)) {
            $this->tuMsg('手机号码已经存在');
            //die('手机号码已经存在');
        }
        $wap_register_yzm = $this->_CONFIG['register']['wap_register_yzm'];
        if ($wap_register_yzm == 1) {
            $sms_yzm = htmlspecialchars($_POST['sms_yzm']);
            if (strtolower($sms_yzm) != strtolower(session('verify'))) {
                session('verify', null);
                die('请输入正确的验证码后获取手机短信');
            }
        }
		
        $randstring = session('scode');
		if(!empty($randstring)){
			session('scode',null);
		}
        $randstring = rand_string(4,1);
        session('scode', $randstring);
     
		D('Sms')->sms_yzm($mobile, $randstring);//发送短信
        die('1');
    }
    public function bind(){
        $this->display();
    }
    public function index(){
        $this->redirect('login');
    }
	
    public function login(){
        if ($this->isPost()) {
            $account = $this->_post('account');
            if(empty($account)){
                $this->tuMsg('请输入用户名');
            }
            $password = $this->_post('password');
            if (empty($password)){
                $this->tuMsg('请输入登录密码');
            }
            $backurl = $this->_post('backurl', 'htmlspecialchars');
            if(empty($backurl)){
                $backurl = U('user/member/index');
            }

            $user=D('Users')->where(['account'=>$account])->find();
            if(empty($user)){
                $this->tuMsg('用户名不存在，请先注册！',U('wap/passport/register'));
            }

            if(true == D('Passport')->login($account, $password)) {
                //$params='?'.http_build_query(['mobile'=>$account,'user_type'=>1]);
                //$this->tuMsg('恭喜您登录会员中心成功', $backurl.$params);
                $this->tuMsg('恭喜您登录会员中心成功', $backurl);
            }else{
                $this->tuMsg(D('Passport')->getError());
            }
        }else{
            if(!empty($_SERVER['HTTP_REFERER']) && strstr($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_REFERER'], 'passport')) {
                $backurl = $_SERVER['HTTP_REFERER'];
            }else{
                $backurl = U('user/member/index');
            }
            $this->assign('backurl', $backurl);
            $this->display();
        }
    }
	
	
	//微博登录
    public function wblogin(){
        $login_url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $this->_CONFIG['connect']['wb_app_id'] . '&response_type=code&redirect_uri=' . urlencode(__HOST__ . U('passport/wbcallback'));
        header("Location:{$login_url}");
        die;
    }
	//微博注册
	public function wbcallback(){
        import('@/Net.Curl');
        $curl = new Curl();
        $params = array('grant_type' => 'authorization_code', 'code' => $_REQUEST['code'], 'client_id' => $this->_CONFIG['connect']['wb_app_id'], 'client_secret' => $this->_CONFIG['connect']['wb_app_key'], 'redirect_uri' => __HOST__ . U('passport/qqcallback'));
        $url = 'https://api.weibo.com/oauth2/access_token';
        $response = $curl->post($url, http_build_query($params));
        $params = json_decode($response, true);
        if (isset($params['error'])) {
            echo '<h3>error:</h3>' . $params['error'];
            echo '<h3>msg  :</h3>' . $params['error_code'];
            die;
        }
        $url = 'https://api.weibo.com/2/account/get_uid.json?source=' . $this->_CONFIG['connect']['wb_app_key'] . '&access_token=' . $params['access_token'];
        $result = $curl->get($url);
        $user = json_decode($result, true);
        if (isset($user['error'])) {
            echo '<h3>error:</h3>' . $user['error'];
            echo '<h3>msg  :</h3>' . $user['error_code'];
            die;
        }
        $data = array(
			'type' => 'weibo', 
			'open_id' => $user['uid'], 
			'token' => $params['access_token'], 
			'create_time' => NOW_TIME, 
			'create_ip' => get_client_ip()
		);
        $this->thirdlogin($data);
    }
	
    public function qqlogin(){
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);
        $login_url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id=' . $this->_CONFIG['connect']['qq_app_id'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/qqcallback')) . '&state=' . $state . '&scope=';
        header("Location:{$login_url}");
        die;
    }
	 public function qqcallback(){
        import('@/Net.Curl');
        $curl = new Curl();
        if ($_REQUEST['state'] == session('state')) {
            $token_url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&' . 'client_id=' . $this->_CONFIG['connect']['qq_app_id'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/qqcallback')) . '&client_secret=' . $this->_CONFIG['connect']['qq_app_key'] . '&code=' . $_REQUEST['code'];
            $response = $curl->get($token_url);
            if (strpos($response, 'callback') !== false) {
                $lpos = strpos($response, '(');
                $rpos = strrpos($response, ')');
                $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
                $msg = json_decode($response);
                echo '<h3>error:</h3>' . $msg->error;
                echo '<h3>msg  :</h3>' . $msg->error_description;
                die;
            }
            $params = array();
            parse_str($response, $params);
            if (empty($params)) {
                die;
            }
            $graph_url = 'https://graph.qq.com/oauth2.0/me?access_token=' . $params['access_token'];
            $str = $curl->get($graph_url);
            if (strpos($str, 'callback') !== false) {
                $lpos = strpos($str, '(');
                $rpos = strrpos($str, ')');
                $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
            }
            $user = json_decode($str, true);
            if (isset($user['error'])) {
                echo '<h3>error:</h3>' . $user['error'];
                echo '<h3>msg  :</h3>' . $user['error_description'];
                die;
            }
            if (empty($user['openid'])) {
                die;
            }
			//如果是qq登录则获取头像昵称
            $user_info = D('Connect')->user_info($user['client_id'], $user['openid'], $params['access_token']);
            $face = $user_info['figureurl_qq_2'] == '' ? $user_info['figureurl_qq_1'] : $user_info['figureurl_qq_2'];
			
            $data = array(
				'type' => 'qq', 
				'client_id' => $user['client_id'], 
				'open_id' => $user['openid'], 
				'token' => $params['access_token'], 
				'nickname' => $user_info['nickname'], 
				'headimgurl' => $face, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			);
            $this->thirdlogin($data);
        }
    }
	
	//分销专用，微信自动注册用户
	public function wxstart(){
        if ($_REQUEST['state'] == session('state')) {
            import('@/Net.Curl');
            $curl = new Curl();
            if (empty($_REQUEST['code'])) {
                $this->error('授权后才能登陆', U('passport/login'));
            }
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->_CONFIG['weixin']['appid'] . '&secret=' . $this->_CONFIG['weixin']['appsecret'] . '&code=' . $_REQUEST['code'] . '&grant_type=authorization_code';
            $str = $curl->get($token_url);
            $params = json_decode($str, true);
            if (!empty($params['errcode'])) {
                echo '<h3>error:</h3>' . $params['errcode'];
                echo '<h3>msg  :</h3>' . $params['errmsg'];
                die;
            }
            if (empty($params['openid'])) {
                $this->error('登录失败', U('passport/login'));
            }
            $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $params['access_token'] . '&openid=' . $params['openid'] . '&lang=zh_CN';
            $info = $curl->get($info_url);
            $info = json_decode($info, true);
            $data = array(
				'type' => 'weixin', 
				'open_id' => $params['openid'], 
				'token' => $params['refresh_token'], 
				'nickname' => $info['nickname'], 
				'headimgurl' => $info['headimgurl']
			);
            $this->wxconn($data);
        }
    }
	
	 //分销专用微信自动注册为用户
    private function wxconn($data) {
        $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
        if(empty($connect)){
            $connect = $data;
            $connect['connect_id'] = D('Connect')->add($data);
        }  
        if(empty($connect['uid'])){
            session('connect', $connect['connect_id']);
			
            // 用户数据整理
            if ($data['nickname'] == ''){
                $nickname = $data['type'] . $connect['connect_id'];
            }else{
                $nickname = $data['nickname'];
            }
            $user = array(
				'account' => $data['type'].'_auto' . $connect['connect_id'], 
				'password' => rand(10000000, 999999999), 
				'nickname' => $nickname, 
				'ext0' => $nickname, 
				'face' => $data['headimgurl'], 
				'token' => $data['token'], 
				'reg_time' => NOW_TIME, 
				'reg_ip' => get_client_ip()
			);
            //注册用户资料
            if (!D('Passport')->register($user)) {
                $this->error('创建帐号失败');
            }
            // 注册第三方接口
            $token = D('Passport')->getToken();
            $connect['uid'] = $token['uid'];
            D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));// 注册成功智能跳转
            $backurl = session('backurl');
			$this->goToUrl($url = '');
        } else {
            setuid($connect['uid']);
            session('access', $connect['connect_id']);
			$this->goToUrl($url = '');
        }
        die;
    }
	
	//成功后跳转
	public function goToUrl($url){
		$backurl = cookie('backurl');
		if($backurl){
			$res = strpos($backurl,'login');
			if($res){
				cookie('backurl',null);
				header('Location:' . U('user/member/index'));die;
			}else{
				cookie('backurl',null);
				header('Location:' .$backurl);die;
			}
		}else{
			header('Location:' . U('user/member/index'));die;
		}
	}
	//微信登录
    public function wxlogin(){
        $state = md5(uniqid(rand(), TRUE));
        session('state', $state);
        $login_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $this->_CONFIG['weixin']['appid'] . '&redirect_uri=' . urlencode(__HOST__ . U('passport/wxcallback')) . '&response_type=code&scope=snsapi_base&state=' . $state . '#wechat_redirect';
        header("Location:{$login_url}");
        die;
    }
 	 //微信注册
    public function wxcallback(){
        if ($_REQUEST['state'] == session('state')) {
            import('@/Net.Curl');
            $curl = new Curl();
            if (empty($_REQUEST['code'])) {
                $this->error('授权后才能登陆', U('passport/login'));
            }
            $token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $this->_CONFIG['weixin']['appid'] . '&secret=' . $this->_CONFIG['weixin']['appsecret'] . '&code=' . $_REQUEST['code'] . '&grant_type=authorization_code';
            $str = $curl->get($token_url);
            $params = json_decode($str, true);
            if (!empty($params['errcode'])) {
                echo '<h3>error:</h3>' . $params['errcode'];
                echo '<h3>msg  :</h3>' . $params['errmsg'];
                die;
            }
            if (empty($params['openid'])) {
                $this->error('登录失败', U('passport/login'));
            }
            $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $params['access_token'] . '&openid=' . $params['openid'] . '&lang=zh_CN';
            $info = $curl->get($info_url);
            $info = json_decode($info, true);
            $data = array(
				'type' => 'weixin', 
				'open_id' => $params['openid'], 
				'token' => $params['refresh_token'], 
				'nickname' => $info['nickname'], 
				'headimgurl' => $info['headimgurl'], 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip()
			);
            $this->thirdlogin($data);
        }
    }
   
    //统一注册方法
    private function thirdlogin($data){
		
		$wx_back_url = cookie('wx_back_url');
		
        if($this->_CONFIG['weixin']['bind']){
            $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
            if(empty($connect)){
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add($data);
            }else{
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));//
            }
			
            //如果是qq登录则获取
            if ($data['type'] == 'qq'){
                $user_info = D('Connect')->user_info($data['client_id'], $data['open_id'], $data['token']);
                $nickname = $user_info['nickname'];
                $face = $user_info['figureurl_qq_2'] == '' ? $user_info['figureurl_qq_1'] : $user_info['figureurl_qq_2'];
            }
            //结束
            if (empty($connect['uid'])){
                $user = array(
					'account' => $data['type'].'_wxlogin' . $connect['connect_id'], 
					'password' => rand(100000, 999999), 
					'nickname' => $nickname, 
					'face' => $face, 
					'ext0' => $account, 
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip()
				);
                if(!D('Passport')->register($user)){
                    $this->error('创建帐号失败');
                }
                $token = D('Passport')->getToken();
                $connect['uid'] = $token['uid'];
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'uid' => $connect['uid']));
            }
            setuid($connect['uid']);
            session('access', $connect['connect_id']);
			header('Location:' . U('user/member/index'));
        }else{
            $connect = D('Connect')->getConnectByOpenid($data['type'], $data['open_id']);
            if (empty($connect)) {
                $connect = $data;
                $connect['connect_id'] = D('Connect')->add($data);
            } else {
                D('Connect')->save(array('connect_id' => $connect['connect_id'], 'token' => $data['token']));
            }
            if (empty($connect['uid'])) {
                session('connect', $connect['connect_id']);
                header('Location: ' . U('passport/bind'));
            } else {
                setuid($connect['uid']);
                session('access', $connect['connect_id']);
				header('Location:' . U('user/member/index'));
            }
        }
    }
    public function logout(){
        cookie('Tudou_TOKEN', 0);
        cookie('goods', null);
        cookie('index_mask_show', null); //清除首页广告缓存数据

        D('Passport')->logout();
        $this->success('退出登录成功', U('Wap/passport/login'));
    }
    public function weixincheck(){
        $state = $this->_param('state');
        if (empty($state)) {
            $this->error('非法访问', U('wap/index/index'));
        }
        if ($this->uid) {
            $wxconn = D('Weixinconn')->where(array('state' => $state))->find();
            $data = array();
            $data['conn_id'] = $wxconn['conn_id'];
            $data['status'] = 1;
            $data['user_id'] = $this->uid;
            D('Weixinconn')->save($data);
            $this->success('扫描成功，请等待电脑端响应', U('user/member/index'));
        }
    }
    public function forget(){
        $way = (int) $this->_param('way');
        $this->assign('way', $way);
        $this->display();
    }
	//找回密码
    public function newpwd() {
        $mobile = htmlspecialchars($_POST['mobile']);
		$count = D('Users')->where(array('mobile'=>$mobile))->count();
		if($count > 1){
			session('scode', null);
			$this->tuMsg('参数错误，请联系管理员解决');
		}
		if(!$Users = D('Users')->where(array('mobile'=>$mobile))->find()){
			session('scode', null);
            $this->tuMsg('手机号码输入错误');
        }
        if(!($scode = trim($_POST['scode']))) {
          $this->tuMsg('请输入短信验证码');
        }
        $scode2 = session('scode');
        if (empty($scode2)) {
           $this->tuMsg('请获取短信验证码');
        }
        if($scode != $scode2) {
            $this->tuMsg('请输入正确的短信验证码');
        }
        if($_POST['password'] != $_POST['qpassword']){
            $this->tuMsg('输入的密码不一致');
        }
        if(empty($_POST['password']) || empty($_POST['qpassword'])){
            $this->tuMsg('密码不能为空');
        }
		$password = $_POST['password'];
		if(D('Passport')->uppwd($Users['account'], '', $password)){
			D('Sms')->sms_user_newpwd($mobile, $password);//发送短信
			session('scode', null);
			$this->tuMsg('恭喜您更新密码成功，请查收手机短信', U('passport/login'));
		}else{
			session('scode', null);
			$this->tuMsg('更新密码失败');
		}
    }
    public function findsms(){
        if (!($mobile = htmlspecialchars($_POST['mobile']))) {
            die('请输入正确的手机号码');
        }
        if (!isMobile($mobile)) {
            die('请输入正确的手机号码');
        }
        if (!($account = htmlspecialchars($_POST['account']))) {
            die('请填写账号');
        }
        if ($user = D('Users')->getUserByAccount($account)) {
            if (empty($user['mobile'])) {
                die('你还未绑定手机号，请选择其他方式');
            } else {
                if ($user['mobile'] != $mobile) {
                    die('请填写您的绑定手机号');
                }
            }
        }
        $randstring = session('scode');
        if (empty($randstring)) {
            $randstring = rand_string(6, 1);
            session('scode', $randstring);
        }
        // $siteName ='甜心快乐';
		D('Sms')->sms_yzm($mobile,$randstring);//发送短信
        die('1');
    }
   
}