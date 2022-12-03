<?php
namespace App\Utility\Requests;

class Request
{
    public $link;
    public $inits=[];
    public $mh;
    public function __construct()
    {
    }

    public function get($links)
    {
        $this->links=$links;
        $this->initialize()->run();
        return $this->inits;
    }
    public function getContent($item)
    {
        $data=curl_multi_getcontent($item);
        // curl_multi_remove_handle ( $this->mh , $item );
        return trim(trim($data,'\n'));
    }
    public function initialize()
    {
        $urls=(array) $this->links;
        foreach ($urls as $urll)
        {
            //  $this->inits[]=curl_init("https://www.imdb.com/title/tt8632862/?ref_=ttls_li_tt");
             $this->inits[]=curl_init($urll);
        }
        // print_r($this->inits);
        // add to multi handler
        $this->mh = curl_multi_init();
        foreach ($this->inits as $ch)
        {
            //$f=fopen(__DIR__."/tmp/".md5(rand().time()),"wr");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($ch, CURLOPT_FILE, $f);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
            curl_multi_add_handle($this->mh, $ch);
        }
        return $this;
    }
    public function run()
    {
        $running = null;
        do {
            curl_multi_exec($this->mh, $running);
        } while ($running);
        // $data=curl_multi_getcontent($this->inits[0]);
        // print_r($data);
        curl_multi_close($this->mh);
        return $this;
    }
}
