<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Objekte;
use App\Models\Haeuser;
use App\Models\Einheiten;
use App\Models\Partner;
use App\Models\Personen;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Response;

class SearchBarController extends Controller
{
    public function search() {
        $response = ['objekte' => [], 'haeuser' => [], 'einheiten' => [], 'partner' => [], 'personen' => []];
        if (!request()->has('q')) {
            return Response::json($response);
        }
        $tokens = explode(' ',request()->input('q'));

        $response['objekte'] = Objekte::query();
        $response['haeuser'] = Haeuser::query();
        $response['einheiten'] = Einheiten::query();
        $response['partner'] = Partner::query();
        $response['personen'] = Personen::query();

        foreach ($tokens as $token) {
            $response['objekte'] = $response['objekte']->search($token);
            $response['haeuser'] = $response['haeuser']->search($token);
            $response['einheiten'] = $response['einheiten']->search($token);
            $response['partner'] = $response['partner']->search($token);
            $response['personen'] = $response['personen']->search($token);
        }

        $response['objekte'] = $response['objekte']->get();
        $response['haeuser'] = $response['haeuser']->get();
        $response['einheiten'] = $response['einheiten']->get();
        $response['partner'] = $response['partner']->get();
        $response['personen'] = $response['personen']->get();

        return Response::json($response);
    }
}