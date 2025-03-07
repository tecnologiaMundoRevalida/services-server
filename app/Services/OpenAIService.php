<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;
use App\Models\Question;
use App\Models\Test;
use GuzzleHttp\Client;

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

    public function correctAlternatives($discursiveAnswers)
    {
        // $client = OpenAI::client($this->apiKey);

        // Criar o prompt com formatação clara
        $prompt = "Você é um assistente especializado em revisar e pontuar respostas de questões de provas de residência médica que sempre é muito fiel ao gabarito, nunca fugindo dele.
        Sua tarefa é:
        1. Ler a pergunta e o comentário original do aluno (ou a resposta dele).
        2. Ler as instruções de correção, que podem conter o gabarito, orientações específicas ou ajustes necessários. É importante seguir de forma restrita as informações do gabarito para a correção, sendo sempre o mais fiel possível. 
        3. Com base nesses dados, elaborar um texto explicativo (um comentário global) que descreva por que cada item (a, b, c, d) recebeu a pontuação que você atribuirá, sempre seguindo o gabarito apenas, nunca fugindo dele.
       - Claro e detalhado: explique de forma objetiva o que o aluno acertou ou errou em cada item, pontuando exatamente como o gabarito determina.  
       - Focado na comparação com o gabarito: se algo não estiver contemplado no gabarito, a nota deve ser 0 naquele critério/item.
       - Justifique cada nota: indique na justificativa não apenas o que foi correto, mas também o que faltou ou está incompleto.
       - Se atente a possíveis formas que podem ser aceitas como sinônimos, como sinais > para maior ou < para menor e escritas de síndromes que podem conter algum erro gramatical. 
       - Em points você deve colocar quantos pontos vale o item avaliado.";

        foreach ($discursiveAnswers as $question) {
            $prompt .= "**Questão:** " . strip_tags($question['question_text']) . "\n\n";

            foreach ($question['alternatives'] as $alt) {
                $prompt .= "**Alternativa original:** " . strip_tags($alt['alternative_text']) . "\n";
                $prompt .= "Resposta do aluno: " . strip_tags($alt['student_answer']) . "\n";
                $prompt .= "Resposta correta: " . strip_tags($alt['correct_answer']) . "\n\n";
            }
        }

        // Especificamos que o OpenAI deve retornar um JSON puro
        $prompt .= "Agora, retorne um JSON estruturado com a correção:\n";
        $prompt .= "```json\n";
        $prompt .= "[{\"alternative\": \"Texto Alternativa A\", \"score\": 8, \"feedback\": \"Boa resposta.\", \"points\": \"float ou int valor referente à 100% de acerto\"},";
        $prompt .= "{\"alternative\": \"Texto Alternativa B\", \"score\": 5, \"feedback\": \"Poderia melhorar.\", \"points\": \"float ou int valor referente à 100% de acerto\"}]\n";
        $prompt .= "```";
        
        $client = new Client();
        try {
        $response = $client->post('https://api.deepseek.com/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('DEEPSEEK_API_KEY'),
            ],
            'json' => [
                'model' => 'deepseek-reasoner',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => $prompt
                    ],
                ],
                'stream' => false
            ],
        ]);

            $body = $response->getBody()->getContents();

        
            //call openai
            // $response = $client->chat()->create([
            //     'model' => 'gpt-4o',
            //     'messages' => [['role' => 'user', 'content' => $prompt]],
            //     'max_tokens' => 1500,
            //     'temperature' => 1,
            // ]);

            $response = json_decode($body);
            
            // Extrair JSON removendo blocos de código markdown
            $rawResponse = $response->choices[0]->message->content;
            preg_match('/```json\s*(.*?)\s*```/s', $rawResponse, $matches);

            if (!empty($matches[1])) {
                $jsonString = trim($matches[1]); // Remove espaços extras
                $decodedJson = json_decode($jsonString, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decodedJson;
                } else {
                    return ['error' => 'Erro ao decodificar JSON', 'details' => json_last_error_msg()];
                }
            }

            return ['error' => 'OpenAI não retornou um JSON válido.', 'raw_response' => $rawResponse];
        } catch (Exception $e) {
            return ['error' => 'Erro ao processar a correção.', 'details' => $e->getMessage()];
        }
    }


}