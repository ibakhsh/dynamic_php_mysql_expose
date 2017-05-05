<?php 
$actionMap = array(
               array('parkingGet'=>'parking', 'method'=>'GET'), 
               array('listGet'=>'static_lists', 'method'=>'GET'),
               array('userGet'=>'user', 'method'=>'POST','unset_columns'=>array('username','password','slash')),
               array('userPost'=>'user', 'method'=>'POST'),
               array('categoryGet'=>'categories', 'method'=>'GET'),
               array('serviceGet'=>'service', 'method'=>'GET'),
               array('testPost'=>'test', 'method'=>'POST'),
               array('testGet'=>'test', 'method'=>'GET'),
               array('login'=>'login.php', 'method'=>'POST'),
               array('register'=>'register.php', 'method'=>'POST')
            );
?>