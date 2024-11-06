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

    private $assistant;
    /**
     * Create a new job instance.
     */
    public function __construct(private readonly int $test_id,public readonly int $completely)
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try{
            $test = Test::with(['questions','questions.alternatives'])->find($this->test_id);
            $this->assistant = AssistantAi::getAvailableAssistantGenerateTags();
            $client = OpenAI::client(config('services.openai.api_key'));
            $this->updateTest($test,null,"GENERATING");
            $this->generateTags($client,$test);
            $this->updateTest($test,null,"GENERATED");
            
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

    public function updateQuestion($question_id){
        $question = Question::find($question_id);
        $question->ai_generated_tag = 1;
        $question->save();
    }

    public function updateTest($test,$key,$tag_generation_status = null){
        if($tag_generation_status != null){
            $test->tag_generation_status = $tag_generation_status;
        }
        if($key != null){
            $test->amount_tags_processed = $key + 1;
        }
        
        $test->save();
    }

    public function saveTags($tag_process,$question_id,$key){
        try{
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Edit Tags Start']);
        if($this->completely == 1){
            MedicineAreaReference::where('question_id',$question_id)->delete();
            SpecialtyReference::where('question_id',$question_id)->delete();
            ThemeReference::where('question_id',$question_id)->delete();
        }
        foreach($tag_process as $tag){
            if(isset($tag['medicine_area_id']) && $tag['medicine_area_id'] != null){
                $medicine_area = MedicineAreaReference::where('question_id',$question_id)->where('medicine_area_id',$tag['medicine_area_id'])->first();
                if(!$medicine_area){
                    MedicineAreaReference::create(['question_id' => $question_id,'medicine_area_id' => $tag['medicine_area_id']]);
                }
            }

            if(isset($tag['id']) && $tag['id'] != null){
                $specialty = SpecialtyReference::where('question_id',$question_id)->where('specialty_id',$tag['id'])->first();
                if(!$specialty){
                    SpecialtyReference::create(['question_id' => $question_id,'specialty_id' => $tag['id']]);
                }
            }

            if(isset($tag['themes']) && count($tag['themes']) > 0){
                foreach($tag['themes'] as $theme){
                    if(isset($theme['id']) && $theme['id'] != null){
                        $theme_old = ThemeReference::where('question_id',$question_id)->where('theme_id',$theme['id'])->first();
                        if(!$theme_old){
                            ThemeReference::create(['question_id' => $question_id,'theme_id' => $theme['id']]);
                        }
                    }
                }
            }
        }
        $this->updateQuestion($question_id);
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Edit Tags finished','question_id' => $question_id]);
    }catch(\Exception $e){
        TestProcessingLog::create(['test_id' => $this->test_id,'number_question' => $key,'log' => 'Erro edit tags'.$e->getMessage()]);
    }
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
                    sleep(85);
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
        $ord = ["0" => "A","1" => "B","2" => "C","3" => "D","4" => "E","5" => "F","6" => "G","7" => "H","8" => "I","9" => "J"];
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
