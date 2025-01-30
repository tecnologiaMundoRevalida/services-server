<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use App\Models\QuestionReserved;
use Illuminate\Support\Facades\DB;

class DeactivateReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

 
    public function __construct()
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $hojeData = now()->toDateString();
        $hojeDataHora = now()->toDateTimeString();

        DB::table('questions')
        ->whereRaw('? >= DATE_ADD(questions.reserved_at, INTERVAL 15 DAY)',[$hojeData])
        ->whereNull('questions.comment_revised_by')
        ->whereNull('questions.comment_revised_by_at')
        ->where('questions.comment_revised',0)
        ->update(['questions.reserved_at' => null, 'questions.reserved_by' => null]);
        
        DB::table('question_reserved')
        ->join('questions','questions.id','question_reserved.question_id')
        ->whereRaw('? >= DATE_ADD(question_reserved.reserved_at, INTERVAL 15 DAY)',[$hojeData])
        ->whereNull('questions.comment_revised_by')
        ->whereNull('questions.comment_revised_by_at')
        ->where('questions.comment_revised',0)
        ->whereNull('question_reserved.automatic_return')
        ->whereNull('question_reserved.return_at')
        ->update(['question_reserved.automatic_return' => 1, 'question_reserved.return_at' => $hojeDataHora]);
    }
}
