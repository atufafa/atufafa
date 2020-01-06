<?php

class ZeroBuyAction extends CommonAction{

    //首页热门商品数据
    public function hotGoods(){

        $page=I('page',1);
        $side_ids1=array(322,323,324,325,326,327,328,329,330);//手机0元购首页下部分板
        $side_ids2=array(331,332,333,334,335,336,337,338,339);//手机0元购首页店铺板块
        $side_ids3=array(340,341,342,343,344,345,346,347,348);//手机0元购首页商品板块
        $page_size=16;
        $index=($page-1)*$page_size;

//        $goods=M('ad')
//            ->alias('a')
//            ->join('tu_pindan p ON (a.pindan_id = p.tuan_id)','left')
//            ->field('p.*')
//            ->where(array('a.site_id'=>$side_ids1[$page-1]))
//            ->order('p.sold_num desc,p.price asc')
//            ->limit($index,$page_size)
//            ->select();

        $goods=M('pindan')
            ->where(['closed'=>0,'audit'=>1])
            ->order('check_price desc,sold_num desc,price asc')
            ->limit($index,$page_size)
            ->select();

        $ad1=M('ad')->where(array('site_id'=>$side_ids2[$page-1]))->field('ad_id,shop_id,shop_name,logo,introduce')->find();

        $ad2=M('ad')
            ->alias('a')
            ->join('tu_pindan p ON (a.pindan_id = p.tuan_id)','left')
            ->field('p.*')
            ->where(array('a.site_id'=>$side_ids3[$page-1],'p.closed'=>0,'p.audit'=>1))
            ->order('p.sold_num desc,p.price asc')
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

        $where=array('parent_id'=>$parent_id,'closed'=>0,'audit'=>1);
        $count=M('pindan')->where($where)->count();
        $group_shop1=M('pindan')
            ->where($where)
            ->order('sold_num desc,price asc')
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
        if(!empty($keyword) && $keyword!='输入商品的关键字'){
            $goods_count = M('pindan')->where($where)->count();
            $group_shop1 = M('pindan')->where($where)->limit($index,$page_size)->select();
        }else{
            $goods_count = M('pindan')->where(['closed'=>0,'audit'=>1])->count();
            $group_shop1 = M('pindan')->where(['closed'=>0,'audit'=>1])->limit($index,$page_size)->select();
        }

        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);

    }

	
}