<?php
header('Content-Type: application/json');

//main arrays for the app 
$response = array();
$errorList = array();


//Handle different type of requests and request wildcards 
$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

include_once('config/actionMap.php');

switch ($method) {
  case 'POST':
    process_request_post($request);  
    break;
  case 'GET':
    process_request_get($request);  
    break;
  default:
    array_push($errorList,'sorry, only Get & Post are allowed.');
    break;
}

$action = array();

function process_request($req, $request_method){
    global $response;
    global $errorList;
    global $action;
    global $actionMap;
    $found = false;
    
    foreach($actionMap as $row){
        $key = array_keys($row);
        if($key[0]==$req[0]) {
            $found = true;
            $action = $row;
            break;
        }
    }
    
    if(!$found){
        array_push($errorList,'invalid action!');
        return false;
    } else {
        $key = array_keys($action);
        $len = strlen($request_method) * -1;
        $actionMethod = strtoupper(substr($key[0],$len));
        if($actionMethod != $request_method)  {
            array_push($errorList,'request method and called api mismatch!'.$actionMethod);
            return false;
        } else {
            $request_info = array('request_info'=>array('method'=>$request_method,'request'=>$req[0]));
            array_push($response,$request_info);
            return $action;
        }
    }
}

function process_request_get($req){
    global $method;
    global $errorList;
    global $response;
    $requestAction = process_request($req, $method);
    if($requestAction) {
        include_once('config/db_functions.php');
        $db = new DB_Functions();
        //TODO:change * with $_GET() a list of columns to populate
        $sql = "select * from ".$requestAction[$req[0]];
        $filter = array();
        if($_GET["filter"]) $filter = $_GET["filter"];
        if(!$filter) {
            $array = $_GET;
            $filter = array();
            foreach($array as $key=>$value){
                array_push($filter,array($key,$value));
            }
        }
        $result = $db->getRowsv2($sql,$filter);
        if ($db->getError()){
            array_push($errorList,$requestAction[$req[0]].":".json_encode($db->getError()));
        } else {
            $data = array("data"=>$result);
            array_push($response,$data);
            array_push($response,array("rowCount"=>$db->getRowCount()));
        }
    }
}


function process_request_post($req){
    global $method;
    global $errorList;
    global $response;
    $requestAction= process_request($req, $method);
    if($requestAction){
        include_once('config/db_functions.php');
        $db = new DB_Functions();
        /**TODO:
        * 1.Workout update solution (set pk_columns so it can be searched then decided)
        * 2.set datetime columns so they can be customized
        */
        $table = $requestAction[$req[0]];
        $json = $_POST;
        //array_push($response,array("POST",$json));
        //$result = $db->insertRow($table,$json);
        $result = $db->insertRowCheckInjections($table,$json);
        if ($db->getError()){
            array_push($errorList,$requestAction[$req[0]].":".json_encode($db->getError()));
        } else {
            array_push($response,array("data"=>$result));
            array_push($response,array("rowCount"=>$db->getRowCount()));
            array_push($response,array("pk_code"=>$db->getPk_Code()));
        }
    }
}

if(count($errorList)){
    array_push($response,array('error'=>true));
    array_push($response,array('error_messages'=>$errorList));
} else {
    array_push($response,array('error'=>false));
}

$rObj["error"] = false;
foreach($response as $responseObj){
    foreach($responseObj as $rkey=>$rval){
        $rObj[$rkey] = $rval;
    }
}
echo json_encode($rObj);

//echo json_encode($response);

?> 