<?php
namespace Movie\Types;

use App\Utility\Types\directorTrait;
use App\Utility\Types\mainType;
use App\Utility\Types\writersTrait;
use Movie\Leech;

class actors extends MainType
{
    use directorTrait;
    use writersTrait;

    public $actorsCount=30;
    public $imdb="https://www.imdb.com";
    public $fullPageCreadit;
    public $page;
    public $args;
    public $of;
    public $itemsPerPage=30;

    public function get()
    {
        if(isset($_GET["of"]) && is_numeric($_GET["of"]))
        {
            $this->of=$_GET["of"];
        }
        $this->args['limit']=isset($_GET['count'])?$_GET['count']:null;
        if(isset($_GET['all']))
        {
            $this->args['limit']=99999999999999999;
        }
        $data=$this->initMainPageLoad();
        $this->args['fillcast']=$this->startgetCastAnd();
        //echo $this->convert(memory_get_usage(true)); // 123 kb
        return $this->respond($data);
    }
    public function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }


    public function handleRequestType()
    {
        if(isset($_GET['page']) && is_numeric($_GET['page']))
        {
            $this->page=(int)$_GET['page'];
        }else{
            $this->page=1;
        }
            $this->args['paginate']=$this->calcAvalablePages();
        if($this->page > $this->args['paginate']['allpages'])
        {
            return $this->respond->error("page not found",404);
        }
        return $this->args['paginate'];
    }
    public function calcAvalablePages()
    {
        $result=[];
        $count=$this->args['actorsCount'];
        // echo $count;
        $rsult['allpages']=ceil($count/$this->itemsPerPage);
        $rsult['itemsPerPage']=$this->itemsPerPage;
        // print_r($rsult['allpages']);
        $rsult['curentPage']=$this->page;
        $rsult['prePage']= $this->page >1;
        $rsult['nextPage']= $this->page < $rsult['allpages'];
        return $rsult;
    }
    public function initMainPageLoad()
    {
        $fullpageCreadit=$this->imdbUrl.$this->imdbId."/fullcredits?ref_=tt_cl_sm#cast";
        $fullPage=$this->imdbUrl.$this->imdbId."?ref_=tt_cl_sm#cast";
        $pages=[$fullpageCreadit,$fullPage];
        $results=$this->request->get($pages);
        $this->fullPageCreadit=$this->request->getContent($results[0]);
        $this->args['fullPage']=$this->request->getContent($results[1]);
        return $this->fullPageCreadit;

    }

    public function respond($raw)
    {
        // print_r($this->getActors($raw)[1]);
        // exit();
        $actorsPages=$this->setInits($this->getActors($raw)[1]);
        $this->handleRequestType();

        $results=$this->request->get($actorsPages);
        // $allActorss=$this->getActorsContent($results);
        array_shift($results);
        $allActorss=$this->getActorsContent($results);
        $allActors=$this->getDataom($allActorss);
        if($this->args['limit']==null)
        {
            $data['paginate']=$this->args['paginate'];
        }
        // $data['CastAndCharacter']=$this->casss();
        $tmp=$this->ontoArray($this->getdatom($this->args['fullPage']));
        $data['writer']=$this->writers($tmp,$this->args['fullPage']);
        $data['director']=$this->director($tmp);
        // print_r($this->getDataom($this->args['fullPage']));
        // exit();
        $data['actors']=$this->refactorActorss($allActors,$allActorss);

        return $this->respond->json($data,200);

    }
    public function casss()
    {
        $r="";

        foreach ($this->args['fillcast'] as $item)
        {
            $r.=$item['actor'] . " as " . $item['char'] ." , ";
        }
        return $r;
    }
 public function reLoad($data)
    {

        $limit=$_GET['reload'] ?? 9999999;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://tmdb.up2dl.xyz/leecher/?id='.'actors'.'&limit='.$limit);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data,'','&'));
        curl_setopt($ch, CURLOPT_POST, 1);

        $result = curl_exec($ch);

        curl_close($ch);
        return json_decode($result,true);
    }
    public function refactorActorss($all,$raw)
    {
        $results=[];
        $index=0;
        // $this->args['datanindex']=0;
        $image=[];
        foreach($all as $act)
        {
           if($index!=0)
           {
               $array = [];
               $in=$this->getImdbIdFromLong($act['url']);
               $array['name']=$act['name'];
               //    $results[$in]['url']=isset($act['url'])? $this->imdb.$act['url']:null;
               $array['id']=$in;
               // ToDO if you whant to disable upload in ir server just edit this line
               $array['image']=$act['image'];
               $image[]['url']=$act['image'] ?? "https://m.media-amazon.com/images/G/01/imdb/images/nopicture/32x44/name-2138558783._CB470041625_.png";
               $array['jobTitle']=(isset($act['jobTitle'])) ? implode('.',(array)$act['jobTitle']) : $act['jobTitle'];
               $array['birthDate']=(isset($act['birthDate']))?$act['birthDate']:null;
               $array['birthLocation']=$this->BirthLocation($raw[$index]);
            // print_r($this->args['fillcast']);
            // exit();
               $array['character']=$this->args['fillcast'][$in]['char'];
               $results[] = $array;
           }
           $index+=1;
        }
        $data=[
            'images'=>$image
        ];

        $r=$this->reLoad($data);

        foreach($results as $key=>$item)
        {
            if(isset($r['images'][$key]))
            {
                // $results[$key]['old_image']=$results[$key]['image'];
                $results[$key]['image']=$r['images'][$key];
            }
        }

        return $results;
    }
    public function startgetCastAnd()
    {
        $fullpage=$this->fullPageCreadit;
        $a='<tr><td colspan="4" class="castlist_label"></td></tr>';
        $str=substr($fullpage,strpos($fullpage,$a));

        $fullpage=substr($str,0,strpos($str,"</table>")+strlen($a));

        $patern='/<td>.*?<a href="\/name\/(.*?)\/.*?>(.*?)<\/a>.*?\/td>.*?<td class="character">(.*?)<+\/+/s';
        $data=$this->getDataAll($patern,$fullpage);
        $result=[];
        foreach($data[1] as $key=>$item)
        {
            $result[$item]['actor']=$data[2][$key];
            $result[$item]['char']=$this->html($data[3][$key]);
        }


        return $result;
    }
    public function html($in)
    {
        $paternforEdisod='/(.*?)<a/s';
        $paternforchar='/ >(.*)/s';
        $result=$in;
        if(strpos($in,'characters')!==false)
        {
            $result=$this->getData($paternforchar,$in)[1];
        }
        if(strpos($in,'episodes')!==false)
        {
            $result=$this->getData($paternforEdisod,$in)[1];

        }
        $result=str_replace("&nbsp;","",$result);
        return $result;
    }


    public function getActorsContent($results)
    {
        $allActors=[];
        foreach($results as $res)
        {
            $allActors[]=$this->request->getContent($res);
        }
    return $allActors;
    }
    public function initByLimit($items,$limit)
    {
        $urls=[];
        $index=0;
        if($this->of==null)
        {
            foreach($items as $key=>$item)
            {
                if($index < $limit)
                {
                    $urls[]=$this->imdb."/name".$item;
                }
                $index+=1;
            }
        }else{
            $innn=0;
            foreach($items as $key=>$item)
            {
                if($innn > $this->of)
                {
                    if($index < $limit)
                    {
                        $urls[]=$this->imdb."/name".$item;
                    }
                    $index+=1;
                }
                $innn+=1;
            }
        }
        return array_values($urls);
    }
    public function setInits($items)
    {
        $limit=$this->args['limit'];
        if($limit!=null){
            $this->args['actorsCount']=900000;
            return $this->initByLimit($items,$limit);
        }
        $urls=[];
        $index=0;
        $this->args['actorsCount']=count($items);
        $thispage=(isset($_GET['page'])) ? $_GET['page'] :1;
        $p=$thispage*$this->itemsPerPage;
        $min=$thispage*$this->itemsPerPage-$this->itemsPerPage;
        foreach($items as $key=>$item)
        {
            if($key >= $min && $key < $p )
            {
                // if(strpos($item,"nm")!==false)
                // {
                    $urls[]=$this->imdb."/name".$item;
                // }
            }
            $index+=1;
        }
        return array_values($urls);
    }

}
