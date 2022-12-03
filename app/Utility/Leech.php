<?php
namespace App\Utility;

class Leech
{
    public static function leech($data,$id='id',$limit=10)
    {
        if($id[0]!=='/')
        {
            $id='/'.$id;
        }
        $o=new Download($id);
        $o->add($data,$limit)->run();

        return $o->response;

    }
}
