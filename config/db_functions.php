<?php
class DB_Functions
{
 
    private $conn;
    private $db;
    private $error;
    private $rowCount;
    private $pk_code;
    private $sql_statement;
    // constructor
    function __construct()
    {
        require_once 'db_connect.php';
        // connecting to database
        $this->db = new DB_CONNECT();
        $this->conn = $this->db->connect();
        $this->error = array();
    }
 
    // destructor
    function __destruct()
    {
    }

    function getSqlStatement(){
        return $this->sql_statement;
    }
    function getPk_Code()
    {
        return $this->pk_code;
    }

    function getError()
    {
        return $this->error;
    }

    function getConn()
    {
        return $this->db->getConn();
    }

    function jsonObjectToArray($obj)
    {
        if (gettype($obj)==string) {
            $obj = json_decode($obj);
            return $obj;
        } elseif (gettype($obj)==object) {
            $paramArray = array();
            foreach ($obj as $key => $value) {
                array_push($paramArray, array($key,$value));
            }
            return $paramArray;
        } else {
            return null;
        }
    }

    function getRowCount()
    {
        return $this->rowCount;
    }
    public function getRows($sql, $inner = false)
    {
        if (!$inner) $this->sql_statement = $sql;
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            array_push($this->error, "getRows: Error in preparing statement(".$sql.") \n".$this->conn->error);
            //echo $this->error;
            return false;
        } else {
            $stmt->execute();
            $rows = $this->db->fetch($stmt);
            $stmt->close();
            if (!$inner) {
                $this->rowCount = count($rows);
            }
            return $rows;
        }
    }

    function checkTableColumns($table, $columns){
        //if($errorArray==null) $errorArray= $this->error;
        $all_columns = $this->getRows("SHOW COLUMNS FROM $table ", true);
        
            $db_columns = array();
            foreach ($all_columns as $sColumn) {
                array_push($db_columns, $sColumn["Field"]);
            }
            
            $db_wrong_columns = "";
            foreach ($columns as $postedColumn) {
                if ($postedColumn != "*") {
                    if (!in_array($postedColumn, $db_columns)) {
                        $db_wrong_columns.= $postedColumn.',';
                    }
                }
            }
            
            if (strlen($db_wrong_columns)>0) {
                array_push($this->error, "columns: $db_wrong_columns not found in table or view: $table");
                return $this->error;
                return false;
            } else {
                return true;
            }
    }

    public function getRowsV3(
        $table,
        $columns = array("check"=>true,"columns"=>array("*")),
        $filter = array("check"=>true, "where"=>array()),
        $inner = false
    ) {
        if (!is_array($filter)) {
            json_decode($filter);
        }
        if (!is_array($columns)) {
            json_decode($columns);
        }
        //if no columns to select
        if (count($columns["columns"])<1) {
            array_push($this->error, "no columns to select!");
                return false;
        }
        
        //if table name is wrong, TODO: consider views ?!
        $tables = $this->getRows("show tables", true);
        $table_found = false;
        foreach ($tables as $tname) {
            $tables_keys = array_keys($tname);
            if ($tname[$tables_keys[0]] == $table) {
                $table_found = true;
                break;
            }
        }
        if (!$table_found) {
            array_push($this->error, "table $table not found.");
            return false;
        }

        //check if columns are in the table (fix the sql injection of one part of the operation)
        if ($columns["check"]) {
            $check_columns = $this->checkTableColumns($table,$columns["columns"]);
            if(!$check_columns) return false;
        }

        //check if columns sent in filter exists in the same table:
        if ($filter["check"]) {
            $whereArray = $filter["where"];
            $all_columns = $this->getRows("SHOW COLUMNS FROM $table ", true);
            $db_columns = array();
            foreach ($all_columns as $sColumn) {
                array_push($db_columns, $sColumn["Field"]);
            }
            $whereColumns = array();
            foreach ($whereArray as $whereRow) {
                $whereRowKeys = array_keys($whereRow);
                array_push($whereColumns, $whereRowKeys[0]);
            }
           
            $db_wrong_columns = "";
            foreach ($whereColumns as $postedColumn) {
                if ($postedColumn != "*") {
                    if (!in_array($postedColumn, $db_columns)) {
                        $db_wrong_columns.= $postedColumn.',';
                    }
                }
            }
            if (strlen($db_wrong_columns)>0) {
                array_push($this->error, "filter columns: $db_wrong_columns not found in table or view: $table");
                return false;
            }
        }
        
        $columnsStr = "";
        foreach ($columns["columns"] as $postedColumn) {
            if (strlen($columnsStr)!=0) {
                $columnsStr.=", $postedColumn";
            } else {
                $columnsStr = $postedColumn;
            }
        }
        
        $sql = "select $columnsStr from $table";
        $this->sql_statement = $sql;
        $filterObj = array();
        foreach ($filter["where"] as $wRow) {
            $wKeys = array_keys($wRow);
            array_push($filterObj, array($wKeys[0],$wRow[$wKeys[0]]));
        }
        return $this->getRowsv2($sql, $filterObj);
    }

    private $allowedOperators = array("=","!=","<>",">","<","<=",">=","IN","NOT IN", "BETWEEN","NOT BETWEEN","LIKE","NOT LIKE");
    private $allowedLinks = array("AND", "AND(","OR","OR(","AND NOT","OR NOT", ")");

    public function processFilter($filter, $errorArray = null)
    {
        $outObj = array();
        $filterWhere = array();
        $filterRowValues = array();

        if ($errorArray==null) {
            $errorArray = $this->error;
        }
        if ($filter != null) {
            if(gettype($filter)==object){
                $filterObj = $filter;
                $filter = array();
                foreach($filterObj as $fKey=>$fValue){
                    array_push($filter,array($fKey,$fValue));
                }
            } elseif (!is_array($filter)) {
                $filter = json_decode($filter);
            }
        }
        if ($filter != null) {
            if (is_array($filter)) {
                foreach ($filter as $filterRow) {
                    $filterRowWhere = $filterRow[0];

                    if (count($filterRow)>2) {
                        if (!in_array(strtoupper($filterRow[1]), $this->allowedOperators)) {
                            array_push($errorArray, "processFilter: Not allowed Operator:".$filterRow[1]);
                            return false;
                        } else {
                            $filterRowOperator = strtoupper($filterRow[1]);
                            switch ($filterRowOperator) {
                                case "IN":
                                case "NOT IN":
                                    $where2 = $filterRow[2];
                                    if (is_array($filterRow[2])) {
                                        $where2 = "";
                                        foreach ($filterRow[2] as $arrayVal) {
                                            if (strlen($where2)==0) {
                                                $where2.="?";
                                                array_push($filterRowValues, $arrayVal);
                                            } else {
                                                $where2.=",?";
                                                array_push($filterRowValues, $arrayVal);
                                            }
                                        }
                                    }
                                    $filterRowWhere.= "$filterRowOperator(".$where2.")";
                                    break;
                                case "BETWEEN":
                                case "NOT BETWEEN":
                                    if (count($filterRow)<4) {
                                        array_push($errorArray, "processFilter: Between & Not between require 4 arrays like:[column_name,between,xxx,yyy]");
                                        return false;
                                    } else {
                                        $nextOperator = " and ";
                                        if (count($filterRow)>4) {
                                            if (in_array(strtoupper($filterRow[4]), $this->allowedLinks)) {
                                                $nextOperator = " ".$filterRow[4]." ";
                                            }
                                        }
                                        $filterRowWhere.= " $filterRowOperator ? AND ? $nextOperator";
                                        array_push($filterRowValues, $filterRow[2]);
                                        array_push($filterRowValues, $filterRow[3]);
                                    }
                                    break;
                                default:
                                    $filterRowWhere.= "$filterRowOperator ? and ";
                                    array_push($filterRowValues, $filterRow[2]);
                                    break;
                            }
                        }
                    }
                    if (count($filterRow)>3 && (!in_array(strtoupper($filterRow[1]), array("BETWEEN","NOT BETWEEN")))) {
                        if (!in_array(strtoupper($filterRow[3]), $this->allowedLinks)) {
                            array_push($errorArray, "processFilter: Not allowed filter Link:".$filterRow[3]);
                            return false;
                        } else {
                            $nextOperator = $filterRow[3];
                            switch (strtoupper($nextOperator)) {
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
                    if (count($filterRow)==1) {
                        if ($filterRow[0] == ")") {
                            $filterRowWhere = ")";
                        }
                    }
                    if (count($filterRow)==2) {
                        $filterRowWhere = $filterRow[0]."= ? and ";
                        array_push($filterRowValues, $filterRow[1]);
                    }
                    array_push($filterWhere, $filterRowWhere);
                    //$filterWhere = $filterWhere . $filterRowWhere;
                }
            }
        }
            $filterWhereText = "";
            if(count($filterWhere)>0){
                $n =0;
                foreach($filterWhere as $whereRow){
                    if($n == count($filterWhere)-1){
                        $filterWhereText .= substr($whereRow, 0, strlen($whereRow)-4);
                    } else {
                        $filterWhereText .= $whereRow;
                    }
                    $n+=1;
                }
            }
            $outObj = array("filterWhere"=>$filterWhereText, "filterRowValues"=>$filterRowValues);
            //echo json_encode($outObj);
            //exit;
            return $outObj;
    } // end of function processFilter

    public function getRowsv2($sql, $filter = null, $inner = false)
    {
        $filterObj = $this->processFilter($filter);
        

        if (strlen($filterObj["filterWhere"])>0) {
            if (!strpos(strtoupper($sql), "WHERE")) {
                $sql.=" where ";
            }
            $sql.=$filterObj["filterWhere"];
        }
        $filterRowValues = $filterObj["filterRowValues"];
        if(!$inner) $this->sql_statement = $sql;
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            array_push($this->error, "getRowsv2: Error in preparing statement4(".$sql.") \n".$this->conn->error);
            //echo $this->error;
            return false;
        } else {
            
            if (count($filterRowValues)>0) {
                $a_params = array();
                $param_types = "";
                foreach ($filterRowValues as $sValue) {
                    $param_types.="s";
                }
                array_push($a_params, $param_types);
                foreach ($filterRowValues as $singleValue) {
                    array_push($a_params, $singleValue);
                }
                    
                call_user_func_array(array($stmt, 'bind_param'), $this->refValues($a_params));
            }
            $stmt->execute();
            $rows = $this->db->fetch($stmt);
            $stmt->close();
            if (!$inner) {
                $this->rowCount = count($rows);
            }
            return $rows;
        }
    }

 
 

 
    public function insertRow($table, $row)
    {
        $array = $row;
        if (!is_array($array)) {
            $array = json_decode($row);
        }
        $count = 0;
        $columns_str = "";
        $values_str = "";
        foreach ($array as $key => $value) {
            $count+=1;
            $columns_str = $columns_str.",".$key;
            $values_str = $values_str."','".$value;
        }
        $columns_str = substr($columns_str, 1);
        $values_str = substr($values_str, 2);
        $values_str = $values_str."'";
        $sql = "insert into ".$table."(".$columns_str.") values (".$values_str.")";
        $this->sql_statement = $sql;
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            array_push($this->error, "insertRow: Error in preparing statement (".$sql.") \n".$this->conn->error );
            return false;
        } else {
            $result = $stmt->execute();
            $stmt->close();
            //$this->rowCount = $result->rowCount();
            return $result;
        }
    }

    public function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) { //Reference is required for PHP 5.3+
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
            return $arr;
    }

    public function insertRowCheckInjections($table, $row)
    {
        /* Bind parameters. Types: s = string, i = integer, d = double,  b = blob */
        
        $array = $row;
        if (!is_array($array)) {
            $array = json_decode($row);
        }
        $count = 0;
        $columns_str = "";
        $values_str = "";
        $values_arr = array();
        $param_type = ""; //array();
        
        if (count($array) ==0) {
            array_push($this->error, "Post does not contain any data!");
            return false;
        }

        //check if any post data is not a valid column_name:
        $all_columns = $this->getRows("SHOW COLUMNS FROM $table ", true);
        $table_columns = array();
        foreach ($all_columns as $col_row) {
            $col_name = $col_row["Field"];
            array_push($table_columns, $col_name);
        }
        $posted_columns = array_keys($array);
        foreach ($posted_columns as $posted_column) {
            if (!in_array($posted_column, $table_columns)) {
                array_push($this->error, "field: $posted_column is not in table: $table");
                return false;
            }
        }
        $required_columns = array();
        foreach ($all_columns as $col_row) {
            if ($col_row["Extra"] != "auto_increment" && (is_null($col_row["Default"])) && $col_row["Null"] == "NO") {
                $col_name = $col_row["Field"];
                if (!in_array($col_name, $posted_columns)) {
                    array_push($required_columns, $col_name);
                }
            }
        }
        if (count($required_columns)>0) {
                $str_columns = "";
            foreach ($required_columns as $col) {
                $str_columns.=$col.",";
            }
                $str_columns = substr($str_columns, 0, strlen($str_columns)-1);
                array_push($this->error, "Required to post: ".$str_columns." in table $table .");
                return false;
        }

        foreach ($array as $key => $value) {
            $count+=1;
            $columns_str .= ",".$key;
            $values_str  .= ", ?";
            array_push($values_arr, $value);
            $param_type .= "s";
        }
        $columns_str = substr($columns_str, 1);
        $values_str = substr($values_str, 2);
        $sql = "insert into ".$table."(".$columns_str.") values (".$values_str.")";
        $this->sql_statement = $sql;
        $stmt = $this->conn->prepare($sql);

        $a_params = array();
        array_push($a_params, $param_type);
        foreach ($values_arr as $singleValue) {
            array_push($a_params, $singleValue);
        }
        
        //echo json_encode($a_params);

       
        if (!$stmt) {
            array_push($this->error, "insertRow: Error in preparing statement (".$sql.") \n".$this->conn->error);
            return false;
        } else {
         //bind with bind_param to avoid sql injections
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($a_params));
            $result = $stmt->execute();
            //$ins_stmt = $result->store_result();
            $count = $this->getConn()->affected_rows;
            $this->rowCount = $count;
            $this->pk_code = mysqli_insert_id($this->getConn());
            $stmt->close();

            //getting the primary key columns:
            $pk_columns = $this->getRows("SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'", true);
            $pk_where = "";
            $i = 0;
            foreach ($pk_columns as $pkRow) {
                $pk_column = $pkRow['Column_name'];
                if (strlen($pk_where) > 0) {
                    $pk_where .= " and ";
                }
                $pk_value = $this->pk_code;
                if (is_array($pk_value)) {
                    $pk_value = $pk_value[$i];
                }
                $pk_where .= $pk_column."='".$pk_value."'" ;
                $i++;
            }
            $result_row = $this->getRows("select * from  $table WHERE $pk_where", true);
            return $result_row;
        }
    }

    public function updateRowCheckInjections($table, $row, $filter)
    {
        /* Bind parameters. Types: s = string, i = integer, d = double,  b = blob */

        //call to check validation of query injection of filter:
        $filterObj = $filter;
        if (gettype($filterObj)==object) {
            $filterObj = array();
            foreach ($filter as $key => $value) {
                array_push($filterObj, array($key,$value));
            }
        }
        $filter_obj = $this->processFilter($filterObj,$this->error);
        if(!$filter_obj) return false;
        
        $array = $row;
        if (!is_array($array)) {
            $array = json_decode($row);
        }
        
        
        if (count($array) ==0) {
            array_push($this->error, "Post does not contain any data!");
            return false;
        }

        //check if any post data is not a valid column_name:
        $all_columns = $this->getRows("SHOW COLUMNS FROM $table ", true);
        $table_columns = array();
        foreach ($all_columns as $col_row) {
            $col_name = $col_row["Field"];
            array_push($table_columns, $col_name);
        }

        $where_columns = array();
        foreach ($filterObj as $filterRow) {
            array_push($where_columns, $filterRow[0]);
        }
        foreach ($where_columns as $where_column) {
            if (!in_array($where_column, $table_columns)) {
                array_push($this->error, "field: $where_column is not in table: $table");
                return false;
            }
        }
        $posted_columns = array_keys($array);
        foreach ($posted_columns as $posted_column) {
            if (!in_array($posted_column, $table_columns)) {
                array_push($this->error, "field: $posted_column is not in table: $table");
                return false;
            }
        }
        
        $setCommand = "";
        $values_arr = array();
        $param_type = ""; //array();
        $count = 0;
        foreach ($array as $key => $value) {
            $count+=1;
            if (strlen($setCommand)==0) {
                $setCommand .= $key." = ? ";
            } else {
                $setCommand .= ", ".$key." = ? ";
            }
            array_push($values_arr, $value);
            $param_type .= "s";
        }
        $filterRowValues =$filter_obj["filterRowValues"];
        foreach ($filterRowValues as $sValue) {
            $param_type.="s";
        }

        $sql = "update $table set ".$setCommand;
        $a_params = array();
        array_push($a_params, $param_type);
        foreach ($values_arr as $singleValue) {
            array_push($a_params, $singleValue);
        }
        //adding params of where:
        
        if(strlen($filter_obj["filterWhere"]) >0){
            foreach ($filterRowValues as $singleValue) {
                array_push($a_params, $singleValue);
            }
            $sql .= " where ".$filter_obj["filterWhere"];
        }
        
        //echo json_encode($a_params);
        $this->sql_statement = $sql;
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            array_push($this->error, "updateRow: Error in preparing statement (".$sql.") \n".$this->conn->error);
            return false;
        } elseif(count($a_params)<1) {
            array_push($this->error, "update must have parameters: ".$sql.") \n");
            return false;
        } else {
            call_user_func_array(array($stmt, 'bind_param'), $this->refValues($a_params));
            $result = $stmt->execute();
            $count = $this->getConn()->affected_rows;
            $this->rowCount = $count;
            $stmt->close();

            if($count>0 && $count<10){
                $result_sql = "select * from  $table ";
                $result_row = $this->getRowsv2($result_sql,$filter, true);
                return $result_row;
            }
            return true;
        }
    }

    /**
     * Encrypting password
     * @param password
     * returns salt and encrypted password
     */
    public function hashSSHA($password)
    {
 
        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return $hash;
    }
 
   /**
   * Encrypting based on predefined key
   * @param salt
   * @param password
   * returns the encrypted password for the key(salt) provided
   */
    public function checkhashSSHA($salt, $password)
    {
 
        $hash = base64_encode(sha1($password . $salt, true) . $salt);
 
        return $hash;
    }
}
