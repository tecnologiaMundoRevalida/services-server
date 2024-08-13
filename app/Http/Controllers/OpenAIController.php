<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Http\Requests\AI\ProcessPdfRequest;
use App\Jobs\ProcessPdfTestFileJob;
use App\Models\Test;

class OpenAIController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }


    public function processPdf(ProcessPdfRequest $request)
    {
        try{
            $file = $request->file('file');
            $filePath= $file->getPathname() . '.' . 'pdf';
            rename($file->getPathname(), $filePath);
            $response = $this->openAIService->uploadPdf($filePath);
            if($response->filename){
                $parts = [25,50,75,100];
                foreach($parts as $part){
                    $this->openAIService->updateTest($request->input('test_id'),'AGUARDANDO',$part);
                    dispatch(new ProcessPdfTestFileJob($response->filename, $request->input('test_id'), $part))->delay(now()->addMinute(5));
                }
            }
        }catch(\Exception $e){
            dd($e->getMessage());
            return response()->json([
                'message' => "Erro ao tentar processar o arquivo.",
            ], 500);
        }
        
    }

    public function processThread(){
        dd($this->openAIService->processThread(47));
    }

}
