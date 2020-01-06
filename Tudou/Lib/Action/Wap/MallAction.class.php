	<?php
class MallAction extends CommonAction{
	
    public function _initialize(){
        parent::_initialize();
        if($this->_CONFIG['operation']['mall'] == 0){
            $this->error('此功能已关闭');die;
        }
        $goods = cookie('goods_spec');
        $this->assign('cartnum', (int) array_sum($goods));
        $goodss = cookie('goods_specs');
        $this->assign('cartnums', (int) array_sum($goodss));
        $cat = (int) $this->_param('cat');
        $this->assign('goodscates', $goodscates = D('Goodscate')->fetchAll());
        $cates = (int)$this->_param('cates');
        $this->assign('goodscatess',$getFurniture = D('Goodscate')->getFurniture($cates));
        $this->assign('title',D('Goodscate')->getFurnitureName($cates));
        // print_r($getFurniture);die;
		$check_user_addr = D('Paddress')->where(array('user_id'=>$this->uid))->find();//全局检测地址
		$this->assign('check_user_addr', $check_user_addr);
        $config=D('Setting')->fetchAll();
        $this->assign('config',$config);
    }
	
	
	 public function push(){
        $obj = D('Goods');
        import('ORG.Util.Page');
        $map = array('audit' => 1, 'closed' => 0, 'city_id' => $this->city_id, 'end_date' => array('EGT', TODAY));
        $count = $obj->where($map)->count();
        $Page = new Page($count, 3);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        $goods = $obj->where($map)->order(array('top_time' =>'desc','orderby' =>'asc'))->limit(0,30)->select();
        $this->assign('goods', $goods);
        $this->assign('page', $show);
        $this->display();
    }
	
