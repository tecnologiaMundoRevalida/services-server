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
use Illuminate\Support\Facades\Storage;
use App\Models\AssistantAi;
use App\Models\TestProcessingLog;
use App\Models\Test;
use App\Models\MedicineAreaReference;
use App\Models\SpecialtyReference;
use App\Models\ThemeReference;

class GenerateTagsForQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 999999; 

    public $assistant;
    public $test_id;
    /**
     * Create a new job instance.
     */
    public function __construct($test_id)
    {
        $this->test_id = $test_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $test = Test::with(['questions','questions.alternatives'])->find($this->test_id);
            $this->assistant = AssistantAi::getAvailableAssistantGenerateTags();
            $client = OpenAI::client(config('services.openai.api_key'));
            $this->generateTags($client,$test);
            
        }catch(\Exception $e){
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => 0,'log' => $e->getMessage()]);
        }
    }

    public function generateTags($client,$test){
        
        foreach($test->questions as $key => $question){
            $thread_id = $this->processThread($question,$client,$key);
            if(isset($thread_id) && $thread_id != null && $thread_id != ""){
                $tag_process = $this->retrieveMessage($thread_id,$client,$key);
                if($tag_process != "" && $tag_process != null && count($tag_process) > 0){
                    $this->saveTags($tag_process,$question->id,$key);
                    $this->updateTest($test,$key);
                }            
            }
            sleep(5);
        }
        
    }

    public function updateTest($test,$key){
        $test->amount_tags_processed = $key + 1;
        $test->save();
    }

    public function saveTags($tag_process,$question_id,$key){
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Edit Tags Start']);
        MedicineAreaReference::where('question_id',$question_id)->delete();
        SpecialtyReference::where('question_id',$question_id)->delete();
        ThemeReference::where('question_id',$question_id)->delete();
        foreach($tag_process as $tag){
            $medicine_area = MedicineAreaReference::where('question_id',$question_id)->where('medicine_area_id',$tag['medicine_area_id'])->first();
            if(!$medicine_area){
                MedicineAreaReference::create(['question_id' => $question_id,'medicine_area_id' => $tag['medicine_area_id']]);
            }
            SpecialtyReference::create(['question_id' => $question_id,'specialty_id' => $tag['id']]);
            foreach($tag['themes'] as $theme){
                ThemeReference::create(['question_id' => $question_id,'theme_id' => $theme['id']]);
            }
        }
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Edit Tags finished','question_id' => $question_id]);
    }

    public function processThread($question,$client,$key)
    {           
        try {
        $threadMessage = $this->getQuestionAndAlternativesText($question); 
            // Create a thread
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Start Create Thread to Generate Tag']);
            $threadResponse = $client->threads()->create([
                    'messages' =>
                        [
                            [
                                'role' => 'user',
                                'content' => $threadMessage
                            ],
                        ],
            ]);
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Finish Create Thread to Generate Tag:' . json_encode($threadResponse)]);
                // Run Thread
                if($threadResponse->id != null || $threadResponse->id != ""){
                    $this->runThread($client,$threadResponse,$key);
                    sleep(55);
                    return $threadResponse->id;
                }else{
                    return null;
                }
        } catch (\Exception $e) {
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'process error:'.$e->getMessage()]);
        }
    }

    public function getQuestionAndAlternativesText($question){
        $alternativesText = "";
        $ord = ["0" => "A","1" => "B","2" => "C","3" => "D","4" => "E"];
        foreach($question->alternatives as $key => $alternative){
            $letter = $ord[$key];
            $alternativesText = $alternativesText . $letter .")" . $alternative->alternative . " ";
        }
        return "Questão:" . $question->question . " " . $alternativesText;
    }

    public function retrieveMessage($threadId,$client,$key){
        
        try{
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'retrieve message started to Generate Tags']);
            $response = $client->threads()->messages()->list(
                threadId: $threadId
            );

            $messagesData = $response->data;
            if (!empty($messagesData)) {
                $messagesCount = count($messagesData);
                $assistantResponseMessage = '';            
                // take the first message
                $assistantResponseMessage = $messagesData[0]->content[0]->text->value;
                // Passo 1: Remover os caracteres de escape usando stripslashes()
                $cleanString = str_replace(['\n', '\t','json'], '', $assistantResponseMessage);
                $cleanString = trim($cleanString);
                $cleanString = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleanString);
                $cleanString = trim($cleanString, "`");
                $data = json_decode($cleanString, true);

                return $data;
            } else {
                TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'retrieve message is empty to Generate Tags']);
            }
        } catch (\Exception $e) {
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Retrieve Message error to Generate Tags:'.$e->getMessage()]);
        }
    }

    public function runThread($client,$threadResponse,$key){   
        try{
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Start Run Thread to Generate Tags']);
            $client->threads()->runs()->createStreamed(
                threadId: $threadResponse->id,
                    parameters: [
                        'assistant_id' => $this->assistant->assistant_id,
                    ],
            );
        }catch(\Exception $e){
            TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'process error:'.$e->getMessage()]);
        } 
        
    }


}
