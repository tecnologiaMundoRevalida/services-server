<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use OpenAI;
use App\Models\Question;
use App\Services\OpenAIService;
use App\Models\TestProcessingLog;
use App\Models\Test;

class ProcessPdfTestFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 999999; 

    public $fileName;
    public $fileId;
    public $vectorStoreId;
    public $test_id;
    public $amount_questions;
    public $assistantId;
    public $qtd_questions_processed = 0;
    /**
     * Create a new job instance.
     */
    public function __construct($fileName,$test_id,$amount_questions,$fileId)
    {
        $test = Test::find($test_id);
        $this->qtd_questions_processed = isset($test->amount_questions_processed) ? ($test->amount_questions_processed > 0 ? $test->amount_questions_processed + 1 : 1) : 1;
        $this->fileName = $fileName;
        $this->fileId = $fileId;
        $this->test_id = $test_id;
        $this->amount_questions = $amount_questions;
        $this->assistantId = config('services.openai.assistant_id');
        $this->vectorStoreId = config('services.openai.vector_store_id');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $client = OpenAI::client(config('services.openai.api_key'));
            $openAiService = new OpenAIService();
            $this->processPdf($client,$openAiService);
            $this->deleteFile($client);
        }catch(\Exception $e){
            $openAiService->updateTest($this->test_id,"WARNING",null);
        }
    }

    public function processPdf($client,$openAiService){
        $warning = false;
        $openAiService->updateTest($this->test_id,"PROCESSANDO",null);
        for ($this->qtd_questions_processed; $this->qtd_questions_processed <= $this->amount_questions; $this->qtd_questions_processed++) {
            $thread_id = $this->processThread($this->qtd_questions_processed,$client);
            if(isset($thread_id) && $thread_id != null && $thread_id != ""){
                $question_process = $this->retrieveMessage($thread_id,$client,$this->qtd_questions_processed);
                if($question_process != "" && $question_process != null && isset($question_process["questao"])){
                    $this->saveQuestion($question_process,$this->qtd_questions_processed);
                    $openAiService->updateTest($this->test_id,"PROCESSANDO",null,$this->qtd_questions_processed);
                }else{
                    $warning = true;
                }              
            }else{
                $warning = true;
            }
            sleep(10);
        }
        if($warning){
            $openAiService->updateTest($this->test_id,"WARNING",null);
        }else{
            $openAiService->updateTest($this->test_id,"PROCESSADA",null);
        }
        
    }

    public function processThread($numero_q,$client)
    {     
        try {
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'process started']);
        // The message or instruction you want to send to the assistant
        $threadMessage = 'converta a questão '.$numero_q.' do arquivo '.$this->fileName.' em json.';
            // Create a thread
            $threadResponse = $client->threads()->create([
                    'messages' =>
                        [
                            [
                                'role' => 'user',
                                'content' => $threadMessage
                            ],
                        ],
            ]);
            // if($threadResponse =! null && isset($threadResponse->id)){
                // Run Thread
                $stream = $this->runThread($client,$threadResponse,$numero_q);
                // await the completion of the thread
                
                $threadIdRun = $this->awaitThreadCompletion($stream,$numero_q);
                TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'Thread created and runned thread_id:'.$threadIdRun]);
                return $threadIdRun;
            // }else{
            //     return null;
            // }
        } catch (\Exception $e) {
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'process error:'.$e->getMessage()]);
        }
    }

    public function retrieveMessage($threadId,$client,$numero_q){
        
        try{
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'retrieve message started']);
            $response = $client->threads()->messages()->list(
                threadId: $threadId
            );

            $messagesData = $response->data;
            if (!empty($messagesData)) {
                $messagesCount = count($messagesData);
                $assistantResponseMessage = '';
                
                // check if assistant sent more than 1 message
                if ($messagesCount > 1) { 
                    foreach ($messagesData as $message) {
                        // concatenate multiple messages
                        $assistantResponseMessage .= $message->content[0]->text->value . "\n\n"; 
                    }
                    // remove the last new line
                    $assistantResponseMessage = rtrim($assistantResponseMessage); 
                } else {
                    // take the first message
                    $assistantResponseMessage = $messagesData[0]->content[0]->text->value;
                }
                // Extract the JSON string from the assistant response
                preg_match('/\{.*\}/s', $assistantResponseMessage, $matches);
                $jsonString = $matches[0];
                // Decodificar o JSON
                $question = json_decode($jsonString, true);
                TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'retrieve message finished']);
                return $question;
            } else {
                TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'retrieve message is empty']);
            }
        } catch (\Exception $e) {
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'Retrieve Message error:'.$e->getMessage()]);
        }
    }

    public function saveQuestion($question_process,$numero_q){
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'create question started']);
        $question = Question::create([
            'question' => '<p>' . $question_process["questao"] . '</p>',
            'is_discursive' => 0,
            'is_new' => 1,
            'active' => 0,
            'test_id' => $this->test_id,
            'has_image' => $question_process['contem_imagem'] == "S" ? 1 : 0,
        ]);
        $this->saveAlternatives($question_process['alternativas'],$question,$question_process["resposta_correta"]);
        $this->saveMedicineAreaReference($question_process['tag'],$question);
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'create question finished','question_id' => $question->id]);
        return $question;
    }

    public function saveAlternatives($alternatives,$question,$correct){
        $array_alt_correct = [1 => "A",2 => "B",3 => "C",4 => "D",5 => "E"];
        $i = 1;
        foreach($alternatives as $alternative){
            $question->alternatives()->create([
                'question_id' => $question->id,
                'alternative' => '<p>' . $alternative . '</p>',
                'is_correct' => $array_alt_correct[$i] == $correct ? 1 : 0,
                'active' => 0,
            ]);
            $i++;
        }
    }

    public function saveMedicineAreaReference($tag,$question){
        $question->medicineAreaReference()->create([
            'question_id' => $question->id,
            'medicine_area_id' => $tag
        ]);
    }

    public function runThread($client,$threadResponse,$numero_q){   
        try{
            $stream = $client->threads()->runs()->createStreamed(
                threadId: $threadResponse->id,
                    parameters: [
                        'assistant_id' => $this->assistantId,
                    ],
            );
            return $stream;
        }catch(\Exception $e){
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'process error:'.$e->getMessage()]);
        } 
        
    }

    public function deleteFile($client){
        try{
            $client->vectorStores()->files()->delete(
                vectorStoreId: $this->vectorStoreId,
                fileId: $this->fileId,
            );        
        }catch(\Exception $e){
            TestProcessingLog::create(['test_id' => $this->test_id,'log' => 'error delete file:'.$this->fileId."--".$e->getMessage()]);
        }
        
    }

    public function awaitThreadCompletion($stream,$numero_q){
        try{
            foreach($stream as $response){
                switch($response->event){
                    case 'thread.run.completed':
                        return $response->response->threadId;
                        break;
                }
            }   
        }catch(\Exception $e){
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $numero_q,'log' => 'process error:'.$e->getMessage()]);
        }
    }

}
