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
            $filePath= $file->getPathname() . '-' . rand() . '.' . 'pdf';
            rename($file->getPathname(), $filePath);
            $response = $this->openAIService->uploadPdf($filePath);
            if($response->filename){
                $this->openAIService->updateTest($request->input('test_id'),'AGUARDANDO',$request->input('amount_questions'));
                dispatch(new ProcessPdfTestFileJob($response->filename, $request->input('test_id'), $request->input('amount_questions'),$response->id));
                return response()->json([
                    'message' => "Arquivo enviado com sucesso.",
                ], 200);
            }else{
                throw new \Exception("Erro ao tentar processar o arquivo.");
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => "Erro ao tentar processar o arquivo.",
            ], 500);
        }
        
    }

    public function processThread(){
        dd($this->openAIService->processThread(1));
    }

}
