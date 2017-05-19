<?php
require_once 'config/db_Functions.php';
$db = new DB_Functions();
 
// receiving the post params
$email = $_POST['email'];
$password = $_POST['password'];
$sql = "select * from user where (username='$email' or email='$email')";
$user = $db->getRows($sql);

if($db->getRowCount()<1){
    array_push($errorList,"No credentials found.");
    return false;
} else {
    $pwd = $db->checkhashSSHA($user[0]["salt"],$password);
    if($pwd!=$user[0]["password"]){
        array_push($errorList,"username or password is wrong.");
        return false;
    } else {

        /*
        some other code 
        */

        // Start the session
        session_start();
        $user = $user[0];
        unset($user["username"]);
        unset($user["password"]);
        unset($user["salt"]);
        unset($user["registrationDate"]);
        $_SESSION['user'] = $user;
        $response["data"] = array($user);
        //i have added a line 
    }
}
?>