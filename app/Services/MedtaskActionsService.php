<?php

namespace App\Services;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Support\Facades\Auth;
use App\Repositories\UserTestsRepository;

class MedtaskActionsService
{

    private $userTestsRepository;
    private $userTestQuestionsRepository;

    public function __construct(
        UserTestsRepository $userTestsRepository,
    ) {
        $this->userTestsRepository = $userTestsRepository;
    }

    public function storeUserTest($data){
        $user = Auth::user();
        $data['user_id'] = $user->id;
        return $this->userTestsRepository->store($data);
    }


}