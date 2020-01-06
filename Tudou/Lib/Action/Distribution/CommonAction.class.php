<?php
class CommonAction extends Action
{
    protected $uid = 0;
    protected $member = array();
    protected $_CONFIG = array();
    protected $shop_id = 0;
    protected $shop = array();
    protected $shopcates = array();
    protected function _initialize(){
        $this->uid = getUid();
        if(!empty($this->uid)) {
            $this->member = D('Users')->find($this->uid);
        }
        if(strtolower(MODULE_NAME) != 'login' && strtolower(MODULE_NAME) != 'public') {
            if (empty($this->uid)) {
                header("Location: " . U('login/index'));
                die;
            }
            $this->shop = D('Applicationmanagement')->find(array("where" => array('user_id' => $this->uid,'sq_delete' => 0)));
            if(empty($this->shop)){
                $this->error('该用户没有开通配送员管理', U('login/index'));
            }

            $this->shop_id = $this->shop['user_id'];
            $this->assign('SHOP', $this->shop);
        }
		
        $this->_CONFIG = D('Setting')->fetchAll();
        if($this->_CONFIG['site']['https'] == 1){
			define('__HOST__', 'https://' . $_SERVER['HTTP_HOST']);
		}else{
			define('__HOST__', 'http://' . $_SERVER['HTTP_HOST']);
		}
		define('IS_MOBILE', is_mobile());
        $this->assign('CONFIG', $this->_CONFIG);
		
		
        $this->assign('MEMBER', $this->member);
        $this->shopcates = D('Shopcate')->fetchAll();
        $this->assign('shopcates', $this->shopcates);
        $this->assign('ctl', strtolower(MODULE_NAME));
        //主要方便调用
        $this->assign('act', ACTION_NAME);
        $this->assign('today', TODAY);
        $this->assign('nowtime', NOW_TIME);
        $waimai = $this->ele = D('Ele')->find($this->shop_id);
        $this->assign('waimai', $waimai);
        $wd = D('WeidianDetails')->where('shop_id =' . $this->shop_id)->find();
        $this->assign('wd', $wd);
	   //权限获取
        /*--获取当前用户的权限ID-- */
        $name = D('Deliveryadmin')->where(array('dj_id'=>$this->shop[
        'dj_id']))->find();
        $hoor=D('Deliveryahonor')->where(array('ry_id'=>$this->shop['ry_id']))->find();
        $Shopgrade = D("ShopauthUser")->where(array("user_id"=>$this->shop['user_id']))->find();
        $Shopgrade['name'] = $name['name'];
        $Shopgrade['fencheng'] = $name['fencheng'];
        $Shopgrade['dj_id'] = $name['dj_id'];
       
        $this->assign('HOOR',$hoor);
        $this->assign('SHOPGRADE', $Shopgrade);
		$this->grade_id = $Shopgrade['shop_id'];//方便程序调用这段可以不要
        $this->assign('msg_day', $counts['msg_day'] = (int) D('Msg')->where(array('cate_id' => 2, 'views' => 0, 'shop_id' => $this->shop_id))->count());
    }
    public function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = ''){
        parent::display($this->parseTemplate($templateFile), $charset, $contentType, $content = '', $prefix = '');
    }
    private function parseTemplate($template = ''){
        $depr = C('TMPL_FILE_DEPR');
        $template = str_replace(':', $depr, $template);
        $theme = $this->getTemplateTheme();
        define('NOW_PATH', BASE_PATH . '/themes/' . $theme . 'Distribution/');
        define('THEME_PATH', BASE_PATH . '/themes/default/Distribution/');
        define('APP_TMPL_PATH', __ROOT__ . '/themes/default/Distribution/');
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
    private function getTemplateTheme()
    {
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
    protected function tuMsg($message, $jumpUrl = '', $time = 3000, $callback = '', $parent = true)
    {
        $parents = $parent ? 'parent.' : '';
        $str = '<script>';
        $str .= $parents . 'bmsg("' . $message . '","' . $jumpUrl . '","' . $time . '","' . $callback . '");';
        $str .= '</script>';
        exit($str);
    }
    protected function tuOpen($message, $close = true, $style)
    {
        $str = '<script>';
        $str .= 'parent.bopen("' . $message . '","' . $close . '","' . $style . '");';
        $str .= '</script>';
        exit($str);
    }
    protected function tuSuccess($message, $jumpUrl = '', $time = 3000, $parent = true)
    {
        $this->tuMsg($message, $jumpUrl, $time, '', $parent);
    }
    protected function tuErrorJump($message, $jumpUrl = '', $time = 3000)
    {
        $this->tuMsg($message, $jumpUrl, $time);
    }
    protected function tuError($message, $time = 3000, $yzm = false, $parent = true)
    {
        $parent = $parent ? 'parent.' : '';
        $str = '<script>';
        if ($yzm) {
            $str .= $parent . 'bmsg("' . $message . '","",' . $time . ',"yzmCode()");';
        } else {
            $str .= $parent . 'bmsg("' . $message . '","",' . $time . ');';
        }
        $str .= '</script>';
        exit($str);
    }
    protected function checkFields($data = array(), $fields = array())
    {
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

    //查询订单数
    public function countorder($level,$delivery_id,$create_time){
        $where['status']=8;
        //配送员
        if($level==1){
            $where['delivery_id']=$delivery_id;
            $where['end_time']=$create_time;
            $count=D('DeliveryOrder')->where($where)->count();
            $count=isset($count)?$count:0;
            return $count;
        }

        //跑腿
        if($level==2){
            $where['cid']=$delivery_id;
            $where['end_time']=$create_time;
            $count=D('Running')->where($where)->count();
            $count=isset($count)?$count:0;
            return $count;
        }

        return 0;
    }


}