    //衣鞋家纺
    public function yxjf()
    {
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;
        
        $cat = (int) $this->_param('cat');
        if($cat){
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
        }
        $this->assign('cat', $cat);
        $linkArr['cat'] = $cat;
        
        $area = (int) $this->_param('area');
        $this->assign('area', $area);
        $linkArr['area'] = $area;
        
        $business = (int) $this->_param('business');
        $this->assign('business', $business);
        $linkArr['business'] = $business; 
         
        $cate_id = (int) $this->_param('cate_id');
        if($cate_id){
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
        }
        $this->assign('cate_id', $cate_id);
        $linkArr['cate_id'] = $cate_id;
        
        $order = (int) $this->_param('order');
        $this->assign('order', $order);
        $linkArr['order'] = $order;
        
        $shop_id = (int) $this->_param('shop_id');
        $this->assign('shop_id', $shop_id);
        $linkArr['shop_id'] = $shop_id;
        
        $type = (int) $this->_param('type');
        $this->assign('type', $type);
        $linkArr['type'] = $type;
        
        $user_id = (int) $this->_param('user_id');
        $this->assign('user_id', $user_id);
        $linkArr['user_id'] = $user_id;
        
        
        //开始组装数组
        $query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
        $arr = array();
        foreach($query_string as $key=>$values){
            if(strstr( $values , 'values_' ) !== false){
                array_push($arr, $values);
            }
        }
        
        foreach($arr as $k=>$v){
            $arr[$v] = $this->_param($v,'htmlspecialchars');
            $query[$v] = $arr[$v];
            $this->assign('query',$query);
            $linkArr[$v] = $arr[$v];
        }
        
        $linkArr['cates'] = $this->_param('cates');
        $this->assign('nextpage', LinkTo('mall/loaddata_yxjf',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('scroll', $scroll = D('Goods')->getScroll());
        $this->assign('linkArr',$linkArr);
        $this->display();
    }

    public function loaddata_yxjf(){
        $Goods = D('Goods');
        import('ORG.Util.Page');
      
        $area = (int) $this->_param('area');
        if($area){
            $map['area_id'] = $area;
            $this->assign('area', $area);
            $linkArr['area'] = $area;
        }
        
        
        $business = (int) $this->_param('business');
        if($business){
            $map['business_id'] = $business;
            $this->assign('business', $business);
            $linkArr['business'] = $business;
        }
        
   
        
        $shop_id = (int) $this->_param('shop_id');
        if($shop_id){
            $map['shop_id'] = $shop_id;
            $this->assign('shop_id', $shop_id);
            $linkArr['shop_id'] = $shop_id;
        }
        
        $user_id = (int) $this->_param('user_id');
        if($user_id){
            $this->assign('user_id', $user_id);
            $linkArr['user_id'] = $user_id;
        }
        
        $type = (int) $this->_param('type');
        if($type){
            $this->assign('type', $type);
            $linkArr['type'] = $type;
        }
        
        $map['audit'] = 1;
        $map['closed'] = 0;
        $map['end_date'] = array('egt', TODAY);
        $map['city_id'] = $this->city_id;
        
        
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
            $linkArr['keyword'] = $keyword;
        }
        
        
        //开始组装数组
        $query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
        $arr = array();
        foreach($query_string as $key=>$values){
            if(strstr( $values , 'values_' ) !== false){
                array_push($arr, $values);
            }
        }
        
        foreach($arr as $k=>$v){
            $arr[$v] = $this->_param($v,'htmlspecialchars');
            $query[$v] = $arr[$v];
            $this->assign('query',$query);
            $linkArr[$v] = $arr[$v];
        }
        
        $array = array();
        foreach($query as $kk=>$vv){
            $explode = explode('_',$kk); 
            $array[$kk]['attr_id'] = $explode['1'];
            $array[$kk]['attr_value'] = $vv;
        }
        foreach($array as $val){
            $attr_values[$val['attr_value']] = $val['attr_value'];
        }

        $maps['attr_value']  = array('IN',$attr_values);
        //$TpGoodsAttr = M('TpGoodsAttr')->where($map)->group('attr_value')->select();
        $TpGoodsAttr = M('TpGoodsAttr')->where($maps)->select();
        
        $result= array();
        foreach($TpGoodsAttr as $key => $info){
            $result[$info['goods_id']][] = $info;
        }
        
        foreach($result as $kkk => $vvv){
            foreach($vvv as $k2 => $v2){
                $attr_valuess[$kkk][$k2] = $v2['attr_value'];
            }
        }
        
        $implode = implode('_',$attr_values);
        
        foreach($attr_valuess as $k3 => $v3){
            $implodes = implode('_',$v3);
            if($implodes != $implode){
                unset($attr_valuess[$k3]);
            }
        }
        
        
        foreach($attr_valuess as $k4 => $v4){
            $goods_ids[$k4] = $k4;
        }
        if($array){
            $map['goods_id'] = array('IN',$goods_ids);
        }
        //多属性搜索结束
        
        
        
        $cate_id = (int) $this->_param('cate_id');
        $cat = (int) $this->_param('cat');
        if($cate_id){
            if($cate_id){
                if(empty($array)){
                    $map['cate_id'] = $cate_id;
                }
                $linkArr['cate_id'] = $cate_id;
                $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
            }
        }else{
            $catids = D('Goodscate')->getChildren($cat);
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
            if(!empty($catids)){
                if(empty($array)){
                    // $map['cate_id'] = array('IN', $catids);
                }
            }else{
                $map['cate_id'] = $cate_id;
                $linkArr['cate_id'] = $cate_id;
            }
            
        }
        $this->assign('cat', $cat);
        $this->assign('cate_id', $cate_id);
        $cates = (int) $this->_param('cates');
        if(!empty($cates)){
            if($cates ==1){
                $Type = array('164','167','165','162','166','134','131','130','128','129');
                $map['cate_id'] = array('IN',$Type);
            }
            if($cates ==2){
                 $Type = array('163','161','168','160','178');
                $map['cate_id'] = array('IN',$Type);
            }
            if($cates ==3){
                $Type = array('44','45','46','47','48');
                $map['cate_id'] = array('IN',$Type);
            }
        }
        // print_r($cates);die;
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
        
        $lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
        
        $order = $this->_param('order', 'htmlspecialchars');
        switch($order){
            case '1':
                $orderby = array('top_time' => 'desc');
                break;
            case '2':
                $orderby = array('old_num' => 'asc');
                break;
            case '3':
                $orderby = array('mall_price' => 'desc');
                break;
            case '4':
                $orderby = array('mall_price' => 'asc');
                break;
            case '5':
                $orderby = array('top_time' => 'desc','orderby' =>'asc');
                break;
            default:
                $orderby = array('top_time' => 'desc');
                break;
        }
        $list = $Goods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val){
            if($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $Shop = D('Shop')->find($val['shop_id']);
            $val['d'] = getDistance($lat, $lng, $Shop['lat'], $Shop['lng']);
            $list[$k] = $val;
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
	
    public function index(){
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
		$linkArr['keyword'] = $keyword;
		
        $cat = (int) $this->_param('cat');
		if($cat){
			$this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
		}
		$this->assign('cat', $cat);
		$linkArr['cat'] = $cat;
		
        $area = (int) $this->_param('area');
		$this->assign('area', $area);
		$linkArr['area'] = $area;
		
        $business = (int) $this->_param('business');
		$this->assign('business', $business);
		$linkArr['business'] = $business; 
		 
        $cate_id = (int) $this->_param('cate_id');
		if($cate_id){
			$this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
		}
		$this->assign('cate_id', $cate_id);
		$linkArr['cate_id'] = $cate_id;
		
        $order = (int) $this->_param('order');
        $this->assign('order', $order);
        $linkArr['order'] = $order;
		
		$shop_id = (int) $this->_param('shop_id');
		$this->assign('shop_id', $shop_id);
		$linkArr['shop_id'] = $shop_id;
		
		$type = (int) $this->_param('type');
		$this->assign('type', $type);
		$linkArr['type'] = $type;
		
		$user_id = (int) $this->_param('user_id');
		$this->assign('user_id', $user_id);
		$linkArr['user_id'] = $user_id;
		
		
		//开始组装数组
		$query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
		$arr = array();
		foreach($query_string as $key=>$values){
			if(strstr( $values , 'values_' ) !== false){
				array_push($arr, $values);
			}
		}
		
		foreach($arr as $k=>$v){
			$arr[$v] = $this->_param($v,'htmlspecialchars');
			$query[$v] = $arr[$v];
			$this->assign('query',$query);
			$linkArr[$v] = $arr[$v];
		}
        //商品类型
        $this->assign('goodscate',D('Goodscate')->where(array('cate_id'=>array('in','41,110,113,75,72,1,4,10,13,15,17,19,22,25,30,31,32,33,34,35,36,37,38')))->select());

        //限量团购
        $tuan=D('MallactivityGoods')->where(['type_id'=>3,'closed'=>0,'audit'=>1])->select();
        $this->assign('eletaun',$tuan);
        //天天特价
        $tejia=D('MallactivityGoods')->where(['type_id'=>1,'closed'=>0,'audit'=>1,'type'=>0])->select();
        $this->assign('elespecial',$tejia);

        //限时秒杀
        $miao=D('MallactivityGoods')->where(['type_id'=>2,'closed'=>0,'audit'=>1])->select();
        $this->assign('elespike',$miao);


        //var_dump($list);die;
        $this->assign('nextpage', LinkTo('mall/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('scroll', $scroll = D('Goods')->getScroll());
		$this->assign('linkArr',$linkArr);
        $this->display();
    }

    //分类商品
    public function index_type(){
        $keyword = $this->_param('keyword', 'htmlspecialchars');
        $this->assign('keyword', $keyword);
        $linkArr['keyword'] = $keyword;

        $cat = (int) $this->_param('cat');
        if($cat){
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
        }
        $this->assign('cat', $cat);
        $linkArr['cat'] = $cat;

        $area = (int) $this->_param('area');
        $this->assign('area', $area);
        $linkArr['area'] = $area;

        $business = (int) $this->_param('business');
        $this->assign('business', $business);
        $linkArr['business'] = $business;

        $cate_id = (int) $this->_param('cate_id');
        if($cate_id){
            $this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
        }
        $this->assign('cate_id', $cate_id);
        $linkArr['cate_id'] = $cate_id;

        $order = (int) $this->_param('order');
        $this->assign('order', $order);
        $linkArr['order'] = $order;

        $shop_id = (int) $this->_param('shop_id');
        $this->assign('shop_id', $shop_id);
        $linkArr['shop_id'] = $shop_id;

        $type = (int) $this->_param('type');
        $this->assign('type', $type);
        $linkArr['type'] = $type;

        $user_id = (int) $this->_param('user_id');
        $this->assign('user_id', $user_id);
        $linkArr['user_id'] = $user_id;


        //开始组装数组
        $query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
        $arr = array();
        foreach($query_string as $key=>$values){
            if(strstr( $values , 'values_' ) !== false){
                array_push($arr, $values);
            }
        }

        foreach($arr as $k=>$v){
            $arr[$v] = $this->_param($v,'htmlspecialchars');
            $query[$v] = $arr[$v];
            $this->assign('query',$query);
            $linkArr[$v] = $arr[$v];
        }

        $this->assign('nextpage', LinkTo('mall/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('scroll', $scroll = D('Goods')->getScroll());
		$this->assign('linkArr',$linkArr);
        $this->display();
    }
	
	
	public function getTpGoodsAttributes($cat){
		$res = D('Goodscate')->find($cat);
		$TpGoodsType = M('TpGoodsType')->where(array('id'=>$res['type_id']))->find();
		$TpGoodsAttributes = M('TpGoodsAttribute')->where(array('type_id'=>$TpGoodsType['id'],'attr_input_type'=>1))->select();
		foreach($TpGoodsAttributes as $k => $val){
			if(empty($val['attr_values']) || trim($val['attr_values']) == ''){ 
				unset($TpGoodsAttributes[$k]);
			}
			
        }
		foreach($TpGoodsAttributes as $kk => $vv){
			$TpGoodsAttribute[$kk]['attr_id'] = $vv['attr_id'];
			$TpGoodsAttribute[$kk]['attr_name'] = $vv['attr_name'];
			$TpGoodsAttribute[$kk]['attr_values'] = explode(PHP_EOL,$vv['attr_values']);
        }
		return $TpGoodsAttribute;
	}

    //更新版衣鞋家纺
    public function getTpGoodsAttriyxjf($cat){
        $res = D('Goodscate')->find($cat);

        $TpGoodsType = M('TpGoodsType')->where(array('id'=>$res['type_id']))->find();
        $TpGoodsAttributes = M('TpGoodsAttribute')->where(array('type_id'=>$TpGoodsType['id'],'attr_input_type'=>1))->select();
        foreach($TpGoodsAttributes as $k => $val){
            if(empty($val['attr_values']) || trim($val['attr_values']) == ''){ 
                unset($TpGoodsAttributes[$k]);
            }
            
        }
        foreach($TpGoodsAttributes as $kk => $vv){
            $TpGoodsAttribute[$kk]['attr_id'] = $vv['attr_id'];
            $TpGoodsAttribute[$kk]['attr_name'] = $vv['attr_name'];
            $TpGoodsAttribute[$kk]['attr_values'] = explode(PHP_EOL,$vv['attr_values']);
        }
        return $TpGoodsAttribute;
    }
   
    public function loaddata(){
        $Goods = D('Goods');
        import('ORG.Util.Page');
      
        $area = (int) $this->_param('area');
        if($area){
            $map['area_id'] = $area;
			$this->assign('area', $area);
			$linkArr['area'] = $area;
        }
		
		
		$business = (int) $this->_param('business');
        if($business){
            $map['business_id'] = $business;
			$this->assign('business', $business);
			$linkArr['business'] = $business;
        }
		
   
		
		$shop_id = (int) $this->_param('shop_id');
        if($shop_id){
            $map['shop_id'] = $shop_id;
			$this->assign('shop_id', $shop_id);
			$linkArr['shop_id'] = $shop_id;
        }
		
		$user_id = (int) $this->_param('user_id');
        if($user_id){
			$this->assign('user_id', $user_id);
			$linkArr['user_id'] = $user_id;
        }
		
		$type = (int) $this->_param('type');
        if($type){
			$this->assign('type', $type);
			$linkArr['type'] = $type;
        }
		
        $map['audit'] = 1;
        $map['closed'] = 0;
        $map['end_date'] = array('egt', TODAY);
		$map['city_id'] = $this->city_id;
		
		
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title|intro'] = array('LIKE', '%' . $keyword . '%');
			$this->assign('keyword', $keyword);
			$linkArr['keyword'] = $keyword;
        }
		
		
		//开始组装数组
		$query_string  = explode ('/',$_SERVER["QUERY_STRING"]);
		$arr = array();
		foreach($query_string as $key=>$values){
			if(strstr( $values , 'values_' ) !== false){
				array_push($arr, $values);
			}
		}
		
		foreach($arr as $k=>$v){
			$arr[$v] = $this->_param($v,'htmlspecialchars');
			$query[$v] = $arr[$v];
			$this->assign('query',$query);
			$linkArr[$v] = $arr[$v];
		}
		
		$array = array();
		foreach($query as $kk=>$vv){
			$explode = explode('_',$kk); 
			$array[$kk]['attr_id'] = $explode['1'];
			$array[$kk]['attr_value'] = $vv;
		}
		foreach($array as $val){
            $attr_values[$val['attr_value']] = $val['attr_value'];
        }

		$maps['attr_value']  = array('IN',$attr_values);
		//$TpGoodsAttr = M('TpGoodsAttr')->where($map)->group('attr_value')->select();
		$TpGoodsAttr = M('TpGoodsAttr')->where($maps)->select();
		
		
		
		$result= array();
		foreach($TpGoodsAttr as $key => $info){
		 	$result[$info['goods_id']][] = $info;
		}
		
		foreach($result as $kkk => $vvv){
			foreach($vvv as $k2 => $v2){
				$attr_valuess[$kkk][$k2] = $v2['attr_value'];
			}
        }
		
		$implode = implode('_',$attr_values);
		
		foreach($attr_valuess as $k3 => $v3){
			$implodes = implode('_',$v3);
			if($implodes != $implode){
				unset($attr_valuess[$k3]);
			}
        }
		
		
		foreach($attr_valuess as $k4 => $v4){
            $goods_ids[$k4] = $k4;
        }
		if($array){
			$map['goods_id'] = array('IN',$goods_ids);
		}
		//多属性搜索结束
		
		
		
		$cate_id = (int) $this->_param('cate_id');
		$cat = (int) $this->_param('cat');
        if($cate_id){
			if($cate_id){
				if(empty($array)){
					$map['cate_id'] = $cate_id;
				}
				$linkArr['cate_id'] = $cate_id;
				$this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cate_id));
			}
        }else{
			$catids = D('Goodscate')->getChildren($cat);
			$this->assign('TpGoodsAttribute',$TpGoodsAttribute = $this->getTpGoodsAttributes($cat));
            if(!empty($catids)){
				if(empty($array)){
					$map['cate_id'] = array('IN', $catids);
				}
            }else{
                $map['cate_id'] = $cate_id;
				$linkArr['cate_id'] = $cate_id;
				
            }
			
		}
		$this->assign('cat', $cat);
		$this->assign('cate_id', $cate_id);
	
        $count = $Goods->where($map)->count();
        $Page = new Page($count, 10);
        $show = $Page->show();
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if ($Page->totalPages < $p) {
            die('0');
        }
		$lat = addslashes(cookie('lat'));
        $lng = addslashes(cookie('lng'));
        if(empty($lat) || empty($lng)){
            $lat = $this->city['lat'];
            $lng = $this->city['lng'];
        }
		
		$order = $this->_param('order', 'htmlspecialchars');
        switch($order){
            case '1':
                $orderby = array('top_time' => 'desc');
                break;
            case '2':
                $orderby = array('old_num' => 'asc');
                break;
            case '3':
                $orderby = array('mall_price' => 'desc');
                break;
            case '4':
                $orderby = array('mall_price' => 'asc');
                break;
			case '5':
                $orderby = array('top_time' => 'desc','orderby' =>'asc');
                break;
			default:
                $orderby = array('top_time' => 'desc','check_price' =>'desc','mall_price'=>'asc','huodong'=>0);
                break;
        }
		
		
		
        $list = $Goods->where($map)->order($orderby)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        
        foreach ($list as $k => $val){
            if($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
			$Shop = D('Shop')->find($val['shop_id']);
			$val['d'] = getDistance($lat, $lng, $Shop['lat'], $Shop['lng']);
            $list[$k] = $val;
        }

        //$this->assign('list', $list);
        //$this->assign('page', $show);
        $this->display();
    }
    //商品收藏
	public function favorites(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $goods_id = (int) $this->_get('goods_id');
        if (!($detail = D('Goods')->find($goods_id))) {
            $this->tuMsg('没有该商品');
        }
        if ($detail['closed']) {
            $this->tuMsg('该商品已经被删除');
        }
        if (D('Goodsfavorites')->check($goods_id, $this->uid)) {
            $this->tuMsg('您已经收藏过了');
        }
        $data = array('goods_id' => $goods_id, 'user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => get_client_ip());
        if (D('Goodsfavorites')->add($data)) {
            $this->tuMsg('恭喜您收藏成功', U('mall/detail', array('goods_id' => $goods_id)));
        }
        $this->tuMsg('收藏失败');
    }
    //立即购买
    public function buy($goods_id){
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
			$this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!($detail = D('Goods')->find($goods_id))) {
			$this->ajaxReturn(array('status' => 'error', 'msg' => '商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->tuMsg('该商品不存在');
        }
        if ($detail['end_date'] < TODAY) {
			$this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $goods_spec= cookie('goods_spec');

        $num = (int) $this->_get('num');
        $spec_key =  $this->_get('spec_key');
        if (empty($num) || $num <= 0) {
            $num = 1;
        }
        $is_spec_stock = is_spec_stock($goods_id,$spec_key,$num);
        if(!$is_spec_stock){
        	$this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
        }
        if ($detail['num'] < $num) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
        }
        $goods_spec_v = $goods_id.'|'.$spec_key; //重新组合那个 商品id和那个啥规格键
            if (isset($goods_spec[$goods_spec_v])) {
	            $goods_spec[$goods_spec_v] += $num;
	        } else {
	            $goods_spec[$goods_spec_v] = $num;
	        }
        $key[$goods_id]=$spec_key;//规格

        cookie('goods_spec', $goods_spec, 604800);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功,正在跳转到购物车', 'url' => U('mall/cart')));
    }

    //拼单立即购买
    public function buy2($goods_id,$users_id){
        $goods_id = (int) $goods_id;
        $users_id = (int) $users_id;
        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!($detail = D('Goods')->find($goods_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->tuMsg('该商品不存在');
        }
        if ($detail['end_date'] < TODAY) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $goods_spec= cookie('goods_specs');

        $num = (int) $this->_get('num');
        $spec_key =  $this->_get('spec_key');
        if (empty($num) || $num <= 0) {
            $num = 1;
        }
        $is_spec_stock = is_spec_stock($goods_id,$spec_key,$num);
        if(!$is_spec_stock){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
        }
        if ($detail['num'] < $num) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
        }
        $goods_spec_v = $goods_id.'|'.$spec_key; //重新组合那个 商品id和那个啥规格键
        if (isset($goods_spec[$goods_spec_v])) {
            $goods_spec[$goods_spec_v] += $num;
        } else {
            $goods_spec[$goods_spec_v] = $num;
        }
        $key[$goods_id]=$spec_key;//规格

        cookie('goods_specs', $goods_spec, 604800);
        cookie('users_id',$users_id,604800);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功,正在跳转到购物车', 'url' => U('mall/pindan_cart')));
    }

    //拼单
    public function pindan($goods_id,$users_id){
        $goods_id = (int) $goods_id;
        $users_id = (int) $users_id;
        if (empty($goods_id)) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择产品'));
        }
        if (!($detail = D('Goods')->find($goods_id))) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '商品不存在'));
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->tuMsg('该商品不存在');
        }
        if ($detail['end_date'] < TODAY) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
        }
        $goods_spec= cookie('goods_specs');
        $num = (int) $this->_get('num');
        $spec_key =  $this->_get('spec_key');
        if (empty($num) || $num <= 0) {
            $num = 1;
        }
        $is_spec_stock = is_spec_stock($goods_id,$spec_key,$num);
        if(!$is_spec_stock){
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
        }
        if ($detail['num'] < $num) {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
        }
        $goods_spec_v = $goods_id.'|'.$spec_key; //重新组合那个 商品id和那个啥规格键
        if (isset($goods_spec[$goods_spec_v])) {
            $goods_spec[$goods_spec_v] += $num;
        } else {
            $goods_spec[$goods_spec_v] = $num;
        }
        $key[$goods_id]=$spec_key;//规格
        cookie('goods_specs', $goods_spec, 604800);
        cookie('users_id',$users_id,604800);
        $this->ajaxReturn(array('status' => 'success', 'msg' => '正在为您跳转，请确认支付发起拼单', 'url' => U('mall/pindan_cart')));
    }

    //拼单购物车
    public function pindan_cart(){
        $goods = cookie('goods');
        $back = end($goods);
        $back = key($goods);
        $goods_spec = cookie('goods_specs');
        $this->assign('back', $back);


        if(empty($goods_spec)) {
            $this->error('亲还没有选购产品呢!', U('mall/index'));
        }

        $spec_keys = array_keys($goods_spec);
        $spec_arr = $this ->spec_to_arr($goods_spec);
        $goods_ids= $this->get_goods_ids($goods_spec);

        foreach($goods_ids as $k=> $v){
            $cart_goods[] = D('Goods')->itemsByIds($v);
        }
        $shop_ids = array();
        foreach ($cart_goods as $k => $val) {
            foreach($val as $key => $det){
                $cart_goods[$k][$key]['buy_num'] = $spec_arr[$k][2];//购买数量
                $cart_goods[$k][$key]['sky'] =  $spec_arr[$k][1];
                $cart_goods[$k][$key]['goods_spec'] = $spec_keys[$k];
                $shop_ids[$det['shop_id']] = $det['shop_id'];
                if(!empty($cart_goods[$k][$key][sky])){
                    //通过这个sky来查多属性里面的价格  其实也就是一条数据
                    $spt=D('TpSpecGoodsPrice')->where("`key`='{$cart_goods[$k][$key][sky]}' and `goods_id`='{$cart_goods[$k][$key][goods_id]}'")->find();
                    $cart_goods[$k][$key]['ping_money']=$spt['pin_price'];
                    $cart_goods[$k][$key]['key_name']=$spt['key_name'];
                }
            }

        }

        //	print_r($cart_goods);
        $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('cart_goods', $cart_goods);

        $this->display();
    }

    //确认
    public function order_pin(){
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }

        $users_id=cookie('users_id');
        $num = $this->_post('num', false);

        $goods_ids = array();
        foreach ($num as $k => $val) {
            $val = (int) $val;
            if (empty($val)) {
                unset($num[$k]);
            } elseif ($val < 1 || $val > 99) {
                unset($num[$k]);
            } else {
                $spec_keys[]=$k;
                $spec_arr[] = explode('|',$k);
                $spec_temp = explode('|',$k);
                $goods_ids[$k][] = (int)$spec_temp[0];
            }
        }
        foreach($goods_ids as $v){
            $goods[] = D('Goods')->itemsByIds($v);
        }
        foreach ($goods as $k => $v) {
            foreach($v as $key => $val){
                if ($val['closed'] != 0 || $val['audit'] != 1 || $val['end_date'] < TODAY) {
                    unset($goods[$key]);
                }
                //把这个商品的规格存进数组
                $goods[$k][$key][sky]=$spec_arr[$k][1]; //把后面的规格存进来 148_150
                $goods[$k][$key]['goods_spec'] = $spec_keys[$k];//整个存一下
                if(!empty($goods[$k][$key][sky])){
                    //改变价格
                    $spt=D('TpSpecGoodsPrice')->where("`key`='{$goods[$k][$key][sky]}' and `goods_id`='{$goods[$k][$key][goods_id]}'")->find();
                    $goods[$k][$key]['mall_price']=$spt['pin_price'];
                    $goods[$k][$key]['key_name']=$spt['key_name'];//建的中文名
                }
            }
        }
        if (empty($goods)) {
            $this->tuMsg('很抱歉，您提交的产品暂时不能购买');
        }
        //下单前检查库存
        foreach ($goods as $val) {
            $val = reset($val);
            //二维数组 取第一个
            //加入购物车时候检查规格库存  如果不走这里他会走下面的
            $is_spec_stock = is_spec_stock($val[goods_id],$val[sky],$num[$val['goods_spec']]);
            if(!$is_spec_stock){
                $spec_one_num =  get_one_spec_stock($val[goods_id],$val[sky]);
                $this->tuMsg('亲！规格为<' . $val['key_name']. '>的商品库存不够了,只剩' . $spec_one_num . '件了');
            }
            if ($val['num'] < $num[$val['goods_spec']]) {
                $this->tuMsg('亲！商品<' . $val['title'] . '>库存不够了,只剩' . $val['num'] . '件了');
            }
        }
        $tprice = 0;
        $all_integral = $total_mobile = 0;
        $ip = get_client_ip();
        $total_canuserintegral = $ordergoods = $total_price = array();
        foreach ($goods as $val) {
            $val = reset($val);
            //二维数组 取第一个
            //二次开发的 其他人可能看不懂 之前是  $num[$val['goods_id']]  这个我前面那个num已经改过了 但是下面的代码不想改了 所以统一赋值一下
            //前面已经通过这个规格的键值来重新传了
            $num[$val['goods_id']] = $num[$val['goods_spec']];
            $price1 = $val['mall_price'] * $num[$val['goods_id']];
           
            if(empty($num[$val['goods_id']])){
                $price2=$val['ping_money'];
            }
            $price=$price1+$price2;
            $js_price = $val['settlement_price'] * $num[$val['goods_id']];
            $mobile_fan = $val['mobile_fan'] * $num[$val['goods_id']]; //每个商品的手机减少的钱
            $canuserintegral = $val['use_integral'] * $num[$val['goods_id']];

            $m_price = $price - $mobile_fan;
            $tprice += $m_price;
            $total_mobile += $mobile_fan;
            $all_integral += $canuserintegral;
            $ordergoods[$val['shop_id']][] = array(
                'goods_id' => $val['goods_id'],
                'shop_id' => $val['shop_id'],
                'num' => $num[$val['goods_id']],
                'kuaidi_id' => $val['kuaidi_id'],
                'price' => $val['mall_price'],
                'redbag_money' =>0,
                'useEnvelope_id' =>0,
                'total_price' => $price,//减去对应商家的红包金额
                'mobile_fan' => $mobile_fan,
                'express_price' => 0, //单个商品运费总价
                'is_mobile' => 1,
                'js_price' => $js_price,
                'create_time' => NOW_TIME,
                'create_ip' => $ip,
                'key' => $val['sky'],
                'key_name' => $val['key_name']
            );
            $total_canuserintegral[$val['shop_id']] += $canuserintegral; //不同商家可使用积分
            $total_price[$val['shop_id']] += $price; //不同商家的总价格

        }
        $order = array('user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => $ip, 'is_mobile' => 1);
        $tui = cookie('tui');
        if (!empty($tui)) {
            $tui = explode('_', $tui);
            $tuiguang = array('uid' => (int) $tui[0], 'goods_id' => (int) $tui[1]);
        }
        $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);//收货地址部分重写
        $order_ids = array();
        foreach ($ordergoods as $k => $val) {
            $order['shop_id'] = $k;
            $order['total_price'] = $total_price[$k];
            $order['mobile_fan'] = 0;
            $order['can_use_integral'] = $total_canuserintegral[$k];
            $order['express_price'] = 0;//写入运费
            $order['address_id'] = $defaultAddress['id'];//写入快递ID

            $val[0]['express_price'] = 0;//写入运费
            $val[0]['address_id'] = $defaultAddress['id'];//写入快递
            $shop = D('Shop')->find($k);
            $order['is_shop'] = (int) $shop['is_goods_pei'];
            if ($order_id = D('Order')->add($order)) {//这里写入订单表了
                $order_ids[] = $order_id;

                foreach ($val as $k1 => $val1) {

                    $Goods = D('Goods')->find($val1['goods_id']);
                    $val1['cate_id'] = $Goods['cate_id'];
                    $val1['weight'] = $Goods['weight'];
                    $val1['order_id'] = $order_id;
                    if(!empty($tuiguang)) {
                        if ($tuiguang['goods_id'] == $val1['goods_id']) {
                            $val1['tui_uid'] = $tuiguang['uid'];
                        }
                    }
                    D('Ordergoods')->add($val1);
                }
            }
        }
        cookie('goods_specs', null);// 清空 cookie
        if (count($order_ids) > 1) {
            $need_pay = D('Order')->useIntegral($this->uid, $order_ids);

            $logs = array(
                'type' => 'goods',
                'user_id' => $this->uid,
                'order_id' => 0,
                'order_ids' => join(',', $order_ids),
                'code' => '',
                'need_pay' => $need_pay,
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
                'is_paid' => 0,
                'is_pin'=>$users_id
            );
            cookie('users_id', null);// 清空 cookie
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $this->tuMsg('合并下单成功，接下来选择支付方式和配送地址', U('mall/pin_paycode', array('log_id' => $logs['log_id'])));
        } else {
            $this->tuMsg('下单成功，接下来选择支付方式和配送地址', U('mall/pay_pin', array('order_id' => $order_id,'address_id'=>$defaultAddress['id'])));
        }
    }

    //支付
    public function pay_pin(){
            if (empty($this->uid)) {
                $this->error('登录状态失效!', U('passport/login'));
                die;
            }
            $this->check_mobile();
            cookie('goods', null); //销毁cookie
            $order_id = (int) $this->_get('order_id');
            $order = D('Order')->find($order_id);
            if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
                $this->error('该订单不存在');
                die;
            }

            $ordergood = D('Ordergoods')->where(array('order_id' => $order_id))->select();
            $goods_id = $shop_ids = array();
            foreach ($ordergood as $k => $val) {
                $goods_id[$val['goods_id']] = $val['goods_id'];
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $this->assign('goods', D('Goods')->itemsByIds($goods_id));
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
            $this->assign('ordergoods', $ordergood);

            //收货地址部分重写
            if (false == $defaultAddress = D('Paddress')->order_address_id($this->uid,$order_id)) {
                $this->error('获取用户地址出错，请先去会员中心添加商城地址后下单');
            }
            $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => pingdan, 'order_id' => $order_id));
            $this -> assign('defaultAddress', $defaultAddress);
            $this -> assign('changeAddressUrl', $changeAddressUrl);
            //重写结束

