<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

use App\Provider;
use App\Program;


class ProviderController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    
    public function store(Request $request) {
        //Validation
        
        $provider = new Provider;
        $provider->proid = $request->proid;
        $provider->name = $request->name;
        $provider->uid = $request->uid;
        //$provider->address = $request->address;
        //$provider->city = $request->city;
        //$provider->plz = $request->plz;
        $provider->phone = $request->phone;
        $provider->save();
        
    }
    
    public function show($gid) {
        $provider = Provider::findOrFail($gid);
        $Program = new Program;
        $programs = $Program->getProgramsByProid($proid);
        return view('provider.edit', array('provider' => $provider,
                                          'programs' => $programs));
    }
    
    public function edit(Request $request, $proid) {
        $request->request->add(['proid' => $gid]);
        $provider = $this->update($request);
        return redirect()->action('ProviderController@show', $provider->proid);
    }
    
    public function update(Request $request) {
        $provider = Provider::findOrFail($request->proid);
        $provider->name = $request->name;
        //$provider->address = $request->address;
        //$provider->city = $request->city;
        //$provider->plz = $request->plz;
        $provider->phone = $request->phone;
        $provider->save();
        return $provider;
    }
}
