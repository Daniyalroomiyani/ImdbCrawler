<?php
namespace App\Utility;

class Download
{
    private $mh;
    private $filesPath="";
    private $files=[];
    private $ch=[];
    public $response=[];
    public $folderPath="";
    public function __construct($id)
    {

        $this->mh=curl_multi_init();
        $this->filesPath="/files".$id;
        $this->folderPath=__DIR__."/../../leecher/".$this->filesPath;
        $this->mkdir($this->folderPath);
    }
    public function run()
    {
        $running = null;
        do {
            curl_multi_exec($this->mh, $running);
            if (curl_multi_select($this->mh) == -1) {
                usleep(100);
            }
        } while ($running);
        $this->close();
        return $this;
    }
    public function close()
    {
        foreach ($this->ch as $key => $ch) {
            curl_multi_remove_handle($this->mh, $ch);
            curl_close($this->ch[$key]);
            @fclose($this->files[$key]);
        }
        curl_multi_close($this->mh);
    }
    public function getExtFromString(string $string=null)
    {
        $pices=pathinfo($string);
        return $pices['extension'] ?? "jpg";
    }
    public function mkdir($path)
    {
        if(@mkdir($path) or file_exists($path)) return true;
        return ($this->mkdir(dirname($path)) and mkdir($path));
    }
    public function add(array $groups,$limit=10,$nameGenerator=null)
    {


        foreach ($groups as $g_key => $nodes) {
            $this->response[$g_key]=[];
            foreach ($nodes as $n_key => $url) {
                if($n_key >= $limit)
                {
                    break;
                }
                $url=$url['url'];
                $fileName=$nameGenerator==null ?
                                    hash("sha256",$url).".".$this->getExtFromString($url):
                                            $nameGenerator($url);
                $fileName=$g_key.DIRECTORY_SEPARATOR.$fileName;

                if(filesize($this->folderPath."/".$fileName) < 20)
                {

                    unlink($this->folderPath."/".$fileName);
                }
                if(file_exists($this->folderPath."/".$fileName))
                {

                    $this->response[$g_key][$n_key]='https://tmdb.up2dl.xyz/leecher'.$this->filesPath."/".$fileName;
                     //change route..!
                }else{
                    if (!filter_var($url, FILTER_VALIDATE_URL)) {

                        $this->response[$g_key][$n_key]=null;
                    }else
                    {
                        $this->mkdir($this->folderPath."/$g_key");
                        $fp=fopen ($this->folderPath."/".$fileName, 'w+');
                        $ch = curl_init(str_replace(" ","%20",$url));
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_multi_add_handle($this->mh,$ch);
                        $ol = curl_exec($ch);
                        echo "resukt:"+$ol;
                        exit();

                        $this->response[$g_key][$n_key]='https://tmdb.up2dl.xyz/leecher'.$this->filesPath."/".$fileName;
                        $this->ch[]=$ch;
                        $this->files[]=$fp;
                    }
                }

            }
        }
        return $this;
    }
}
