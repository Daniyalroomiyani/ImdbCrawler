<?php
namespace Movie\Types;

use App\Utility\Types\mainType;
use Movie\Leech;
class Toptv extends MainType
{
    public $cache_name=__DIR__."/cache/toptv.json";
    public function get()
    {
        if(file_exists($this->cache_name))
        {
            $data=json_decode(file_get_contents($this->cache_name),1);
            if(time()-$data['created_at'] < 10*60)
            {
                return  $this->respond->json($data['result'],200);;
            }
        }

        $re = '/<td class="posterColumn">.*?<span name="rk" data-value="([0-9]+)"><\/span>.*?<a href="\/title\/(.*?)\/.*?>.*?src="(https:\/\/m\.media-amazon\.com\/images.*?\.jpg).*?alt="(.*?)".*?<td class="ratingColumn imdbRating">(.*?)<\/td>/ms';
        $str = $this->getUrl('https://www.imdb.com/chart/toptv');

        preg_match_all($re, $str, $matches);

        $data=[];
        foreach ($matches[1] as $key => $value) {
            $item=[];
            $item['rank']=$value;
            $item['id']=$matches[2][$key];
            $item['name']=$matches[4][$key];
            $item['rate']=trim(strip_tags($matches[5][$key]));
            $data[]=$item;
        }
        $cache['result']=$data;
        $cache['created_at']=time();
        file_put_contents($this->cache_name,json_encode($cache));

        return $this->respond->json($data,200);
    }
    public function getUrl($url)
    {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
}
