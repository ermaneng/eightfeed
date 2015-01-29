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
   

    $sql = "SELECT hashtag AS id, feed_name AS name, logo, color AS feed_color FROM feed_sources_tech WHERE active = 1 ORDER BY name ASC";
    

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
    $callback = $_GET["callback"]; 
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
