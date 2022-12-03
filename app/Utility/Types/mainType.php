<?php
namespace App\Utility\Types;

use App\Utility\Requests\Request;
use App\Utility\Response;

class mainType
{
    public $imdbId;
    public $imdbUrl="https://www.imdb.com/title/";
    public $iranHost = 'https://www.imdbfile.com/fuckingLeecher/leech.php?';
    public $request;
    public function __construct($imdbId)
    {
        $this->imdbId=$imdbId;
        $this->request=new Request();
        $this->respond=new Response();
    }

    // this function use fro upload image to ir host
    public function getLinkFromIranHost($link){
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL,$this->iranHost."id={$this->imdbId}&link={$link}");
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Imdb Api');
        $obj = curl_exec($curl_handle);
        curl_close($curl_handle);

        $obj = json_decode($obj);

        if(!$obj->error){
            return $obj->link;
        }else{
            return $link;
        }

    }
    public function getData($patern,$string)
    {
        return $this->regex($patern,$string);
    }
    public function getDataAll($patern,$string)
    {
        return $this->regexall($patern,$string);
    }
    public function getActors($fullpage)
    {
        $a='<tr><td colspan="4" class="castlist_label"></td></tr>';
        $str=substr($fullpage,strpos($fullpage,$a));
        $fullpage=substr($str,0,strpos($str,"</table>"));
        // echo $fullpage;
        // exit();
        // echo $str;
        // exit();
        // $patern='/<tr><td colspan="4" class="castlist_label"></td></tr>/s';
        // $fullpage=$this->getData($patern,$fullpage);
        // print_r($data);
        // exit();
        $patern='/<td>\n<a href="\/name(.*?)"\n> (.*?)\n<\/a>/s';
        $data=$this->regexall($patern,$fullpage);
        // print_r($data);
        $this->args['actorCount']=count($data[1]);
        return $data;
    }
    public function generateActorUrl($actors)
    {
        $result=[];
        foreach($actors as $act=>$value)
        {
            $result[$act]=$this->imdb."/name/".$act;
        }
        return $result;
    }
    public function trailer($item)
    {
        if(isset($item['trailer']))
        {
            $trailer['thumbnail']=$item['trailer']['thumbnail']['contentUrl'];
            $trailer['video']=$item['trailer']['embedUrl'];
            return $trailer;
        }
    }
    public function getRate($raw)
    {
        $result['count'] = number_format($raw['aggregateRating']['ratingCount']);
        $result['avg'] = $raw['aggregateRating']['ratingValue'];
        return $result;
    }
    public function refactorActors($all,$indexx=9999999)
    {
        $result=[];
        $index=0;
        foreach($all[1] as $item)
        {
            if($index < $indexx)
            {
                $result[$this->getImdbIdFromActor($item)]=$all[2][$index];
            }
            $index+=1;
        }
        return $result;
    }
    public function BirthLocation($raw)
    {

        // print_r($raw);
        // exit();
        $patern='/<a href="\/search\/name\?birth_place=.*?"\n>(.*?)<\/a>/s';
        @$data=$this->getData($patern,$raw)[1];
        $result=isset($data) ? $data : null;
        return $result;

    }
     public function getDataom($items)
    {
        $results=[];
        foreach($items as $item)
        {

            $results[]=$this->ontoArray($this->getdatom($item));
        }
        return $results;
    }
    public function getImdbIdFromActor($item)
    {
        return substr($item,1,9);
    }
    public function getWriter($fullpage)
    {
        $patern='/<h4 class="inline">Writers:<\/h4>(.*?)<\/div>/s';
        $writersDiv=$this->getData($patern,$fullpage);
        $patern='/<a href="(.*?)"\n>(.*?)<\/a>/s';
        @$writersDiv=$this->getDataAll($patern,$writersDiv[1]);
        $result=[];
        foreach($writersDiv[1] as $item=>$value)
        {
            if(strpos($this->getImdbIdFromLong($value),'nm') !== false)
            {
                $result[$this->getImdbIdFromLong($value)]=$writersDiv[2][$item];
            }
        }
        return $result;
    }
    public function writersUrl($writer)
    {
        $data=array_keys($writer);
        $result=[];
        foreach($data as $item)
        {
            $result[$item]=$this->imdb."/name/".$item;
        }
        return $result;
    }
    public function getImdbIdFromLong($item)
    {
        return substr($item,6,9);
    }
    public function getdatom($string)
    {
        $patern='/<script type="application\/ld\+json">(.*?)<\/script>/s';
        return $this->regex($patern,$string);
    }

    public function regex($patern,$string)
    {
        preg_match($patern,$string,$result);
        return $result;
    }

    public function regexall($patern,$string)
    {
        preg_match_all($patern,$string,$result);
        return $result;
    }
    public function ontoArray($data)
    {
        // $data=(array) $data[0];
        @$data=json_decode($data[1],true);
        return $data;
    }
}
