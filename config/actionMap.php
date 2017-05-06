<?php 
$actionMap = array(
               array('testPost'=>'test', 'method'=>'POST'),
               array('testGet'=>'test', 'method'=>'GET'),
               array('login'=>'login.php','method'=>'POST', 'required_keys'=>array("email","password"))
            );
?>