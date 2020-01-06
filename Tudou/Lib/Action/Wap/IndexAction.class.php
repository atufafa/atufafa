<?php


class IndexAction extends CommonAction {
    public function index2(){
        $this->display();
    }
      public function index(){
		$lat = cookie('lat_ok');
		$lng = cookie('lng_ok');
		if(empty($lat) || empty($lng)){
			$lat = cookie('lat');
			$lng = cookie('lng');
		}
        if (empty($lat) || empty($lng)) {
            $lat = $this->_CONFIG['site']['lat'];
            $lng = $this->_CONFIG['site']['lng'];
        }
		  
		$orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";  

        $this->assign('lifecate', D('Lifecate')->fetchAll());
        $this->assign('channel', D('Lifecate')->getChannelMeans());
		$this->assign('config',D('Setting')->fetchAll());
		
        $this->assign('news', $news = D('Article')->where(array( 'closed' => 0, 'audit' => 1))->order(array('create_time' => 'desc'))->limit(0, 3)->select());
		$this->assign('ele', $ele = D('Ele')->where(array('city_id'=>$this->city_id, 'audit' => 1))->order(array('orderby' => 'desc'))->limit(0, 3)->select());
		$this->assign('life', $life = D('Life')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order(array('create_time' => 'desc'))->limit(0, 3)->select());


		$maps = array('status' => 2,'closed'=>0);
		$this->assign('nav',$nav = D('Navigation') ->where($maps)->order(array('orderby' => 'asc'))->select());
		$bg_time = strtotime(TODAY);
		$this->assign('sign_day', $sign_day = (int) D('Usersign')->where(array('user_id' => $this->uid, 'create_time' => array(array('ELT', NOW_TIME), array('EGT', $bg_time))))->count());
		$this->assign('goods',$goods = D('Goods')->where(array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY)))->order(array('top_time' =>'desc','check_price' =>'desc','orderby' =>'asc'))->limit(0,16)->select());
          if(($goods['is_vs1'] || $goods['is_vs2'] || $goods['is_vs3'] || $goods['is_vs4'] || $goods['is_vs5'] || $goods['is_vs6']) || $goods['is_vs7'] || $goods['is_vs8'] || $goods['is_vs9']==1 ){
              $this->assign('is_vs', $is_vs = 1);
          }else{
              $this->assign('is_vs', $is_vs = 0);
          }
		$orderby = " (ABS(lng - '{$lng}') +  ABS(lat - '{$lat}') ) asc ";  
		$shoplist = D('Shop')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order(array('orderby' => 'asc'))->limit(0, 3)->select();
		foreach ($shoplist as $k => $val) {
            $shoplist[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
        $tuanlist = D('Tuan')->where(array('city_id'=>$this->city_id, 'closed' => 0, 'audit' => 1))->order(array('orderby' => 'desc'))->limit(0, 3)->select();
		foreach ($tuanlist as $k => $val) {
            $tuanlist[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
		$livelist = D('Live')->where(array('city_id'=>$this->city_id))->order(array('create_time' => 'desc'))->limit(0,3)->select();
		foreach ($livelist as $k => $val) {
            $livelist[$k]['d'] = getDistance($lat, $lng, $val['lat'], $val['lng']);
        }
		$this->assign('livelist', $livelist);
        $this->assign('shoplist', $shoplist);
		$this->assign('tuanlist', $tuanlist);
		
		
		//根据类型筛选分类
		$arr = D('navCate')->where(array('type'=>2))->order(array('cate_id' => 'asc'))->select();//得到总分类
		$cate_ids = array();
		foreach($arr as $k =>$v){
			$cate_ids[] = $v['cate_id'];
		}
		$arrs = D('navCate')->where(array('parent_id'=>array('IN',$cate_ids)))->order(array('cate_id' => 'asc'))->select();//筛选一级数组
		$cate_ids2 = array();
		foreach($arrs as $kk =>$vv){
			$cate_ids2[] = $vv['cate_id'];
		}
		$arrs2 = D('navCate')->where(array('parent_id'=>array('IN',$cate_ids2)))->order(array('cate_id' => 'asc'))->select();//筛选二级分类
		$lists = $arrs2;
		$mobile=isset($_GET['mobile'])?$_GET['mobile']:'';
		$user_type=isset($_GET['user_type'])?$_GET['user_type']:'';
		$chat_url='&mobile='.$mobile.'&user_type='.$user_type;
		$this->assign('chat_url',$chat_url );
		$this->assign('nav2',$lists );

		//商品类型
		$this->assign('goodscates',D('Goodscate')->where(array('parent_id'=>0,'cate_id'=>array('not in','179')))->select());

        //图片循环
        //预留给商家开放
        //$this->assign('good',$goods = D('Goods')->where(array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id,'shop_id'=>104, 'end_date' => array('EGT', TODAY)))->order(array('top_time' =>'desc','check_price' =>'desc','orderby' =>'asc'))->limit(0,6)->select());
        //$this->assign('imgs',D('Ad')->where(array('site_id'=>array('in','80,86,87,88,89,14'),'closed'=>0))->select());

        //限量团购
        $tuan=D('MallactivityGoods')->where(['type_id'=>3,'colsed'=>0,'audit'=>1])->select();

        $this->assign('eletaun',$tuan);
        //平台天天特价
        $tejia=D('AdminGoods')->where(['colsed'=>0,'audit'=>1])->select();
        $this->assign('elespecial',$tejia);
      //判断平台是否存在天天特价活动，不然就显示商家天天特价活动
         $shoptejia=D('MallactivityGoods')->where(['type_id'=>1,'colsed'=>0,'audit'=>1,'type'=>0])->select();
          $this->assign('shoptejia',$shoptejia);

        //限时秒杀
          $miao=D('MallactivityGoods')->where(['type_id'=>2,'colsed'=>0,'audit'=>1])->select();
          $this->assign('elespike',$miao);




          //新版生成带参数二维码
              $fuid = (int) $this->uid;
              if($fuid){
                  $file = D('Api')->getQrcode($fuid);//生成二维码
                  $this->assign('file', $file);
              }

          $this->display();
    }
   

    public function search() {
        $keys = D('Keyword')->fetchAll();
        $keytype = D('Keyword')->getKeyType();
        $this->assign('keys',$keys);
        $this->display();
    }



	public function more() {
		$cates = D('NavCate')->order(array('cate_id' => 'asc'))->select();
		
        foreach($cates as $k => $v){
			if($v['parent_id'] == 0){
            	if($v['type'] != 2){
                	unset($cates[$k]);
                }
				
			}
		}
		$this->assign('cates',$cates);
		$this->display();
	}
	
	public function fabu() {
		$this->display();
	}
	public function house() {
		$this->display();
	}
	//获取定位
	public function dingwei(){
        $lat = I('lat');
        $lng = I('lon');
		$url = I('url');
		$address = I('address');
		cookie('lat',$lat,3600);
        cookie('lng',$lng,3600);
		cookie('url', $url);//保存clookie
		list($code,$city_id,$city_name,$msg,$address,$url) = $this->getDingwei($lat, $lng);
		//p($code.'---'.$city_id.'---'.$city_name.'---'.$msg.'---'.$address.'---'.$url);die;			
        $this->ajaxReturn(array('la'=>$lat,'lon'=>$lng,'code'=>$code,'city_id'=> $city_id,'city_name'=> $city_name,'msg'=> $msg,'address'=>$address,'url'=>$url));
    }
	
	//返回定位
	public function getDingwei($lat, $lng){
		$config = D('Setting')->fetchAll();
		$addr = $this->getArea($lat, $lng);
		cookie('addr',$addr, 60 * 60 * 12);
		if(!empty($addr)) {
			cookie('location', 2);
		}
		$city = mb_substr($addr['city'], 0, -1, 'utf-8');
		$district = mb_substr($addr['district'], 0, -1, 'utf-8');
	
		$position = $city . $district;
		$city = D('Pinyin')->pinyin($city);//城市拼音
		$district = D('Pinyin')->pinyin($district);//地区拼音
		
		$town = D('City')->where(array('pinyin'=>$district,'is_open' =>1))->find();//城市
		$county = D('City')->where(array('pinyin'=>$city,'is_open' =>1))->find();//县城
		
		
		$city_id = cookie('city_id');
		$cityop = cookie('cityop');
		
		
		
		if(!empty($city_id)){
				if($city_id == $county['city_id'] || $city_id == $town['city_id'] || $cityop == 1) {
					//return array($code = 1,$city_id = 999,$city_name = '有城市了',$msg = '不弹出了',$position);
					return array($code = 1,$city_id = $config['site']['city_id'],$city_name = '有城市了',$msg = '不弹出了',$position);
					//return array($code = 2,$city_id = $town['city_id'],$city_name = $town['name'],$msg = '城市位置【'.$position.'】',$position,$url = U('city/change', array('city_id' => $town['city_id'],'type'=>3)));
				}else{
					if(!empty($county)){
						return array($code = 2,$city_id = $county['city_id'],$city_name = $county['name'],$msg = '当前城市【'.$position.'】',$position,$url = U('city/change', array('city_id' => $county['city_id'],'type'=>3)));
					}elseif(!empty($town)) {
						return array($code = 2,$city_id = $town['city_id'],$city_name = $town['name'],$msg = '城市位置【'.$position.'】',$position,$url = U('city/change', array('city_id' => $town['city_id'],'type'=>3)));
					}
				}
			}else{
				if(!empty($county)){
					return array($code = 2,$city_id = $county['city_id'],$city_name = $county['name'],$msg = '当前城市：'.$position,$position,$url = U('city/change', array('city_id' => $county['city_id'],'type'=>3)));
				}elseif(!empty($town)) {
					return array($code = 2,$city_id = $town['city_id'],$city_name = $town['name'],$msg = '当前城市：'.$position,$position,$url = U('city/change', array('city_id' => $town['city_id'],'type'=>3)));
				}else{
					$detail = D('City')->find($config['site']['city_id']);//城市
					return array($code = 6,$city_id = $config['site']['city_id'],$city_name = $detail['name'],$msg = '当前位置：'.$position.'没有匹配到城市 ',$position,$url = U('city/change', array('city_id' => $config['site']['city_id'],'type'=>3)));
				}
			}
		
		
		
	}
	
	//通过接口将坐标转地理位置
    function getArea($lat, $lng){
        $url = 'https://api.map.baidu.com/geocoder/v2/?ak=C9613fa45f450daa331d85184c920119&location=' . $lat . ',' . $lng . '&output=json&pois=1';
        $arr = file_get_contents($url);
        $arr = json_decode($arr);
        $place = $pois = $po = array();
        foreach ($arr->result->pois as $value) {
            $po['name'] = $value->name;
            $po['addr'] = $value->addr;
            $pois[] = $po;
        }
        $place['formatted_address'] = $arr->result->formatted_address;
		$place['city'] = $arr -> result -> addressComponent -> city;
	//	$place['district'] = $arr -> result -> addressComponent -> district;
        $place['pois'] = $pois;
        return $place;
    }
	
	//中转站
	public function url(){
		$config = D('Setting')->fetchAll();
		$index_mask_cookie = ($config['other']['index_mask_cookie'] >= 7200) ? $config['other']['index_mask_cookie'] : 3600*4;
		if($config['other']['index_mask_show'] && $config['other']['index_mask_url']){
			cookie('index_mask_show', $config['other']['index_mask_cookie']);
			header("Location:" . $config['other']['index_mask_url']);
			die;
		}else{
			cookie('index_mask_show',$index_mask_cookie);
			header("Location:" . U('Wap/index/index'));
         	die;
		}
    }
	
	/*
	* 设置首页cookie，第一次访问时显示抢红包，后面访问隐藏--liuf
	*/
	public function setAdCookie(){
		$config = D('Setting')->fetchAll();
		$index_mask_cookie = ($config['other']['index_mask_cookie'] >= 7200) ? $config['other']['index_mask_cookie'] : 3600*4;
		if($config['other']['index_mask_show'] && $config['other']['index_mask_url']){
			cookie('index_mask_show', $config['other']['index_mask_cookie'], 60 * 60 * 12);
		}else{
			cookie('index_mask_show', $index_mask_cookie, 60 * 60 * 12);
		}
	}
	

    public function hongbao() {
    	$this->display();
    }

    public function hongbao_data(){
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));

        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        $envelope = D('Envelope');
        import('ORG.Util.Page');
        $map = array('is_shop' => 1, 'type' => 3, 'status' => 1);
        $count = $envelope->where($map)->count();
        $where = " '".date('Y-m-d H:i:s',NOW_TIME)."' > bg_date AND end_time >'".date('Y-m-d H:i:s',NOW_TIME)."'";
        $Page = new Page($count, 10);
        $show = $Page->show();
		
		$var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $list = $envelope->where($where)->where($map)->limit($Page->firstRow . ',' . $Page->listRows)->order(array('is_tui' => 'desc'))->select();
        // print_r($list);die;
        $config = D('Setting')->fetchAll();
        $pei=$config['freight']['radius'];
        foreach ($list as $key => $value) {
        	$shop = D('Shop')->where(['shop_id'=>$value['shop_id']])->find();
            if ($shop['parent_id']==100 || $shop['parent_id']== 101 || $shop['parent_id']== 102){
                $lists[$key]['is_radius'] = getDistanceNone($lat, $lng, $shop['lat'], $shop['lng']);
                if (!empty($val['is_radius'])){
                    if ((int) $lists[$key]['is_radius'] > $pei*10000){
                        unset($lists[$key]);
                    }
                }
            }
        	$list[$key]['shop_name'] = $shop['shop_name'];
        }
        // echo $envelope->getlastSql();die;
        
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //打车
    public function dache(){
        $this->display();
    }

    //数据存库
    public function check_price()
    {
        if(IS_AJAX){
            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
            $where= "create_time between ".$beginToday." and ".$endToday." and get_ip ='".get_client_ip()."'";
            $now_ip = D('TaketaxiLog')->where($where)->select();
            $count = count($now_ip);
            //查询IP限制
            $config=D('Setting')->fetchAll();
            $sum=$config['taketaxi']['number'];
            if($sum>=$count){//如果超过限制，不扣费
                D('TaketaxiLog')->add(array(
                    'get_ip'=>get_client_ip(),
                    'intro'=>'点击进入聚合打车',
                    'create_time'=>NOW_TIME,
                    'user_id'=>$this->uid
                ));
            }
        }
    }

    //直播
    public function zhibo(){
        $this->display();
    }










}
