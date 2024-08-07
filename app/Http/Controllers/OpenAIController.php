<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;

class OpenAIController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getResponse(Request $request)
    {
        // $prompt = $request->input('prompt');
        // $thread = $request->input('thread');

        $prompt = "Aqui está o id do arquivo que acabei de te enviar: file-xBgUYEVjZBxENFVwNtRa6zz8. Por favor leia e me envie a questão 5 em formato json.";
        
        $response = $this->openAIService->getResponse($prompt);

        return response()->json([
            'response' => $response,
        ]);
    }

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        $filePath = $file->getPathname();
        $fileId = $this->openAIService->uploadFile('/Users/eldercarmo/Documents/services-server/public/enare.pdf');

        return response()->json([
            'file_id' => $fileId,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $message = $request->input('message');
        $threadId = $request->input('thread_id');
        $response = $this->openAIService->sendMessage($message, $threadId);

        return response()->json([
            'response' => $response,
        ]);
    }

    public function processCsv(Request $request)
    {
        $file = $request->file('file');
        // $filePath = $file->getPathname();
        $content = utf8_encode(file_get_contents('/Users/eldercarmo/Documents/services-server/public/enare.pdf'));
        $prompt = "Leia o pdf no endereço: https://mundo-revalida-checklist-images.s3.amazonaws.com/summary_pdf/AMP+2016+-+Objetiva.pdf, Converta as questões em linhas json, converta em json as questões 5,6,7,8 as propriedades do json são enunciado, alternativas e resposta_correta.";
        $response = $this->openAIService->getResponse($prompt);

        return response()->json([
            'response' => $response,
        ]);
    }

    public function processPdf(){
        $response = $this->openAIService->processPdf();
        return $response;
    }

    public function retrieveMessage(Request $request){
        $threadId = $request->input('thread_id');
        $response = $this->openAIService->retrieveMessage($threadId);
        return $response;
    }
}
