<?php
//在线抢购
class MobileShopAction extends CommonAction{

    //首页热门商品数据
    public function hotGoods(){

        $page=I('page',1);
        $side_ids1=array(376,377,378,379,380,381,382,383,384);//手机商城下部分板块
        $side_ids2=array(385,386,387,388,389,390,391,392,393);//手机商城商家店铺
        $side_ids3=array(394,395,396,397,398,399,400,401,402);//手机商城商家商品
        $side_ids4=array(403,404,405,406,407,408,409,410,411);//手机商城查看更多商品
        $page_size=14;
        $index=($page-1)*$page_size;

//        $goods=M('ad')
//            ->alias('a')
//            ->join('tu_goods g ON (a.shop_goods_id = g.goods_id)','left')
//            ->field('g.*')
//            ->where(array('a.site_id'=>$side_ids1[$page-1]))
//            ->order('g.sold_num desc,g.check_price desc,g.mall_price asc')
//            ->limit($index,$page_size)
//            ->select();

        //获取平台商店ID号
        $shop_id = M('shop')->where("mobile='13689551286'")->getField('shop_id');
        //获取商家的数量
        $goods_list = M()->query("SELECT count(*) as tp_count FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
            shop_id != ".$shop_id." AND huodong=0 AND closed=0 AND audit=1  ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
            (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND is_show = 1 AND huodong=0 AND closed=0 AND audit=1)) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC");
        $goods_count = $goods_list[0]['tp_count']; 
        //每家店只允许上传一次热门产品
        $goods = M()->query("SELECT * FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
        shop_id != ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
        (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND is_show = 1 AND huodong=0 AND closed=0 AND audit=1)) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC limit ".$index.", ".$page_size);

        $ad1=M('ad')->where(array('site_id'=>$side_ids2[$page-1]))->field('ad_id,shop_id,shop_name,logo,introduce')->find();
        //echo M('ad')->getLastSql();
        if(empty($ad1)){
            $ad1['ad_id'] = 224;
            $ad1['shop_id'] = 9999;
            $ad1['shop_name'] = '';
            $ad1['logo'] = '';
            $ad1['introduce'] = '';
        }

        $ad2=M('ad')
            ->alias('a')
            ->join('tu_goods g ON (a.goods_id = g.goods_id)','left')
            ->field('g.*')
            ->where(array('a.site_id'=>$side_ids3[$page-1],'g.closed'=>0,'g.audit'=>1))
            ->order('g.sold_num desc,g.check_price desc,g.mall_price asc')
            ->limit(6)
            ->select();
        $ad3=M('ad')->where(array('site_id'=>$side_ids4[$page-1]))->field('ad_id,link_url')->find();
        $data=[
            'goods'=> $goods,
            'ad1'=> $ad1,
            'ad2'=> $ad2,
            'ad3'=> $ad3,
        ];
        echoJson(['status'=>1,'msg'=>'','data'=>$data,'pages'=> ceil($goods_count / $page_size)]);
    }
    //分类商品数据
    public function categoryGoods()
    {
        $page=I('page',1);
        $parent_id=I('parent_id',0);
        $page_size=10;
        $index=($page-1)*$page_size;

        //获取平台商店ID号
        $shop_id = M('shop')->where("mobile='13689551286'")->getField('shop_id');
        //获取商家的数量
        $goods_list = M()->query("SELECT count(*) as tp_count FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
            shop_id != ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 AND parent_id = ".$parent_id." ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
            (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 AND parent_id = ".$parent_id." AND is_show = 1)) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC");
        $goods_count = $goods_list[0]['tp_count'];

        $group_shop1 = M()->query("SELECT * FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
            shop_id != ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 AND parent_id = ".$parent_id." ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
            (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 AND parent_id = ".$parent_id." AND is_show = 1)) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC limit ".$index.", ".$page_size);
        //echo M('goods')->getLastSql();
        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);
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
        $where['huodong']=array('EQ',0);
        if(!empty($keyword) && $keyword!='输入商品的关键字') {
            $goods_count = D('Goods')->where($where)->count();
            $group_shop1 = D('Goods')->where($where)->limit($index, $page_size)->select();
        }else{
            $goods_count = D('Goods')->where(['closed'=>0,'huodong'=>0,'audit'=>1])->count();
            $group_shop1 = D('Goods')->where(['closed'=>0,'huodong'=>0,'audit'=>1])->limit($index, $page_size)->select();
        }
        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);

    }









	
}