<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class DocsController extends BaseController
{
    public function index()
    {
        return view('swagger_ui');
    }
}
