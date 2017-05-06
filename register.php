<?php
 
require_once 'config/db_Functions.php';
$db = new DB_Functions();
 
// json response array
header('Content-Type: application/json');
$response = array("error" => false);
 
if (isset($_POST['f_name']) && isset($_POST['l_name']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['phone']) ) {
 
    // receiving the post params
    $f_name = $_POST['f_name'];
	$l_name = $_POST['f_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
	$phonenumber = $_POST['phone'];
 
    // check if user is already existed with the same email

    
    $email_exists = $db->isUserExisted($email);
    if ($email_exists) {
        // user already existed
        $response["error"] = true;
        $response["error_msg"] = "User already existed with " . $email;
        //echo json_encode($response);
    } else {
        // create a new user
        $user = $db->storeUser($f_name,$l_name, $email, $password,$phonenumber);
        if ($user) {
            // user stored successfully
            $response["error"] = false;
            foreach($user as $row){
                $response["user"]["id"]= $row["id"];
                $response["user"]["f_name"] = $row["f_name"];
                $response["user"]["l_name"] = $row["l_name"];
                $response["user"]["email"] = $row["email"];
                $response["user"]["phone"] = $row["phone"];
		    }
            //echo json_encode($response);
        } else {
            // user failed to store
            $response["error"] = true;
            $response["error_msg"] = "Unknown error occurred in registration!";
            //echo json_encode($response);
        }
    }
} else {
    //$response['isUserExists']= $db->isUserExisted('john@example.com');
    //$response['newUser']= $db->storeUser('igb1','bakhsh', 'a@a.a', 'pwd',33434);
    
    $response["error"] = true;
    $response["error_msg"] = "Required parameters (f_name,l_name, email , password or phone) is missing!";
    //echo json_encode($response);
}
?>