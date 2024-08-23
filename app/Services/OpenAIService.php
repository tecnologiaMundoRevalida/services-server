<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use App\Models\Question;
use App\Models\Test;

use OpenAI;

class OpenAIService
{
    protected $client;
    protected $assistantModel;
    protected $vectorStoreId;

    public function __construct()
    {
        $this->assistantModel = config('services.openai.assistant_model');
        $this->vectorStoreId = config('services.openai.vector_store_id');
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function updateTest($test_id,$status,$amount_questions = null,$amount_questions_processed = null){
        $test = Test::find($test_id);
        $test->status = $status;
        if($amount_questions != null){
            $test->amount_questions = $amount_questions;
        }
        if($amount_questions_processed != null){
            $test->amount_questions_processed = $amount_questions_processed;
        }
        $test->save();
    }

    public function uploadPdf($fileName){
        $fileUploadResponse = $this->client->files()->upload([
            'purpose' => 'assistants',
            'file' => fopen($fileName, 'r'), 
        ]);
        $this->client->vectorStores()->files()->create(
            vectorStoreId: $this->vectorStoreId,
            parameters: [
                'file_id' => $fileUploadResponse->id,
            ]
        );
        return $fileUploadResponse;
    }


}