# dynamic_php_services
dynamically expose the database over http requests for the entire database 
 * Get=Select, Post=Insert/update, Delete=delete
 * will fix the full sql injection thing in new versions.
 * will fix the call of procedures and functions in new version 
 
 
 to use: set your database information in (config/actionMap.php)
 then just create your database tables for example: 
 1-create your database: 
```SQL
CREATE TABLE IF NOT EXISTS `test` (
  `id` int(11) NOT NULL,
  `col1` varchar(200) DEFAULT NULL,
  `col2` int(11) DEFAULT NULL,
  `record_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;

ALTER TABLE `test`
  ADD PRIMARY KEY (`id`);
  
ALTER TABLE `test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=14;
```
2-add service links on your config/actionMap.php
```php
$actionMap = array(
               array('testPost'=>'test', 'method'=>'POST'),
               array('testGet'=>'test', 'method'=>'GET')
            );
```

You are all done. 

now just try the app: 

A.Posting Data:
Method: POST
URL: http://localhost/{your_project_name}/index.php/testPost
#posted Data: col1=value1&col2=123
```JSON
{
   "error":false,
   "request_info":{
      "method":"POST",
      "request":"testPost"
   },
   "data":[
      {
         "id":1,
         "col1":"value1",
         "col2":123,
         "record_date":"2017-05-04 18:47:45"
      }
   ],
   "rowCount":1,
   "pk_code":1
}
```

B.Getting Data: 
Method: GET
URL: http://localhost/{your_project_name}/index.php/testPost?id=1
```JSON
{
   "error":false,
   "request_info":{
      "method":"GET",
      "request":"testGet"
   },
   "data":[
      {
         "id":1,
         "col1":"value1",
         "col2":123,
         "record_date":"2017-05-04 18:47:45"
      }
   ],
   "rowCount":1
}
```

C.Updating Data: 
Method: PUT
URL: http://localhost/easy.parking/index.php/testPut/{"id":"5"}
#Posted Data: col2=2011&col1=igb5
```JSON
    {
        "error": false,
        "request_info":
        {
            "method": "PUT",
            "request": "testPut"
        },
        "data":
        [
            {
                "id": 5,
                "col1": "igb5",
                "col2": 2011,
                "record_date": "2017-05-09 16:19:20"
            }
        ],
        "rowCount": 1
    }
```
will continue improving the code .. let me know of any issues to fix. 
Best of luck. 


