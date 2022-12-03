<?php

namespace App\Utility\Types;

use http\Env\Response;
use App\Utility\Types\mainType;
use App\Utility\Leech;
//use function GuzzleHttp\Psr7\str;

class Series extends MainType
{
    public function get($id)
    {

        $items = json_decode(seriesLoad($id), true);
        $result = [];
        foreach ($items as $key => $item) {
            if (isset($_GET['with_key']) && $_GET['with_key'] == true) {
                $result[$key] = [
                    "Name" => $key,
                    "Episodes" => $item
                ];
            } else {
                $result[] = [
                    "Name" => $key,
                    "Episodes" => $item
                ];
            }

        }
        $data = [];


        return $this->respond->json($result, 200);
    }
}

function seriesLoad($id)
{
//    if (!isset($_GET['id'])) { //change to laravel request
//        exit('parameter [id] not found');
//    }

//    $imdb_id = $_GET['id']; //change to laravel request
    $imdb_id = $id;
    if (!file_exists(__DIR__ . "/cache")) {
        mkdir(__DIR__ . "/cache");
    }
    if (!file_exists(__DIR__ . "/cache/json")) {
        mkdir(__DIR__ . "/cache/json");
    }

    $url = "https://www.imdb.com/title/$imdb_id/";
    $result_path = __DIR__ . "/cache/json/" . md5($url) . '.json';
    if (file_exists($result_path)) {
        $data = json_decode(file_get_contents($result_path), true);
//            return json_encode($data['result']);

        if (time() - $data['created_at'] < 86400 ) {
            return json_encode($data['result']);
        } else {
            $sessions_count = getSeriesSessionsCount($url);
            $result['result'] = handle($sessions_count, $imdb_id);
            $result['created_at'] = time();
            file_put_contents($result_path, json_encode($result));
            return json_encode($result['result']);
        }
    } else {
        $sessions_count = getSeriesSessionsCount($url);

        $result['result'] = handle($sessions_count, $imdb_id);
        $result['created_at'] = time();
        file_put_contents($result_path, json_encode($result));
        return json_encode($result['result']);
    }

    //test -> do not touch this shit
//
//    $sessions_count = getSeriesSessionsCount($url);
//    $result['result'] = handle($sessions_count, $imdb_id);
//    $result['created_at'] = time();
//    return json_encode($result['result']);
//
}
function urlToSessionNumber($input)
{
    $re = '/season=([0-9])+/im';
    preg_match($re, $input, $matches);

    return $matches[1];
}

// Print the entire match result
function handle($sessions_count, $imdb_id)
{
    $urls = [];
    $result = [];


    for ($i = 1; $i <= $sessions_count; $i++) {
        $url = "https://www.imdb.com/title/$imdb_id/episodes?season=" . $i;

//        if ($i != $sessions_count && file_exists(__DIR__ . "/cache/" . md5($url) . ".json")) {
//            $data = json_decode(file_get_contents(__DIR__ . "/cache/" . md5($url) . ".json"), true);
//            if (is_array($data)) {
//                $result[$url] = $data;
//                continue;
//            }
//        }

        $urls[] = $url;


    }



    if (count($urls) > 0) {

        $contents = multiRequest($urls);


        foreach ($contents as $key => $value) {

            $re = '/<a href="\/title\/([tt0-9]+)\/\?ref_=ttep_ep[0-9]{1,2}"\ntitle="(.*?)" itemprop="name">.*?<\/a>/im';
            //$re = '/<strong><a href="\/title\/([tt0-9]+)\/\?ref_=ttep_ep([0-9])+/sg';
            $result[$key] = [];

            preg_match_all($re, $value, $matches);

            foreach ($matches[1] as $keyy => $valuee) {
                $result[$key][] = [
                    'episode' =>strval($keyy+1),
                    'name' => $matches[2][$keyy],
                    'imdb_id' => $valuee,
                    //   'poster'=>getPoster($value)
                ];
                //file_put_contents(__DIR__."/file.json",$value);
            }
            file_put_contents(__DIR__ . "/cache/" . md5($key) . ".json", json_encode($result[$key]));
        }
    }
    $res = [];
    foreach ($result as $key => $value) {
        $res[urlToSessionNumber($key)] = $value;
    }
    $result = null;
    return $res;
}

function get($url) // works find
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');


    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Authority: www.imdb.com';
    $headers[] = 'Cache-Control: max-age=0';
    $headers[] = 'Upgrade-Insecure-Requests: 1';
    $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36 OPR/60.0.3255.95';
    $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: en-US,en;q=0.9';

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    return $result;
}

function getSeriesSessionsCount($url)
{
    $series_page = get($url);
   $series_page= strtolower($series_page);
    $re = '/[1-80]{1,2} season/ms';
    preg_match_all($re, $series_page,$matches);
//    dd($matches);
    $str = $matches[0][0];
    $index = strlen($str) - 7;
    return (int)substr($str ,0 , $index);
}

function getPoster($str)
{
    $re = '/<link rel=\'image_src\' href="(.*?)">/m';
    preg_match($re, $str, $matches);
    return $matches[1];
}

function multiRequest($data, $options = array())
{

    $chs = array();
    $result = array();

    $mh = curl_multi_init();

    foreach ($data as $id => $d) {

        $chs[$id] = curl_init();

        $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
        curl_setopt($chs[$id], CURLOPT_URL, $url);
        curl_setopt($chs[$id], CURLOPT_HEADER, 0);
        curl_setopt($chs[$id], CURLOPT_RETURNTRANSFER, 1);

        if (is_array($d)) {
            if (!empty($d['post'])) {
                curl_setopt($chs[$id], CURLOPT_POST, 1);
                curl_setopt($chs[$id], CURLOPT_POSTFIELDS, $d['post']);
            }
        }

        // extra options?
        if (!empty($options)) {
            curl_setopt_array($chs[$id], $options);
        }
        $headers = array();
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36 OPR/60.0.3255.95';
        $headers[] = 'Referer: https://www.google.ro/';
        curl_setopt($chs[$id], CURLOPT_HTTPHEADER, $headers);

        curl_multi_add_handle($mh, $chs[$id]);
    }

    // execute the handles
    $running = null;
    do {
        curl_multi_exec($mh, $running);
    } while ($running > 0);


    // get content and remove handles
    foreach ($chs as $c) {
        $id = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
        $result[$id] = curl_multi_getcontent($c);
        curl_multi_remove_handle($mh, $c);
    }

    // all done
    curl_multi_close($mh);


    return $result;
}
