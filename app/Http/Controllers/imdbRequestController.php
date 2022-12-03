<?php

namespace App\Http\Controllers;

use App\Utility\Types\Series;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Utility\Types\actors;
use App\Utility\Types\alltypes;
use App\Utility\Types\Box;
use App\Utility\Types\gallery;
use App\Utility\Types\gtype;
use App\Utility\Types\moviee;
use App\Utility\Types\title;
use App\Utility\Types\Top;
use App\Utility\Types\Toptv;
use function Symfony\Component\Translation\t;

class imdbRequestController extends Controller
{
    public function usage(Request $request)
    {
        return view('usage');
    }

    public function Fetch(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
            'id' => 'required ',
        ]);

        $imdbid= $request->input('id');
        if (substr($imdbid , 0,2) != 'tt' || !is_numeric(substr($imdbid , 2)))
        {
            throw ValidationException::withMessages(['id' => 'The ID is not correct']);
        }


        $type=strtolower($request->input('type'));
        $map=[
            "title"=>title::class,
            "gallery"=>gallery::class,
            "actors"=>actors::class,
            "gtype"=>gtype::class,
            "movie"=>moviee::class,
            "alltypes"=>alltypes::class,
            "top"=>Top::class,
            "toptv"=>Toptv::class,
            "box"=>Box::class,
            "series"=>Series::class
        ];
        $imdbOBj =  new $map[$type]($imdbid);
        $result = $imdbOBj->get($imdbid);
//        echo ($result);
        return view('showData' , compact('result' , 'imdbid'));


    }

}