             $need_pay = $order['total_price'] + $order['express_price'];

            $this->assign('need_pay', $need_pay);
            $this->assign('order', $order);
            $this->assign('payment', D('Payment')->getPayments(true));
            $this->display();
    }


    public function cartadd($goods_id){
        $shop_id = (int) $this->_param('shop_id');
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            die('请选择产品');
        }
        if (!($detail = D('Goods')->find($goods_id))) {
            die('该商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            die('该商品不存在');
        }
        if ($detail['end_date'] < TODAY) {
            die('该商品已经过期，暂时不能购买');
        }
        $goods = cookie('goods');
        if (isset($goods[$goods_id])) {
            $goods[$goods_id] = $goods[$goods_id] + 1;
        } else {
            $goods[$goods_id] = 1;
        }
        cookie('goods', $goods);
        die('0');
    }
    public function cartadd2(){
        if (IS_AJAX) {
            $shop_id = (int) $_POST['shop_id'];
            $goods_id = (int) $_POST['goods_id'];
            $goods_spec= cookie('goods_spec');
            $spec_key =  $_POST['spec_key'];
            $num =  $_POST['num'];
            if (empty($goods_id)) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '请选择商品'));
            }
            if (!($detail = D('Goods')->find($goods_id))) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }
            if ($detail['closed'] != 0 || $detail['audit'] != 1) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品不存在'));
            }
            if ($detail['end_date'] < TODAY) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '该商品已经过期，暂时不能购买'));
            }
            if ($detail['num'] <= 0) {
                $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！没有库存了！'));
            }
            $goods_spec_v = $goods_id.'|'.$spec_key; 
			//重新组合那个 商品id和那个啥规格键
            //加入购物车时候检查规格库存  如果不走这里他会走下面的
	        $is_spec_stock = is_spec_stock($goods_id,$spec_key,$num);

	        if(!$is_spec_stock){
	        	$this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该规格库存不足了，少买点吧！'));
	        }
	        if ($detail['num'] < $num) {
	            $this->ajaxReturn(array('status' => 'error', 'msg' => '亲！该商品只剩' . $detail['num'] . '件了，少买点吧！'));
	        }
            if (isset($goods_spec[$goods_spec_v])) {
	            $goods_spec[$goods_spec_v] += $num;
	        } else {
	            $goods_spec[$goods_spec_v] = $num;
	        }
         	cookie('goods_spec', $goods_spec, 604800);
            $goods = cookie('goods');
            if (isset($goods[$goods_id])) {
                $goods[$goods_id] = $goods[$goods_id] + 1;
            } else {
                $goods[$goods_id] = 1;
            }
            $this->ajaxReturn(array('status' => 'success', 'msg' => '加入购物车成功'));
        }
    }

    //商城购物车
    public function cart(){
		
        $goods = cookie('goods');
        $back = end($goods);
        $back = key($goods);
        $goods_spec = cookie('goods_spec');
        $this->assign('back', $back);
		
        if(empty($goods_spec)) {
            $this->error('亲还没有选购产品呢!', U('mall/index'));
        }
		
        $spec_keys = array_keys($goods_spec);
        $spec_arr = $this ->spec_to_arr($goods_spec);           
        $goods_ids= $this->get_goods_ids($goods_spec);

        foreach($goods_ids as $k=> $v){
          $cart_goods[] = D('Goods')->itemsByIds($v);
        }
        $shop_ids = array();
        foreach ($cart_goods as $k => $val) {
            foreach ($val as $key => $det) {
                //查询这个商家里面未使用的红包
                $useEnvelope = D('Order')->GetuseEnvelope($this->uid, $det['shop_id']);
                //获取这个商家里面未使用的优惠卷
                $user_coupon = M('coupon_download')
                    ->alias('user_coupon')
                    ->join('tu_coupon c on(user_coupon.coupon_id=c.coupon_id)','left')
                    ->field('user_coupon.download_id,user_coupon.coupon_id,c.title')
                    ->where(['user_coupon.user_id' => $this->uid, 'user_coupon.shop_id' => $det['shop_id'], 'user_coupon.is_used' =>0])
                    ->select();
                $cart_goods[$k][$key]['buy_num'] = $spec_arr[$k][2];//购买数量
                $cart_goods[$k][$key]['sky'] = $spec_arr[$k][1];
                $cart_goods[$k][$key]['goods_spec'] = $spec_keys[$k];
                $cart_goods[$k][$key]['useEnvelope'] = $useEnvelope;
                $cart_goods[$k][$key]['user_coupon'] = $user_coupon;
                $shop_ids[$det['shop_id']] = $det['shop_id'];
                if (!empty($cart_goods[$k][$key]['sky'])) {
                    //通过这个sky来查多属性里面的价格  其实也就是一条数据
                    $spt = D('TpSpecGoodsPrice')->where("`key`='{$cart_goods[$k][$key]['sky']}' and `goods_id`='{$cart_goods[$k][$key]['goods_id']}'")->find();
                    $cart_goods[$k][$key]['mall_price'] = $spt['price'];
                    $cart_goods[$k][$key]['key_name'] = $spt['key_name'];
                }
            }

        }
		
	//	print_r($cart_goods);
        $this->assign('cart_shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('cart_goods', $cart_goods);
		
        $this->display();
    }

    private function spec_to_arr($goods_spec){

    	    $spec_key = array_keys($goods_spec);

            foreach($spec_key as $k=> $v){
            	$spec_arr[$k] = explode('|',$v); 
            	$spec_arr[$k][]= $goods_spec[$v];
            }
            return $spec_arr;
            
    }
    private function get_goods_ids($goods_spec){
    		$spec_arr = $this -> spec_to_arr($goods_spec);
    		foreach($spec_arr as $k => $v){
    				$goods_ids[] = $v[0];
    			}		
    		return $goods_ids;
    }

    public function detail($goods_id) {
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!($detail = D('Goods')->find($goods_id))) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }
        $shop_id = $detail['shop_id'];
        $shops =D('Shop')->find($shop_id);

        if($detail['is_tui'] ==1 && $shops['user_id'] != $this->uid){
            D('Goods')->check_price($goods_id);
        }

        $is_shop = D('Users')->get_is_shop($this->uid);
        $user_type = $is_shop === true ? 2 : 1;
        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo=$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");
        $usergroup=$rs[0]['memberIdx'];
        $shopname=$re_shop[0]['memberIdx'];
        //聊天默认为好友
        //好友加商家
        $default=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        if(empty($default)){
            $addgroup=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($usergroup,'商城好友')");
        }
        $default2=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$usergroup and mygroupName='商城好友'");
        $groupid=$default2[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default3=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$shopname and mygroupIdx=$groupid");
        if(empty($default3)){
            $addfriend=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid,$shopname)");

        }

        //商家加陌生人
        $default4=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        if(empty($default4)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_group (memberIdx,mygroupName) values($shopname,'商城好友')");
        }
        $default5=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select mygroupIdx from tb_my_group where memberIdx=$shopname and mygroupName='商城好友'");
        $groupid2=$default5[0]['mygroupIdx'];
        //判断是否存在，存在就不添加
        $default6=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select * from tb_my_friend where memberIdx=$usergroup and mygroupIdx=$groupid2");
        if(empty($default6)){
            M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("insert into  tb_my_friend (mygroupIdx,memberIdx) values($groupid2,$usergroup)");
        }

        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['title'].'--'.$detail['intro'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );
