<?php
class WapAction extends CommonAction{
	//首页热门商品数据
	public function hotGoods(){
	    $page=I('page',1);
        $side_ids1=array(89,95,101,107,113,119,125,136,137);//手机首页下部分板块
        $side_ids2=array(219,220,221,222,223,224,225,226,227);//手机首页商家店铺
        $side_ids3=array(250,251,252,253,254,255,256,257,258);//手机首页商家商品
        $side_ids4=array(268,269,270,271,272,273,274,275,276);//手机首页查看更多商品
        $page_size=14;

        $index=($page-1)*$page_size;

//        $goods=M('ad')
//            ->alias('a')
//            ->join('tu_goods g ON (a.goods_id = g.goods_id)','left')
//            ->field('g.*')
//            ->where(array('a.site_id'=>$side_ids1[$page-1]))
//            ->order('g.sold_num desc,g.check_price desc,g.mall_price asc')
//            ->limit($index,$page_size)
//            ->select();

       //获取平台商店ID号
       $shop_id = M('shop')->where("mobile='13689551286'")->getField('shop_id');
       //获取商家的数量
       $goods_list = M()->query("SELECT count(*) as tp_count FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
           shop_id != ".$shop_id." AND huodong = 0 AND closed=0 AND audit=1 ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
           (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND is_show = 1 AND huodong=0 AND closed=0 AND audit=1 )) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC");
       $goods_count = $goods_list[0]['tp_count'];
       //每家店只允许上传一次热门产品
       $goods = M()->query("SELECT *  FROM ((SELECT * FROM (SELECT * FROM tu_goods WHERE
       shop_id != ".$shop_id." AND huodong=0 AND closed=0 AND audit=1 ORDER BY sold_num DESC, check_price DESC, mall_price ASC) tmp GROUP BY shop_id) UNION all
       (SELECT * FROM tu_goods WHERE shop_id = ".$shop_id." AND is_show = 1 AND huodong=0 AND closed=0 AND audit=1 )) tmp2 ORDER BY sold_num DESC, check_price DESC, mall_price ASC limit ".$index.", ".$page_size);
        $ad1=M('ad')->where(array('site_id'=>$side_ids2[$page-1]))->field('ad_id,shop_id,shop_name,logo,introduce')->find();

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
            'goods'=>$goods,
            'ad1'=>$ad1,
            'ad2'=>$ad2,
            'ad3'=>$ad3,
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
        //$group_shop1=M('goods')->where($where)->limit($index,$page_size)->select();
        
        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);
    }
}