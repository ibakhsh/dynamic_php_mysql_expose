<?php
require_once 'config/db_Functions.php';
$db = new DB_Functions();
$params = $_POST;
if(!$params["password"]){
    array_push($errorList,"password must be entered.");
    return false;
} else {
    if(!$params["username"]) $params["username"] = $params["email"];
    $email = $params["email"];
    $sql = "select * from user where (username='$email' or email='$email')";
    $user = $db->getRows($sql);

    if($db->getRowCount()>0){
        array_push($errorList,"username or email provided already registered.");
        return false;
    }  else {
        $pwdArray = $db->hashSSHA($params["password"]);
        $params["password"] = $pwdArray["encrypted"];
        $params["salt"] = $pwdArray["salt"];
        
        $result = $db->insertRowCheckInjections("user",$params);
        if ($db->getError()){
            array_push($errorList,json_encode($db->getError()));
            return false;
        } else {
            $user = $result[0];
            unset($user["username"]);
            unset($user["password"]);
            unset($user["salt"]);
            unset($user["registrationDate"]);
            $response["data"] = array($user);
            $response["rowCount"] = $db->getRowCount();
            $response["pk_code"] = $db->getPk_Code();
            return true;
        }
    }
}