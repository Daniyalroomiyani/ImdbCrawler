<?php
namespace App\Utility;

class Response
{

    public function error($message,$code=422,$header=['Content-Type: application/json'])
    {
        http_response_code($code);
        header($header[0]);
        $message=[
            'Response'=>false,
            'status_code'=>$code,
            'Error'=>$message,
        ];
        echo  json_encode($message);
        exit();
    }
    public function json($data=[null],$code=200,$header=['Content-Type: application/json'])
    {
        http_response_code($code);
        header($header[0]);
        $message=[
            'Response'=>TRUE,
            'status_code'=>$code,
            'body'=>$data,
        ];
        return  json_encode($message);
    }
}
