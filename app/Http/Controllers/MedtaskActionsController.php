<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MedtaskActionsService;
use App\Http\Requests\AI\ProcessPdfRequest;
use App\Jobs\ProcessPdfTestFileJob;
use App\Models\Test;
use App\Http\Requests\UserTestRequest;

class MedtaskActionsController extends Controller
{
    protected $medtaskActionsService;

    public function __construct(MedtaskActionsService $medtaskActionsService)
    {
        $this->medtaskActionsService = $medtaskActionsService;
    }

    public function storeUserTest(UserTestRequest $request)
    {
        $this->medtaskActionsService->storeUserTest($request->all());
    }

}
