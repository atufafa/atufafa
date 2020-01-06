<?php
class IntegralShopAction extends CommonAction{

    //首页热门商品数据
    public function hotGoods(){
        $page=I('page',1);
        $side_ids1=array(144,152,160,168,179,187,195,203,211);//手机积分商城首页下部分板块
        $side_ids2=array(239,240,241,242,243,244,245,246,247);//手机积分商城首页商家店铺
        $side_ids3=array(259,260,261,262,263,264,265,266,267);//手机积分商城首页商家商品
        $page_size=15;
        $index=($page-1)*$page_size;

//        $goods=M('ad')
//            ->alias('a')
//            ->join('tu_integral_goodslist g ON (a.integral_goods_id = g.goods_id)','left')
//            ->field('g.*')
//            ->where(array('a.site_id'=>$side_ids1[$page-1]))
//            ->order('g.exchange_sum desc,g.exchange_sum asc,g.redbag_price desc')
//            ->limit($index,$page_size)
//            ->select();

        $goods=M('integral_goodslist')
            ->where(['audit'=>1,'closed'=>0])
            ->order('exchange_sum desc,exchange_sum asc,redbag_price desc')
            ->limit($index,$page_size)
            ->select();

        $ad1=M('ad')->where(array('site_id'=>$side_ids2[$page-1]))->field('ad_id,shop_id,shop_name,logo,introduce')->find();

        $ad2=M('ad')
            ->alias('a')
            ->join('tu_integral_goodslist g ON (a.integral_goods_id = g.goods_id)','left')
            ->field('a.*,g.mall_price,g.price')
            ->where(array('a.site_id'=>$side_ids3[$page-1],'g.audit'=>1,'g.closed'=>0))
            ->order('g.exchange_sum desc,g.exchange_sum asc,g.redbag_price desc')
            ->limit(8)
            ->select();

        $data=[
            'goods'=>$goods,
            'ad1'=>$ad1,
            'ad2'=>$ad2,
        ];
        echoJson(['status'=>1,'msg'=>'','data'=>$data,'pages'=>1]);
    }
    //分类商品数据
    public function categoryGoods()
    {
        $page=I('page',1);
        $parent_id=I('parent_id',0);
        $page_size=10;
        $index=($page-1)*$page_size;

        $where=array('parent_id'=>$parent_id,'audit'=>1,'closed'=>0);
        $count=M('integral_goodslist')->where($where)->count();
        $group_shop1=M('integral_goodslist')
            ->where($where)
            ->order('exchange_sum desc,exchange_sum asc,redbag_price desc')
            ->limit($index,$page_size)
            ->select();
        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($count/$page_size)]);
    }

    //根据条件筛选
    public function selectGoods(){
        $page=I('page',1);
        $keyword=I('keyword');
        $page_size=10;
        $index=($page-1)*$page_size;
        $where['title|intro'] = array('LIKE', '%' . $keyword . '%');
        $where['closed']=array('EQ',0);
        $where['audit']=array('EQ',1);
        if(!empty($keyword) && $keyword!='输入商品的关键字') {
            $goods_count = M('integral_goodslist')->where($where)->count();
            $group_shop1 = M('integral_goodslist')->where($where)->limit($index, $page_size)->select();
        }else{
            $goods_count = M('integral_goodslist')->where(['closed'=>0,'audit'=>1])->count();
            $group_shop1 = M('integral_goodslist')->where(['closed'=>0,'audit'=>1])->limit($index, $page_size)->select();
        }
        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);

    }



}