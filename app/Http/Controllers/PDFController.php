<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;
use App\Models\Checklist;
use App\Models\User;

class PDFController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function generatePDF($checklist_id, $user_id)
    {
        $user = User::find($user_id);

        $summary = Checklist::with('summaries')->find($checklist_id);
        $resumeAll = '';

        foreach($summary['summaries'] as $summarie){
            $resumeAll .= $summarie['description'];
        }

        $pdf = PDF::loadView('resume', ['resumeAll'=> $resumeAll], ['user'=> $user]);
        return $pdf->stream('resume.pdf');
        // return $pdf->download('resume.pdf');
    }
}
