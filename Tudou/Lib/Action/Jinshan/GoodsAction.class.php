<?php

class GoodsAction extends CommonAction {

    private $create_fields = array('title','intro','shoplx','guige', 'num','is_reight','weight','kuaidi_id','shop_id', 'photo', 'cate_id', 'price', 'mall_price','use_integral','mobile_fan', 'sold_num', 'orderby', 'views', 'instructions', 'details', 'end_date', 'orderby','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','is_backers');
    private $edit_fields = array('title','intro','shoplx','guige','num', 'is_reight','weight','kuaidi_id','shop_id', 'photo', 'cate_id', 'price', 'mall_price','use_integral','mobile_fan', 'sold_num', 'orderby', 'views', 'instructions', 'details', 'end_date', 'orderby','is_vs1','is_vs2','is_vs3','is_vs4','is_vs5','is_vs6','is_backers');
    private $tuan_edit_fields = array('shop_id', 'orderby', 'use_integral', 'cate_id', 'intro', 'title', 'photo', 'thumb', 'price', 'tuan_price', 'settlement_price','mobile_fan', 'num', 'sold_num', 'bg_date', 'end_date', 'fail_date', 'is_hot', 'is_new', 'is_chose', 'freebook','xiadan','xiangou', 'activity_id','instructions');
	
	
    public function _initialize(){
        parent::_initialize();
        $this->assign('ranks',D('Userrank')->fetchAll());
    }
	
	
    public function index(){
        $Goods = D('Goods');
        import('ORG.Util.Page'); 
        $map = array();
		$map['closed'] =0;
        if($keyword = $this->_param('keyword', 'htmlspecialchars')){
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if($parent_id = (int) $this->_param('parent_id')){
            $this->assign('parent_id', $parent_id);
        }

        if($cate_id = (int) $this->_param('cate_id')){
            $map['cate_id'] = $cate_id;
            $this->assign('cate_id', $cate_id);
        }
        if($shop_id = (int) $this->_param('shop_id')){
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if($audit = (int) $this->_param('audit')){
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Goods->where($map)->count(); 
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $Goods->where($map)->order(array('goods_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach($list as $k => $val){
            if($val['shop_id']){
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val = $Goods->_format($val);
            $list[$k] = $val;
        }
        if($shop_ids){
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Goodscate')->fetchAll());

        $this->assign('list', $list); 
        $this->assign('page', $show);
        $this->display(); 
    }

    public function create(){
        if($this->isPost()){
            $data = $this->createCheck();
            $obj = D('Goods');
            if($goods_id = $obj->add($data)){
                $wei_pic = D('Weixin')->getCode($goods_id, 3); //购物类型是3
                $obj->save(array('goods_id'=>$goods_id,'wei_pic'=>$wei_pic));
                $photos = $this->_post('photos', false);
                if(!empty($photos)){
                    D('Goodsphoto')->upload($goods_id, $photos);
                }
                
				$this->shuxin($goods_id);//更新商品库存
				$this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性
				
                $this->tuSuccess('添加成功', U('goods/index'));
            }
            $this->tuError('操作失败');
        }else{
            $this->assign('cates', D('Goodscate')->fetchAll());
			$this->assign('goodsInfo',D('Goods')->where('goods_id='.I('GET.id',0))->find());  // 商品详情   
			$this->assign('goodsType',M("TpGoodsType")->select());
			$this->assign('goodscategory', D('TpGoodsCategory')->select());
            $this->display();
        }
    }
	
	
	
	
		
			
			

    private function createCheck(){
        $data = $this->checkFields($this->_post('data', false), $this->create_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if(empty($data['title'])){
            $this->tuError('产品名称不能为空');
        }
		$data['intro'] = htmlspecialchars($data['intro']);
		$data['guige'] = htmlspecialchars($data['guige']);
		$data['num'] = (int) $data['num'];
        if(empty($data['num'])) {
            $this->tuError('库存不能为空');
        } 
		$data['is_reight'] = (int) $data['is_reight'];
		$data['weight'] = (int) $data['weight'];
		if($data['is_reight'] == 1){
			if (empty($data['weight'])){
             	$this->tuError('重量不能为空');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if($data['is_reight'] == 1){
			if (empty($data['kuaidi_id'])) {
				$this->tuError('运费模板不能为空');
			}
		}	
        $data['shop_id'] = (int) $data['shop_id'];
        if(empty($data['shop_id'])){
            $this->tuError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if(empty($shop)){
            $this->tuError('请选择正确的商家');
        }
        $data['cate_id'] = (int) $data['cate_id'];
        if (empty($data['cate_id'])) {
            $this->tuError('请选择分类');
        }
		$Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
		$parent_id = $Goodscate['parent_id'];
		if($parent_id == 0) {
			$this->tuError('请选择二级分类');
		}
		$data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];
		
		$data['goods_type'] = I('goods_type');
		
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])) {
            $this->tuError('请上传缩略图');
        }
        if(!isImage($data['photo'])){
            $this->tuError('缩略图格式不正确');
        } 
		$data['price'] = (float) ($data['price']);
        if(empty($data['price'])){
            $this->tuError('市场价格不能为空');
        }
		$data['mall_price'] = (float) ($data['mall_price']);
        if(empty($data['mall_price'])){
            $this->tuError('商城价格不能为空');
        } 
        $data['mobile_fan'] = (float) ($data['mobile_fan']);
		$data['use_integral'] = (int) $data['use_integral'];
		if (!D('Goods')->check_add_use_integral($data['use_integral'],$data['mall_price'])){
            $this->tuError(D('Goods')->getError(), 3000, true);
        }
		$data['views'] = (int) $data['views'];
      	$data['instructions'] = SecurityEditorHtml($data['instructions']);
        if($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        } 
		$data['details'] = SecurityEditorHtml($data['details']);
        if(empty($data['details'])){
            $this->tuError('商品详情不能为空');
        }
        if($words = D('Sensitive')->checkWords($data['details'])){
            $this->tuError('商品详情含有敏感词：' . $words);
        } 
		$data['end_date'] = htmlspecialchars($data['end_date']);
        if(empty($data['end_date'])){
           $this->tuError('过期时间不能为空');
        }
        if(!isDate($data['end_date'])){
           $this->tuError('过期时间格式不正确');
        }
		$data['is_vs1'] = (int) $data['is_vs1'];
		$data['is_vs2'] = (int) $data['is_vs2'];
		$data['is_vs3'] = (int) $data['is_vs3'];
		$data['is_vs4'] = (int) $data['is_vs4'];
		$data['is_vs5'] = (int) $data['is_vs5'];
		$data['is_vs6'] = (int) $data['is_vs6'];
        $data['sold_num'] = (int) $data['sold_num'];
        $data['create_time'] = NOW_TIME;
        $data['create_ip'] = get_client_ip();
        $data['orderby'] = (int) $data['orderby'];
        $data['is_mall'] = 1;
		$data['is_backers'] = (int) $data['is_backers'];
        return $data;
    }
	
	
	
	//编辑或者添加分销通用
	public function profit($goods_id){  
		if(!$detail = M('Goods')->find($goods_id)){
            $this->error('请选择要编辑的商品');
        }
		if(!$shop = M('Shop')->find($detail['shop_id'])){
            $this->error('商家不存在');
        }
		if($shop['is_profit'] != 1){
            $this->error('您尚未开通分销权限');
        }
		$profit = M('GoodsProfit')->where(array('goods_id'=>$goods_id))->find();
		
		if($this->isPost()){
            $data = $this->checkFields($this->_post('data', false), array('profit_enable','profit_rate1','profit_rate2','profit_rate3'));
			$data['goods_id'] = $goods_id;
			$data['shop_id'] = $shop['shop_id'];
			$data['profit_enable'] = (int) $data['profit_enable'];
			$data['profit_rate1'] = $data['profit_rate1'];
			$data['profit_rate2'] = $data['profit_rate2'];
			$data['profit_rate3'] = $data['profit_rate3'];
			if(($data['profit_rate1'] + $data['profit_rate2'] + $data['profit_rate3']) >= 100){
				$this->tuError('分成比例相加不能大于或者等于100%');
			}
			if($profit){
				$res = M('GoodsProfit')->save($data);
			}else{
				$res = M('GoodsProfit')->add($data);
			}
            if($res){
				$this->tuSuccess('操作成功', U('goods/index'));
            }
			$this->tuError('操作失败');
        }else{
           $this->assign('detail', M('Goods')->find($goods_id));
		   $this->assign('profit', $profit);
           $this->display();
		}
	}
	
	


    public function edit($goods_id = 0){
        if($goods_id = (int) $goods_id){
            $obj = D('Goods');
            if(!$detail = $obj->find($goods_id)){
                $this->tuError('请选择要编辑的商品');
            }
            if($this->isPost()){
                $data = $this->editCheck();
                $data['goods_id'] = $goods_id;
                if(!empty($detail['wei_pic'])){
                    if(true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($goods_id, 3);
                        $data['wei_pic'] = $wei_pic;
                    }
                }else{
                    $wei_pic = D('Weixin')->getCode($goods_id,3);
                    $data['wei_pic'] = $wei_pic;
                }
                if(false !== $obj->save($data)){
                    $photos = $this->_post('photos', false);
                    if(!empty($photos)){
                        D('Goodsphoto')->upload($goods_id, $photos);
                    }
                     
					$this->shuxin($goods_id);//更新商品库存
					$this->saveGoodsAttr($goods_id,$_POST['goods_type']); //更新商品属性
					 
                    $this->tuSuccess('操作成功', U('goods/index'));
                }
                $this->tuError('操作失败');
            }else{
                $this->assign('detail', $obj->_format($detail));
				$this->assign('parent_id',D('Goodscate')->getParentsId($detail['cate_id']));
				$this->assign('attrs', D('Goodscateattr')->order(array('orderby' => 'asc'))->where(array('cate_id' => $detail['cate_id']))->select());
                $this->assign('cates', D('Goodscate')->fetchAll());
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
				$this->assign('photos', D('Goodsphoto')->getPics($goods_id));
				$this->assign('kuaidi', D('Pkuaidi')->where(array('shop_id'=>$detail['shop_id'],'type'=>goods))->select());
				$goodsInfo=D('Goods')->where('goods_id='.I('GET.goods_id',0))->find();
                $this->assign('goodsInfo',$goodsInfo); 
        	 	$this->assign('goodsType',M("TpGoodsType")->select());
            	$this->assign('goodscategory', D('TpGoodsCategory')->select());
                $this->display();
            }
        }else{
            $this->tuError('请选择要编辑的商品');
        }
    }

    private function editCheck() {
        $data = $this->checkFields($this->_post('data', false), $this->edit_fields);
        $data['title'] = htmlspecialchars($data['title']);
        if(empty($data['title'])) {
            $this->tuError('产品名称不能为空');
		}
		$data['intro'] = htmlspecialchars($data['intro']);
		$data['guige'] = htmlspecialchars($data['guige']);
		$data['num'] = (int) $data['num'];
        if (empty($data['num'])) {
            $this->tuError('库存不能为空');
        } 
		$data['is_reight'] = (int) $data['is_reight'];
		$data['weight'] = (int) $data['weight'];
		if($data['is_reight'] == 1){
			if(empty($data['weight'])){
             	$this->tuError('重量不能为空');
			}
        }
		$data['kuaidi_id'] = (int) $data['kuaidi_id'];
		if($data['is_reight'] == 1){
			if(empty($data['kuaidi_id'])){
				$this->tuError('运费模板不能为空');
			}
		}
			
		$data['shop_id'] = (int) $data['shop_id'];
        if(empty($data['shop_id'])){
            $this->tuError('商家不能为空');
        }
        $shop = D('Shop')->find($data['shop_id']);
        if(empty($shop)){
            $this->tuError('请选择正确的商家');
        }
    
        $data['cate_id'] = (int) $data['cate_id'];
        if(empty($data['cate_id'])){
            $this->tuError('请选择分类');
        }
		$Goodscate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
		$parent_id = $Goodscate['parent_id'];
		if($parent_id == 0){
			$this->tuError('请选择二级分类');
		}
		$data['city_id'] = $shop['city_id'];
        $data['area_id'] = $shop['area_id'];
        $data['business_id'] = $shop['business_id'];
		
		$data['goods_type'] = I('goods_type');
		
        $data['photo'] = htmlspecialchars($data['photo']);
        if(empty($data['photo'])){
            $this->tuError('请上传缩略图');
        }
        if(!isImage($data['photo'])) {
            $this->tuError('缩略图格式不正确');
        } 
        $data['price'] = (float) ($data['price']);
        if(empty($data['price'])) {
            $this->tuError('市场价格不能为空');
        } 
        $data['mall_price'] = (float) ($data['mall_price']);
        if(empty($data['mall_price'])) {
            $this->tuError('商城价格不能为空');
        }
        $data['mobile_fan'] = (float) ($data['mobile_fan']);
		$data['use_integral'] = (int) $data['use_integral'];
		if(!D('Goods')->check_add_use_integral($data['use_integral'],$data['mall_price'])) {//传2参数
            $this->tuError(D('Goods')->getError(), 3000, true);
        }
        $data['views'] = (int) $data['views'];
		$data['instructions'] = SecurityEditorHtml($data['instructions']);
        if($words = D('Sensitive')->checkWords($data['instructions'])) {
            $this->tuError('购买须知含有敏感词：' . $words);
        } 
		$data['details'] = SecurityEditorHtml($data['details']);
        if(empty($data['details'])){
            $this->tuError('商品详情不能为空');
        }
        if($words = D('Sensitive')->checkWords($data['details'])) {
            $this->tuError('商品详情含有敏感词：' . $words);
        }
		$data['end_date'] = htmlspecialchars($data['end_date']);
        if(empty($data['end_date'])){
            $this->tuError('过期时间不能为空');
        }
        if(!isDate($data['end_date'])){
            $this->tuError('过期时间格式不正确');
        }
		$data['is_vs1'] = (int) $data['is_vs1'];
		$data['is_vs2'] = (int) $data['is_vs2'];
		$data['is_vs3'] = (int) $data['is_vs3'];
		$data['is_vs4'] = (int) $data['is_vs4'];
		$data['is_vs5'] = (int) $data['is_vs5'];
		$data['is_vs6'] = (int) $data['is_vs6'];
        $data['sold_num'] = (int) $data['sold_num'];
        $data['orderby'] = (int) $data['orderby'];
		$data['is_backers'] = (int) $data['is_backers'];
        return $data;
    }
	

    public function delete($goods_id = 0){
        if(is_numeric($goods_id) && ($goods_id = (int) $goods_id)){
            $obj = D('Goods');
            $obj->save(array('goods_id' => $goods_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('goods/index'));
        }else{
            $goods_id = $this->_post('goods_id', false);
            if(is_array($goods_id)) {
                $obj = D('Goods');
                foreach ($goods_id as $id){
                    $obj->save(array('goods_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('删除成功', U('goods/index'));
            }
            $this->tuError('请选择要删除的商家');
        }
    }

	//商品上架下架
    public function update($goods_id = 0){
        if($goods_id = (int) $goods_id){
			if(!($detail = D('Goods')->find($goods_id))){
				$this->tuError('请选择要操作的商品');
			}
			$data = array('closed' =>0,'goods_id' => $goods_id);
			$intro = '上架商品成功';
			if($detail['closed'] == 0){
				$data['closed'] = 1;
				$intro = '下架商品成功';
			}
			if(D('Goods')->save($data)){
				$this->tuSuccess($intro, U('goods/index'));
			}
        }else{
            $this->tuError('请选择要操作的商品');
        }
    }

    public function audit($goods_id = 0) {
        if(is_numeric($goods_id) && ($goods_id = (int) $goods_id)){
            $obj = D('Goods');
            $obj->save(array('goods_id' => $goods_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('goods/index'));
        }else{
            $goods_id = $this->_post('goods_id', false);
            if(is_array($goods_id)){
                $obj = D('Goods');
                foreach ($goods_id as $id) {
                    $obj->save(array('goods_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('审核成功！'.$error.'条失败', U('goods/index'));
            }
            $this->tuError('请选择要审核的商品');
        }
    }
	
	
	
	 public function shuxin($goods_id){
         if($_POST['item']){
             $spec = M('TpSpec')->getField('id,name'); 
             $specItem = M('TpSpecItem')->getField('id,item');
                          
             $specGoodsPrice = M("TpSpecGoodsPrice"); 
             $specGoodsPrice->where('goods_id = '.$goods_id)->delete(); 
             foreach($_POST['item'] as $k => $v){
                   $v['price'] = trim($v['price']);
                   $store_count = $v['store_count'] = trim($v['store_count']);
                   $v['bar_code'] = trim($v['bar_code']);
                   $dataList[] = array('goods_id'=>$goods_id,'key'=>$k,'key_name'=>$v['key_name'],'price'=>$v['price'],'store_count'=>$v['store_count'],'bar_code'=>$v['bar_code']);                                      
             }             
             $specGoodsPrice->addAll($dataList);             
         }   
         refresh_stock($goods_id); 

    }
     /**
     * 动态获取商品规格选择框 根据不同的数据返回不同的选择框
     */
    public function ajaxGetSpecSelect(){
        $goods_id = $_GET['goods_id'] ? $_GET['goods_id'] : 0;
        $specList = D('TpSpec')->where("type_id = ".$_GET['spec_type'])->order('`order` desc')->select();
        foreach($specList as $k => $v)        
            $specList[$k]['spec_item'] = D('TpSpecItem')->where("spec_id = ".$v['id'])->getField('id,item'); // 获取规格项
        $items_id = M('TpSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField("GROUP_CONCAT(`key` SEPARATOR '_') AS items_id");
        $items_ids = explode('_', $items_id);       
        if($goods_id){
           $specImageList = M('TpSpecImage')->where("goods_id = $goods_id")->getField('spec_image_id,src');                 
        }        
        $this->assign('specImageList',$specImageList);
        
        $this->assign('items_ids',$items_ids);
        $this->assign('specList',$specList);
        $this->display('ajax_spec_select');        
    }    

     /**
     * 动态获取商品规格输入框 根据不同的数据返回不同的输入框
     */    
    public function ajaxGetSpecInput(){     
         
         $goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
         $str = $this->getSpecInput($goods_id ,$_POST['spec_arr']);
         exit($str);   
    }

     /**
     * 获取 规格的 笛卡尔积
     * @param $goods_id 商品 id     
     * @param $spec_arr 笛卡尔积
     * @return string 返回表格字符串
     */
    public function getSpecInput($goods_id, $spec_arr){
        foreach ($spec_arr as $k => $v){
            $spec_arr_sort[$k] = count($v);
        }
        asort($spec_arr_sort);        
        foreach ($spec_arr_sort as $key =>$val){
            $spec_arr2[$key] = $spec_arr[$key];
        }
         $clo_name = array_keys($spec_arr2);         
         $spec_arr2 = combineDika($spec_arr2);            
                       
         $spec = M('TpSpec')->getField('id,name'); 
         $specItem = M('TpSpecItem')->getField('id,item,spec_id');
         $keySpecGoodsPrice = M('TpSpecGoodsPrice')->where('goods_id = '.$goods_id)->getField('key,key_name,price,store_count,bar_code');
                          
       $str = "<table class='table table-bordered' id='spec_input_tab'>";
       $str .="<tr>";       
       foreach ($clo_name as $k => $v) {
           $str .=" <td><b>{$spec[$v]}</b></td>";
       }    
        $str .="<td><b>价格</b></td>
               <td><b>库存</b></td>
               <td><b>条码</b></td>
             </tr>";
       foreach ($spec_arr2 as $k => $v) {
            $str .="<tr>";
            $item_key_name = array();
            foreach($v as $k2 => $v2)
            {
                $str .="<td>{$specItem[$v2][item]}</td>";
                $item_key_name[$v2] = $spec[$specItem[$v2]['spec_id']].':'.$specItem[$v2]['item'];
            }   
            ksort($item_key_name);            
            $item_key = implode('_', array_keys($item_key_name));
            $item_name = implode(' ', $item_key_name);
            
            $keySpecGoodsPrice[$item_key][price] ? false : $keySpecGoodsPrice[$item_key][price] = 0; // 价格默认为0
            $keySpecGoodsPrice[$item_key][store_count] ? false : $keySpecGoodsPrice[$item_key][store_count] = 0; //库存默认为0
            $str .="<td><input name='item[$item_key][price]' value='{$keySpecGoodsPrice[$item_key][price]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")' /></td>";
            $str .="<td><input name='item[$item_key][store_count]' value='{$keySpecGoodsPrice[$item_key][store_count]}' onkeyup='this.value=this.value.replace(/[^\d.]/g,\"\")' onpaste='this.value=this.value.replace(/[^\d.]/g,\"\")'/></td>";            
            $str .="<td><input name='item[$item_key][bar_code]' value='{$keySpecGoodsPrice[$item_key][bar_code]}' />
                <input type='hidden' name='item[$item_key][key_name]' value='$item_name' /></td>";
            $str .="</tr>";           
       }
        $str .= "</table>";
       return $str;   
    }
	
	
	
	
	//动态获取商品属性入框根据不同的数据返回不同的输入框类型
    public function ajaxGetAttrInput(){
		$goods_id = $_REQUEST['goods_id'] ? $_REQUEST['goods_id'] : 0;
		$type_id = $_REQUEST['type_id'] ? $_REQUEST['type_id'] : 0;
		$str = $this->getAttrInput($goods_id,$type_id);
        exit($str);
    }
	
	
	  /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     * @param int $goods_id 商品id
     * @param int $type_id 商品属性类型id
     */
    public function getAttrInput($goods_id,$type_id){
		
	  
		
        $attributeList = M('tpGoodsAttribute')->where(array('type_id'=>$type_id))->select();  
        foreach($attributeList as $key => $val){                                                                        
            
            $curAttrVal = $this->getGoodsAttrVal(NULL,$goods_id, $val['attr_id']);
             //促使他循环
            if(count($curAttrVal) == 0 || false == $curAttrVal)
                $curAttrVal[] = array('goods_attr_id' =>'','goods_id' => '','attr_id' => '','attr_value' => '','attr_price' => '');
            foreach($curAttrVal as $k =>$v){                                        
                            $str .= "<tr class='attr_{$val['attr_id']}'>";            
                            $addDelAttr = ''; //加减符号
                            //单选属性或者复选属性
                            if($val['attr_type'] == 1 || $val['attr_type'] == 2){
                                if($k == 0)                                
                                    $addDelAttr .= "<a onclick='addAttr(this)' href='javascript:void(0);'>[+]</a>&nbsp&nbsp";                                                                    
                                else                                
                                     $addDelAttr .= "<a onclick='delAttr(this)' href='javascript:void(0);'>[-]</a>&nbsp&nbsp";                               
                            }

                            $str .= "<td>$addDelAttr {$val['attr_name']}</td> <td>";            

                            //手工录入
                            if($val['attr_input_type'] == 0){
                                $str .= "<input type='text' size='40' value='{$v['attr_value']}' name='attr_{$val['attr_id']}[]' />";
                            }
                            //从下面的列表中选择（一行代表一个可选值）
                            if($val['attr_input_type'] == 1){
                                $str .= "<select name='attr_{$val['attr_id']}[]'>";
                                $tmp_option_val = explode(PHP_EOL, $val['attr_values']);
                                foreach($tmp_option_val as $k2=>$v2){
                                    //编辑的时候有选中值
                                    $v2 = preg_replace("/\s/","",$v2);
                                    if($v['attr_value'] == $v2)
                                        $str .= "<option selected='selected' value='{$v2}'>{$v2}</option>";
                                    else
                                        $str .= "<option value='{$v2}'>{$v2}</option>";
                                }
                                $str .= "</select>";                
                            }
                            //多行文本框
                            if($val['attr_input_type'] == 2){
                                $str .= "<textarea cols='40' rows='3' name='attr_{$val['attr_id']}[]'>{$v['attr_value']}</textarea>";
                            }                                                        
                            $str .= "</td></tr>";
            }                        
            
        }        
        return  $str;
    }
    
    /**
     * 获取 tp_goods_attr 表中指定 goods_id  指定 attr_id  或者 指定 goods_attr_id 的值 可是字符串 可是数组
     * @param int $goods_attr_id tp_goods_attr表id
     * @param int $goods_id 商品id
     * @param int $attr_id 商品属性id
     * @return array 返回数组
     */
    public function getGoodsAttrVal($goods_attr_id = 0 ,$goods_id = 0, $attr_id = 0)
    {
        if($goods_attr_id > 0)
            return M('tpGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->select(); 
        if($goods_id > 0 && $attr_id > 0)
            return M('tpGoodsAttr')->where(array('goods_id'=>$goods_id,'attr_id'=>$attr_id))->select();        
    }
	
	
	 /**
     *  给指定商品添加属性 或修改属性 更新到 tp_goods_attr
     * @param int $goods_id  商品id
     * @param int $goods_type  商品类型id
     */
    public function saveGoodsAttr($goods_id,$goods_type){  
     
                
         // 属性类型被更改了 就先删除以前的属性类型 或者没有属性 则删除        
        if($goods_type == 0)  {
            M('tpGoodsAttr')->where(array('goods_id'=>$goods_id))->delete(); 
            return;
        }
        
            $GoodsAttrList = M('tpGoodsAttr')->where(array('goods_id'=>$goods_id))->select();
            
            $old_goods_attr = array(); //数据库中的的属性以attr_id_和值的组合为键名
            foreach($GoodsAttrList as $k => $v){                
                $old_goods_attr[$v['attr_id'].'_'.$v['attr_value']] = $v;
            }            
                              
            // post提交的属性以attr_id_和值的组合为键名    
            $post_goods_attr = array();
	
            foreach($_POST as $k => $v){
                $attr_id = str_replace('attr_','',$k);
                if(!strstr($k, 'attr_') || strstr($k, 'attr_price_'))
                   continue;                                 
               foreach ($v as $k2 => $v2){                      
                   $v2 = str_replace('_', '', $v2); //替换特殊字符
                   $v2 = str_replace('@', '', $v2); //替换特殊字符
                   $v2 = trim($v2);
                   
                   if(empty($v2))
                       continue;
                   $tmp_key = $attr_id."_".$v2;
                   $attr_price = $_POST["attr_price_$attr_id"][$k2]; 
                   $attr_price = $attr_price ? $attr_price : 0;
                   if(array_key_exists($tmp_key , $old_goods_attr)){  
				   //如果这个属性原来就存在 
                       if($old_goods_attr[$tmp_key]['attr_price'] != $attr_price){    
					   	//并且价格不一样就做更新处理                   
                           $goods_attr_id = $old_goods_attr[$tmp_key]['goods_attr_id'];                         
                           M('tpGoodsAttr')->where(array('goods_attr_id'=>$goods_attr_id))->save(array('attr_price'=>$attr_price));                       
                       }
                   }else{
					   //否则这个属性 数据库中不存在 说明要做删除操作
                       M('tpGoodsAttr')->add(array('goods_id'=>$goods_id,'attr_id'=>$attr_id,'attr_value'=>$v2,'attr_price'=>$attr_price));                       
                   }
                   unset($old_goods_attr[$tmp_key]);
               }
            }     
            //没有被unset($old_goods_attr[$tmp_key]); 掉是说明数据库中存在表单中没有提交过来则要删除操作
            foreach($old_goods_attr as $k => $v){                
               M('tpGoodsAttr')->where(array('goods_attr_id'=>$v['goods_attr_id']))->delete();
            }                       
    }




    //分销商品列表     ------新增部分
    
    public function tuan_index()
    {
        $Tuan = D('Pindan');
        import('ORG.Util.Page'); 
        $map = array('closed' => 0);
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['title'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($cate_id = (int) $this->_param('cate_id')) {
            $map['cate_id'] = array('IN', D('Tuancate')->getChildren($cate_id));
            $this->assign('cate_id', $cate_id);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if ($audit = (int) $this->_param('audit')) {
            $map['audit'] = ($audit === 1 ? 1 : 0);
            $this->assign('audit', $audit);
        }
        $count = $Tuan->where($map)->count();  
        $Page = new Page($count, 25); 
        $show = $Page->show(); 
        $list = $Tuan->where($map)->order(array('tuan_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $val) {
            if ($val['shop_id']) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $val['create_ip_area'] = $this->ipToArea($val['create_ip']);
            $val = $Tuan->_format($val);
            $list[$k] = $val;
        }
        if ($shop_ids) {
            $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        }
        $this->assign('cates', D('Tuancate')->fetchAll());
        $this->assign('list', $list); 
        $this->assign('page', $show); 
        $this->display(); 
    }
	//分销商品审核    ------新增部分
	public function tuan_audit($tuan_id)
    {
        if (is_numeric($tuan_id) && ($tuan_id = (int) $tuan_id)) {
            $obj = D('Pindan');
            $obj->save(array('tuan_id' => $tuan_id, 'audit' => 1));
            $this->tuSuccess('审核成功', U('goods/tuan_index'));
        } else {
            $tuan_id = $this->_post('tuan_id', false);
            if (is_array($tuan_id)) {
                $obj = D('Pindan');
                foreach ($tuan_id as $id) {
                    $obj->save(array('tuan_id' => $id, 'audit' => 1));
                }
                $this->tuSuccess('审核成功', U('goods/tuan_index'));
            }
            $this->tuError('请选择要审核的商品');
        }
    }
    //分销商品编辑    ------新增部分
    public function tuan_edit($tuan_id)
    {
        if ($tuan_id = (int) $tuan_id) {
            $obj = D('Pindan');
            if (!$detail = $obj->find($tuan_id)) {
                $this->tuError('请选择要编辑的商品');
            }
            $tuan_details = M('PindanDetails')->find($tuan_id);

            if ($this->isPost()) {
                $data = $this->tuan_editCheck();
                $details = $this->_post('details', 'SecurityEditorHtml');
                if (empty($details)) {
                    $this->tuError('商品详情不能为空');
                }
                if ($words = D('Sensitive')->checkWords($details)) {
                    $this->tuError('详细内容含有敏感词：' . $words);
                }
                $instructions = $this->_post('instructions', 'SecurityEditorHtml');
                if (empty($instructions)) {
                    $this->tuError('购买须知不能为空');
                }
                if ($words = D('Sensitive')->checkWords($instructions)) {
                    $this->tuError('购买须知含有敏感词：' . $words);
                }
                $thumb = $this->_param('thumb', false);
                foreach ($thumb as $k => $val) {
                    if (empty($val)) {
                        unset($thumb[$k]);
                    }
                    if (!isImage($val)) {
                        unset($thumb[$k]);
                    }
                }
                $data['thumb'] = serialize($thumb);
                $branch_id = $this->_post('branch_id', false);
                foreach ($branch_id as $val) {
                    if (!$brdetail = D('Shopbranch')->find($val)) {
                        unset($val);
                    }
                    if ($brdetail['shop_id'] != $this->shop_id) {
                        unset($val);
                    }
                }
                $branch = implode(',', $branch_id);
                $data['branch_id'] = $branch;
                $data['tuan_id'] = $tuan_id;
                if (!empty($detail['wei_pic'])) {
                    if (true !== strpos($detail['wei_pic'], "https://mp.weixin.qq.com/")) {
                        $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                        $data['wei_pic'] = $wei_pic;
                    }
                } else {
                    $wei_pic = D('Weixin')->getCode($tuan_id, 2);
                    $data['wei_pic'] = $wei_pic;
                }
                $ex = array(
                    'tuan_id' => $tuan_id,
                    'details' => $details,
                    'instructions' => $instructions,
                );
                if (false !== $obj->save($data)) {
                    D('Pindandetails')->save($ex);
                    $this->tuSuccess('操作成功', U('goods/tuan_index'));
                }
                $this->tuError('操作失败');
            } else {
                $branch = D('Shopbranch')->where(array('shop_id' => $detail['shop_id'], 'closed' => 0, 'audit' => 1))->select();
                $this->assign('branch', $branch);
                $hd = D('Activity')->order(array('orderby' => 'asc'))->where(array('shop_id'=>$detail['shop_id'],'bg_date' => array('ELT', TODAY), 'end_date' => array('EGT', TODAY), 'sign_end' => array('EGT', TODAY), 'closed' => 0, 'audit' => 1))->select();
                $this->assign('hd',$hd);
                $this->assign('detail', $obj->_format($detail));
                $thumb = unserialize($detail['thumb']);
                $this->assign('thumb', $thumb);
                $this->assign('shop', D('Shop')->find($detail['shop_id']));
                $this->assign('tuan_details', $tuan_details);
                $branch_id = explode(',', $detail['branch_id']);
                $this->assign('branch_id', $branch_id);
                $this->assign('cates', D('Goodscate')->where(['parent_id'=>179])->fetchAll());
                $this->display();
            }
        } else {
            $this->tuError('请选择要编辑的商品');
        }
    }
	//分销商品审核（数据确认）    ------新增部分
    public function tuan_editCheck()
    {
            $data = $this->checkFields($this->_post('data', false), $this->tuan_edit_fields);
            $data['shop_id'] = (int) $data['shop_id'];
            if (empty($data['shop_id'])) {
                $this->tuError('商家不能为空');
            }
            $shop = D('Shop')->find($data['shop_id']);
            if (empty($shop)) {
                $this->tuError('请选择正确的商家');
            }
            $data['cate_id'] = (int) $data['cate_id'];
            if (empty($data['cate_id'])) {
                $this->tuError('商品分类不能为空');
            }
             $Tuancate = D('Goodscate')->where(array('cate_id' => $data['cate_id']))->find();
             $parent_id = $Tuancate['parent_id'];
             if ($parent_id == 0) {
                $this->tuError('请选择二级分类');
             }
            $data['lng'] = $shop['lng'];
            $data['lat'] = $shop['lat'];
            $data['city_id'] = $shop['city_id'];
            $data['area_id'] = $shop['area_id'];
            $data['business_id'] = $shop['business_id'];
            $data['title'] = htmlspecialchars($data['title']);
            if (empty($data['title'])) {
                $this->tuError('商品名称不能为空');
            }
            $data['intro'] = htmlspecialchars($data['intro']);
            if (empty($data['intro'])) {
                $this->tuError('副标题不能为空');
            }
            $data['photo'] = htmlspecialchars($data['photo']);
            if (empty($data['photo'])) {
                $this->tuError('请上传图片');
            }
            if (!isImage($data['photo'])) {
                $this->tuError('图片格式不正确');
            }$data['price'] = (float) ($data['price']);
            if (empty($data['price'])) {
                $this->tuError('市场价格不能为空');
            } $data['tuan_price'] = (float) ($data['tuan_price'] );
            if (empty($data['tuan_price'])) {
                $this->tuError('商品价格不能为空');
            }
            $data['settlement_price'] = (float) ($data['settlement_price']);
            if (empty($data['settlement_price'])) {
                $this->tuError('结算价格不能为空');
            }
            $data['mobile_fan'] = (float) ($data['mobile_fan']);
            $data['use_integral'] = (int) $data['use_integral'];
            //商品检测积分合法性结束
            $data['num'] = (int) $data['num'];
            if (empty($data['num'])) {
                $this->tuError('库存不能为空');
            } $data['sold_num'] = (int) $data['sold_num'];
            $data['bg_date'] = htmlspecialchars($data['bg_date']);
            if (empty($data['bg_date'])) {
                $this->tuError('开始时间不能为空');
            }
            if (!isDate($data['bg_date'])) {
                $this->tuError('开始时间格式不正确');
            } $data['end_date'] = htmlspecialchars($data['end_date']);
            if (empty($data['end_date'])) {
                $this->tuError('结束时间不能为空');
            }
            if (!isDate($data['end_date'])) {
                $this->tuError('结束时间格式不正确');
            }
            $data['is_hot'] = (int) $data['is_hot'];
            $data['is_new'] = (int) $data['is_new'];
            $data['is_chose'] = (int) $data['is_chose'];
            $data['freebook'] = (int) $data['freebook'];
            $data['is_return_cash'] = (int) $data['is_return_cash'];
            $data['create_time'] = NOW_TIME;
            $data['create_ip'] = get_client_ip();
            $data['xiadan'] = (int) $data['xiadan'];
            $data['xiangou'] = (int) $data['xiangou'];
            $data['fail_date'] = htmlspecialchars($data['fail_date']);
            $data['orderby'] = (int) $data['orderby'];
            return $data;
    }
    //分销商品删除    ------新增部分
    public function tuan_delete($tuan_id)
    {
        if (is_numeric($tuan_id) && ($tuan_id = (int) $tuan_id)) {
            $obj = D('Pindan');
            $obj->save(array('tuan_id' => $tuan_id, 'closed' => 1));
            $this->tuSuccess('删除成功', U('goods/tuan_index'));
        } else {
            $tuan_id = $this->_post('tuan_id', false);
            if (is_array($tuan_id)) {
                $obj = D('Pindan');
                foreach ($tuan_id as $id) {
                    $obj->save(array('tuan_id' => $id, 'closed' => 1));
                }
                $this->tuSuccess('删除成功', U('tuan_index/index'));
            }
            $this->tuError('请选择要删除的商品');
        }
    }
	//分销商品新增      ------新增部分
    public function tuan_create()
    {
        if ($this->isPost()) {
            $data = $this->tuan_editCheck();
            $obj = D('Pindan');
            $details = $this->_post('details', 'SecurityEditorHtml');
            if (empty($details)) {
                $this->tuError('抢购详情不能为空');
            }
            if ($words = D('Sensitive')->checkWords($details)) {
                $this->tuError('详细内容含有敏感词：' . $words);
            }
            $instructions = $this->_post('instructions', 'SecurityEditorHtml');
            if (empty($instructions)) {
                $this->tuError('购买须知不能为空');
            }
            if ($words = D('Sensitive')->checkWords($instructions)) {
                $this->tuError('购买须知含有敏感词：' . $words);
            }
          
            $branch_id = $this->_post('branch_id', false);
            foreach ($branch_id as $k => $val) {
                if (!$brdetail = D('Shopbranch')->find($val)) {
                    unset($branch_id[$k]);
                }
                if ($brdetail['shop_id'] != $this->shop_id) {
                    unset($branch_id[$k]);
                }
            }
            $branch = implode(',', $branch_id);
            $data['branch_id'] = $branch;
            $thumb = $this->_param('thumb', false);
            foreach ($thumb as $k => $val) {
                if (empty($val)) {
                    unset($thumb[$k]);
                }
                if (!isImage($val)) {
                    unset($thumb[$k]);
                }
            }
            $data['thumb'] = serialize($thumb);
            if ($tuan_id = $obj->add($data)) {
                $wei_pic = D('Weixin')->getCode($tuan_id, 2); //抢购类型是2
                $obj->save(array('tuan_id' => $tuan_id, 'wei_pic' => $wei_pic));
                D('Pindandetails')->add(array('tuan_id' => $tuan_id, 'details' => $details, 'instructions' => $instructions));
                $this->tuSuccess('添加成功', U('goods/tuan_index'));
            }
            $this->tuError('操作失败');
        } else {
            $this->assign('cates',D('Goodscate')->fetchAll());
            $this->display();
        }
    }

    //分销商品订单列表     --------新增部分
    public function tuan_list()
    {
        $Tuanorder = D('Pindanorder');
        import('ORG.Util.Page');
        // 导入分页类
        $map = array();
        if (($bg_date = $this->_param('bg_date', 'htmlspecialchars')) && ($end_date = $this->_param('end_date', 'htmlspecialchars'))) {
            $bg_time = strtotime($bg_date);
            $end_time = strtotime($end_date);
            $map['create_time'] = array(array('ELT', $end_time), array('EGT', $bg_time));
            $this->assign('bg_date', $bg_date);
            $this->assign('end_date', $end_date);
        } else {
            if ($bg_date = $this->_param('bg_date', 'htmlspecialchars')) {
                $bg_time = strtotime($bg_date);
                $this->assign('bg_date', $bg_date);
                $map['create_time'] = array('EGT', $bg_time);
            }
            if ($end_date = $this->_param('end_date', 'htmlspecialchars')) {
                $end_time = strtotime($end_date);
                $this->assign('end_date', $end_date);
                $map['create_time'] = array('ELT', $end_time);
            }
        }
        if ($user_id = (int) $this->_param('user_id')) {
            $users = D('Users')->find($user_id);
            $this->assign('nickname', $users['nickname']);
            $this->assign('user_id', $user_id);
            $map['user_id'] = $user_id;
        }
        if ($keyword = $this->_param('keyword', 'htmlspecialchars')) {
            $map['order_id'] = array('LIKE', '%' . $keyword . '%');
            $this->assign('keyword', $keyword);
        }
        if ($shop_id = (int) $this->_param('shop_id')) {
            $map['shop_id'] = $shop_id;
            $shop = D('Shop')->find($shop_id);
            $this->assign('shop_name', $shop['shop_name']);
            $this->assign('shop_id', $shop_id);
        }
        if (isset($_GET['st']) || isset($_POST['st'])) {
            $st = (int) $this->_param('st');
            if ($st != 999) {
                $map['status'] = $st;
            }
            $this->assign('st', $st);
        } else {
            $this->assign('st', 999);
        }
        $count = $Tuanorder->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($count, 25);
        // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show();
        // 分页显示输出
        $list = $Tuanorder->where($map)->order(array('order_id' => 'desc'))->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $shop_ids = $user_ids = $tuan_ids = array();
        foreach ($list as $k => $val) {
            if (!empty($val['shop_id'])) {
                $shop_ids[$val['shop_id']] = $val['shop_id'];
            }
            $user_ids[$val['user_id']] = $val['user_id'];
            $tuan_ids[$val['tuan_id']] = $val['tuan_id'];
        }
        $this->assign('users', D('Users')->itemsByIds($user_ids));
        $this->assign('shops', D('Shop')->itemsByIds($shop_ids));
        $this->assign('tuan', D('Pindan')->itemsByIds($tuan_ids));
        $this->assign('list', $list);
        // 赋值数据集
        $this->assign('page', $show);
        // 赋值分页输出
        $this->display();
        // 输出模板
    }

    public function tuan_del($order_id = 0)
    {
        if (is_numeric($order_id) && ($order_id = (int) $order_id)) {
            $obj = D('Pindanorder');
            $orderinfo = $obj->Field('tuan_id,num')->find($order_id);
            $tuan_num = $orderinfo['num'];
            $where['tuan_id'] = $orderinfo['tuan_id'];
            D("Pindan")->where($where)->setInc("num", $tuan_num);
            $obj->delete($order_id);
            $this->tuSuccess('删除成功', U('goods/tuan_list'));
        } else {
            $order_id = $this->_post('order_id', false);
            if (is_array($order_id)) {
                $obj = D('Tuanorder');
                $tuan = D("Tuan");
                foreach ($order_id as $id) {
                    $orderinfo = $obj->Field('tuan_id,num')->find($id);
                    $tuan_num = $orderinfo['num'];
                    $where['tuan_id'] = $orderinfo['tuan_id'];
                    $tuan->where($where)->setInc("num", $tuan_num);
                    $obj->delete($id);
                }
                $this->tuSuccess('删除成功', U('goods/tuan_list'));
            }
            $this->tuError('请选择要删除的商品订单');
        }
    }
    
}
