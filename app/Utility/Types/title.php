<?php
namespace App\Utility\Types;

use App\Utility\Types\directorTrait;
use App\Utility\Types\writersTrait;
use App\Utility\Leech;
class   title extends mainType
{
    use directorTrait;
    use writersTrait;
    public $imdb="https://www.imdb.com";
    public $fullPageCreadit;
    public $fullPageraw;
    public $args;
    public function get($id)
    {
        $this->imdbId = $id;
        $data=$this->initMainPageLoad();

        if(strpos($this->args['fullPage'],'<div id="error" class="error_code_404">')!==false  )
        {
            return $this->respond->error("movie not found",404);
        }

         return $this->responde($data,$this->fullPageraw);

    }
    public function prepareActors()
    {
        $actors=$this->getActors($this->fullPageCreadit);
        return $this->refactorActors($actors,3);
    }

    public function initMainPageLoad()
    {
        // links
        $fullpage=$this->imdbUrl.$this->imdbId;
        $fullpageCreadit=$this->imdbUrl.$this->imdbId."/fullcredits";
        $pilotPage=$this->imdbUrl.$this->imdbId."/plotsummary";
        $awardsPage=$this->imdbUrl.$this->imdbId."/awards";
        $reviewPage=$this->imdbUrl.$this->imdbId."/criticreviews";
        //
        $pages=[$fullpage,$fullpageCreadit,$pilotPage,$awardsPage,$reviewPage];
        $results=$this->request->get($pages);
        //set creadit page
        $this->args['fullPage']=$this->request->getContent($results[0]);
        $this->fullPageCreadit=$this->request->getContent($results[1]);
        $this->args['pilot']=$this->request->getContent($results[2]);
        $this->args['awardsPage']=$this->request->getContent($results[3]);
        $this->args['review']=$this->request->getContent($results[4]);
        $result=$this->request->getContent($results[0]);
        $this->fullPageraw=$result;
        // echo $result;
        $data=$this->getdatom($result);
//        dd($data);
        return $data=$this->ontoArray($data);
    }
    public function reviews()
    {
        $raw=$this->args['review'];
        $patern='/<div class="critscore.*?<span itemprop="ratingValue">(.*?)<\/span>.*?<td class="review">.*?<div class="summary" itemprop="reviewbody">(.*?)<\/div>/s';
        $namePatern='/<td class="review">.*?<span itemprop="name">(.*?)<\/span>/s';
        $data=$this->getDataAll($patern,$raw);
        $names=$this->getDataAll($namePatern,$raw)[1];
        $result=[];
        foreach($data[2] as $key=>$value)
        {
            $result[$key]['name']=$names[$key];
            $result[$key]['reviewbody']=$value;
            $result[$key]['rate']=$data[1][$key];
        }
        return $result;
    }

    /*
    public function getMetaScoreAvg()
    {

        $raw=$this->args['fullPage'];
        $patern='/<div class="metacriticScore .*? titleReviewBarSubItem">.*?<span>(.*?)<\/span>.*?<\/div>/s';
        if(strpos($raw,'<div class="metacriticScore')!==false)
        {
            $data=$this->getData($patern,$raw);
            return $data[1];
        }
        return null;
    }
    */

    public function getMetaScoreAvg()
    {
        $raw=$this->args['fullPage'];
        $patern='/<span class="score-meta".*?>(.*?)<\/span>/s';
        $data=$this->getDataAll($patern,$raw);

        if(!isset($data[1]) || is_null($data[1]))
            return null;

        $MetaScoreValue = $data[1];
        return $MetaScoreValue[0];
    }
    public function budget()
    {
        $raw=$this->args['fullPage'];
        if(strpos($raw,'Budget')!==false && strpos($raw,'$')!==false)
        {
            $patern='/<h4 class="inline">Budget:<\/h4>(.*?)<span/s';
            $data=$this->getData($patern,$raw);
            return @$data[1];
        }
        return null;
    }

