<?php
require_once 'connection.php';



function printArray($array)
{
    echo"<pre>";
    print_r($array);
    echo"</pre>";
}

function getFeedSources(){
    global $connection;
    //get sources
    $sql = sprintf("SELECT feed_name,hashtag,url FROM feed_sources WHERE active='1'");
    $query = mysql_query($sql,$connection);
    $total = mysql_num_rows($query);

    $array = array();



    for ($i=0;$i<$total;$i++)
    {
        $data = mysql_fetch_assoc($query);
        array_push($array, (object) array('url' => $data["url"], 'source' => $data["feed_name"], 'hashtag' => $data["hashtag"]));
    }

    return $array;
}


function getItems ($feedSources)
{
    global $connection;

    $entries = array();

    foreach ($feedSources as $feed) {

        //$xml = @file_get_contents($feed->url, 0, $ctx);
        
        $curl_opt = array
        (
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_USERAGENT        => 'Firefox / 2.0.16',
            CURLOPT_FOLLOWLOCATION => TRUE
        );

        $curl = curl_init();
        curl_setopt($curl , CURLOPT_URL , $feed->url );
        curl_setopt_array($curl , $curl_opt);
        $xml = curl_exec($curl);
        curl_close($curl);
        

        if($xml === FALSE){
            echo "xml is false";
        }
        else{
            $xml = str_replace(array('<media:', '</media:'), array('<', '</'), $xml);
            $xml = @simplexml_load_string($xml);

            

            if(is_object($xml)){
                 foreach ($xml->xpath('/rss//item') as $item) {
                    $item->addChild('feed_hashtag',$feed->hashtag);
                    //printArray($item);
                    //echo $item->pubDate;
                }
                $entries = array_merge($entries, array_slice($xml->xpath('/rss//item'),0,8));
            }
        }

    }

    foreach ($entries as $item) 
    {
        $img_src = "";
        $media_src = "";

        try {
            $desc_dom = new DOMDocument();
            libxml_use_internal_errors(true);
            @$desc_dom->loadHTML($item->description);
            libxml_clear_errors();
            if($desc_dom->getElementsByTagName('img')->length > 0){
                $img_src = $desc_dom->getElementsByTagName('img')->item(0)->getAttribute('src');
            }

        } catch (Exception $e) {
        }

        $media_src = $item->thumbnail["url"];


        if($img_src!==""){
            $item->addChild('imgsrc',$img_src);
        }
        else if($media_src!==""){
            $item->addChild('imgsrc',$media_src);
        }
        else{
            $item->addChild('imgsrc',"");
        }



        if(!empty($item->imgsrc)){

            $file = $item->imgsrc;
            $file_headers = @get_headers($file);
            if(substr($file_headers[0],0,12) == 'HTTP/1.1 404') {
                $exists = false;
            }
            else {
                $exists = true;
            }
            
            if ($exists)
            {
                list($width, $height, $type, $attr) = getimagesize($item->imgsrc);
            }
            else 
            {
                $width = "0";
                $height = "0";
            }
            $item->addChild('imgWidth',  $width);
            $item->addChild('imgHeight',  $height);
        }



        $item->addChild('guid', preg_replace("/\[CDATA\](.*?)\[\/CDATA\]/ies", "base64_decode('$1')", (string) $item->link));
        $item->title = strip_tags(preg_replace("/\[CDATA\](.*?)\[\/CDATA\]/ies", "base64_decode('$1')", (string) $item->title));

        $item->description = mb_substr(strip_tags(preg_replace("/\[CDATA\](.*?)\[\/CDATA\]/ies", "base64_decode('$1')", (string) $item->description)),0,10000,"UTF-8");
    }

    return $entries;
}

function formatPubDate($text){
    

    return $text;
}

