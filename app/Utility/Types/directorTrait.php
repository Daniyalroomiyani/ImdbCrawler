<?php

namespace App\Utility\Types;

use Movie\Utils\Utils;

trait directorTrait

{

    private $name = "Director";

    public function directorForArray($raw, $all)
    {
        // $directorPage=$this->getDirectorDet($raw);
        // $directorPageJson=$this->ontoArray($this->getdatom($directorPage));

        // print_r($directorPage);
        // exit();
        $results['name'] = $raw['name'];
        $results['id'] = substr($raw['url'], 7, 9);
        // $results[0]['for']=$raw['name'];
        // ToDO uploaded to Iran host
        $results['image'] = isset($raw['image']) ?  Utils::getLinkFromIranHost($raw['image'],$this->name): null;
        $results['jobTitle'] = isset($raw['jobTitle']) ? implode('.', (array)$raw['jobTitle']) : null;
        $results['birthDate'] = (isset($raw['birthDate'])) ? $raw['birthDate'] : null;
        $results['birthLocation'] = $this->BirthLocation($all);
        // @$results[0]['character']=$this->args['fillcast'][$in]['char'];
        return $results;
    }

    public function directorForMovie($raw)
    {
        if (!isset($raw['director'])) {
            $results['name'] = null;
            $results['id'] = null;
            // $results[0]['for']=$raw['name'];
            $results['image'] = null;
            $results['jobTitle'] = null;
            $results['birthDate'] = null;
            $results['birthLocation'] = null;
            return $results;
        }
        if (!isset($raw['director']['name'])) {
            // $raw['director']=$raw['director'][0];
            $results = [];
            foreach ($raw['director'] as $fg) {
                $fg['director'] = $fg;
                $results[] = $this->getSingledir($fg);
            }
            return $results;
        }
        // exit("obor");

        return $this->getSingledir($raw);
    }

    public function getSingledir($raw)
    {
        $directorPage = $this->getDirectorDet($raw);
        $directorPageJson = $this->ontoArray($this->getdatom($directorPage));


        $results['name'] = $raw['director']['name'];
        $results['id'] = substr($raw['url'], 7, 9);
        // $results[0]['for']=$raw['name'];
        // ToDO uploaded to Iran host

        $results['image'] = isset($directorPageJson['image']) ?  Utils::getLinkFromIranHost($directorPageJson['image'],$this->name) : null;
        $results['jobTitle'] = implode('.', (array)$directorPageJson['jobTitle']);
        $results['birthDate'] = (isset($directorPageJson['birthDate'])) ? $directorPageJson['birthDate'] : null;
        $results['birthLocation'] = $this->BirthLocation($directorPage);
        // @$results[0]['character']=$this->args['fillcast'][$in]['char'];
        return $results;

    }

    public function getDirectorDet($raw)
    {
        if (!isset($raw['director']['url'])) {
            return null;
        }
        $url = $this->imdb . $raw['director']['url'];
        $rawData = $this->request->get($url);
        $rawData = end($rawData);
        $contents = $this->request->getContent($rawData);
        return $contents;
    }

    public function getDirectorDetArray($dirs)
    {
        $inits = [];
        foreach ($dirs as $dir) {
            $inits[] = curl_init($this->imdb . "/name/" . $dir);
        }
        $mh = curl_multi_init();
        foreach ($inits as $ch) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_multi_add_handle($mh, $ch);
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running);
        curl_multi_close($mh);

        $contents = [];
        foreach ($inits as $direc) {
            $contents[] = curl_multi_getcontent($direc);
        }
        return $contents;

    }

    public function director($raww)
    {
        if ((strpos($this->args['fullPage'], "Creators:") !== false || strpos($this->args['fullPage'], "Creator:") !== false)
            && $raww['@type'] != "Movie") {
            $result = [];
            $raw = $this->args['fullPage'];
            $patern = '/<div class="credit_summary_item">.*?<h4.class="inline">Creator.*?:<\/h4>.*?<a.*?<\/a>.*?<\/div>/s';
            $cats = $this->getData($patern, $raw)[0];
            $patern = '/<a href="\/name\/(.*?)\/.*?".>(.*?)<\/a>/s';
            $cats = $this->getDataAll($patern, $cats);
            $content = $this->getDirectorDetArray($cats[1]);
            $contents = $this->getDataom($content);
            foreach ($contents as $key => $item) {
                $result[] = $this->directorForArray($item, $content[$key]);
            }
            return $result;
            // print_r($cats);
            // exit();


        }
        // getDirectorDet($dirs);
//        return array($this->directorForMovie($raww));

        $rawe = array($this->directorForMovie($raww));
        if(!empty($rawe[0][0]) && is_array($rawe[0][0])){
            $rawe = $rawe[0];
        }
        return $rawe;


    }
}
