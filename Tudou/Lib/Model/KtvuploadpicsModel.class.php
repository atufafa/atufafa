<?php
/**
 * KTV用户上传图片模型
 * @author  pingdan <[<email address>]>
 */
class KtvuploadpicsModel extends CommonModel
{
    protected $pk = 'pic_id';
    protected $tableName = 'ktv_upload_pics';

    /**
     * Ktv上传图片
     * @author pingdan <[<email address>]>
     * @param  [type] $order_id [description]
     * @param  [type] $photos   [description]
     * @param  [type] $type     type图片类型，1点评，2退款
     * @return [type]           [description]
     */
    public function upload($order_id, $photos, $type)
    {
        $order_id = (int) $order_id;
        $type = (int) $type;
        $this->delete(array("where" => array('order_id' => $order_id, 'type' => $type))); 
        foreach ($photos as $val) {
            $this->add(array('pic' => $val, 'order_id' => $order_id, 'type' => $type));
        }
        return true;
    }

    /**
     * 获取图片
     * @author pingdan <[<email address>]>
     * @param  [type] $order_id [description]
     * @param  [type] $type     [description]
     * @return [type]           [description]
     */
    public function getPics($order_id, $type)
    {
        $order_id = (int) $order_id;
        $type = (int) $type;
        return $this->where(array('order_id' => $order_id, 'type' => $type))->select();
    }
}