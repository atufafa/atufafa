<?php
class IntegralmallAction extends CommonAction{
    public function _initialize(){
        parent::_initialize();
        if($this->_CONFIG['operation']['mall'] == 0){
            $this->error('此功能已关闭');die;
        }
        $goods = cookie('goods_spec');
        $this->assign('cartnum', (int) array_sum($goods));
        $cat = (int) $this->_param('cat');
        $this->assign('goodscates', $goodscates = D('Goodscate')->fetchAll());
        $cates = (int)$this->_param('cates');
        $this->assign('goodscatess',$getFurniture = D('Goodscate')->getFurniture($cates));
        $this->assign('title',D('Goodscate')->getFurnitureName($cates));
        // print_r($getFurniture);die;
        $check_user_addr = D('Paddress')->where(array('user_id'=>$this->uid))->find();//全局检测地址
        $this->assign('check_user_addr', $check_user_addr);
    }

    //显示列表
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

        $this->assign('nextpage', LinkTo('integralmall/loaddata',$linkArr,array('t' => NOW_TIME,'p' => '0000')));
        $this->assign('scroll', $scroll = D('Goods')->getScroll());
        $this->assign('linkArr',$linkArr);
        $this->display();
    }

    //加载
    public function loaddata(){




    }


}