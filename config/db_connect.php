
<?php
 
/**
 * A class file to connect to database
 */
class DB_CONNECT {
 
    private $g_link = false;
    private $CON;
    
    // constructor
    function __construct() {
        // connecting to database
        $this->connect();
    }
 
    // destructor
    function __destruct() {
        // closing db connection
        $this->close();
    }
 
    function getConn(){
        return $this->CON;
    }
    /**
     * Function to connect with database
     */
    function connect() {
        // import database connection variables
        require_once('db_info.php');
        
        global $g_link;
        global $CON;

        if( $g_link )
            return $this->CON;
        
        // Connecting to mysql database
        $this->CON = mysqli_connect(DB_SERVER, DB_USER, DB_PASSWORD,DB_DATABASE) or die(mysqli_connect_error());
        //$CON = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD) or die(mysql_error());
        //$db = mysql_select_db(DB_DATABASE) or die(mysql_error()) or die(mysql_error());
        // Selecing database
        //$db = 
        //mysqli_select_db(DB_DATABASE) or die(mysql_error());
        $g_link = true;
        // returing connection cursor
        return $this->CON;
    }
 
    /**
     * Function to close db connection
     */
    function close() {
        // closing db connection
        global $g_link;
        global $CON;
        if ($g_link){
            mysqli_close($this->CON);
            $g_link = false;
        } 
        
    }


    function fetch($result)
    {   
        $array = array();
    
        if($result instanceof mysqli_stmt)
        {
            $result->store_result();
        
            $variables = array();
            $data = array();
            $meta = $result->result_metadata();
        
            while($field = $meta->fetch_field())
                $variables[] = &$data[$field->name]; // pass by reference
        
            call_user_func_array(array($result, 'bind_result'), $variables);
        
            $i=0;
            while($result->fetch())
            {
                $array[$i] = array();
                foreach($data as $k=>$v)
                    $array[$i][$k] = $v;
                $i++;
            
                // don't know why, but when I tried $array[] = $data, I got the same one result in all rows
            }
        }
        elseif($result instanceof mysqli_result)
        {
            while($row = $result->fetch_assoc())
                $array[] = $row;
        }
    
        return $array;
    }
 
}
 
?>