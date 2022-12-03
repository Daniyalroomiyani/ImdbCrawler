<?php
namespace App\Utility\Types;

trait writersTrait
{

    public function queueforWriters($items)
    {
        $items=(array) $items;
        $inits=[];
        foreach($items as $dir)
        {
            $inits[]=curl_init($this->imdb."/name/".$dir);
        }
        $mh = curl_multi_init();
        foreach ($inits as $ch)
        {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_multi_add_handle($mh, $ch);
        }
            $running = null;
            do {
                curl_multi_exec($mh, $running);
            } while ($running);
            curl_multi_close($mh);

            $contents=[];
            foreach($inits as $direc)
            {
                $contents[]=curl_multi_getcontent($direc);
            }
        return $contents;
    }
    public function writers($raw,$fullpage)
    {
        if($raw['@type']=="Movie")
        {
            $writers=$this->getWriter($fullpage);
            foreach($writers as $key=>$writer)
            {
                $tinit[]=$key;
            }
            $contente=$this->queueforWriters($tinit);
            $contents=$this->getDataom($contente);
            $result=[];
            foreach($contents as $key=>$content)
            {
               $in=$this->getImdbIdFromLong($content['url']);
                $results[]['name']=$content['name'];
            //    $results[$in]['url']=isset($act['url'])? $this->imdb.$act['url']:null;
                $results[$key]['id']=$in;
                $results[$key]['image']=(isset($content['image']))?$content['image']:null;
                $results[$key]['jobTitle']=(is_array($content['jobTitle'])) ? implode('.',$content['jobTitle']) : $content['jobTitle'];
                $results[$key]['birthDate']=(isset($content['birthDate']))?$content['birthDate']:null;
                $results[$key]['birthLocation']=$this->BirthLocation($contente[$key]);
                // @$results[]['character']=$this->args['fillcast'][$in]['char'];
            }
            return $results;
            // exit();
            // $writersUrl=$this->writersUrl($writer);
        }else{
            return null;
        }
    }
}
