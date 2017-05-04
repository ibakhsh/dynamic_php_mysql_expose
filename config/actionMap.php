<?php 
$actionMap = array(
               array('parkingGet'=>'parking'), 
               array('listGet'=>'static_lists'),
               array('userGet'=>'user','unset_columns'=>array('username','password')),
               array('userPost'=>'user'),
               array('categoryGet'=>'categories'),
               array('serviceGet'=>'service'),
               array('testPost'=>'test'),
               array('testGet'=>'test')
            );
?>