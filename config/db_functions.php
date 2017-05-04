<?php
class DB_Functions {
 
    private $conn;
    private $db;
    private $error;
    private $rowCount;
    private $pk_code;
    // constructor
    function __construct() {
        require_once 'db_connect.php';
        // connecting to database
        $this->db = new DB_CONNECT();
        $this->conn = $this->db->connect();
        $this->error = array();
    }
 
    // destructor
    function __destruct() {
         
    }

function getPk_Code() {
    return $this->pk_code;
}

function getError(){
    return $this->error;
}

function getConn(){
    return $this->db->getConn();
}

function jsonObjectToArray($obj){
    if(gettype($obj)==string) {
        $obj = json_decode($obj);
        return $obj;
    } elseif(gettype($obj)==object){
        $paramArray = array();
        foreach($obj as $key=>$value){
            array_push($paramArray,array($key,$value));
        }
        return $paramArray;
    } else {
        return null;
    }
}

function getRowCount(){
    return $this->rowCount;
}
    public function getRows($sql, $inner = false){
        $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                array_push($this->error,"getRows: Error in preparing statement(".$sql.") \n".$this->conn->error);
                //echo $this->error;
                return false;
            } else { 
                $stmt->execute();
                $rows = $this->db->fetch($stmt);
                $stmt->close();
                if (!$inner) $this->rowCount = count($rows);
                return $rows;
            }
    }

    private $allowedOperators = array("=","!=","<>",">","<","<=",">=","IN","NOT IN", "BETWEEN","NOT BETWEEN");
    private $allowedLinks = array("AND", "AND(","OR","OR(","AND NOT","OR NOT", ")");


        public function getRowsv2($sql,$filter = null, $inner = false){
            
            if($filter != null) if(!is_array($filter)) $filter = json_decode($filter);

            if($filter != null) if(is_array($filter)) {
                foreach($filter as $filterRow){
                    $filterRowWhere = $filterRow[0];
                    if(count($filterRow)>2) {
                        
                        if(!in_array(strtoupper($filterRow[1]),$this->allowedOperators)) {
                            array_push($this->error,"getRowsv2: Error in preparing statement1(".$sql.") Not allowed Operator:".$filterRow[1]);
                            return false;
                        } else {
                            $filterRowOperator = strtoupper($filterRow[1]);
                            switch($filterRowOperator){
                                case "IN":
                                case "NOT IN":
                                    $where2 = $filterRow[2];
                                    if(is_array($filterRow[2])){
                                        $where2 = "";
                                        foreach($filterRow[2] as $arrayVal) {
                                            if(strlen($where2)==0) {
                                                $where2.="'".$arrayVal."'";
                                            } else {
                                                $where2.=",'".$arrayVal."'";
                                            }
                                        }
                                    }
                                    $filterRowWhere.= "$filterRowOperator(".$where2.")";
                                    break;
                                case "BETWEEN":
                                case "NOT BETWEEN":
                                    if(count($filterRow)<4) {
                                        array_push($this->error,"getRowsv2: Error in preparing statement2(".$sql.") Between & Not between require 4 arrays like:[reg_no,between,1111,2222]");
                                        return false;
                                    }  else {
                                        $nextOperator = " and ";
                                        if(count($filterRow)>4) if(in_array(strtoupper($filterRow[4]),$this->allowedLinks))  $nextOperator = " ".$filterRow[4]." ";
                                        $filterRowWhere.= " $filterRowOperator '".$filterRow[2]."' AND '".$filterRow[3]."'$nextOperator";
                                    }
                                    break;
                                default:
                                    $filterRowWhere.= "$filterRowOperator'".$filterRow[2]."'";
                                    break;
                            }
                        }
                    }
                    if(count($filterRow)>3 && (!in_array(strtoupper($filterRow[1]),array("BETWEEN","NOT BETWEEN")))){
                        if(!in_array(strtoupper($filterRow[3]),$this->allowedLinks)) {
                            array_push($this->error,"getRowsv2: Error in preparing statement3(".$sql.") Not allowed filter Link:".$filterRow[3]);
                            return false;
                        } else {
                            $nextOperator = $filterRow[3];
                            switch(strtoupper($nextOperator)){
                                case "AND":
                                case "AND(":
                                case ")":
                                case "OR":
                                case "OR(":
                                    $filterRowWhere.=" $nextOperator ";
                                    break;
                                case "AND NOT":
                                case "OR NOT":
                                    $filterRowWhere.=" $nextOperator (";
                                    break;
                                default:
                                    $filterRowWhere.=" and ";
                                break;
                            }
                        }
                    }
                    if(count($filterRow)==1) if($filterRow[0] == ")" )  $filterRowWhere = ")";
                    if(count($filterRow)==2) $filterRowWhere = $filterRow[0]."='".$filterRow[1]."' and ";

                    if(!strpos(strtoupper($sql),"WHERE")) {
                        $sql.=" where ".$filterRowWhere;
                    } else {
                        $sql.=$filterRowWhere;
                    }
                }
            }
            $len = strlen($sql);
            if($len>5) if(substr($sql,-5) == " and ") $sql = substr($sql,0,$len-4);
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                array_push($this->error,"getRowsv2: Error in preparing statement4(".$sql.") \n".$this->conn->error);
                //echo $this->error;
                return false;
            } else { 
                $stmt->execute();
                $rows = $this->db->fetch($stmt);
                $stmt->close();
                if (!$inner) $this->rowCount = count($rows);
                return $rows;
            }
    }

 
 

 
    public function insertRow($table, $row){
        $array = $row;
        if(!is_array($array))   $array = json_decode($row);
        $count = 0;
        $columns_str = "";
        $values_str = "";
        foreach($array as $key=>$value){
            $count+=1;
            $columns_str = $columns_str.",".$key;
            $values_str = $values_str."','".$value;
        }
        $columns_str = substr($columns_str,1);
        $values_str = substr($values_str,2);
        $values_str = $values_str."'";
        $sql = "insert into ".$table."(".$columns_str.") values (".$values_str.")";
        $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                array_push($this->error , "insertRow: Error in preparing statement (".$sql.") \n".$this->conn->error );
                return false;
            } else { 
                $result = $stmt->execute();
                $stmt->close();
                //$this->rowCount = $result->rowCount();
                return $result;
            }
    }

    public function refValues($arr){
            if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
            {
                $refs = array();
                foreach($arr as $key => $value)
                    $refs[$key] = &$arr[$key];
                return $refs;
            }
            return $arr;
        }

    public function insertRowCheckInjections($table, $row){
        /* Bind parameters. Types: s = string, i = integer, d = double,  b = blob */
        
        $array = $row;
        if(!is_array($array))   $array = json_decode($row);
        $count = 0;
        $columns_str = "";
        $values_str = "";
        $values_arr = array();
        $param_type = ""; //array();
        foreach($array as $key=>$value){
            $count+=1;
            $columns_str .= ",".$key;
            $values_str  .= ", ?";
            array_push($values_arr,$value);
            $param_type .= "s";
        }
        $columns_str = substr($columns_str,1);
        $values_str = substr($values_str,2);
        $sql = "insert into ".$table."(".$columns_str.") values (".$values_str.")";
        $stmt = $this->conn->prepare($sql);

        $a_params = array();
        array_push($a_params,$param_type);
        foreach($values_arr as $singleValue){
            array_push($a_params,$singleValue);
        }
        
        //echo json_encode($a_params);

        //bind with bind_param to avoid sql injections 
        call_user_func_array(array($stmt, 'bind_param'), $this->refValues($a_params));

            if (!$stmt) {
                array_push($this->error , "insertRow: Error in preparing statement (".$sql.") \n".$this->conn->error);
                return false;
            } else { 
                $result = $stmt->execute();
                //$ins_stmt = $result->store_result();
                $count = $this->getConn()->affected_rows;
                $this->rowCount = $count;
                $this->pk_code = mysqli_insert_id($this->getConn());
                $stmt->close();

                //getting the primary key columns: 
                $pk_columns = $this->getRows("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'",true);
                $pk_where = "";
                $i = 0;
                foreach($pk_columns as $pkRow){
                    $pk_column = $pkRow['Column_name'];
                    if(strlen($pk_where) > 0) $pk_where .= " and ";
                    $pk_value = $this->pk_code;
                    if(is_array($pk_value)) $pk_value = $pk_value[$i];
                    $pk_where .= $pk_column."='".$pk_value."'" ;
                    $i++;
                }
                $result_row = $this->getRows("select * from  $table WHERE $pk_where",true);
                return $result_row;
            }
    }

    /**
     * Storing new user
     * returns user details
     */
    public function storeUser($f_name,$l_name, $email, $password , $phonenumber) {
       
        $hash = $this->hashSSHA($password);
        $encrypted_password = $hash["encrypted"]; // encrypted password
        $salt = $hash["salt"]; // salt
 
        $stmt = $this->conn->prepare("INSERT INTO customer(f_name, l_name, email, encrypted_password, salt, phonenumber, registrationDate) VALUES(?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            echo "INSERT: Error in preparing statement \n";
            echo $this->conn->error;
            exit;
        }
        $stmt->bind_param("ssssss", $f_name, $l_name, $email, $encrypted_password, $salt, $phonenumber);
        $result = $stmt->execute();
        $stmt->close();
 
        // check for successful store
        if ($result) {
            $stmt = $this->conn->prepare("SELECT * FROM customer WHERE email = ?");
            if (!$stmt) {
                echo "result: Error in preparing statement \n";
                echo $this->conn->error;
                exit;
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            //$user = $stmt->get_result()->fetch_assoc();
            $user = $this->db->fetch($stmt);
            $stmt->close();
 
            return $user;
        } else {
            return false;
        }
    }
 
    /**
     * Get user by email and password
     */
    public function getUserByEmailAndPassword($email, $password) {
 
        $stmt = $this->conn->prepare("SELECT * FROM customer WHERE email = ?");
            if (!$stmt) {
                echo "getUserByEmail: Error in preparing statement \n";
                echo $this->conn->error;
                exit;
            }
        $stmt->bind_param("s", $email);
 
        if ($stmt->execute()) {
            $user = $this->db->fetch($stmt);
            $stmt->close();
            
            foreach($user as $vRow){
                $row = $vRow;
            }
            // verifying user password
            $hash = $this->checkhashSSHA($row["salt"], $password);
            $encrypted_password = $hash["encrypted"]; // encrypted password
            // check for password equality

            if ($row["encrypted_password"] == $hash) {
                // user authentication details are correct
                return $row;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
 
    /**
     * Check user is existed or not
     */
    public function isUserExisted($email) {
        
        $stmt = $this->conn->prepare("SELECT email from customer WHERE email = ?");
 
        $stmt->bind_param("s", $email);
 
        $stmt->execute();
 
        $stmt->store_result();
 
        if ($stmt->num_rows > 0) {
            // user existed 
            $stmt->close();
            return true;
        } else {
            // user not existed
            $stmt->close();
            return false;
        }
    }
 
    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password) {
 
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
 
    /**
     * Decrypting password
     * @param salt, password
     * returns hash string
     */
    public function checkhashSSHA($salt, $password) {
 
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
 
        return $hash;
    }
 
}
 
?>