<?php
class PindandetailsModel extends CommonModel
{
    protected $pk = 'tuan_id';
    protected $tableName = 'pindan_details';
    public function getDetail($tuan_id)
    {
        $data = $this->find($tuan_id);
        if (empty($data)) {
            $data = array('tuan_id' => $tuan_id);
            $this->add($data);
        }
        return $data;
    }
}