//        $datas2=array(
//            'chat_user_id' => $rs[0]['memberIdx'],
//            'token'=>$md,
//            'user_logo'=>$logo,
//            'chat_shop_id'=>$re_shop[0]['memberIdx'],
//            'shop_name'=>$shops['shop_name'],
//        );
        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
//        $params2 = http_build_query($datas2);
//        //查询聊天记录是否存在
//        $kefu=M('kefu')->where(['user_id'=>$this->uid,'shop_id'=>$detail['shop_id'],'goods_id'=>$goods_id,'type'=>1])->find();
//        if(empty($kefu)){
//            $arr=array(
//              'shop_id'=>$detail['shop_id'],
//                'user_id'=>$this->uid,
//                'goods_id'=>$goods_id,
//                'type'=>1
//            );
//            M('kefu')->add($arr);
//

            $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
            $this->assign('service_url', $service_url);
//        }else{
//            $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params2;
//            $this->assign('service_url', $service_url);
//        }



        $youhuijuan = D('Coupon')->where(array('shop_id'=>$shop_id,'audit'=>1,'closed'=>0))->select();
        $this->assign('youhuijuan',$youhuijuan);
        $xiangsi=D('Goods')->where(array('goods_id'=>$goods_id))->find();
        $this->assign('list',D('Goods')->where(array('cate_id'=>$xiangsi['cate_id'],'audit'=>1,'closed'=>0,'huodong'=>0))->select());

        //查询拼团商品
        $wheres=array('is_pintuan'=>1,'audit'=>1,'closed'=>0);
        $tuan_goods=D('Goods')->where($wheres)->select();
        $count=D('Goods')->where($wheres)->count();
        $shops_id = $shop_id2 = array();
        foreach ($tuan_goods as $val){
          $shops_id[]=$val['shop_id'];
        }
        $this->assign('count',$count);
        $this->assign('tuan',$tuan_goods);
        $this->assign('shops',D('Shop')->itemsByIds($shops_id));

        $tuan_goods2=D('Goods')->where($wheres)->order('min_price desc')->limit(0,10)->select();
        foreach ($tuan_goods2 as $val){
            $shop_id2[]=$val['shop_id'];
        }
        $this->assign('tuan2',$tuan_goods2);
        $this->assign('shop2',D('Shop')->itemsByIds($shop_id2));
		// print_r($detail);die;s
        $this->assign('detail', $detail);
        $this->assign('shop', D('Shop')->find($shop_id));
        $filter_spec = $this->get_spec($goods_id); //获取商品规格参数        
        $goodsss=M('goods')->find($goods_id);
        $goodsss[mall_price]=$goodsss[mall_price];

        $spec_goods_price  = M('TpSpecGoodsPrice')->where("goods_id = $goods_id")->getField("key,price,pin_price,store_count"); // 规格 对应 价格 库存表
        if($spec_goods_price != null){
        	$this->assign('spec_goods_price', json_encode($spec_goods_price,JSON_UNESCAPED_UNICODE)); // 规格 对应 价格 库存表
      	}else{
            $this->assign('spec_goods_price',0);
        }
               $yh=$goodsss[yh];
                if($yh!= '0'){
                        $yh=explode(PHP_EOL,$yh);
                            for ($i=0; $i < count($yh)-1;$i++){ 
                               $yh[s][]=explode(',',$yh[$i]);                   
                            }
                    foreach($yh[s] as $k2=>$vo){                 
                        foreach($vo as $k2=>$v2){
                            $rs[$k2][] = $v2;    
                        }
                     }
                    $goodsss['zks'][]=$rs[0];
                    $goodsss['zks'][]=$rs[1];
                }
        
        $this->assign('filter_spec',$filter_spec);
        $this->assign('goods', $goodsss);
        $pingnum = D('Goodsdianping')->where(array('goods_id' => $goods_id, 'show_date' => array('ELT', TODAY)))->count();
        $this->assign('pingnum', $pingnum);
        $score = (int) D('Goodsdianping')->where(array('goods_id' => $goods_id))->avg('score');
        if ($score == 0) {
            $score = 5;
        }
        $this->assign('score', $score);
		if(($detail['is_vs1'] || $detail['is_vs2'] || $detail['is_vs3'] || $detail['is_vs4'] || $detail['is_vs5'] || $detail['is_vs6']) || $detail['is_vs7'] || $detail['is_vs8'] || $detail['is_vs9']==1 ){
			 $this->assign('is_vs', $is_vs = 1);
		}else{
			$this->assign('is_vs', $is_vs = 0);
		}
        $this->assign('pics', D('Goodsphoto')->limit(0,1)->getPics($detail['goods_id']));
		$this->assign('count_goodsfavorites',$count_goodsfavorites = D('Goodsfavorites')->where(array('goods_id'=>$detail['goods_id']))->count());
		$this->assign('goodsfavorites', $goodsfavorites = D('Goodsfavorites')->check($goods_id, $this->uid));//检测自己是不是收藏
		$map_coupon_where = array('shop_id' =>$detail['shop_id'], 'audit' => 1,'closed' => 0, 'expire_date' => array('EGT', TODAY));
		$this->assign('coupon',$coupon = D('Coupon')->order('coupon_id desc ')->where($map_coupon_where )->limit(0, 2)->select());
		$this->assign('mall_coupon_null',$mall_coupon_null = D('Coupon')->order('coupon_id desc ')->where($map_coupon_where)->count());
		
		$this->assign('goods_attribute',$goods_attribute = M('tpGoodsAttribute')->getField('attr_id,attr_name'));//属性值     
		$this->assign('goods_attr_list',$goods_attr_list = M('tpGoodsAttr')->where("goods_id = $goods_id")->select());//属性列表
        $this->display();
    }

    //客服
    public function kefu(){
        if (IS_AJAX) {
            $shop_id = (int)$_POST['shop_id'];
            $goods_id = (int)$_POST['goods_id'];
            //查询聊天记录是否存在
        $kefu=M('kefu')->where(['user_id'=>$this->uid,'shop_id'=>$shop_id,'goods_id'=>$goods_id,'type'=>1])->find();
        if(empty($kefu)) {
            $arr = array(
                'shop_id' =>$shop_id,
                'user_id' => $this->uid,
                'goods_id' => $goods_id,
                'type' => 1
            );
            M('kefu')->add($arr);
        }
        }

    }

    //秒杀(平台的)
    public function secondkill($goods_id){
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        $stock=D('AdminGoods')->where(['goods_id'=>$goods_id])->find();


        //判断库存是否大于0
        if($stock['num1']>0){
            $ordesr=D('Order')->where(['user_id'=>$this->uid,'goods_id'=>$goods_id,'status'=>0])->select();
            if(!empty($ordesr)){
                $this->ajaxReturn(array('status' => 'error', 'msg' => '禁止重复抢购!'));
            }


            $data=array(
                'user_id'=>$this->uid,
                'goods_id'=>$goods_id,
                'type'=>1,
                'shop_id'=>$stock['shop_id'],
                'time'=>NOW_TIME
            );
            $secondk=D('GoodsHurry')->add($data);//添加进虚拟表
            if(false !== $secondk){
                //修改库存数量
                $sum=$stock['num1']-1;
                D('AdminGoods')->where(['goods_id'=>$goods_id])->save(['num1'=>$sum]);
                $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);
                if($stock['is_reight']==1){
                    $pay=$stock['yunfei'];
                }else{
                    $pay=0;
                }

                $needpay=$stock['mall_price'];
                $arr=array(
                    'shop_id'=>$stock['shop_id'],
                    'user_id'=>$this->uid,
                    'total_price'=>$needpay,
                    'create_time'=>NOW_TIME,
                    'express_price'=>$pay,
                    'need_pay'=>round($needpay+$pay,2),
                    'address_id'=>$defaultAddress['id']
                );
                $order=D('Order')->add($arr);
                $arr2=array(
                    'order_id'=>$order,
                    'goods_id'=>$goods_id,
                    'shop_id'=>$stock['shop_id'],
                    'cate_id'=>$stock['cate_id'],
                    'num'=>1,
                    'kuaidi_id'=>$stock['kuaidi_id'],
                    'price'=>$needpay,
                    'total_price'=>$needpay,
                    'express_price'=>$pay,
                    'key_name'=>$stock['guige']
                );
                D('Ordergoods')->add($arr2);
                D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$goods_id,'type'=>4,'is_pingtai'=>1])->save(['close'=>1]);
                //抢购成功，跳转到支付页面
                $this->ajaxReturn(array('status' => 'success', 'msg' => '抢购成功!','url'=>U('user/goods/index',array('index'=>0))));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '抢购失败!'));
            }

        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '活动已结束,已售'.$stock['num'].'台请参与下轮活动'));
        }

    }



    //天天特价抢购详情页(平台的)
    public function details($goods_id) {
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!($detail = D('AdminGoods')->find($goods_id))) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }
        //查询点赞是否够数
        $fabulous=D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$goods_id,'type'=>4,'type_id'=>1,'close'=>0,'is_pingtai'=>1])->find();
        $this->assign('fabulous',$fabulous);
        //查询时间是否满足

