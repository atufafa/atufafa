<?php
class EduCommentPicsModel extends CommonModel{
    protected $pk   = 'photo_id';
    protected $tableName =  'edu_comment_pics';
    
     
    public function upload($comment_id,$photos){
        $comment_id = (int)$comment_id;
        $this->delete(array("where"=>array('comment_id'=>$comment_id)));
        foreach($photos as $val){
            $this->add(array('photo'=>$val,'comment_id'=>$comment_id));
        }
        return true;
    }
	
	 public function get_pic($comment_id){
        $comment_id = (int)$comment_id;
        return $this->where(array('comment_id'=>$comment_id))->select();
    }
     
}