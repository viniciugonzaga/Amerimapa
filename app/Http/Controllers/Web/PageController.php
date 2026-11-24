<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function show(string $slug)
    {
        return view('pages.'.$slug);
    }

    public function apiDocs()
    {
        return view('pages.api');
    }
}
