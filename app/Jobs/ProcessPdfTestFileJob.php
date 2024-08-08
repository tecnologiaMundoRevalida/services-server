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

class ProcessPdfTestFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $fileName;
    /**
     * Create a new job instance.
     */
    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = OpenAI::client(config('services.openai.api_key'));
        $this->processPdf($client);
    }

    public function processPdf($client){
        $array_questions = [15,16,17];
        $questions1234 = [];
        foreach($array_questions as $question){
            $thread_id = $this->processThread($question,$client);
            $question_process = $this->retrieveMessage($thread_id,$client);
            $question = $this->saveQuestion($question_process);
        }
    }

    public function saveQuestion($question_process){
        $question = Question::create([
            'question' => '<p>' . $question_process["questao"] . '</p>',
            'is_discursive' => 0,
            'is_new' => 1,
            // 'is_annulled' => $question_process['is_annulled'],
            'active' => 1,
            'test_id' => 153,
        ]);
        $this->saveAlternatives($question_process['alternativas'],$question,$question_process["resposta_correta"]);
        $this->saveMedicineAreaReference($question_process['tag'],$question);
        return $question;
    }

    public function saveMedicineAreaReference($tag,$question){
        $question->medicineAreaReference()->create([
            'question_id' => $question->id,
            'medicine_area_id' => $tag
        ]);
    }

    public function saveAlternatives($alternatives,$question,$correct){
        $array_alt_correct = [1 => "A",2 => "B",3 => "C",4 => "D",5 => "E"];
        $i = 1;
        foreach($alternatives as $alternative){
            $question->alternatives()->create([
                'question_id' => $question->id,
                'alternative' => '<p>' . $alternative . '</p>',
                'is_correct' => $array_alt_correct[$i] == $correct ? 1 : 0,
                'active' => 1,
            ]);
            $i++;
        }
    }

    public function processThread($numero_q,$client)
    {
        $openai = $client; 
        

        // The ID of the pre-created assistant
        $assistantId = 'asst_u2ULDafLGVlbUrUowkh3QGru';

        // The path to the PDF file you want to upload
        $filePath = '/Users/eldercarmo/Documents/services-server/public/enare.pdf';

        try {
            // Upload the file
            // $fileUploadResponse = $openai->files()->upload([
            //     'purpose' => 'assistants',
            //     'file' => fopen($filePath, 'r'), 
            // ]);
            // $fileId = $fileUploadResponse->id;
            // $response = $openai->vectorStores()->files()->create(
            //     vectorStoreId: 'vs_r3Jym7P2sxlxkHVNk0kiGbTl',
            //     parameters: [
            //         'file_id' => $fileId,
            //     ]
            // );

            // The message or instruction you want to send to the assistant
        $threadMessage = 'converta a questão '.$numero_q.' do arquivo uerj_2023.pdf em json.';


            // Create a thread
            $threadResponse = $openai->threads()->create([
                    'messages' =>
                        [
                            [
                                'role' => 'user',
                                'content' => $threadMessage
                                // 'attachments'=> [
                                //     [
                                //       "file_id"=> $fileId,
                                //       "tools"=> [["type"=> "file_search"]]
                                //     ]
                                //   ]
                            ],
                        ],
            ]);

            $stream = $openai->threads()->runs()->createStreamed(
                threadId: $threadResponse->id,
                parameters: [
                    'assistant_id' => $assistantId,
                ],
            );
            
            foreach($stream as $response){
                switch($response->event){
                    case 'thread.run.completed':
                        return $response->response->threadId;
                        break;
                }
            }
            
        } catch (\Exception $e) {
            // Handle errors appropriately (log, return error response, etc.)
            // return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function retrieveMessage($threadId,$client){
        
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
            return $question;
        } else {
            // \Log::error('Something went wrong; assistant didn\'t respond');
            // dd('Something went wrong; assistant didn\'t respond');
        }
    }
}
