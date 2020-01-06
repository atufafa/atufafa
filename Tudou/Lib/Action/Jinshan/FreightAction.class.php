<?php		
class FreightAction extends CommonAction{

	//显示
	public function index(){
		$list=D('Freight')->select();
		$zdlist=D('Zdfreifht')->select();
		
		$vehicle=D('Vehicle')->select();

		$this->assign('list', $list);
		$this->assign('zdlist',$zdlist);
		$this->assign('vehicle',$vehicle);
		$this->display();
	}


	//添加
	public function up(){
		
		 if ($this->isPost()) {
		 	$data=array();
		 	$data['price_1']=I('post.price_1');
		 	$data['distance_1']=I('post.distance_1');

		 	$data['price_2']=I('post.price_2');
		 	$data['distance_2']=I('post.distance_2');

		 	$data['price_3']=I('post.price_3');
		 	$data['distance_3']=I('post.distance_3');

		 	$data['price_4']=I('post.price_4');
		 	$data['distance_4']=I('post.distance_4');

		 	$data['price_5']=I('post.price_5');
		 	$data['distance_5']=I('post.distance_5');

		 	$data['radius']=I('post.radius');
		 	$data['chajia']=I('post.chajia');

		 	$datas=array();
		 	$datas['zdprice_1']=I('post.zdprice_1');
		 	$datas['zddistance_1']=I('post.zddistance_1');

		 	$datas['zdprice_2']=I('post.zdprice_2');
		 	$datas['zddistance_2']=I('post.zddistance_2');

		 	$datas['zdprice_3']=I('post.zdprice_3');
		 	$datas['zddistance_3']=I('post.zddistance_3');

		 	$datas['zdprice_4']=I('post.zdprice_4');
		 	$datas['zddistance_4']=I('post.zddistance_4');

		 	$datas['zdprice_5']=I('post.zdprice_5');
		 	$datas['zddistance_5']=I('post.zddistance_5');

		 	$datas['zdchajia']=I('post.zdchajia');

		 	$datasss=array();
		 	$datasss['vehicle_price']=I('post.vehicle_price');
		 	$datasss['vehicle_distance']=I('post.vehicle_distance');
		 	$datasss['vehicle_jiaja']=I('post.vehicle_jiaja');
		 	$datasss['vehiclechajia']=I('post.vehiclechajia');
	
		 	$gai=D('Freight')->where(array('p_id'=>1))->save($data);	
			$gais=D('Zdfreifht')->where(array('zd_id'=>1))->save($datas);
			$gaisss=D('Vehicle')->where(array('v_id'=>1))->save($datasss);


			$this->tuSuccess('保存成功', U('freight/index'));
			 	
		}
	}

}
