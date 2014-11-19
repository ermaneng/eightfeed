<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
 </head>
 <body>
 	
 
<?php
require_once 'connection.php';

$ITEMS_PER_NEWSPAPER_LIMIT = 100; //0 is infinite

function convertToTR($text)
{
	
	//8859-1 normal
	$text = str_replace("&#286;",	"Ğ",$text);
	$text = str_replace("&Uuml;",	"Ü",$text);
	$text = str_replace("&#350;",	"Ş",$text);
	$text = str_replace("&#304;",	"İ",$text);
	$text = str_replace("&Ouml;",	"Ö",$text);
	$text = str_replace("&#Ccedil;","Ç",$text);

	$text = str_replace("&#287;",	"ğ",$text);
	$text = str_replace("&uuml;",	"ü",$text);
	$text = str_replace("&#351;",	"ş",$text);
	$text = str_replace("&#305;",	"ı",$text);
	$text = str_replace("&ouml;",	"ö",$text);
	$text = str_replace("&ccedil;",	"ç",$text);


	//converted to windows-1254 from utf-8
	$text = str_replace("ÄŸ",	"ğ",$text);
	$text = str_replace("Ã¼",	"ü",$text);
	$text = str_replace("ÅŸ",	"ş",$text);
	$text = str_replace("Ä±",	"ı",$text);
	$text = str_replace("Ã¶",	"ö",$text);
	$text = str_replace("Ã§",	"ç",$text);

	$text = str_replace("Ãœ",	"Ü",$text);
	$text = str_replace("Ä°",	"İ",$text);
	$text = str_replace("Ã–",	"Ö",$text);
	$text = str_replace("Ã‡",	"Ç",$text);
	$text = str_replace("Ä",	"Ğ",$text);
	$text = str_replace("Å",	"Ş",$text);


	//converted to 8859-1 from utf-8
	$text = str_replace("ð",	"ğ",$text);
	$text = str_replace("þ",	"ş",$text);
	$text = str_replace("ý",	"ı",$text);

	$text = str_replace("Ý",	"İ",$text);
	$text = str_replace("Ð",	"Ğ",$text);
	$text = str_replace("Þ",	"Ş",$text);


	//uel decode
	$text = str_replace("%C4%B1","%FD",$text);
	$text = str_replace("%C4%B0","%DD",$text);
	$text = str_replace("%C4%9F","%F0",$text);
	$text = str_replace("%C4%9E","%D0",$text);
	$text = str_replace("%C3%BC","%FC",$text);
	$text = str_replace("%C3%9C","%DC",$text);
	$text = str_replace("%C5%9F","%FE",$text);
	$text = str_replace("%C5%9E","%DE",$text);
	$text = str_replace("%C3%B6","%F6",$text);
	$text = str_replace("%C3%96","%D6",$text);
	$text = str_replace("%C3%A7","%E7",$text);
	$text = str_replace("%C3%87","%C7",$text);
	$text = str_replace("%FD","ı",$text);
	$text = str_replace("%DD","İ",$text);
	$text = str_replace("%F0","ğ",$text);
	$text = str_replace("%D0","Ğ",$text);
	$text = str_replace("%FC","ü",$text);
	$text = str_replace("%DC","Ü",$text);
	$text = str_replace("%FE","ş",$text);
	$text = str_replace("%DE","Ş",$text);
	$text = str_replace("%F6","ö",$text);
	$text = str_replace("%D6","Ö",$text);
	$text = str_replace("%E7","ç",$text);
	$text = str_replace("%C7","Ç",$text);

	//ajax decode
	$word=str_replace("%u011F","ğ",$word);
	$word=str_replace("%FC","ü",$word);
	$word=str_replace("%u015F","ş",$word);
	$word=str_replace("%u0131","ı",$word);	
	$word=str_replace("%F6","ö",$word);
	$word=str_replace("%E7","ç",$word);
	$word=str_replace("%u011E","Ğ",$word);
	$word=str_replace("%DC","Ü",$word);
	$word=str_replace("%u015E","Ş",$word);
	$word=str_replace("%u0130","İ",$word);
	$word=str_replace("%D6","Ö",$word);
	$word=str_replace("%C7","Ç",$word);

	$text = mysql_real_escape_string($text);
	//echo $text."<br><br>";
	return $text;
}


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

function customImageFileName($filename)
{
	$filename = convertToTR($filename);

	if (strpos($filename, "im.haberturk.com") && strpos($filename, "htufak"))
	{
		$filename = str_replace("htufak", "detay", $filename);
	}
	else if (strpos($filename, "media.ntvmsnbc.com") && strpos($filename, "thumb.jpg"))
	{
		$filename = str_replace("thumb.jpg", "hmedium.jpg", $filename);	
	}
    else if (strpos($filename, "turkiyegazetesi.com.tr") && strpos($filename, "113x"))
    {
        $filename = str_replace("113x", "", $filename); 
    }
	return $filename;
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
            $xml = simplexml_load_string($xml);                        
            

            if(is_object($xml)){
                if(sizeof($xml->xpath('/rss//item'))>0){
                     foreach ($xml->xpath('/rss//item') as $item) {
                        $item->addChild('feed_hashtag',$feed->hashtag);
                    }
                    
                    if ($ITEMS_PER_NEWSPAPER_LIMIT)
                    {
                        $entries = array_merge($entries, array_slice($xml->xpath('/rss//item'),0,$ITEMS_PER_NEWSPAPER_LIMIT));    
                    }
                    else
                    {
                        $entries = array_merge($entries, $xml->xpath('/rss//item'));
                    }
                    
                }
                else{
                    $cnt = 0;
                    foreach ($xml->entry as $item) {
                        $cnt++;
                        if(($ITEMS_PER_NEWSPAPER_LIMIT && $cnt<=$ITEMS_PER_NEWSPAPER_LIMIT) || !$ITEMS_PER_NEWSPAPER_LIMIT){
                            $item->addChild('feed_hashtag',$feed->hashtag);
                            $item->addChild('pubDate',$item->published);
                            $item->addChild('description',$item->summary);
                            $item->addChild('thumbnail');
                            $item->addChild('link',$item->link["href"]);
                            $item->thumbnail["url"] = $item->link->content->thumbnail[1]["url"];                            
                            array_push($entries, $item);
                        }
                    }
                }
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
            $item->addChild('imgsrc',customImageFileName($img_src));
        }
        else if($media_src!==""){
            $item->addChild('imgsrc',customImageFileName($media_src));
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
    //$text = str_replace('"', '\"', $text);
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
</body>
</html>