//        $mgoods=D('MallactivityGoods')->where(['goods_id'=>$goods_id,'colsed'=>0,'type'=>1])->find();
//        $time=D('MallactivitySpecia')->where(['time_id'=>$mgoods['time_id']])->find();

        $shijian=date("H:i:s",time());
        if('06:00:00'>$shijian){
            $time='6:00:00';
        }elseif('12:00:00'>$shijian){
            $time='12:00:00';
        }elseif ('20:00:00'>$shijian){
            $time='20:00:00';
        }elseif ('23:00:00'){
            $time='23:00:00';
        }
        $this->assign('time',$time);
        $shop_id = $detail['shop_id'];
        $shops =D('Shop')->find($shop_id);
        $is_shop = D('Users')->get_is_shop($this->uid);
        $user_type = $is_shop === true ? 2 : 1;
        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo='https://'.$_SERVER['HTTP_HOST'].$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");
        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['title'].'--'.$detail['intro'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );
        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
        $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
        $this->assign('service_url', $service_url);


        $youhuijuan = D('Coupon')->where(array('shop_id'=>$shop_id,'audit'=>1,'closed'=>0))->select();
        $this->assign('youhuijuan',$youhuijuan);
        $xiangsi=D('Goods')->where(array('goods_id'=>$goods_id))->find();
        $this->assign('list',D('Goods')->where(array('cate_id'=>$xiangsi['cate_id'],'audit'=>1,'closed'=>0))->select());

        //查询拼团商品
        $wheres=array('is_pintuan'=>1,'audit'=>1,'closed'=>0);
        $tuan_goods=D('Goods')->where($wheres)->select();
        $count=D('Goods')->where($wheres)->count();
        $shops_id = $shop_id2 = array();
        foreach ($tuan_goods as $val){
            $shops_id[]=$val['shop_id'];
        }
        $this->assign('count',$count);
        $this->assign('tuan',$tuan_goods);
        $this->assign('shops',D('Shop')->itemsByIds($shops_id));

        $tuan_goods2=D('Goods')->where($wheres)->order('min_price desc')->limit(0,10)->select();
        foreach ($tuan_goods2 as $val){
            $shop_id2[]=$val['shop_id'];
        }
        $this->assign('tuan2',$tuan_goods2);
        $this->assign('shop2',D('Shop')->itemsByIds($shop_id2));
        // print_r($detail);die;s
        $this->assign('detail', $detail);
        $this->assign('shop', D('Shop')->find($shop_id));

        $this->assign('score', $score);
        if(($detail['is_vs1'] || $detail['is_vs2'] || $detail['is_vs3'] || $detail['is_vs4'] || $detail['is_vs5'] || $detail['is_vs6']) || $detail['is_vs7'] || $detail['is_vs8'] || $detail['is_vs9']==1 ){
            $this->assign('is_vs', $is_vs = 1);
        }else{
            $this->assign('is_vs', $is_vs = 0);
        }
        $this->assign('pics', D('AdminGoodsPic')->getPics($detail['goods_id']));
        $this->assign('count_goodsfavorites',$count_goodsfavorites = D('Goodsfavorites')->where(array('goods_id'=>$detail['goods_id']))->count());
        $this->assign('goodsfavorites', $goodsfavorites = D('Goodsfavorites')->check($goods_id, $this->uid));//检测自己是不是收藏


        $this->display();
    }


    //秒杀(商家的)
    public function shopsecondkill($goods_id){
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        $spec_key = $this->_get('spec_key');
        $stock=D('Goods')->where(['goods_id'=>$goods_id])->find();
        //判断库存是否大于0
        if($stock['num']>0){
            $data=array(
                'user_id'=>$this->uid,
                'goods_id'=>$goods_id,
                'type'=>1,
                'shop_id'=>$stock['shop_id'],
                'time'=>NOW_TIME
            );
            $secondk=D('GoodsHurry')->add($data);//添加进虚拟表
            if(false !== $secondk){
                //修改库存数量
                $sum=$stock['num']-1;
                D('Goods')->where(['goods_id'=>$goods_id])->save(['num'=>$sum]);
                //用户地址
                $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);
                $spec_key =  $this->_get('spec_key');
                if(!empty($spec_key)){
                    $spt=D('TpSpecGoodsPrice')->where(['goods_id'=>$goods_id,'key'=>$spec_key])->find();
                    $needpay=$spt['price'];
                    $name=$spt['key_name'];
                }else{
                    $needpay=$stock['mall_price'];
                    $name=null;
                }
                //运费
                $kuaidi=D('Pkuaidi')->where(['shop_id'=>$stock['shop_id'],'id'=>$stock['kuaidi_id']])->find();
                $yunfei=D('Pyunfei')->where(['shop_id'=>$kuaidi['shop_id'],'kuaidi_id'=>$kuaidi['id']])->find();
                if($stock['is_reight']==1){
                    $pay=$yunfei['shouzhong'];
                }else{
                    $pay=0;
                }
                $arr=array(
                    'shop_id'=>$stock['shop_id'],
                    'user_id'=>$this->uid,
                    'total_price'=>$needpay,
                    'create_time'=>NOW_TIME,
                    'express_price'=>$pay,
                    'need_pay'=>round($needpay+$pay,2),
                    'address_id'=>$defaultAddress['id']
                );
                $order=D('Order')->add($arr);
                $arr2=array(
                    'order_id'=>$order,
                    'goods_id'=>$goods_id,
                    'shop_id'=>$stock['shop_id'],
                    'cate_id'=>$stock['cate_id'],
                    'num'=>1,
                    'kuaidi_id'=>$stock['kuaidi_id'],
                    'price'=>$needpay,
                    'total_price'=>$needpay,
                    'express_price'=>$pay,
                    'key'=>$spec_key,
                    'key_name'=>$name
                );
                D('Ordergoods')->add($arr2);
                D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$goods_id,'type'=>4])->save(['close'=>1]);
                //抢购成功，跳转到支付页面
                $this->ajaxReturn(array('status' => 'success', 'msg' => '抢购成功!','url'=>U('user/goods/index',array('index'=>0))));
            }else{
                $this->ajaxReturn(array('status' => 'error', 'msg' => '抢购失败!'));
            }

        }else{
            $this->ajaxReturn(array('status' => 'error', 'msg' => '活动已结束,请参与下轮活动'));
        }

    }


    //天天特价抢购详情页(商家的)
    public function shopdetails($goods_id) {
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        $goods_id = (int) $goods_id;
        if (empty($goods_id)) {
            $this->error('商品不存在');
        }
        if (!($detail = D('Goods')->find($goods_id))) {
            $this->error('商品不存在');
        }
        if ($detail['closed'] != 0 || $detail['audit'] != 1) {
            $this->error('商品不存在');
        }
        //查询点赞是否够数
        $fabulous=D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$goods_id,'type'=>4,'type_id'=>1,'close'=>0])->find();
        $this->assign('fabulous',$fabulous);
        //查询时间是否满足
        $mgoods=D('MallactivityGoods')->where(['goods_id'=>$goods_id,'colsed'=>0,'type'=>1])->find();
        $time=D('MallactivitySpecia')->where(['time_id'=>$mgoods['time_id']])->find();
        $this->assign('time',$time);
        $shop_id = $detail['shop_id'];
        $shops =D('Shop')->find($shop_id);

        if($detail['is_tui'] ==1 && $shops['user_id'] != $this->uid){
            D('Goods')->check_price($goods_id);
        }

        $is_shop = D('Users')->get_is_shop($this->uid);
        $user_type = $is_shop === true ? 2 : 1;
        $mobile = M('users')->where(array('user_id' => $this->uid))->getField('mobile');
        $logo=M('users')->where(['user_id'=>$this->uid])->getField('face');
        $url='https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $goods_lo='https://'.$_SERVER['HTTP_HOST'].$detail['photo'];
        $goods_logo=urlencode($goods_lo);
        $urls= urlencode($url);
        $rs=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$mobile'");
        $re_shop=M()->db(1,'mysql://chat:root@47.104.172.88:3306/chat')->query("select memberIdx from tb_person where phoneNumber='$shops[mobile]'");
        $md=md5($rs[0]['memberIdx']);
        $datas=array(
            'chat_user_id' => $rs[0]['memberIdx'],
            'token'=>$md,
            'user_logo'=>$logo,
            'chat_shop_id'=>$re_shop[0]['memberIdx'],
            'shop_name'=>$shops['shop_name'],
            'goods_name'=>$detail['title'].'--'.$detail['intro'],
            'shop_logo'=>$shops['logo'],
            'goods_logo'=>$goods_logo,
            'goods_url'=>$urls
        );
        //$data_url=json_encode($datas);
        $params = http_build_query($datas);
        $service_url = 'http://chat.atufafa.com/mobile/chatdemo.php?' . $params;
        $this->assign('service_url', $service_url);


        $youhuijuan = D('Coupon')->where(array('shop_id'=>$shop_id,'audit'=>1,'closed'=>0))->select();
        $this->assign('youhuijuan',$youhuijuan);
        $xiangsi=D('Goods')->where(array('goods_id'=>$goods_id))->find();
        $this->assign('list',D('Goods')->where(array('cate_id'=>$xiangsi['cate_id'],'audit'=>1,'closed'=>0))->select());

        //查询拼团商品
        $wheres=array('is_pintuan'=>1,'audit'=>1,'closed'=>0);
        $tuan_goods=D('Goods')->where($wheres)->select();
        $count=D('Goods')->where($wheres)->count();
        $shops_id = $shop_id2 = array();
        foreach ($tuan_goods as $val){
            $shops_id[]=$val['shop_id'];
        }
        $this->assign('count',$count);
        $this->assign('tuan',$tuan_goods);
        $this->assign('shops',D('Shop')->itemsByIds($shops_id));

        $tuan_goods2=D('Goods')->where($wheres)->order('min_price desc')->limit(0,10)->select();
        foreach ($tuan_goods2 as $val){
            $shop_id2[]=$val['shop_id'];
        }
        $this->assign('tuan2',$tuan_goods2);
        $this->assign('shop2',D('Shop')->itemsByIds($shop_id2));
        // print_r($detail);die;s
        $this->assign('detail', $detail);
        $this->assign('shop', D('Shop')->find($shop_id));
        $filter_spec = $this->get_spec($goods_id); //获取商品规格参数
        $goodsss=M('goods')->find($goods_id);

        $goodsss[mall_price]=$goodsss[mall_price];

        $spec_goods_price  = M('TpSpecGoodsPrice')->where("goods_id = $goods_id")->getField("key,price,pin_price,store_count"); // 规格 对应 价格 库存表
        if($spec_goods_price != null){
            $this->assign('spec_goods_price', json_encode($spec_goods_price,JSON_UNESCAPED_UNICODE)); // 规格 对应 价格 库存表
        }else{
            $this->assign('spec_goods_price',0);
        }
        $yh=$goodsss[yh];
        if($yh!= '0'){
            $yh=explode(PHP_EOL,$yh);
            for ($i=0; $i < count($yh)-1;$i++){
                $yh[s][]=explode(',',$yh[$i]);
            }
            foreach($yh[s] as $k2=>$vo){
                foreach($vo as $k2=>$v2){
                    $rs[$k2][] = $v2;
                }
            }
            $goodsss['zks'][]=$rs[0];
            $goodsss['zks'][]=$rs[1];
        }

        $this->assign('filter_spec',$filter_spec);
        $this->assign('goods', $goodsss);
        $pingnum = D('Goodsdianping')->where(array('goods_id' => $goods_id, 'show_date' => array('ELT', TODAY)))->count();
        $this->assign('pingnum', $pingnum);
        $score = (int) D('Goodsdianping')->where(array('goods_id' => $goods_id))->avg('score');
        if ($score == 0) {
            $score = 5;
        }
        $this->assign('score', $score);
        if(($detail['is_vs1'] || $detail['is_vs2'] || $detail['is_vs3'] || $detail['is_vs4'] || $detail['is_vs5'] || $detail['is_vs6']) || $detail['is_vs7'] || $detail['is_vs8'] || $detail['is_vs9']==1 ){
            $this->assign('is_vs', $is_vs = 1);
        }else{
            $this->assign('is_vs', $is_vs = 0);
        }
        $this->assign('pics', D('Goodsphoto')->getPics($detail['goods_id']));
        $this->assign('count_goodsfavorites',$count_goodsfavorites = D('Goodsfavorites')->where(array('goods_id'=>$detail['goods_id']))->count());
        $this->assign('goodsfavorites', $goodsfavorites = D('Goodsfavorites')->check($goods_id, $this->uid));//检测自己是不是收藏
        $map_coupon_where = array('shop_id' =>$detail['shop_id'], 'audit' => 1,'closed' => 0, 'expire_date' => array('EGT', TODAY));
        $this->assign('coupon',$coupon = D('Coupon')->order('coupon_id desc ')->where($map_coupon_where )->limit(0, 2)->select());
        $this->assign('mall_coupon_null',$mall_coupon_null = D('Coupon')->order('coupon_id desc ')->where($map_coupon_where)->count());

        $this->assign('goods_attribute',$goods_attribute = M('tpGoodsAttribute')->getField('attr_id,attr_name'));//属性值
        $this->assign('goods_attr_list',$goods_attr_list = M('tpGoodsAttr')->where("goods_id = $goods_id")->select());//属性列表
        $this->display();
    }


   public function get_spec($goods_id){
        //商品规格 价钱 库存表 找出 所有 规格项id
        $keys = M('TpSpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`key` SEPARATOR '_')");
        $keyss = M('TpSpecGoodsPrice')->where("goods_id = $goods_id")->getField("GROUP_CONCAT(`price`)");
       $filter_spec = array();
        if($keys){
	// $sql = "SELECT a.name,a.order,b.* FROM __PREFIX__tp_spec AS a INNER JOIN __PREFIX__tp_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY a.order";
            //$specImage =  M('TpSpecImage')->where("goods_id = $goods_id and src != '' ")->getField("spec_image_id,src");// 规格对应的 图片表， 例如颜色
            $keys = str_replace('_',',',$keys);
            //$sql  = "SELECT a.name,a.order,b.*,c.* FROM tu_tp_spec AS a INNER JOIN tu_tp_spec_item AS b ON a.id = b.spec_id INNER JOIN tu_tp_spec_goods_price AS c ON b.id=c.key  WHERE b.id IN($keys) ORDER BY c.price";
            $sql  = "SELECT a.name,a.order,b.* FROM tu_tp_spec AS a INNER JOIN tu_tp_spec_item AS b ON a.id = b.spec_id WHERE b.id IN($keys) ORDER BY $keyss";
            $filter_spec2 = M()->db(2,'mysql://zgtianxin:sTXMbCGhCAxLNCNs@127.0.0.1:3306/zgtianxin')->query($sql);
            foreach($filter_spec2 as $key => $val){
                $filter_spec[$val['name']][] = array(
                        'item_id'=> $val['id'],
                        'item'=> $val['item'],
                        'price'=>$val['price'],
                );
            }
        }
        return $filter_spec;
   }

     public function cartdel(){
        $goods_spec = $_POST['goods_spec'];
        $goods_spec_all = cookie('goods_spec');
        if (isset($goods_spec_all[$goods_spec])) {
            unset($goods_spec_all[$goods_spec]);
            cookie('goods_spec', $goods_spec_all, 604800);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '删除失败'));
        }
    }

    public function pincartdel(){
        $goods_spec = $_POST['goods_spec'];
        $goods_spec_all = cookie('goods_specs');
        if (isset($goods_spec_all[$goods_spec])) {
            unset($goods_spec_all[$goods_spec]);
            cookie('goods_specs', $goods_spec_all, 604800);
            $this->ajaxReturn(array('status' => 'success', 'msg' => '删除成功'));
        } else {
            $this->ajaxReturn(array('status' => 'error', 'msg' => '删除失败'));
        }
    }
    
    public function cartdel2(){
        $goods_id = (int) $this->_get('goods_id');
        $goods = cookie('goods');
        if (isset($goods[$goods_id])) {
            unset($goods[$goods_id]);
            cookie('goods', $goods);
        }
        header('Location:' . U('mall/cart'));
    }
    public function neworder(){
        $goods = $this->_get('goods');
        $goods = explode(',', $goods);
        if (empty($goods)) {
            $this->error('亲购买点吧');
        }
        $datas = array();
        foreach ($goods as $val) {
            $good = explode('-', $val);
            $good[1] = (int) $good[1];
            if (empty($good[0]) || empty($good[1])) {
                $this->error('亲购买点吧');
            }
            if ($good[1] > 99 || $good[1] < 0) {
                $this->error('本店不支持批发');
            }
            $datas[$good[0]] = $good[1];
        }
        cookie('goods', $datas);
        header('Location:' . U('mall/cart'));
        die;
    }
    public function order() {
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
		
        $user_integral = D("users")->field('integral')->find($this->uid);
        $num = $this->_post('num', false);
        $redbag_money = $this->_post('redbag_money', false);
        $goods_ids = array();
        foreach ($num as $k => $val) {
            $val = (int) $val;
            if (empty($val)) {
                unset($num[$k]);
            } elseif ($val < 1 || $val > 99) {
                unset($num[$k]);
            } else {    
              $spec_keys[]=$k;
          	  $spec_arr[] = explode('|',$k); 
          	  $spec_temp = explode('|',$k); 
              $goods_ids[$k][] = (int)$spec_temp[0];              
            }
        }
        foreach($goods_ids as $v){
            $goods[] = D('Goods')->itemsByIds($v); 
        }
        foreach ($goods as $k => $v) {
        	foreach($v as $key => $val){
            if ($val['closed'] != 0 || $val['audit'] != 1 || $val['end_date'] < TODAY) {
                unset($goods[$key]);
            }
            //把这个商品的规格存进数组
            $goods[$k][$key][sky]=$spec_arr[$k][1]; //把后面的规格存进来 148_150
             $goods[$k][$key]['goods_spec'] = $spec_keys[$k];//整个存一下
            if(!empty($goods[$k][$key][sky])){
            //改变价格
            $spt=D('TpSpecGoodsPrice')->where("`key`='{$goods[$k][$key][sky]}' and `goods_id`='{$goods[$k][$key][goods_id]}'")->find();	              
			$goods[$k][$key]['mall_price']=$spt['price'];
			$goods[$k][$key]['key_name']=$spt['key_name'];//建的中文名
            	}
            }
        }
       if (empty($goods)) {
            $this->tuMsg('很抱歉，您提交的产品暂时不能购买');
        }
         //下单前检查库存
        foreach ($goods as $val) {
		$val = reset($val); 
		//二维数组 取第一个  
		//加入购物车时候检查规格库存  如果不走这里他会走下面的
        $is_spec_stock = is_spec_stock($val[goods_id],$val[sky],$num[$val['goods_spec']]);
       	if(!$is_spec_stock){
       	 	$spec_one_num =  get_one_spec_stock($val[goods_id],$val[sky]);
        	     $this->tuMsg('亲！规格为<' . $val['key_name']. '>的商品库存不够了,只剩' . $spec_one_num . '件了');
        	}
	        if ($val['num'] < $num[$val['goods_spec']]) {
	            $this->tuMsg('亲！商品<' . $val['title'] . '>库存不够了,只剩' . $val['num'] . '件了');
	        }
	    }
        $tprice = 0;
        $all_integral = $total_mobile = 0;
        $ip = get_client_ip();
        $total_canuserintegral = $ordergoods = $total_price = array();
        foreach ($goods as $val) {
        $val = reset($val); 
		//二维数组 取第一个  
        //二次开发的 其他人可能看不懂 之前是  $num[$val['goods_id']]  这个我前面那个num已经改过了 但是下面的代码不想改了 所以统一赋值一下
		//前面已经通过这个规格的键值来重新传了
		$num[$val['goods_id']] = $num[$val['goods_spec']];
            $price = $val['mall_price'] * $num[$val['goods_id']];
            if (!empty($redbag_money[$val['shop_id']]['money'])) {
                $price -= ($redbag_money[$val['shop_id']]['money']);//减去对应商家的红包金额
            }

            $js_price = $val['settlement_price'] * $num[$val['goods_id']];
            $mobile_fan = $val['mobile_fan'] * $num[$val['goods_id']]; //每个商品的手机减少的钱
            $canuserintegral = $val['use_integral'] * $num[$val['goods_id']];
			$order_express_price = D('Ordergoods')->calculation_express_price($this->uid,$val['kuaidi_id'], $num[$val['goods_id']],$val['goods_id'],0);
			//返回单个商品运费
            $m_price = $price - $mobile_fan;
            $tprice += $m_price;
            $total_mobile += $mobile_fan;
            $all_integral += $canuserintegral;
            $ordergoods[$val['shop_id']][] = array(
                'goods_id' => $val['goods_id'],
                'shop_id' => $val['shop_id'],
                'num' => $num[$val['goods_id']],
                'kuaidi_id' => $val['kuaidi_id'],
                'price' => $val['mall_price'],
                'redbag_money' => $redbag_money[$val['shop_id']]['money'],
                'useEnvelope_id' => $redbag_money[$val['shop_id']]['useEnvelope_id'],
                'total_price' => $price,//减去对应商家的红包金额
                'mobile_fan' => $mobile_fan,
                'express_price' => $order_express_price, //单个商品运费总价
                'is_mobile' => 1,
                'js_price' => $js_price,
                'create_time' => NOW_TIME,
                'create_ip' => $ip,
                'key' => $val['sky'],
                'key_name' => $val['key_name']
            );
            $total_canuserintegral[$val['shop_id']] += $canuserintegral; //不同商家可使用积分
            $total_price[$val['shop_id']] += $price; //不同商家的总价格
			$express_price[$val['shop_id']] += $order_express_price; //不同商家总运费
            $mm_price[$val['shop_id']] += $mobile_fan;  //不同商家的手机下单立减
        }
        $order = array('user_id' => $this->uid, 'create_time' => NOW_TIME, 'create_ip' => $ip, 'is_mobile' => 1);
        $tui = cookie('tui');
        if (!empty($tui)) {
            $tui = explode('_', $tui);
            $tuiguang = array('uid' => (int) $tui[0], 'goods_id' => (int) $tui[1]);
        }
		$defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);//收货地址部分重写
        $order_ids = array();
        foreach ($ordergoods as $k => $val) {
            $order['shop_id'] = $k;
            $order['total_price'] = $total_price[$k];
            $order['mobile_fan'] = $mm_price[$k];
            $order['can_use_integral'] = $total_canuserintegral[$k];
			$order['express_price'] = $express_price[$k];//写入运费
            $order['address_id'] = $defaultAddress['id'];//写入快递ID

			$val[0]['express_price'] = $express_price[$k];//写入运费
			$val[0]['address_id'] = $defaultAddress['id'];//写入快递
            $shop = D('Shop')->find($k);
            $order['is_shop'] = (int) $shop['is_goods_pei'];
            if ($order_id = D('Order')->add($order)) {//这里写入订单表了
                $order_ids[] = $order_id;
			
                foreach ($val as $k1 => $val1) {
                    //扣除用户选择使用的红包
                    if (!empty($val1['useEnvelope_id']) && !empty($val1['redbag_money'])) {
                        D('Users')->hongbaos($this->uid, $order_id, $val1['useEnvelope_id']);
                    }
                    $Goods = D('Goods')->find($val1['goods_id']);
                    $val1['cate_id'] = $Goods['cate_id'];
                    $val1['weight'] = $Goods['weight'];
                    $val1['order_id'] = $order_id;
                    if(!empty($tuiguang)) {
                        if ($tuiguang['goods_id'] == $val1['goods_id']) {
                            $val1['tui_uid'] = $tuiguang['uid'];
                        }
                    }
                    D('Ordergoods')->add($val1);
                }
            }
        }
        cookie('goods_spec', null);// 清空 cookie
        if (count($order_ids) > 1) {
            $need_pay = D('Order')->useIntegral($this->uid, $order_ids);
			
            $logs = array(
				'type' => 'goods', 
				'user_id' => $this->uid, 
				'order_id' => 0, 
				'order_ids' => join(',', $order_ids), 
				'code' => '', 
				'need_pay' => $need_pay, 
				'create_time' => NOW_TIME, 
				'create_ip' => get_client_ip(), 
				'is_paid' => 0
			);
            $logs['log_id'] = D('Paymentlogs')->add($logs);
            $this->tuMsg('合并下单成功，接下来选择支付方式和配送地址', U('mall/paycode', array('log_id' => $logs['log_id'])));
        } else {
            $this->tuMsg('下单成功，接下来选择支付方式和配送地址', U('mall/pay', array('order_id' => $order_id,'address_id'=>$defaultAddress['id'])));
        }
        die;
    }
    public function paycode(){
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->error('没有有效支付记录');
        }
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->error('没有有效的支付记录');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录');
        }
        $order_ids = explode(',', $detail['order_ids']);
        $ordergood = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shopList = D('Order')
            ->alias('o')
            ->join('tu_shop s on(o.shop_id=s.shop_id)','left')
            ->where(array('o.order_id' => array('IN', $order_ids)))
            ->field('o.shop_id,sum(o.total_price) total_prcie,s.shop_name')
            ->group('o.shop_id')
            ->select();
        foreach($shopList as $key=>$value){
            //查询这个商家里面未使用的红包
            $useEnvelope = D('Order')->GetuseEnvelope($this->uid, $value['shop_id']);
            //获取这个商家里面未使用的优惠卷
            $user_coupon = M('coupon_download')
                ->alias('user_coupon')
                ->join('tu_coupon c on(user_coupon.coupon_id=c.coupon_id)','left')
                ->field('user_coupon.download_id,user_coupon.coupon_id,c.title,c.full_price,c.reduce_price')
                ->where(['user_coupon.user_id' => $this->uid, 'user_coupon.shop_id' => $value['shop_id'], 'user_coupon.is_used' =>0])
                ->select();
            $value['useEnvelope']=$useEnvelope;
            $value['user_coupon']=$user_coupon;
            $shopList[$key]=$value;
        }

        //dump($shopList);die;
        $this->assign('goods', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);
		//收货地址部分重写
        $di=$_GET['address_id'];
        if(empty($di)){
            $defaultAddress = D('Paddress')->defaultAddress($this->uid,$type);
        }else{
            $defaultAddress = D('Paddress')->where(['id'=>$di])->find();
        }

		$changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => goods, 'log_id' => $log_id));
		$this -> assign('defaultAddress', $defaultAddress);
		$this -> assign('changeAddressUrl', $changeAddressUrl);
		//重写结束
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('logs', $detail);
        $this->assign('shopList', $shopList);
        $this->display();
    }

    //拼单合单支付
    public function pin_paycode(){
        $log_id = (int) $this->_get('log_id');
        if (empty($log_id)) {
            $this->error('没有有效支付记录');
        }
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->error('没有有效的支付记录');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录');
        }
        $order_ids = explode(',', $detail['order_ids']);
        $ordergood = D('Ordergoods')->where(array('order_id' => array('IN', $order_ids)))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shopList = D('Order')
            ->alias('o')
            ->join('tu_shop s on(o.shop_id=s.shop_id)','left')
            ->where(array('o.order_id' => array('IN', $order_ids)))
            ->field('o.shop_id,sum(o.total_price) total_prcie,s.shop_name')
            ->group('o.shop_id')
            ->select();

        //dump($shopList);die;
        $this->assign('goods', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);
        //收货地址部分重写
        $di=$_GET['address_id'];
        if(empty($di)) {
            $defaultAddress = D('Paddress')->defaultAddress($this->uid, $type);
        }else{
            $defaultAddress = D('Paddress')->where(['id'=>$di])->find();
        }
        $changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => pingdan, 'log_id' => $log_id));
        $this -> assign('defaultAddress', $defaultAddress);
        $this -> assign('changeAddressUrl', $changeAddressUrl);
        //重写结束
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->assign('logs', $detail);
        $this->assign('shopList', $shopList);
        $this->display();
    }


    public function pay(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $this->check_mobile();
        cookie('goods', null); //销毁cookie
        $order_id = (int) $this->_get('order_id');
        $order = D('Order')->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->error('该订单不存在');
            die;
        }
        
        if($this->isPost()){
            $useEnvelope = $_POST['useEnvelope'];
            D('Order')->where(['order_id'=>$order_id])->save(['useEnvelope'=>$useEnvelope,'useEnvelope_id'=>$_POST['useEnvelope_id']]);
            D('Paymentlogs')->where(['log_id'=>$Paymentlogs['log_id']])->save(['need_pay'=>$need_pay]);
        }
        $ordergood = D('Ordergoods')->where(array('order_id' => $order_id))->select();
        $goods_id = $shop_ids = array();
        foreach ($ordergood as $k => $val) {
            $goods_id[$val['goods_id']] = $val['goods_id'];
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $this->assign('goods', D('Goods')->itemsByIds($goods_id));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('ordergoods', $ordergood);
		
		//收货地址部分重写
		if (false == $defaultAddress = D('Paddress')->order_address_id($this->uid,$order_id)) {
		   $this->error('获取用户地址出错，请先去会员中心添加商城地址后下单');
		}
		$changeAddressUrl = "http://" . $_SERVER['HTTP_HOST'] . U('address/addlist', array('type' => goods, 'order_id' => $order_id));
		$this -> assign('defaultAddress', $defaultAddress);
		$this -> assign('changeAddressUrl', $changeAddressUrl);
		//重写结束
		//如果没有优惠劵ID就去获取开始
		if(!empty($order['download_id'])){
			$this->assign('download_id', $order['download_id']);
		}else{
			$this->assign('coupon', $coupon = D('Coupon')->Obtain_Coupon($order_id,$this->uid));
		}
        //AJAX部分获取更新抵扣
		$coupon = D('Coupon')->Obtain_Coupon($order_id,$this->uid);
		//获取优惠劵ID结束
		$this->assign('use_integral', $use_integral = D('Order')->GetUseIntegral($this->uid, array($order_id)));//预算积分抵扣
		$Paymentlogs = D('Paymentlogs')->getLogsByOrderId('goods', $order_id);
		if($Paymentlogs['need_pay']){
			$need_pay = $Paymentlogs['need_pay'];
		}else{
			$need_pay = $order['total_price'] + $order['express_price'] -$coupon['reduce_price'] - $use_integral - $order['mobile_fan']-$useEnvelope;
		}
        $this->assign('useEnvelope',D('Order')->GetuseEnvelope($this->uid,$shop_ids));
		$this->assign('need_pay', $need_pay);
        $this->assign('order', $order);
        $this->assign('payment', D('Payment')->getPayments(true));
        $this->display();
    }
    public function paycode2(){
        //这里是因为原来的是按订单付，这里是合并付款逻辑部分
        $log_id = (int)$this->_get('log_id');
        $redbag=$_POST['redbag'];

        if (empty($log_id)) {
            $this->tuMsg('没有有效支付记录');
        }
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->tuMsg('没有有效的支付记录');
        }
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->tuMsg('没有有效的支付记录');
        }
        $order_ids = explode(',', $detail['order_ids']);
		//这里合并付款逻辑暂时不做1，做留言系统，2，做优惠劵ID，3;优惠劵减去的金额
        D('Order')->where(array('order_id' => array('IN', $order_ids)))->save(array('addr_id' => $addr_id));
        /**********************修复合并付款的时候的系列订单错误问题*****************************/
        $orders = D('order')->where(array('order_id' => array('IN', $order_ids)))->select();
        foreach ($orders as $k => $val) {
            $need_pay[$val[order_id]] = $val['total_price'] - $val['mobile_fan'] - $val['use_integral'];
            D('Order')->where(array('order_id' => $val['order_id']))->save(array('need_pay' => $need_pay[$val[order_id]]));
        }
        /*****************************************************/
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
        if ($code == 'wait') {
            //如果是货到付款
            D('Order')->save(array('is_daofu' => 1, 'status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
            D('Ordergoods')->save(array('is_daofu' => 1, 'status' => 1), array('where' => array('order_id' => array('IN', $order_ids))));
            D('Order')->mallSold($order_ids);//更新销量
            D('Order')->mallPeisong(array($order_ids), 1);//更新配送
			//D('Sms')->mallTZshop($order_ids);//用户下单通知商家
			D('Order')->combination_goods_print($order_ids);//多商家订单打印
            $this->tuMsg('恭喜您下单成功', U('user/goods/index'));
        } else {
            $payment = D('Payment')->checkPayment($code);
            if (empty($payment)) {
                $this->tuMsg('该支付方式不存在');
            }

            //合并付款开始
            foreach ($order_ids as $v) {
                $need_pay = D('Order')->useIntegral($this->uid, array($v));//这个不知道能不能返回
                D('Order')->where("order_id={$v}")->save(array('need_pay' => $need_pay));//合并付款的时候更新实际付款金额
                $log_need += $need_pay;
            }
            $detail['need_pay'] = $log_need;
            $detail['code'] = $code;
			//合并付款结束
            $detail['code'] = $code;
            D('Paymentlogs')->save($detail);
            $this->tuMsg('订单设置完成，即将进入付款。', U('mall/combine', array('log_id' => $detail['log_id'])));
        }
    }
    public function combine(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $log_id = (int) $this->_get('log_id');
        if (!($detail = D('Paymentlogs')->find($log_id))) {
            $this->error('没有有效的支付记录');
        }
    
        if ($detail['is_paid'] != 0 || empty($detail['order_ids']) || !empty($detail['order_id']) || empty($detail['need_pay'])) {
            $this->error('没有有效的支付记录');
        }
        $total_reduce_price=M('shop_pay_log')->where(array('log_id'=>$detail['log_id']))->sum('reduce_price');
        $detail['need_pay']-=$total_reduce_price;
        D('Paymentlogs')->where(array('log_id'=>$detail['log_id']))->save(array('need_pay'=>$detail['need_pay']));
        //$this->tuMsg('订单设置完成，即将进入付款。', U('payment/payment', array('log_id' => $logs['log_id'])));
        $this->assign('button', D('Payment')->getCode($detail));
        $this->assign('logs', $detail);
        $this->display();
    }
	//付款
    public function pay2(){
        if (empty($this->uid)) {
            $this->error('登录状态失效!', U('passport/login'));
            die;
        }
        $users_id=cookie('users_id');
		$obj = D('Order');
        $order_id = (int) $this->_get('order_id');
        $order = $obj ->find($order_id);
        if (empty($order) || $order['status'] != 0 || $order['user_id'] != $this->uid) {
            $this->tuMsg('该订单不存在');
        }
		
		$address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : $order['address_id'];//检测配送地址ID
        if (empty($address_id)) {
            $this->tuMsg('配送的地址异常');
        } else {
            $obj->save(array('order_id' => $order_id, 'address_id' => $address_id));
        }

        //添加优惠劵满减的优惠劵
        $download_id = (int)$this->_post('download_id');
        if (!empty($download_id)) {
            $coupon_price = D('Coupon')->Obtain_Coupon_Price($order_id, $download_id);
            if (!empty($coupon_price)) {
                $obj->where("order_id={$order_id}")->save(array('coupon_price' => $coupon_price, 'download_id' => $download_id));
            }
        }

        //新增
        $xz = (int) $this->_post('yuyue');
        $times = $this->_post('times');
        $remarks=$this->_post('remarks');
        $shop=D('Shop')->where(['shop_id'=>$order['shop_id']])->find();
        if($shop['is_yuyue']==1){
            if(empty($xz)){
                $this->tuMsg('请选择是否要预约');
            }
            if($xz==1&&empty($times)){
                $this->tuMsg('请选择预约时间');
            }
            if($xz==1){
                $t=date("Y-m-d",time());
                if($times<=$t){
                    $this->tuMsg('请选择正确时间');
                }
                //查询商品出售时间
                $g=D('Ordergoods')->where(['order_id'=>$order_id])->find();
                $go=D('Goods')->find($g['goods_id']);
                if($times<$go['yuyue_time']){
                    $this->tuMsg('不能小于商家出货时间,当前商家出货时间为：'.$go['yuyue_time']);
                }

                D('Order')->where(['order_id'=>$order_id])->save(['is_yuyeu'=>1,'yuyue_time'=>$times,'remarks'=>$remarks]);
        }
        }else{
            D('Order')->where(['order_id'=>$order_id])->save(['is_yuyeu'=>2,'yuyue_time'=>0,'remarks'=>$remarks]);
        }

        //优惠劵结束
        if (!($code = $this->_post('code'))) {
            $this->tuMsg('请选择支付方式');
        }
		$this->goods_mum($order_id);//检测库存		
        $address = D('Paddress')->where(array('address_id' => $order['address_id']))->find();
        if ($code == 'wait'){
            //如果是货到付款
            $obj ->save(array('order_id' => $order_id, 'status' => 1,'is_daofu' => 1));
            D('Ordergoods')->save(array('is_daofu' => 1,'status' => 1), array('where' => array('order_id' => $order_id)));			
            $obj ->mallSold($order_id);//更新销量
            $obj ->mallPeisong(array($order_id), 1);//更新配送
			//D('Sms')->mallTZshop($order_id);//用户下单通知商家
			$obj ->combination_goods_print($order_id);//万能商城订单打印
		    D('Weixintmpl')->weixin_notice_goods_user($order_id,$this->uid,0);//商城微信通知
            $this->tuMsg('恭喜您下单成功', U('user/goods/index'));
        }else{
            $payment = D('Payment')->checkPayment($code);
            if(empty($payment)){
                $this->tuMsg('该支付方式不存在');
            }
			
			
            $logs = D('Paymentlogs')->getLogsByOrderId('goods', $order_id); //写入支付记录


			if($order['is_change'] != 1){
				$need_pay = $obj->useIntegral($this->uid, array($order_id));//更新支付结果,这里加了配送费，这里是没改价的状态，这里改变的是积分状态
			}else{
				$need_pay = $order['need_pay'];//如果是改价的扫码都不加
			}
			//判断计算好的价格
			if($this->ispost()){
                $money=I('post.money');
                $envelope_id=I('post.envelope');
            if(empty($logs)){
                $logs = array(
					'type' => 'goods', 
					'user_id' => $this->uid, 
					'order_id' => $order_id, 
					'code' => $code, 
					'need_pay' => $money,
					'create_time' => NOW_TIME, 
					'create_ip' => get_client_ip(), 
					'is_paid' => 0,
                    //红包id
                    'envelopes_id'=>$envelope_id
				);
                //单个付款走的这里，为什么没写入数据库
                $logs['log_id'] = D('Paymentlogs')->add($logs);
            }else{
                $logs['need_pay'] = $money;
                $logs['code'] = $code;
                D('Paymentlogs')->save($logs);
            }
            }
            $obj ->where("order_id={$order_id}")->save(array('need_pay' => $money));//再更新一次最终的价格
            D('Weixintmpl')->weixin_notice_goods_user($order_id,$this->uid,1);//商城微信通知

         //$honbao=D('Paymentlogs')->where(array('order_id'=>$order_id,'user_id'=>$this->uid))->find();
        //var_dump($honbao);var_dump($honbao['envelopes_id']);
        $envelope=D('UserEnvelope')->where(array('user_envelope_id'=>$envelope_id,'user_id'=>$this->uid))->find();
        //var_dump($envelope);
        if(!empty($envelope['shop_id'])){
            D('UserEnvelope')->where(array('user_id'=>$this->uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
            $arr=array(
                'user_id'=>$this->uid,
                'envelope'=>$envelope['envelope'],
                'intro'=>'使用商家红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
            );
           // echo 1;
        }else{
           // echo 2;
            $ss =$envelope['num']-1;
            D('UserEnvelope')->where(array('user_id'=>$this->uid,'user_envelope_id'=>$envelope_id))->save(array('num'=>$ss));
            $cha=D('UserEnvelope')->where(array('user_id'=>$this->uid,'user_envelope_id'=>$envelope_id))->find();

            if($cha['num']==0){
                D('UserEnvelope')->where(array('user_id'=>$this->uid,'user_envelope_id'=>$envelope_id))->save(array('is_use'=>1));
            }
            $arr=array(
                'user_id'=>$this->uid,
                'envelope'=>$envelope['envelope'],
                'intro'=>'使用通用红包' . $envelope['envelope'] . '元，订单号[' . $order_id . ']',
                'create_time' => NOW_TIME,
                'create_ip' => get_client_ip(),
            );
        }
        D('UserEnvelopeLogs')->add($arr);

            $this->tuMsg('订单设置完成，即将进入付款。', U('payment/payment', array('log_id' => $logs['log_id'])));
        }
    }
    //团购点评
    public function dianping(){
        $goods_id = (int) $this->_get('goods_id');
        if(!($detail = D('Goods')->find($goods_id))){
            $this->error('没有该商品');
            die;
        }
        if($detail['closed']){
            $this->error('该商品已经被删除');
            die;
        }

        $this->assign('next', LinkTo('mall/dianpingloading', $linkArr, array('goods_id' => $goods_id, 't' => NOW_TIME, 'p' => '0000')));
        $this->assign('detail', $detail);
        $this->display();
    }
	
	
    public function dianpingloading(){
        $goods_id = (int) $this->_get('goods_id');
        if(!($detail = D('Goods')->find($goods_id))){
            die('0');
        }
        if($detail['closed']){
            die('0');
        }
        $Goodsdianping = D('Goodsdianping');
        import('ORG.Util.Page');
        $map = array('closed' => 0, 'goods_id' => $goods_id, 'show_date' => array('ELT', TODAY));
        $count = $Goodsdianping->where($map)->count();
        $Page = new Page($count, 5);
        $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
        $p = $_GET[$var];
        if($Page->totalPages < $p){
            die('0');
        }
        $list = $Goodsdianping->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $user_ids = $orders_ids = array();
        foreach($list as $k => $val){
            $user_ids[$val['user_id']] = $val['user_id'];
            $orders_ids[$val['order_id']] = $val['order_id'];
        }
        if(!empty($user_ids)){
            $this->assign('users', D('Users')->itemsByIds($user_ids));
        }
        if(!empty($orders_ids)){
            $this->assign('pics', D('Goodsdianpingpics')->where(array('order_id' => array('IN', $orders_ids)))->select());
        }
        $this->assign('totalnum', $count);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('detail', $detail);
        $this->display();
    }
	
	
	
	//点评详情
    public function img(){
        $order_id = (int) $this->_get('order_id');
        if(!($detail = D('Goodsdianping')->find($order_id))){
            $this->error('没有该点评');
            die;
        }
        if($detail['closed']){
            $this->error('该点评已经被删除');
            die;
        }
        $list =  D('Goodsdianpingpics')->where(array('order_id' => $order_id))->select();
        $this->assign('list', $list);
        $this->assign('detail', $detail);
        $this->display();
    }
	
	//付款前检测库存
    public function goods_mum($order_id){
        $order_id = (int) $order_id;
        $ordergoods_ids = D('Ordergoods')->where(array('order_id' => $order_id))->select();
        foreach($ordergoods_ids as $k => $v){
         $goods_num = D('Goods')->where(array('goods_id' => $v['goods_id']))->find();
         //也得检查下那个多规格的 这里
         $is_spec_stock = is_spec_stock($v[goods_id],$v[key],$v['num']);
       	 if(!$is_spec_stock){
       	 	$spec_one_num =  get_one_spec_stock($v[goods_id],$v[key]);
        	     $this->tuMsg('亲！规格为<' . $v['key_name']. '>的商品库存不够了,只剩' . $spec_one_num . '件了');
        	}
            if($goods_num['num'] < $v['num']){
				$this->tuMsg('商品ID' . $v['goods_id'] . '库存不足无法付款',U('user/goods/index',array('aready'=>1)));;
            }
        }
        return false;
    }

    //分享抢红包
    public function share(){
        $tuan=D('Goods');
        $map=array('closed'=>0,'audit'=>1,'is_pintuan'=>1);
        $lis=$tuan->where($map)->order('mall_price asc')->select();
        foreach ($lis as $val){
            $shop_id['shop_id']=$val['shop_id'];
        }
        $this->assign('tuan',$lis);
        $this->assign('shop',D('Shop')->itemsByIds($shop_id));
        $this->display();
    }

    //分享
    public function fenxian(){
        $config=D('Setting')->fetchAll();
        $obj=D('Goodsshare');
        if($this->ispost()){
            $product_id=I('post.product_id');
            $shop=I('post.shop_id');

            $set= (int) $config['goods']['times'];
            $num1=(int) $config['goods']['num1'];
            $num2=(int) $config['goods']['num2'];
            $nums=mt_rand($num1,$num2);
            $nowtime=date('Y:m:d H:i:m',time());
            $bg=date("Y-m-d H:i:s",strtotime('+'.$set.'hours',strtotime($nowtime)));
            $endtime=strtotime($bg);
            $cunzai=$obj->where(array('user_id'=>$this->uid,'shop_id'=>$shop,'goods_id'=>$product_id,'closed'=>0))->find();
            if(empty($cunzai)){
                $data=array();
                $data['goods_id']=$product_id;
                $data['shop_id']=$shop;
                $data['user_id']=$this->uid;
                $data['create_time']=NOW_TIME;
                $data['create_ip']=get_client_ip();
                $data['end_time']=$endtime;
                $data['num']=$nums;
                $obj->add($data);
            }
        }
    }

    //预约商品
    public function yuyue(){
        $aready = (int) $this->_param('aready');
        $this->assign('aready', $aready);
        $this->display();
    }

    //加载商城
    public function yuyueloadata(){
        $aready = I('aready', '', 'trim,intval');
        if($aready==1){
            $obj = D('Goods');
            import('ORG.Util.Page');
            $map = array('audit' => 1, 'closed' =>0,'is_yuyue'=>1);
            $lists = $obj->where($map)->order('check_price asc')->select();
            $count = count($lists);
            $Page = new Page($count, 6);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $config=D('Setting')->fetchAll();
            $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        }elseif($aready==2){
            $obj = D('Marketproduct');
            import('ORG.Util.Page');
            $map = array('audit' => 1, 'closed' =>0,'is_yuyue'=>1);
            $lists = $obj->where($map)->select();
            $count = count($lists);
            $Page = new Page($count, 6);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $config=D('Setting')->fetchAll();
            $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        }elseif($aready==3){
            $obj = D('Storeproduct');
            import('ORG.Util.Page');
            $map = array('audit' => 1, 'closed' =>0,'is_yuyue'=>1);
            $lists = $obj->where($map)->select();
            $count = count($lists);
            $Page = new Page($count, 6);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $config=D('Setting')->fetchAll();
            $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        }elseif($aready==4){
            $obj = D('Eleproduct');
            import('ORG.Util.Page');
            $map = array('audit' => 1, 'closed' =>0,'is_yuyue'=>1);
            $lists = $obj->where($map)->select();
            $count = count($lists);
            $Page = new Page($count, 6);
            $show = $Page->show();
            $var = C('VAR_PAGE') ? C('VAR_PAGE') : 'p';
            $p = $_GET[$var];
            if($Page->totalPages < $p){
                die('0');
            }
            $config=D('Setting')->fetchAll();
            $list = array_slice($lists,$Page->firstRow, $Page->listRows);
        }
        $this->assign('config',$config);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('aready', $aready);
        if($aready==1){
            $this->display();
        }elseif($aready==2){
            $this->display(yuyuemarket);
        }elseif($aready==3){
            $this->display(yuyuestore);
        }elseif($aready==4){
            $this->display(yuyueele);
        }else{
            $this->display();
        }

    }

    //商家资质
    public function qualifications($shop_id){
        $shop=M('shop_audit')->where(array('shop_id'=>$shop_id))->find();

        $this->assign('yingye',$shop['yingye'].'?x-oss-process=style/atufafa');
        if(!empty($shop['weisheng'])){
            $this->assign('weishen',$shop['weisheng'].'?x-oss-process=style/atufafa');
        }

        $this->display();
    }

    public function jiabuy($goods_id){
        if (empty($this->uid)) {
            $this->error('请先登陆', U('passport/login'));
        }
        if(D('FabulousEleStoreMarkert')->where(['user_id'=>$this->uid,'product_id'=>$goods_id,'type'=>4,'is_pingtai'=>1])->save(['close'=>1])){
            //抢购成功，跳转到支付页面
            $this->ajaxReturn(array('status' => 'error', 'msg' => '本轮活动已结束，请参与下轮活动!'));
        }
        $this->ajaxReturn(array('status' => 'error', 'msg' => '本轮活动已结束，请参与下轮活动!'));

    }


}