<?php 

$userRoles = array("root","sysAdmin","owner","parkingAdmin","vip","customer");


$actionMap = array(
               array('testPost'=>'test'         ,'method'=>'POST'),
               array('testGet'=>'test'          ,'method'=>'GET'),
               array('testPut'=>'test'          ,'method'=>'PUT'),
               array('getActiveBookings'=>'active_bookings'          ,'method'=>'GET', 'adWhere'=>array("")),
               array('login'=>'login.php'       ,'method'=>'POST', 'required_keys'=>array("email","password")),
               array('register'=>'register.php' ,'method'=>'POST', 'unique_keys'=>array(array("username"),array("email"),array("id")), 'required_keys'=>array("email","password","firstname","lastname","phone"))
            );

?>