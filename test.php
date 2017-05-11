<?php
    header('Content-Type: application/json');

    require_once("config/db_functions.php");
    $db = new DB_Functions();
    /** emulating a call like this: 
    * Get: Http://localhost/{your_project_name}/index.php/customeActionName
    * Example1: Query String: lastname=bakhsh&email=a@b.c
    * Example2: Query String: [["lastname","=","bakhsh3"],["email","=","a@b.c"]]
    * Example3: Query String: [["lastname","=","bakhsh3"],["age",">","30"]] 
    * Example4: Query String: [["lastname","=","bakhsh3"],["age","between","22","60"]] 
    */ 
    $result = $db->getRowsV3("user",
                             array("check"=>true, "columns"=>array("*")),
                             array("check"=>true, "where"=>array(
                                                                array("lastname"=>"bakhsh3") ,
                                                                array("email"=>"a@b.c")
                                                                )
                                  )
                            );
    if($db->getError()) {
        echo "error:".json_encode($db->getError());
    } else {
        $response["error"] = false;
        $response["data"] = $result;
        $response["rowCount"] = $db->getRowCount();
        $response["sql_statement"] = $db->getSqlStatement();
        echo json_encode($response);
    }
?>