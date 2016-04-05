<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class MapController extends Controller
{
    public function __construct() {
    }
    public function maps() {
        return \Illuminate\Support\Facades\View::make('maps');
    }
}
