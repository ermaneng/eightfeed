<?php
require_once 'connection.php';

function printArray($array)
{
    echo"<pre>";
    print_r($array);
    echo"</pre>";
}

function getItems()
{
    global $connection;

    $sources = $_GET["sources"];
    if (!empty($sources)){$sources = explode(",",$sources);}
    
   

    $sql = "SELECT hashtag AS id, feed_name AS name, logo FROM feed_sources WHERE active = 1";
    

    $query = mysql_query($sql,$connection);
    $total = mysql_num_rows($query) ;

    
    $data = array();
    for ($i=0;$i<$total;$i++)
    {
        $item = mysql_fetch_assoc($query);
        array_push($data, $item);
    }

    return $data;
}


function printResponse($data)
{
    $mode = $_GET["mode"];
    $callback = $_GET["callback"]; 

    if (empty($mode)) $mode = "json";

    if ($mode == "json")
    {
        $output = "";
        if (!empty($callback))
        {
            $output = $callback."(".json_encode($data).")";
        }
        else
        {
            $output =  json_encode($data);
        }

        header('Content-type: application/javascript');
        echo $output;
        
    }
    else if ($mode == "dump")
    {
        printArray($data);
    }
}

function execute()
{
    //GET ALL ITEMS FROM DB
    /////////////////////////////
    $items = getItems();

    //PRINT TO PAGE
    /////////////////////////////
    printResponse($items);

}


execute();


//CLOSE MYSQL CONNECTION
/////////////////////////////

if (isset($query)){@mysql_free_result($query);}
mysql_close($connection);

?>
