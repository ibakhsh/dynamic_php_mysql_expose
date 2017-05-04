<?php

header('Content-Type: application/json');
require_once('config/db_functions.php');
$db = new DB_Functions;
$filter = array();
        if($_GET["filter"]) $filter = $_GET["filter"];
if(!$filter) {
    $array = $_GET;
    $filter = array();
    foreach($array as $key=>$value){
        array_push($filter,array($key,$value));
    }
}
echo json_encode($db->getRowsv2("select * from test",$filter));
echo "<br/>";
echo json_encode($db->getError());
echo "<br/>";
?>