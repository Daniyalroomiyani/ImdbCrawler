<?php
namespace Movie\Types;

use App\Utility\Types\mainType;

class gtype extends MainType
{
    public $imdb="https://www.imdb.com";
    public $fullPageCreadit;
    public $args=[];
    public $page;
    public $type;
    public $gtype;
    public function get()
    {
        $data=$this->initMainPageLoad();
        $this->getImgaesCount();
        $this->args['pregman']=$this->pregman();
        $this->route();

    }

    public function HandleAllImages()
    {
        $countOFall=isset($_GET['count'])==true ? $_GET['count'] : 999999;
        $typesName=array_keys($this->getTypes());
        $type=str_replace(' ','_',strtolower($typesName[$this->gtype]));
        $data=[];
        $raw=$this->args['pregman'];
        $index=0;
        foreach($raw as $key=>$con)
        {
            if($index >= $countOFall)
            {
                break;
            }
            if($con['imageType']==$type)
            {
                $data["images"][$index]['url']=$con['src'];
                $index+=1;
            }
        }

        return $data;
    }
    public function normalizeUrl($a)
    {
        foreach($this->args['pregman'] as $item)
        {

            if($a['url'] == $item['id'])
            {
                $a['url']=$item['src'];
                break;
            }
        }
        return $a;
    }
    public function getImgaesCount()
    {
        $patern='/<a href=".*?".*?> See all\n(.*?) photos<\/a>/s';
        $raw= $this->args['main'];
        $data=$this->getData($patern,$raw)[1];
        $this->args['ImagesCount']=str_replace(',','',$data);
        return $data;
    }
    public function pregman()
    {
        $file=$this->args['mediaviewer'];
        $firstKey="window.IMDbReactInitialState.push({'mediaviewer': ";
        $endtKey=");";
        $strpos=strpos($file,$firstKey);
        $file=substr($file,$strpos+strlen($firstKey));
        $strpos=strpos($file,$endtKey);
        $data=substr($file,0,$strpos-1);
        return json_decode($data,true)['galleries'][$this->imdbId]['allImages'];
    }
    public function getContentsForAll($results)
    {
        $result=[];
        foreach($results as $item)
        {
            $result[]=$this->request->getContent($item);
        }
        return $result;
    }
    public function genUrlForAllTypeImages($pages,$typesName)
    {
        $urls=[];
        for ($i=0; $i < $pages ; $i++) {
            $aa=str_replace(' ','_',strtolower($typesName[$this->gtype]));
            $urls[]=$this->imdbUrl.$this->imdbId."/mediaindex?refine=".$aa."&page=".$i;
        }
        return $urls;
    }
    public function route()
    {
        if(isset($_GET['all']) && isset($_GET['select']))
        {
            $this->gtype=$_GET['select'];
            $data=$this->HandleAllImages();
            return $this->respond->json($data,200);
        }
        if(isset($_GET['select']))
        {
            $data=$this->initMainPageLoad();
            $this->args['paginate']=$this->handleRequestType();
            return $this->responds($data);
        }else{
            $datas=$this->getTypes();
            $data=[];
            $index=0;
            foreach($datas as $key=>$item)
            {
                $data[$index]['key']=$index;
                $data[$index]['name']=$key;
                $data[$index]['url']=$item;
                $index+=1;
            }
            $this->respond->json($data,200);
        }
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
        $this->args['types']=$this->request->getContent($results[0]);
        $this->args['mediaviewer']=$this->request->getContent($results[1]);
        $this->args['main']=$this->request->getContent($results[2]);
        return $this->fullPageCreadit;

    }


    public function calcAvalablePages()
    {
        $result=[];
        $count=(int) $this->args['ImagesCount'];
        $rsult['allpages']=ceil($count/48);
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
        $images=$this->getImages();
        $data['paginate']=$this->args['paginate'];
        $dataraw=$this->grepImages($images);
        foreach ($dataraw as $item)
        {
            $data['images'][]=$this->normalizeUrl($item);
        }
        return $this->respond->json($data,200);
    }
    public function selectType()
    {
        $type=$_GET['select'];
        $types=array_values($this->getTypes());
        if(isset($types[$type]))
        {
           return $types[$type];
        }else{
            return $this->respond->error("item not found",404);
        }
    }
    public function imagesPageUrlGenerator()
    {
        $url=$this->selectType();
        $url.="&page=".$this->page;
        return $url;
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
        $patern='/<a href=".*?mediaviewer\/(.*?)\?.*?"\ntitle=".*?" ><img height="100" width="100" alt=".*?"\nsrc=".*?.jpg"\n\/><\/a>/s';
        $data=$this->getDataAll($patern,$raw);

        $images=[];
        foreach ($data[1] as $key=>$item)
        {
            $images[$key]['url']=$item;
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