    public function awards()
    {
        $havenot="It looks like we don't have any Awards for this title yet";
        $raw=$this->args['awardsPage'];
        if(strpos($raw,$havenot)!==false){
            return null;
        }
        $patern='/<h3>(.*?)<a href="\/event.*?".class="event_year" >(.*?)<\/a>  <\/h3>.*?<table class="awards"(.*?)<\/table>/s';
        $data=$this->getDataAll($patern,$raw);
        // print_r($data);
        // exit();
        $result=[];
        foreach($data[1] as $key=>$value)
        {
            $result[$key]['name']=$data[1][$key];
            $result[$key]['year']=$data[2][$key];
            $result[$key]['detail']=$this->awardDetails($data[3][$key]);
        }

        //return $result;
        $f=[];
        foreach ($result as $res)
        {
            $res['detail']=array_filter($res['detail'],function($i){
                return trim(strtolower($i['reward']['status']))=='winner';
            });
            if(count($res['detail'])>0)
            {

            $f[]=$res;
            }
        }
        return  $f;
    }
    public function awardDetails($raw)
    {
        $patern='/<tr>.*?<td.class="award_description">.*?<\/tr>/s';
        $datas=$this->getDataAll($patern,$raw);
        // print_r($datas);
        // exit();
        $results=[];
        $index=0;
        foreach($datas as $item)
        {
            foreach($item as $in)
            {
                $patern2='/<td .*?">(.*?)<\/td>/s';
                $iii=$this->getDataAll($patern2,$in);
                // print_r($iii);
                // exit();

                    // exit('here');
                    $paternFoRWinner='/.*?">.*?<b>(.*?)<\/b><br \/>.*?<span class="award_category">(.*?)<\/span>.*?<\/td>/s';
                    $paternforNonStatus='/<td class="award_description">(.*?)<br \/>.*?<a.href=".*?"*.?>(.*?)<\/a>.*?<br \/>.*?<\/td>/s';
                    // print_r($in);
                    // print_r($this->getData($paternFoRWinner,$in)[1]);
                    // exit();
                    @$results[$index]['Receptor']['name']=$this->getData($paternforNonStatus,$in)[2];
                    @$results[$index]['Receptor']['details']=$this->getData($paternforNonStatus,$in)[1];

                    if(isset($this->getData($paternFoRWinner,$in)[1]))
                    {
                        @$results[$index]['reward']['status']=$this->getData($paternFoRWinner,$in)[1];
                        @$results[$index]['reward']['name']=$this->getData($paternFoRWinner,$in)[2];
                        @$this->args['pp']=$this->getData($paternFoRWinner,$in)[1];
                        @$this->args['tt']=$this->getData($paternFoRWinner,$in)[2];
                    }else{
                        @$results[$index]['reward']['status']=$this->args['pp'];
                        @$results[$index]['reward']['name']=$this->args['tt'];
                    }

                    foreach($results[$index]['reward'] as $key=>$i)
                    {
                        $d=trim($i);
                        $results[$index]['reward'][$key]=$d!="" ? $d : null;
                    }

                    foreach($results[$index]['Receptor'] as $key=>$i)
                    {
                        $d=trim($i);
                        $results[$index]['Receptor'][$key]=$d!="" ? $d : null;
                    }
                $index+=1;

            }
        }
        return $results;
        // print_r($results);
        // exit();
    }
    public function pilot()
    {
        $raw=$this->args['pilot'];
        $patern='/<li class="ipl-zebra-list__item" id=".*?">.*?<p>.*?<\/p>.*? <li class="ipl-zebra-list__item" (.*?)<p>(.*?)<\/p>/s';
        $data=$this->getData($patern,$raw);
        $result['key']=substr($data[1],4,17);
        $result['pilot']=$data[2];
        return $result;
    }
    /*public function getContry()
    {
        $raw=$this->args['fullPage'];
        $patern='/<a href="\/search\/title\?country_of_origin=.*?&ref_=tt_dt_dt".*?>(.*?)<\/a>/s';
        $data=$this->getDataAll($patern,$raw);
        return $data[1];
    }*/
    public function getContry()
    {
        $raw=$this->args['fullPage'];
        $patern='/data-testid="title-details-origin">(.*?)<li role="presentation" class="ipc-metadata-list__item(.*?) data-testid/s';
        $data=$this->getDataAll($patern,$raw);

        $countryValue = array();
        if(!isset($data[1][0]) || is_null($data[1][0]))
            return null;
        $data[1][0] = str_replace("<a ", "kiavashjon <a",$data[1][0]);
        $countryValue = explode("kiavashjon ", strip_tags($data[1][0]));
        $countryValeResult = array();
        $countryValeResultCounter = 0;
        for($i = 0; $i <= count($countryValue); $i++)
            if(empty($countryValue[$i]) || $countryValue[$i] == "Countries of origin" || $countryValue[$i] == "Country of origin")
                unset($countryValue[$i]);
            else {
                $countryValeResult[$countryValeResultCounter] = $countryValue[$i];
                $countryValeResultCounter += 1;
            }

        return $countryValeResult;
    }

