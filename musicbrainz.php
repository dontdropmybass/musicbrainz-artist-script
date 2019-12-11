<?php

/**
 * @param array $data
 * @param SimpleXMLElement $xml_data
 */
function array_to_xml(array $data, SimpleXMLElement &$xml_data ) {
    foreach($data as $key => $value) {
        if(is_numeric($key)){
            $key = 'item'.$key; //dealing with <0/>..<n/> issues
        }
        if( is_array($value) ) {
            $subNode = $xml_data->addChild($key);
            array_to_xml($value, $subNode);
        } else {
            $xml_data->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

/**
 * @param array $artist
 * @return array
 */
function flattenArtist(array $artist) : array {
    $result = [];
    array_walk_recursive($artist, function($value, $key) use (&$result) {
        $result[] = "$key:$value";
    });
    return $result;
}

// take the name of the city as arguments and create url
$cityName = urlencode(implode(' ', array_slice($argv, 1)));
$url = "https://musicbrainz.org/ws/2/artist/?limit=10&fmt=json&query=area:$cityName%20OR%20beginarea:$cityName";

// initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
$output = curl_exec($ch);
curl_close($ch);

// process data...
$artists = json_decode($output, true)["artists"];
foreach ($artists as $artist) {
    $artist["name"] = preg_replace("/o/i", "^", $artist["name"]);
}

// output to xml file
$xml_data = new SimpleXMLElement('<?xml version="1.0"?><data></data>');

array_to_xml($artists, $xml_data);
$xml_data->asXML("artists-from-$cityName.xml");

// output to csv file
$csvFile = fopen("artists-from-$cityName.csv",'w');
header("Content-Type:application/csv");
header("Content-  Disposition:attachment;filename=product_catalog.csv");
foreach ($artists as $artist) {
    fputcsv($csvFile, flattenArtist($artist));
}
fclose($csvFile);
