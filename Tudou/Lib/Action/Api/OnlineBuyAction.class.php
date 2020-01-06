<?php
//在线抢购
class OnlineBuyAction extends CommonAction{

    //首页热门商品数据
    public function hotGoods(){

        $page=I('page',1);
        $side_ids1=array(349,350,351,352,353,354,355,356,357);//手机在线抢购首页商品板块
        $side_ids2=array(358,359,360,361,362,363,364,365,366);//手机在线抢购首页商家店铺
        $side_ids3=array(367,368,369,370,371,372,373,374,375);//手机在线抢购首页商家商品
        $page_size=16;
        $index=($page-1)*$page_size;

//        $goods=M('ad')
//            ->alias('a')
//            ->join('tu_tuan t ON (a.tuan_id = t.tuan_id)','left')
//            ->field('t.*')
//            ->where(array('a.site_id'=>$side_ids1[$page-1]))
//            ->order('t.jiangjia_money desc,t.sold_num desc,t.tuan_price asc')
//            ->limit($index,$page_size)
//            ->select();

        $goods=M('tuan')
            ->where(['closed'=>0,'audit'=>1])
            ->order('check_price desc,sold_num desc,tuan_price asc')
            ->limit($index,$page_size)
            ->select();

        $ad1=M('ad')->where(array('site_id'=>$side_ids2[$page-1]))->field('ad_id,shop_id,shop_name,logo,introduce')->find();
       
        $ad2=M('ad')
            ->alias('a')
            ->join('tu_tuan t ON (a.tuan_id = t.tuan_id)','left')
            ->field('t.*')
            ->where(array('a.site_id'=>$side_ids3[$page-1],'t.closed'=>0,'t.audit'=>1))
            ->order('t.jiangjia_money desc,t.sold_num desc,t.tuan_price asc')
            ->limit(6)
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
        $count=M('tuan')->where($where)->count();
        $group_shop1=M('tuan')
            ->where($where)
            ->order('sold_num desc,tuan_price asc')
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
        if(!empty($keyword) && $keyword!='输入抢购的关键字'){
            $goods_count = M('tuan')->where($where)->count();
            $group_shop1 = M('tuan')->where($where)->limit($index,$page_size)->select();
        }else{
            $goods_count = M('tuan')->where(['closed'=>0,'audit'=>1])->count();
            $group_shop1 = M('tuan')->where(['closed'=>0,'audit'=>1])->limit($index,$page_size)->select();
        }

        echoJson(['status'=>1,'msg'=>'','data'=>$group_shop1,'pages'=>ceil($goods_count/$page_size)]);

    }

	
}