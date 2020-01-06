<?php

class ShopbidlogsModel extends CommonModel{
    protected $pk   = 'bid_id';
    protected $tableName =  'shopbidlogs';
	
	//商户资金结算
    public function insertData($order_id,$id, $shop_id, $jiesuan_price, $type, $info){
		
		$config = D('Setting')->fetchAll();
	    // print_r($jiesuan_price);
     //    die;
		//防止二次结算
		if($res = D('Shopmoney')->where(array('shop_id'=>$shop_id,'type'=>$type,'order_id'=>$order_id))->find()){
			return false;
		}
		
		return true;
    }
	
	
    public function sumByIds($bg_date,$end_date,$shop_ids = array()){
        if(empty($shop_ids)) return array();
        $bg_time =  (int) strtotime($bg_date.' 00:00:00');
        $end_time = (int) strtotime($end_date.' 23:59:59');
        $shop_ids = join(',',$shop_ids);
        $datas = $this->query("SELECT  count(1) as num,sum(money) as money from ".$this->getTableName()." where  create_time >= '{$bg_time}' AND  create_time <='{$end_time}' AND shop_id IN({$shop_ids}) ");
        return $datas[0];
    }

      public function sumByIdsTop10($bg_date,$end_date,$shop_ids = array()){
        if(empty($shop_ids)) return array();
        $bg_time =  (int) strtotime($bg_date.' 00:00:00');
        $end_time = (int) strtotime($end_date.' 23:59:59');
        $shop_ids = join(',',$shop_ids);
        $datas = $this->query("SELECT shop_id,sum(money) as money from ".$this->getTableName()." where  create_time >= '{$bg_time}' AND  create_time <='{$end_time}' AND shop_id IN({$shop_ids}) group by  shop_id order by  sum(money) desc limit 0,10 ");
        return $datas;

    }

    

    public function  sum2($bg_date,$end_date){
        $bg_time =  (int) strtotime($bg_date.' 00:00:00');
        $end_time = (int) strtotime($end_date.' 23:59:59');
        return $this->query("SELECT  shop_id,sum(money) as money from ".$this->getTableName()." where  create_time >= '{$bg_time}' AND  create_time <='{$end_time}' group by shop_id ");
    }

    

