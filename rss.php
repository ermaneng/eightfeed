<?php
require_once 'connection.php';

$MIN_ITEM_COUNT = 100;

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
    $M="";

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
    global $MIN_ITEM_COUNT;

    $sources = $_GET["sources"];

    if (!empty($sources)){$sources = explode(",",$sources);}
    if (count($sources)==0)
    {
        //get all sources
        $sources = array();
        $sql = sprintf("SELECT hashtag FROM feed_sources");
        $query = mysql_query($sql,$connection);
        $total = mysql_num_rows($query);
        for ($i=0;$i<$total;$i++)
        {
            $item = mysql_result($query,$i,"hashtag");
            array_push($sources,$item);
        }

    }
    

    $limitForEachSource = ceil($MIN_ITEM_COUNT / count($sources));
    

    $data = array();

    foreach ($sources as $value)
    {
        $sql = sprintf("SELECT fs.feed_name, i.title, i.link, i.pubdate, i.guid, i.description, i.summary, i.imgWidth, i.imgHeight, i.imgsrc, i.feed_hashtag FROM items AS i, feed_sources AS fs WHERE fs.hashtag = i.feed_hashtag AND i.feed_hashtag = '%s' ORDER BY pubdate DESC LIMIT 0, %d",$value,$limitForEachSource);
        $query = mysql_query($sql,$connection);
        $total = mysql_num_rows($query) ;

        for ($i=0;$i<$total;$i++)
        {
            $item = mysql_fetch_assoc($query);
            $item["orderTime"] = $item["pubdate"];
            $item["pubdate"] = formatDate($item["pubdate"]);
            
            if(trim($item["summary"]) !== ''){
                //echo '<script>console.log("--'.$item["summary"].'--")</script>';
                //echo $item["summary"] . " -- <br>";
                $item["description"]=$item["summary"];
            }


            array_push($data, $item);
        }
    }


    usort($data,function($x,$y){
        return $y["orderTime"] - $x["orderTime"];
    });

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
