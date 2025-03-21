<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\OpenAIService;
use App\Http\Requests\AI\ProcessPdfRequest;
use App\Http\Requests\AI\GenerateTagsRequest;
use App\Http\Requests\CorrectAlternativesRequest;
use App\Jobs\ProcessPdfTestFileJob;
use App\Jobs\GenerateTagsForQuestionsJob;
use App\Jobs\GenerateCommentsForQuestionsJob;

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
            if ($request->hasFile('file') && $request->file('file')->isValid()) {
                $file = $request->file('file');
                $fileName = uniqid() . '-' . time() . '.pdf';
                $filePath = $file->storeAs('public/pdfs_provas', $fileName);
                if($filePath){
                    $this->openAIService->updateTest($request->input('test_id'),'AGUARDANDO',$request->input('amount_questions'),null,$fileName);
                    dispatch((new ProcessPdfTestFileJob($fileName,$request->input('test_id'), $request->input('amount_questions')))->onQueue('low'));
                }
                return response()->json([
                    'message' => "Arquivo enviado com sucesso.",
                ], 200);
            }else{
                return response()->json([
                    'message' => "Arquivo inválido.",
                ], 400);
            }
        }catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
        
    }

    public function generateTags(GenerateTagsRequest $request)
    {
        $this->openAIService->updateTest($request->test_id,null,null,null,null,"WAITING");
        dispatch((new GenerateTagsForQuestionsJob($request->test_id,$request->completely))->onQueue('low'));
        return response()->json([
            'message' => "Geração de Tags Lançada na fila ...",
        ], 200);
    }

    public function generateComments(Request $request)
    {

        $this->openAIService->updateTest($request->test_id,null,null,null,null,null,"WAITING");
        dispatch((new GenerateCommentsForQuestionsJob($request->test_id,$request->completely))->onQueue('low'));
        return response()->json([
            'message' => "Geração de Comentários Lançada na fila ...",
        ], 200);

    }

    public function correctAlternatives(CorrectAlternativesRequest $request)
    {
        // try {
            $data = $request->validated();
            $result = $this->openAIService->correctAlternatives($data['discursive_answers']);

            return response()->json(['correction' => $result]);
        // }
        // catch (Exception $e) {
        //     return response()->json([
        //         'error' => 'Erro inesperado ao processar a correção.',
        //         'details' => $e->getMessage()
        //     ], 500);
        // }
    }

}