    /*
    public function langs()
    {
        $raw=$this->args['fullPage'];
        $patern='/<a href="\/search\/title\?title_type=feature&primary_language=.*?&sort=.*?>(.*?)<\/a>/s';
        $data=$this->getDataAll($patern,$raw);
        return $data[1];
    }
    */
    public function langs()
    {
        $raw=$this->args['fullPage'];
        $patern='/data-testid="title-details-languages">(.*?)<li role="presentation" class="ipc-metadata-list__item(.*?) data-testid/s';
        $data=$this->getDataAll($patern,$raw);

        $langValue = array();
        if(!isset($data[1][0]) || is_null($data[1][0]))
            return null;
        $data[1][0] = str_replace("<a ", "kiavashjon <a",$data[1][0]);
        $langValue = explode("kiavashjon ", strip_tags($data[1][0]));
        $langValeResult = array();
        $langValeResultCounter = 0;
        for($i = 0; $i <= count($langValue); $i++)
            if(empty($langValue[$i]) || $langValue[$i] == "Language" || $langValue[$i] == "Languages")
                unset($langValue[$i]);
            else {
                $langValeResult[$langValeResultCounter] = $langValue[$i];
                $langValeResultCounter += 1;
            }

        return $langValeResult;
    }

    /*public function company()
    {
        $raw=$this->args['fullPage'];
        $patern='/<a href="\/company\/.*?".*?>(.*?)<\/a>/s';
        $data=$this->getDataAll($patern,$raw);
        return $data[1];
    }*/
    public function company()
    {
        $raw=$this->args['fullPage'];
        $patern='/data-testid="title-details-companies">(.*?)<li role="presentation" class="ipc-metadata-list__item ipc-metadata-list-item--link"/s';
        $data=$this->getDataAll($patern,$raw);

        $companyVale = array();
        if(!isset($data[1][0]) || is_null($data[1][0]))
            return null;
        $data[1][0] = str_replace("<a ", "kiavashjon <a",$data[1][0]);
        $companyVale = explode("kiavashjon ", strip_tags($data[1][0]));
        $companyValeResult = array();
        $companyValeResultCounter = 0;
        for($i = 0; $i <= count($companyVale); $i++)
            if(empty($companyVale[$i]) || $companyVale[$i] == "Production companies" || $companyVale[$i] == "Production company")
                unset($companyVale[$i]);
            else {
                $companyValeResult[$companyValeResultCounter] = $companyVale[$i];
                $companyValeResultCounter += 1;
            }

        return $companyValeResult;
    }

    /*public function website()
    {
        $raw=$this->args['fullPage'];
        $patern='/<h4 class="inline">Official Sites:<\/h4>.*?<a href="\/offsite\/(.*?)".*?rel="nofollow" >(.*?)<\/a>/s';
        $webs=$this->getDataAll($patern,$raw);
        if(isset($webs[1][0]))
        {
            return $this->imdb.'/offsite/'.$webs[1][0];
        }
    }*/
    public function website()
    {
        $raw=$this->args['fullPage'];
        $patern='/data-testid="title-details-officialsites">(.*?)<\/a>/s';
        $data=$this->getDataAll($patern,$raw);

        if(!isset($data[1][0]) || is_null($data[1][0]))
            return null;
        preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $data[1][0], $matchedURL);
        if(count($matchedURL) < 1)
            $resultWebsite = null;
        else
            $resultWebsite = implode("",$matchedURL[0]);

