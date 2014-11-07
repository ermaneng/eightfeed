<?php
require_once 'connection.php';

function printArray($array)
{
    echo"<pre>";
    print_r($array);
    echo"</pre>";
}

function formatDate($date)
{
    $Y=substr($date,0,4);
    $m=substr($date,4,2);
    $d=substr($date,6,2);
    $H=substr($date,8,2);
    $i=substr($date,10,2);
    $s=substr($date,12,2);

    if ($m=="01"){$M="Ocak";}
    else if ($m=="02"){$M="Şubat";}
    else if ($m=="03"){$M="Mart";}
    else if ($m=="04"){$M="Nisan";}
    else if ($m=="05"){$M="Mayıs";}
    else if ($m=="06"){$M="Haziran";}
    else if ($m=="07"){$M="Temmuz";}
    else if ($m=="08"){$M="Ağustos";}
    else if ($m=="09"){$M="Eylül";}
    else if ($m=="10"){$M="Ekim";}
    else if ($m=="11"){$M="Kasım";}
    else if ($m=="12"){$M="Aralık";}
    
    return $d." ".$M." ".$H.":".$i;
}


function getItems()
{
    global $connection;

    $sources = $_GET["sources"];
    if (!empty($sources)){$sources = explode(",",$sources);}
    
   

    $sql = "SELECT fs.feed_name, i.title, i.link, i.pubdate, i.guid, i.description, i.imgWidth, i.imgHeight, i.imgsrc, i.feed_hashtag FROM items AS i, feed_sources AS fs WHERE fs.hashtag = i.feed_hashtag";
    if (count($sources)>0)
    {
        $inSourcesString = "";
        foreach ($sources as $value)
        {
            $inSourcesString.="'".$value."',";
        }

        $inSourcesString = rtrim($inSourcesString, ",");


        $sql .= sprintf(" AND i.feed_hashtag IN(%s)",$inSourcesString);   
    }
    $sql .= " ORDER BY pubdate DESC";
    

    $query = mysql_query($sql,$connection);
    $total = mysql_num_rows($query) ;

    
    $data = array();
    for ($i=0;$i<$total;$i++)
    {
        $item = mysql_fetch_assoc($query);
        $item["pubdate"] = formatDate($item["pubdate"]);
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

//$callback = $GET["callback"];
//$callback . "(" . {as:bb} . ")"

?>
