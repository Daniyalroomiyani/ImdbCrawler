<?php
namespace Movie\Types;

use App\Utility\Types\mainType;

class gallery extends MainType
{
    public $imdb="https://www.imdb.com";
    public $fullPageCreadit;
    public $args=[];
    public $page;
    public $type;
    public function get()
    {
        $data=$this->initMainPageLoad();
        $this->getImgaesCount();
        return $this->responds($data);
    }
    public function handleRequestType()
    {

        if(isset($_GET['page']) && is_numeric($_GET['page']))
        {
            $this->page=(int)$_GET['page'];
        }else{
            $this->page=1;
        }
            $paginate=$this->calcAvalablePages();
        if($this->page > $paginate['allpages'])
        {
            return $this->respond->error("page not found",404);
        }
        return $paginate;
    }

    public function initMainPageLoad()
    {
        $fullpageCreadit=$this->imdbUrl.$this->imdbId."/mediaindex?ref_=tt_pv_mi_sm";
        $mediaviewer=$this->imdbUrl.$this->imdbId."/mediaviewer/rm1241571584?ref_=tt_pv_mi_sm";
        $main=$this->imdbUrl.$this->imdbId;
        $pages=[$fullpageCreadit,$mediaviewer,$main];
        $results=$this->request->get($pages);
        $this->fullPageCreadit=$this->request->getContent($results[0]);
        $this->args['types']=$this->fullPageCreadit;
        $this->args['mediaviewer']=$this->request->getContent($results[1]);
        $this->args['main']=$this->request->getContent($results[2]);
        return $this->fullPageCreadit;

    }

    public function getImgaesCount()
    {
        $patern='/<a href=".*?".*?> See all\n(.*?) photos<\/a>/s';
        $raw= $this->args['main'];
        $data=$this->getData($patern,$raw)[1];
        $this->args['ImagesCount']=str_replace(',','',$data);
        return $data;
    }
    public function getCountFromPregMan()
    {
        $file=$this->args['mediaviewer'];
            $firstKey="window.IMDbReactInitialState.push({'mediaviewer': ";
            $endtKey=");";
            $strpos=strpos($file,$firstKey);
            $file=substr($file,$strpos+strlen($firstKey));
            $strpos=strpos($file,$endtKey);
            $data=substr($file,0,$strpos-1);
            $images=json_decode($data,true);
            return count($images['galleries'][$this->imdbId]['allImages']);
    }
    public function calcAvalablePages()
    {
        $result=[];
        $count=$this->getCountFromPregMan();
        $rsult['allpages']=ceil($count/50);
        $rsult['curentPage']=$this->page;
        $rsult['prePage']= $this->page >1;
        $rsult['nextPage']= $this->page < $rsult['allpages'];
        return $rsult;
    }
    public function getTypes()
    {
        $raw=$this->args['types'];
        $patern='/<h4>Type<\/h4>.*?<\/ul>/s';
        $data=$this->getDataAll($patern,$raw);
        $patern='/<li><a href="(.*?)"\n>(.*?)<\/a>/s';
        $data=$this->getDataAll($patern,$data[0][0]);
        $result=[];
        foreach($data[1] as $key=>$item)
        {
            $result[$data[2][$key]]=$this->imdb.$item;
        }
        $this->args['typess']=array_keys($result);
        return $result;

    }
    public function responds($raw)
    {
        if(isset($_GET["all"])==true)
        {
            $data['Count']=$this->getRealLink('count');
            $data['images']=$this->getRealLink();
        }else{
            $this->args['paginate']=$this->handleRequestType();

            $data['paginate']=$this->args['paginate'];
            $max=$data['paginate']['curentPage']*50;
            $min=$max-50;
            $data['images']=$this->getRealLinkLimit($min,$max);


        }
        return $this->respond->json($data,200);
    }
    public function imagesPageUrlGenerator()
    {
        $url=$this->imdb.'/title/'.$this->imdbId.'/mediaindex?';
        $url.="page=".$this->page;
        return $url;
    }
    public function getRealLinkLimit($min,$max)
    {

            $file=$this->args['mediaviewer'];
            $firstKey="window.IMDbReactInitialState.push({'mediaviewer': ";
            $endtKey=");";
            $strpos=strpos($file,$firstKey);
            $file=substr($file,$strpos+strlen($firstKey));
            $strpos=strpos($file,$endtKey);
            $data=substr($file,0,$strpos-1);
            $Images=json_decode($data,true);
        $result=[];
        $index=0;
        foreach($Images['galleries'][$this->imdbId]['allImages'] as $key=>$uu)
        {
            if($key>=$min && $key <$max){
                $result[$index]['url']=$uu['src'];
                $result[$index]['type']=$uu['imageType'];
                $index+=1;
            }
        }
        return $result;



    }
    public function getRealLink($count=null)
    {
            $file=$this->args['mediaviewer'];
            $firstKey="window.IMDbReactInitialState.push({'mediaviewer': ";
            $endtKey=");";
            $strpos=strpos($file,$firstKey);
            $file=substr($file,$strpos+strlen($firstKey));
            $strpos=strpos($file,$endtKey);
            $data=substr($file,0,$strpos-1);
            $Images=json_decode($data,true);
        $result=[];
        if($count!=null)
        {
            return count($Images['galleries'][$this->imdbId]['allImages']);
        }
        foreach($Images['galleries'][$this->imdbId]['allImages'] as $key=>$uu)
        {
            $result[$key]['url']=$uu['src'];
            $result[$key]['type']=$uu['imageType'];
        }
        return $result;

    }
    public function getImages()
    {
        $url=$this->imagesPageUrlGenerator();
        return file_get_contents($url);
    }
    public function getJsons($items,$urls)
    {
        $results=[];
        foreach($items as $key=>$item)
        {
            $data=$this->request->getContent($item);
            $results[]=$this->ontoArray($this->getdatom($data));
        }
        return $results;
    }
    public function initUrls($urls)
    {
        $result=[];
        foreach($urls as $key=>$value)
        {
            $result[]=$value;
        }
        return $result;
    }
    public function grepImages($raw)
    {
        $patern='/<a href="(.*?)"\ntitle="(.*?)" ><img height="100" width="100" alt=".*?"\nsrc=".*?.jpg"\n\/><\/a>/';
        $data=$this->getDataAll($patern,$raw);

        $images=[];
        foreach ($data[1] as $key=>$item)
        {
            $images[$key]['url']=$this->imdb.$item;
        }
        return $images;
    }
    public function refactorImages($items)
    {
        $result=[];
        foreach($items['image'] as $key=>$Value)
        {
            $result[$key]['url']=$Value['url'];
            $result[$key]['width']=$Value['width'];
            $result[$key]['height']=$Value['height'];
            $result[$key]['caption']=$Value['height'];
        }
        return $result;
    }
}
