<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use App\Helpers\SikluUptime;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'history_log' => 'required',
            'end_date' => 'datetime',
        ]);
        $siklu = new SikluUptime($request->get('history_log'), new Carbon($request->get('end_date')));
        $siklu = $siklu->parse();
        $modulations = $siklu->getModulations();
        $changes = $siklu->getModulationChanges();

        return view('result', compact('siklu', 'modulations', 'changes'));
    }
}
