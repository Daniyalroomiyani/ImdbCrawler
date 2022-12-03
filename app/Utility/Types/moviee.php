<?php 
namespace Movie\Types;

class moviee extends mainType
{
    public $imdb="https://www.imdb.com";
    public $fullPageCreadit;
    public $fullPageraw;
    public $args;
    public function get()
    {
        $data=$this->initMainPageLoad();
        // echo json_encode( );
        // exit();
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
    public function getMetaScoreAvg()
    {
        $raw=$this->args['fullPage'];
        $patern='/<div class="metacriticScore score_favorable titleReviewBarSubItem">.*?<span>(.*?)<\/span>.*?<\/div>/s';
        if(strpos($raw,'<div class="metacriticScore score_favorable')!==false)
        {
            $data=$this->getData($patern,$raw);
            return $data[1];
        }
        return null;
    }
  
    public function responde($raw,$fullpage)
    {
        if($raw['@type']!="TVSeries")
        {
            $writer=$this->getWriter($fullpage);
            $writersUrl=$this->writersUrl($writer);
        }else{
            $writersUrl=null;
            $writer=null;
        }
        $actors=$this->prepareActors();
        $data=[
            "name"          =>$raw['name'],
            "imdbId"        =>substr($raw['url'],7,9),
            "RateA"         =>$raw['contentRating'],
            "Rate"          =>$this->getRate($raw),
            "MetaCraitic"   =>$this->getMetaScoreAvg(),
            // "Metascore"     =>$this->reviews(),
        ];
        return $this->respond->json($data,200);
    }

}
 