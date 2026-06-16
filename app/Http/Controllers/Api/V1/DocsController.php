<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class DocsController extends Controller
{
    public function openapi(): Response
    {
        $path = public_path('docs/openapi.yaml');

        if (! is_file($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/yaml',
            'Cache-Control' => 'no-store, must-revalidate',
        ]);
    }

    public function swagger(): Response
    {
        return response()->view('swagger');
    }
}