        return $resultWebsite;
    }

    public function genre($raw)
    {
        return isset($raw['genre'])?implode(",",(array)$raw['genre']):null;
    }

    /*
    public function runtime($fullpage)
    {
        return $this->getData('/<time datetime=".*?">(.*?)<\/time>/s',$fullpage)[1];
    }
    */
    public function runtime($fullpage)
    {
        $raw=$this->args['fullPage'];

     //   $patern(old)='/<span class="ipc-metadata-list-item__list-content-item">(.*?)<\/span>/s';
//        $patern='/<li role=\"presentation\" class=\"ipc-inline-list__item\">([^<]*)<\/li>/';
//            $patern='/<div class=\"ipc-metadata-list-item__content-container\"><!-- --><!-- --><!-- --><!-- --><!-- --><!-- -->([^<]*)\<\/div>/gm';
//         /<div class=\"ipc-metadata-list-item__content-container\"><!-- --><!-- --><!-- --><!-- --><!-- --><!-- -->([^<]*)\<\/div>
//        $patern="/<li role=\"presentation\" class=\"ipc-inline-list__item\">\d<!-- -->[h]<!-- --> <!-- -->\d\d<!-- -->[m]\<\/li>//";
//        $patern="/<li role=\"presentation\" class=\"ipc-inline-list__item\">[0-9]*<!-- -->[h]*<!-- --> <!-- -->[0-9]*<!-- -->[m]*<\/li>/s";
//        $patern="/[0-9]* hour [0-9]* minutes/i";
        $patern='/<li role="presentation" class="ipc-inline-list__item">[0-9]*<!-- -->[h]*[<!-- --> <!-- -->]*[0-9]*[<!-- -->]*[m]*<\/li>/';
//        $test = 'metadata-list ipc-metadata-list--dividers-none ipc-metadata-list--compact ipc-metadata-list<!-- --><!-- --><!-- --><!-- --><!-- --><!-- -->1 hour 58 minutes';
//        $mathches = preg_match_all($patern,$raw);
//        var_dump($mathches);
        file_put_contents(__DIR__."/test.html",$raw);


        $data=$this->getDataAll($patern,$raw);
//        var_dump($data);

        file_put_contents(__DIR__."/test.json",json_encode($data[1]));
        return $data[0][0];
//        foreach($data[1] as $dnll)
//            if(strpos($dnll, "h") && strpos($dnll, "min")) {
//                $runtimeValue = $dnll;
//                return $runtimeValue;
//            }
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
    public function cron_top() {
        $cache_name=__DIR__."/cache/top.json";

        $re = '/<td class="posterColumn">.*?<span name="rk" data-value="([0-9]+)"><\/span>.*?<a href="\/title\/(.*?)\/.*?>.*?src="(https:\/\/m\.media-amazon\.com\/images.*?\.jpg).*?alt="(.*?)".*?<td class="ratingColumn imdbRating">(.*?)<\/td>/ms';
        $str = $this->getUrl('https://www.imdb.com/chart/top');

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
        file_put_contents($cache_name,json_encode($cache));
    }
    public function cron_toptv() {
        $cache_name=__DIR__."/cache/toptv.json";

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
        file_put_contents($cache_name,json_encode($cache));
    }
    public function is_top()
    {
        $this->cron_top();
        $this->cron_toptv();

        $path=__DIR__."/cache/top.json";
        $path_tv=__DIR__."/cache/toptv.json";
        $data=[];
        $datatv=[];
        if(file_exists($path))
        {
            $data=array_column(json_decode(file_get_contents($path),1)['result'],'id');
        }
        if(file_exists($path_tv))
        {
            $datatv=array_column(json_decode(file_get_contents($path_tv),1)['result'],'id');
        }

        $data=array_merge($data,$datatv);
        $status= in_array($this->imdbId,$data);
        $index=array_search($this->imdbId,$data);
        if($index > 250)
        {
            $index=$index-250;
        }
        return [
            'status'=>$status,
            'index'=>$status ? $index+1 : null
            ];
    }
    public function leech($url)
    {
        $data=[
            'main_poster'=>[['url'=>$url]]
            ];
        $r=Leech::leech($data,$this->imdbId);
        return @$r['main_poster'][0];
    }
    public function trailerPoster($data)
    {
        $re = '/<img alt="Trailer"[\W]+title="Trailer"[\W]+src="(.*?)" \/>/ms';

        preg_match($re, $data, $matches);

        return $matches[1];
    }
    public function responde($raw,$fullpage)
    {

        $actors=$this->prepareActors();

        // }
        $data=[
            "Title"         =>$raw['name'],
            "is_top"         =>$this->is_top(),
            "imdbId"        =>str_replace("/", "", substr($raw['url'],7)),
            "Year"          =>substr($raw['datePublished'],0,4),
            "Released"      =>$raw['datePublished'],
            "genre"         =>$this->genre($raw),
            "Type"          =>$raw['@type'],
            "budget"        =>trim($this->budget()),
//            "MainPoster"    =>$this->leech($raw['image']),
            "RateA"         =>isset($raw['contentRating'])?$raw['contentRating']:null,
            "Runtime"       =>$this->runtime($fullpage),
            "Metascore"   =>$this->getMetaScoreAvg(),
            "website"       =>$this->website(),
            // "Writer"        =>$this->writers($raw,$fullpage),
            // "writerAsUrl"   =>$writersUrl,
            // "Director"      =>$this->director($raw),
            "company"       =>$this->company(),
            "Country"       =>$this->getContry(),
            "pilotKey"      =>$this->pilot()['key'],
            "pilot"         =>strip_tags($this->pilot()['pilot']),
            "Language"      =>$this->langs(),
            "Rate"          =>$this->getRate($raw),
            "Trailer"       =>$this->trailer($raw),
            "trailerPoster"       =>$this->trailerPoster($fullpage),
            // "reviews"       =>$this->reviews(),
            // "actorsCount"   =>count($actors),
            // "actors"        =>$actors,
            "award"         =>$this->awards(),

        ];
        foreach($data as $key=>$i)
        {
            if(!is_array($i))
            {
                $data[$key]=trim(strip_tags($i));
            }
        }
        return $this->respond->json($data,200);
    }

}
