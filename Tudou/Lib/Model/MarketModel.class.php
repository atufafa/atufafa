<?php
class MarketModel extends CommonModel{
    protected $pk = 'shop_id';
    protected $tableName = 'market';
    public function updateMonth($shop_id){
        $shop_id = (int) $shop_id;
        $month = date('Ym', NOW_TIME);
        $num = (int) D('Marketorder')->where(array('shop_id' => $shop_id, 'month' => $month))->count();
        return $this->execute("update " . $this->getTableName() . " set  month_num={$num} where shop_id={$shop_id}");
    }
    public function getMarketCate(){
        $result = M('Marketcate')->where(['is_look'=>1,'status'=>0,'parent_id'=>0])->select();
        foreach ($result as $key => $value) {
            $data[$value['cate_id']] = $value;
        }
        // print_r($result);die;
        return $data;
  //       return array(
		// 	'1' => '蔬菜类', 
		// 	'2' => '水果类', 
		// 	'3' => '海鲜类', 
		// 	'4' => '干菜类', 
		// 	'5' => '调料类', 
		// 	'6' => '肉类',
		// 	'7' => '凉菜类',
		// 	'8' => '其他类',
		// );
    }
      public function getEleCateIds()
    {
        $where = " parent_id >0";
        $result =M('Marketcate')->where($where)->select();
		$data2 = array();
        foreach ($result as $key => $value) {
            $data[$value['parent_id']] = $value;
			array_push($data2,$value);
        }
	//	print_r($data2);
        return $data2;
    }
    public function getAllEleCate()
    {
        $result =M('Marketcate')->select();
        foreach ($result as $key => $value) {
            $data[$value['cate_id']] = $value;
        }
        return $data;
    }
	public function get_file_Code($shop_id,$size){
        $url = U('wap/market/shop', array('shop_id' => $shop_id, 't' => NOW_TIME, 'sign' => md5($shop_id . C('AUTH_KEY') . NOW_TIME)));
        $token = 'shop_id_' . $shop_id;
        $file = ToQrCode($token, $url,$size);
        return $file;
    }
    public function CallDataForMat($items){
        if (empty($items)) {
            return array();
        }
        $obj = D('Shop');
        $shop_ids = array();
        foreach ($items as $k => $val) {
            $shop_ids[$val['shop_id']] = $val['shop_id'];
        }
        $shops = $obj->itemsByIds($shop_ids);
        foreach ($items as $k => $val) {
            $val['shop'] = $shops[$val['shop_id']];
            $items[$k] = $val;
        }
        return $items;
    }
}