    public function tjmonthCount($month = '',$shop_id = ''){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        $shop_id = (int)$shop_id;
        if($month && $shop_id){

            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}' and shop_id='{$shop_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id)tb  ";         
        }else{
            if($month){

                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}'  group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id)tb  ";         

            }elseif($shop_id){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id)tb  ";         
            }else{
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id)tb  ";         

            }
        }

        $data = $this->query($sql);
        return (int)$data[0]['num'];


    }

    

    public function tjmonth($month = '',$shop_id = '',$start,$num){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        $start = (int)$start;
        $num = (int)$num;
        $shop_id = (int)$shop_id;
        if($month && $shop_id){
            $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}' and shop_id='{$shop_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id order by FROM_UNIXTIME(create_time,'%Y%m') desc  limit {$start},{$num} ";         
        }else{
            if($month){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}'  group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num}  ";         
            }elseif($shop_id){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num} ";         
            }else{
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc  limit {$start},{$num}  ";         
            }
        }
        $data = $this->query($sql);
        return $data;
    }

    

    

    

    public function tjdayCount($day = '',$shop_id = ''){
        $sql = "";
        $day = $day ? str_replace('-', '', $day): '';
        $shop_id = (int)$shop_id;
        if($day && $shop_id){
            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}' and shop_id='{$shop_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id)tb  ";         
        }else{
            if($day){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}'  group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id)tb  ";         
            }elseif($shop_id){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id)tb  ";         
            }else{
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id)tb  ";         
            }
        }
     	$data = $this->query($sql);
        return (int)$data[0]['num'];
    }

    

    public function tjday($day = '',$shop_id = '',$city_id = '',$start,$num){
        $sql = "";
        $day = $day ? str_replace('-', '', $day): '';
        $start = (int)$start;
        $num = (int)$num;
        $shop_id = (int)$shop_id;
		$city_id = (int)$city_id;
        if($day && $shop_id){
		if(!empty($city_id ))
            	$sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}' and shop_id='{$shop_id}' and city_id='{$city_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id order by FROM_UNIXTIME(create_time,'%Y%m%d') desc  limit {$start},{$num} ";         
    		else
    			$sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}' and shop_id='{$shop_id}'  group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id order by FROM_UNIXTIME(create_time,'%Y%m%d') desc limit {$num},{$start}  ";            

        }else{
			
            if($day){
            	if(!empty($city_id ))
                		$sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}'  group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m%d') desc   limit {$start},{$num}  ";         
           		else
                		$sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m%d') = '{$day}'  group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m%d') desc   limit {$num},{$start}  ";           
            }elseif($shop_id){
			if(!empty($city_id ))
             		   $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'    and city_id='{$city_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m%d') desc   limit {$start},{$num} ";         
          		else
          			   $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m%d') desc  limit {$num},{$start}  ";       
            }else{
			
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m%d')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m%d'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m%d') desc  limit {$num},{$start}  ";         

            }
        }
        
        
        
        
        $data = $this->query($sql);
		//p($this->query($sql));die;
        return $data;
    }

    

     public function tjyearCount($year = '',$shop_id = ''){
        $sql = "";
        $year = (int)$year ;
        $shop_id = (int)$shop_id;
        if($year && $shop_id){
            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y') = '{$year}' and shop_id='{$shop_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id)tb  ";         
        }else{
            if($year){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y') = '{$year}'  group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id)tb  ";         

            }elseif($shop_id){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id)tb  ";         
            }else{
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id)tb  ";         
            }
        }
        $data = $this->query($sql);
        return (int)$data[0]['num'];
    }

    

    public function tjyear($year = '',$shop_id = '',$start,$num){
        $sql = "";
        $year = (int)$year ;
        $start = (int)$start;
        $num = (int)$num;
        $shop_id = (int)$shop_id;
        if($year && $shop_id){
            $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y') = '{$year}' and shop_id='{$shop_id}' group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id order by FROM_UNIXTIME(create_time,'%Y') desc  limit {$start},{$num} ";         
        }else{
            if($year){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y') as m,shop_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y') = '{$year}'  group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id  order by FROM_UNIXTIME(create_time,'%Y') desc   limit {$start},{$num}  ";         
            }elseif($shop_id){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y')  as m,shop_id  FROM ".$this->getTableName()." where shop_id='{$shop_id}'   group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id  order by FROM_UNIXTIME(create_time,'%Y') desc   limit {$start},{$num} ";         
            }else{
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y')  as m,shop_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y'),shop_id  order by FROM_UNIXTIME(create_time,'%Y') desc  limit {$start},{$num}  ";         

            }

        }
        $data = $this->query($sql);
        return $data;
    }


    public function money($bg_time,$end_time,$shop_id){      
        $bg_time   = (int)$bg_time;
        $end_time  = (int)$end_time;
        $shop_id = (int) $shop_id;
        if(!empty($shop_id)){
            $data = $this->query(" SELECT sum(money) as price,FROM_UNIXTIME(create_time,'%m%d') as d from  ".$this->getTableName()."   where  create_time >= '{$bg_time}' AND create_time <= '{$end_time}' AND shop_id = '{$shop_id}'  group by  FROM_UNIXTIME(create_time,'%m%d')");   
        }else{
            $data = $this->query(" SELECT sum(money) as price,FROM_UNIXTIME(create_time,'%m%d') as d from  ".$this->getTableName()."   where  create_time >= '{$bg_time}' AND create_time <= '{$end_time}'  group by  FROM_UNIXTIME(create_time,'%m%d')");      
        }
        $showdata = array();
        $days = array();
        for($i=$bg_time;$i<=$end_time;$i+=86400){
            $days[date('md',$i)] = '\''.date('m月d日',$i).'\''; 
        }
        $price = array();
        foreach($days  as $k=>$v){
            $price[$k] = 0;
            foreach($data as $val){
                if($val['d'] == $k){
                    $price[$k] = $val['price'];
                }
            }
        }
       $showdata['d'] = join(',',$days);
       $showdata['price'] = join(',',$price);
       return $showdata;
    }
	
	
	
	
	//新版带城市的月订单统计数量
	 public function CityMonthCount($month = '',$city_ids = ''){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        for($i=0;$i<count($city_ids);$i++){
			$uname=$uname."'".$city_ids[$i]."',";
		}
	
		$the_city_id ="city_id in(".$uname."'')";
        if($month && $city_ids){
            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,city_id  FROM ".$this->getTableName()." where ".$the_city_id."  and FROM_UNIXTIME(create_time,'%Y%m') = '{$month}'  group  by  FROM_UNIXTIME(create_time,'%Y%m'),city_id)tb  ";         
        }else{
            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,city_id  FROM ".$this->getTableName()." where ".$the_city_id."  group  by  FROM_UNIXTIME(create_time,'%Y%m'),city_id)tb  ";  
        }
        $data = $this->query($sql);
        return (int)$data[0]['num'];


    }
	
	
	//新版带城市的月订单统计输出
    public function CityMonth($month = '',$city_ids = '',$start,$num){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        $start = (int)$start;
        $num = (int)$num;
        for($i=0;$i<count($city_ids);$i++){
			$uname=$uname."'".$city_ids[$i]."',";
		}
		$the_city_id ="city_id in(".$uname."'')";
        if($month && $city_ids){
			$sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,city_id  FROM ".$this->getTableName()." where ".$the_city_id."  and FROM_UNIXTIME(create_time,'%Y%m') = '{$month}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),city_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num} ";   
        }else{
         $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,city_id  FROM ".$this->getTableName()." where ".$the_city_id."   group  by  FROM_UNIXTIME(create_time,'%Y%m'),city_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num} ";  
        }
        $data = $this->query($sql);
        return $data;
    }
	//新版地区统计
	 public function AreaMonthCount($month = '',$area_id = ''){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        $area_id = (int)$area_id;
        if($month && $area_id){
            $sql="select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,area_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}' and area_id='{$area_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id)tb  ";         
        }else{
            if($month){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,area_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}'  group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id)tb  ";         

            }elseif($area_id){
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,shop_id  FROM ".$this->getTableName()." where area_id='{$area_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id)tb  ";         
            }else{
                $sql=" select count(1) as num from (SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,area_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id)tb  ";         
            }
        }
        $data = $this->query($sql);
        return (int)$data[0]['num'];
    }
	
	//新版地区数据
	  public function AreaMonth($month = '',$area_id = '',$start,$num){
        $sql = "";
        $month = $month ? str_replace('-', '', $month): '';
        $start = (int)$start;
        $num = (int)$num;
        $area_id = (int)$area_id;
        if($month && $area_id){
            $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,area_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}' and area_id='{$area_id}' group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id order by FROM_UNIXTIME(create_time,'%Y%m') desc  limit {$start},{$num} ";         
        }else{
            if($month){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m') as m,area_id  FROM ".$this->getTableName()." where FROM_UNIXTIME(create_time,'%Y%m') = '{$month}'  group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num}  ";         
            }elseif($area_id){
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,area_id  FROM ".$this->getTableName()." where area_id='{$area_id}'   group  by  FROM_UNIXTIME(create_time,'%Y%m'),shop_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc   limit {$start},{$num} ";         
            }else{
                $sql="SELECT sum(money) as money,FROM_UNIXTIME(create_time,'%Y%m')  as m,area_id  FROM ".$this->getTableName()."    group  by  FROM_UNIXTIME(create_time,'%Y%m'),area_id  order by FROM_UNIXTIME(create_time,'%Y%m') desc  limit {$start},{$num}  ";         
            }
        }
        $data = $this->query($sql);
        return $data;
    }
	
	
	


}