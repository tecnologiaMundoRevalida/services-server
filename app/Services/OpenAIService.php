<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use App\Models\Question;
use App\Models\Test;

use OpenAI;

class OpenAIService
{
    // protected $client;
    // protected $assistantModel;
    // protected $vectorStoreId;

    public function __construct()
    {
        // $this->assistantModel = config('services.openai.assistant_model');
        // $this->vectorStoreId = config('services.openai.vector_store_id');
        // $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function updateTest($test_id,$status = null,$amount_questions = null,$amount_questions_processed = null,$file_path = null,$tag_generation_status = null,$comment_generation_status = null){
        $test = Test::find($test_id);

        if($status != null){
            $test->status = $status;
        }
        
        if($amount_questions != null){
            $test->amount_questions = $amount_questions;
        }
        if($amount_questions_processed != null){
            $test->amount_questions_processed = $amount_questions_processed;
        }
        if($file_path != null){
            $test->file_path = $file_path;
        }

        if($tag_generation_status != null){
            $test->tag_generation_status = $tag_generation_status;
        }

        if($comment_generation_status != null){
            $test->comment_generation_status = $comment_generation_status;
        }
        
        $test->save();
    }

    public function uploadPdf($fileName,$client,$vectorStoreId){

        // Ajusta o caminho correto do arquivo dentro de 'storage/app/public'
        $filePath = storage_path('app/public/pdfs_provas/' . $fileName);

        // if (!Storage::disk('public')->exists('pdfs_provas/' . $fileName)) {
        //     throw new \Exception('Arquivo não encontrado no caminho especificado: ' . $filePath);
        // }

        $fileUploadResponse = $client->files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($filePath, 'r'), 
        ]);
        $client->vectorStores()->files()->create(
            vectorStoreId: $vectorStoreId,
            parameters: [
                'file_id' => $fileUploadResponse->id,
            ]
        );
        return $fileUploadResponse;
    }


}