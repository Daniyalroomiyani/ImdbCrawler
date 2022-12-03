<?php
namespace Movie\Types;

ini_set('max_execution_time', 300);

use App\Utility\Types\mainType;
use Movie\Leech;
class alltypes extends MainType
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
        $this->args['pregman']=$this->pregman();

        return $this->all();
    }

     public function getNewImages($id){

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://graphql.imdb.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $data = json_decode(file_get_contents(__DIR__.'/payload.json'),true);
        $data['variables']['id'] = $id;

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

        $headers = array();
        $headers[] = 'Authority: graphql.imdb.com';
        $headers[] = 'X-Imdb-User-Country: US';
        $headers[] = 'X-Imdb-User-Language: en-US';
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36';
        $headers[] = 'X-Imdb-Client-Name: imdb-web-next';
        $headers[] = 'Accept: */*';
        $headers[] = 'Origin: https://www.imdb.com';
        $headers[] = 'Sec-Fetch-Site: same-site';
        $headers[] = 'Sec-Fetch-Mode: cors';
        $headers[] = 'Sec-Fetch-Dest: empty';
        $headers[] = 'Referer: https://www.imdb.com/';
        $headers[] = 'Accept-Language: en-US,en;q=0.9,la;q=0.8,fa;q=0.7';

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

         $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $data = json_decode($result,1)['data']['title']['images']['edges'];
        $lllinks = [];
        foreach ($data as $key => $value) {
            $lllinks[] = [
                'url'=>$value['node']['url'],
                'type'=>$value['node']['type']
                ];
        }
        return $lllinks;
    }

    public function all()
    {
        // if(isset($_GET['count']) && is_numeric($_GET['count']))
        // {
        //     return $this->allwithCount();
        // }
        // $raw=$this->args['pregman'];
        $data=[];


        // foreach($raw as $key=>$con)
        // {

        //     $data["images"][$con['imageType']][]['url']=$con['src'];

        // }

        $raw = $this->getNewImages($_GET['id']);

        foreach($raw as $key=>$con)
        {

            $data["images"][$con['type']][]['url']=$con['url'];

        }


        $limit=$_GET['reload'] ?? 999;
        $d=Leech::leech($data["images"],"/".$_GET['id'],$limit);


       foreach ($data["images"] as $k=>$value) {
            foreach ($d[$k] as $_key => $_value) {
                $data["images"][$k][$_key]['url']=$_value;
            }

        }
        return $this->respond->json($data,200);
    }
    public function reLoad($data)
    {

        $limit=$_GET['reload'] ?? 10;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://tmdb.up2dl.xyz/leecher/?id='.$_GET['id'].'&limit='.$limit);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data,'','&'));
        curl_setopt($ch, CURLOPT_POST, 1);

        $result = curl_exec($ch);

        curl_close($ch);
        return json_decode($result,true);
    }

    public function allwithCount()
    {
        $count=$_GET['count'];
        $raw=$this->args['pregman'];
        $data=[];
        foreach($raw as $key=>$con)
        {
            $countt=isset($data["images"][$con['imageType']]) ? count($data["images"][$con['imageType']]) : 0;
            if($countt<$count)
            {
                $data["images"][$con['imageType']][]['url']=$con['src'];
            }
        }
        return $this->respond->json($data,200);

    }
    public function normalizeUrl($a)
    {
        foreach($this->args['pregman'] as $item)
        {

            if($a['url'] == $item['id'])
            {
                $a['url']=$item['src'];
                $a['caption']=$item['altText'];
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
        $firstKey="window.IMDbMediaViewerInitialState = {'mediaviewer':";
        $endtKey="};";
        $strpos=strpos($file,$firstKey);
        $file=substr($file,$strpos+strlen($firstKey));
        $strpos=strpos($file,$endtKey);
        $data=substr($file,0,$strpos);

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
        $mediaviewer=$this->imdbUrl.$this->imdbId."/mediaviewer/rm1241571584?ref_=tt_pv_mi_sm";
        $pages=[$mediaviewer];
        $results=$this->request->get($pages);
        $this->args['mediaviewer']=$this->request->getContent($results[0]);
        return $this->args['mediaviewer'];
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
