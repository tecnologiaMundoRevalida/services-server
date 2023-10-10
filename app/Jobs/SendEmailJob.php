<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\CreatedUser;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $user;
    public $password;
    public $type;

    public function __construct(User $user, $password,$type)
    {
        $this->user = $user;
        $this->password = $password;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $email = new CreatedUser($this->user,$this->password,$this->type);
        Mail::to($this->user)->send($email);
    }
}