function formatDate($pubdate)
{
    $pubdate = str_replace("Oca", "Jan", $pubdate);
    $pubdate = str_replace("Şub", "Feb", $pubdate);
    $pubdate = str_replace("Mar", "Mar", $pubdate);
    $pubdate = str_replace("Nis", "Apr", $pubdate);
    $pubdate = str_replace("May", "May", $pubdate);
    $pubdate = str_replace("Haz", "Jun", $pubdate);
    $pubdate = str_replace("Tem", "Jul", $pubdate);
    $pubdate = str_replace("Ağu", "Aug", $pubdate);
    $pubdate = str_replace("Eyl", "Sep", $pubdate);
    $pubdate = str_replace("Eki", "Oct", $pubdate);
    $pubdate = str_replace("Kas", "Nov", $pubdate);
    $pubdate = str_replace("Ara", "Dec", $pubdate);
    $pubdate = str_replace("Ara", "Dec", $pubdate);

    $pubdate = str_replace("Pzt", "Mon", $pubdate);
    $pubdate = str_replace("Sal", "Tue", $pubdate);
    $pubdate = str_replace("Çar", "Wed", $pubdate);
    $pubdate = str_replace("Per", "Thu", $pubdate);
    $pubdate = str_replace("Cum", "Fri", $pubdate);
    $pubdate = str_replace("Cts", "Sat", $pubdate);
    $pubdate = str_replace("Paz", "Sun", $pubdate);

    $pubdate = str_replace("T1", "", $pubdate);
    $pubdate = str_replace("T2", "", $pubdate);
    $pubdate = str_replace("T3", "", $pubdate);

    return date("YmdHis",strtotime($pubdate));
}

function truncateItems()
{
    global $connection;
    $sql = "TRUNCATE items;";
    $query = mysql_query($sql,$connection);
}

function cleanText($text)
{
    $text = addslashes($text);
    $order   = array("\r\n", "\n", "\r");
    $replace = '';
    $text = str_replace($order, $replace, $text);
    $text = str_replace('"', '\"', $text);
    return $text;
}

function saveItems($items)
{
    global $connection;
    truncateItems();

    for ($i=0; $i<count($items); $i++)
    {
        $item = $items[$i];
        

        $sentDataItem = array(
            "item_id"       => $i+1,
            "title"         => $item->title,
            "link"          => $item->link,
            "pubdate"       => formatDate($item->pubDate),
            "guid"          => $item->guid[0],
            "description"   => $item->description,
            "imgWidth"      => $item->imgWidth,
            "imgHeight"     => $item->imgHeight,  
            "imgsrc"        => $item->imgsrc,  
            "feed_hashtag"  => $item->feed_hashtag
            );
        


        foreach ($sentDataItem as $key => $value) 
        {
            $sentDataItem[$key] = cleanText($value);
        }      


        $sql = sprintf("INSERT INTO items (item_id, title, link, pubdate, guid, description, imgWidth, imgHeight, imgsrc, feed_hashtag) VALUES (%d, '%s', '%s', '%s', '%s', '%s', %d, %d, '%s', '%s'); ",
        $sentDataItem["item_id"] , 
        $sentDataItem["title"] , 
        $sentDataItem["link"] , 
        $sentDataItem["pubdate"] , 
        $sentDataItem["guid"] , 
        $sentDataItem["description"] , 
        $sentDataItem["imgWidth"] , 
        $sentDataItem["imgHeight"] , 
        $sentDataItem["imgsrc"] , 
        $sentDataItem["feed_hashtag"]
        );
        
        
        

        $query = mysql_query($sql,$connection);

    }

    
}

function execute()
{

    //GET FEEED SOURCES FROM DB
    /////////////////////////////
    $feedSources = getFeedSources();


    //GET ENTRIES
    /////////////////////////////
    $items = getItems($feedSources);
    printArray($items);
    //SAVE ENTRIES TO DB
    /////////////////////////////
    saveItems($items);
}


execute();



//printArray($items);




//CLOSE MYSQL CONNECTION
/////////////////////////////
if (isset($query)){@mysql_free_result($query);}
mysql_close($connection);
?>
