<?php



class AddDataAction extends CommonAction{
	
	
	//首页热门商品数据
	public function add(){
//        $side_ids1=array(89,95,101,107,113,119,125,136,137);//手机首页下部分板块
//        $side_ids2=array(219,220,221,222,223,224,225,226,227);//手机首页商家店铺
//        $side_ids3=array(250,251,252,253,254,255,256,257,258);//手机首页商家商品
//        $side_ids4=array(268,269,270,271,272,273,274,275,276);//手机首页查看更多商品

//        $side_ids1=array(144,152,160,168,179,187,195,203,211);//手机积分商城首页下部分板块
//        $side_ids2=array(239,240,241,242,243,244,245,246,247);//手机积分商城首页商家店铺
//        $side_ids3=array(259,260,261,262,263,264,265,266,267);//手机积分商城首页商家商品

//        $side_ids1=array(322,323,324,325,326,327,328,329,330);//手机0元购首页下部分板
//        $side_ids2=array(331,332,333,334,335,336,337,338,339);//手机0元购首页店铺板块
//        $side_ids3=array(340,341,342,343,344,345,346,347,348);//手机0元购首页商品板块

//        $side_ids1=array(349,350,351,352,353,354,355,356,357);//手机在线抢购首页商品板块
//        $side_ids2=array(358,359,360,361,362,363,364,365,366);//手机在线抢购首页商家店铺
//        $side_ids3=array(367,368,369,370,371,372,373,374,375);//手机在线抢购首页商家商品

        $side_ids1=array(376,377,378,379,380,381,382,383,384);//手机商城下部分板块
        $side_ids2=array(385,386,387,388,389,390,391,392,393);//手机商城商家店铺
        $side_ids3=array(394,395,396,397,398,399,400,401,402);//手机商城商家商品
        $side_ids4=array(403,404,405,406,407,408,409,410,411);//手机商城查看更多商品

        $data=[
            'site_id'=>0,
            'city_id'=>0,
            'user_id'=>0,
            'title'=>'快餐外卖',
            'link_url'=>'https://avycbh.zgtianxin.net/wap/ele/index.html?nav_id=54',
            'photo'=>'/attachs/2019/01/16/5c3ed383289af.jpg',
            'code'=>'吃喝红包嗨个不停',
            'prestore_integral'=>0,
            'click'=>0,
            'is_target'=>0,
            'bg_date'=>'2018-01-14',
            'end_date'=>'2020-01-16',
            'reset_time'=>0,
            'closed'=>0,
            'orderby'=>0,
            'shop_id'=>124,
            'shop_name'=>'',
            'logo'=>'',
            'introduce'=>'',
            'goods_id'=>34,
            'integral_goods_id'=>11,
            'pindan_id'=>14,
            'tuan_id'=>13,
            'shop_goods_id'=>34,
        ];
        foreach($side_ids4 as $site_id){
            $data['site_id']=$site_id;
            for($i=0;$i<20;$i++){
                M('ad')->add($data);
            }
        }
        die('数据添加成功');
	}

	public  function add2(){
        $data=[
            'theme'=>'default',
            'site_type'=>2,
            'site_place'=>17,
            'site_price'=>0,
        ];

        for($i=0;$i<9;$i++){
            $data['site_name']='手机商城查看更多商品'.$this->getChines($i);
            M('ad_site')->add($data);
        }

    }

    public  function getChines($num){
        $arr=['一','二','三','四','五','六','七','八','九'];
        return $arr[$num];
    }
}