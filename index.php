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
  case 'PUT':
    if(count($request)<1) {
        array_push($errorList,'put requires params for the primary key as json object.');
        break;
    } else {
        $pk_json = $request[1];
        $pk_json = json_decode($pk_json);
        if(gettype($pk_json)!=object) {
            array_push($errorList,'sorry parameters for primary key must be a json object like: ...~index.php/'.$request[0].'/{"ID":"5"}');
            break;
        } else {
            process_request($request, $method, $pk_json);
            break;
        }
    }
  default:
    array_push($errorList,'sorry, only Get & Post are allowed.');
    break;
}

$action = array();

function process_request($req, $request_method, $pk_json=null){
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
            } elseif($request_method=="PUT") {
                process_request_put($req, $action, $pk_json);
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
        if(key_exists("filter",$_GET)) $filter = json_decode($_GET["filter"]);
        if(!$filter) {
            $array = $_GET;
            $filter = array();
            foreach($array as $key=>$value){
                array_push($filter,array($key,$value));
            }
        }
        //echo json_encode($filter);
        //exit;
        
        $filterColumns = array();
        foreach($filter as $filterRow){
            array_push($filterColumns,$filterRow[0]);
        }

        $check_columns = $db->checkTableColumns($requestAction[$req[0]],$filterColumns);
        
        if(is_array($check_columns)) {
            array_push($errorList,$requestAction[$req[0]].":".json_encode($check_columns));
            return false;
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


function process_request_put($req, $requestAction, $pk_json){
    global $method;
    global $errorList;
    global $response;

        include_once('config/db_functions.php');
        $db = new DB_Functions();
        $table = $requestAction[$req[0]];
        parse_str(file_get_contents("php://input"),$json);
        if(count($json)==0) $json = $_POST;
        if(count($json)==0) $json = $_GET;
        $result = $db->updateRowCheckInjections($table,$json, $pk_json);
        if ($db->getError()){
            array_push($errorList,$requestAction[$req[0]].":".json_encode($db->getError()));
        } else {
            $response["data"] = $result;
            $response["rowCount"] = $db->getRowCount();
            //$response["pk_code"] = $db->getPk_Code();
        }
        $response["sql_statement"] = $db->getSqlStatement();
}

if(count($errorList)){
        $response["error"] = true;
        $response["error_messages"] = $errorList;
        http_response_code(400);
}

if(!$response["rowCount"] && is_array($response["data"]))   $response["rowCount"] = count($response["data"]);
if(!($_GET["debug"])) if(strlen($response["sql_statement"])>0) unset($response["sql_statement"]);
echo json_encode($response);

?> 