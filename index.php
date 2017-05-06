<?php
header('Content-Type: application/json');

//main arrays for the app 
$response = array();
$errorList = array();
$response["error"] = false;

//Handle different type of requests and request wildcards 
$method = $_SERVER['REQUEST_METHOD'];
$request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

include_once('config/actionMap.php');

switch ($method) {
  case 'POST':
    process_request($request, $method);
    //process_request_post($request);  
    break;
  case 'GET':
    process_request($request, $method);
    //process_request_get($request);  
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
        $action_name = $action[$req[0]];
        if($action["method"]!=$request_method)  {
            array_push($errorList,'request method and called api mismatch!'.$action["method"]);
            return false;
        } else {
            $response["request_info"] = array('method'=>$request_method,'request'=>$req[0]);
            $posted_keys = array();
            if($request_method=="POST"){
                $posted_keys = array_keys($_POST);
            } else {
                $posted_keys = array_keys($_GET);
            }
            $required_keys = $action['required_keys'];
            $keysNotFound = "";
            if(is_array($required_keys)){
                foreach($required_keys as $rKey){
                    if (!in_array($rKey,$posted_keys)){
                        $keysNotFound .= $rKey.",";
                    }
                }
                if(strlen($keysNotFound)>0){
                    $keysNotFound = substr($keysNotFound,0,strlen($keysNotFound)-1);
                    array_push($errorList,"Required parameters: $keysNotFound are missing!");
                    return false;
                } 
            }
            if(strpos($action_name,'.php')) {
                include_once($action_name);
                return true;
            } elseif ($request_method=="POST"){
                process_request_post($req, $action);
            } elseif($request_method=="GET") {
                process_request_get($req, $action);
            } else {
                return $action;
            }
        }
    }
}

function process_request_get($req, $requestAction){
    global $method;
    global $errorList;
    global $response;
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
            $response["data"] = $result;
            $response["rowCount"] = $db->getRowCount();
        }
}


function process_request_post($req, $requestAction){
    global $method;
    global $errorList;
    global $response;

        include_once('config/db_functions.php');
        $db = new DB_Functions();
        /**TODO:
        * 1.Workout update solution (set pk_columns so it can be searched then decided)
        * 2.set datetime columns so they can be customized (think getting column type from db & autoconfig it)
        */
        $table = $requestAction[$req[0]];
        $json = $_POST;
        $result = $db->insertRowCheckInjections($table,$json);
        if ($db->getError()){
            array_push($errorList,$requestAction[$req[0]].":".json_encode($db->getError()));
        } else {
            $response["data"] = $result;
            $response["rowCount"] = $db->getRowCount();
            $response["pk_code"] = $db->getPk_Code();
        }
}


if(count($errorList)){
        $response["error"] = true;
        $response["error_messages"] = $errorList;
}

if(!$response["rowCount"] && is_array($response["data"]))   $response["rowCount"] = count($response["data"]);

echo json_encode($response);

?> 