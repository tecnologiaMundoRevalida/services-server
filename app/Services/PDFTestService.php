<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\MedicineAreaReference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Enums\FilterDeleteQuestionPDFTest;
use Illuminate\View\View;
use Normalizer;

class PDFTestService
{
    private array $questionsId;
    private bool $flagTags = false;
    private string $deleteQuestion = 'n';
    private array $answerKey = [];

    public function processPdfTest(int $userTestId): mixed
    {
        if (request()->get('tags') === 's') {
            $this->flagTags = true;
        }

        if (
            request()->get('delete_question') === FilterDeleteQuestionPDFTest::RESOLVIDAS->value ||
            request()->get('delete_question') === FilterDeleteQuestionPDFTest::CERTAS->value
        ) {
            $this->deleteQuestion = request()->get('delete_question');
        }

        $userTestReferences = $this->getUserTestReferences($userTestId);
        $questions = $this->getTestQuestions($userTestReferences);
        $alternatives = $this->getAlternativesQuestion();

        foreach ($this->questionsId as $id) {
            $questions[$id['id']]->alternatives = $alternatives[$id['id']];
        }

        $fontSize = $this->getFontSizePDF(
            request()->get('font_size') ?? 'm'
        );

        // dd(mb_detect_encoding($questions[21769]->question));

        $html = \Illuminate\Support\Facades\View::make('pdfs.pdf-test', [
            'data' => $questions,
            'fontSize' => $fontSize,
            'defaultFont' => 'DejaVu Sans',
            'logoBackground' => $this->getLogoBackgroundPDFBase64(),
            'answerKey' => $this->answerKey
        ])->render();

        // $html = mb_convert_encoding($html, 'UTF-8');

        $pdf = Pdf::loadHTML($html);


//        $pdf = Pdf::loadView('pdfs.pdf-test', [
//            'data' => $questions,
//            'fontSize' => $fontSize,
//            'logoBackground' => $this->getLogoBackgroundPDFBase64(),
//            'answerKey' => $this->answerKey
//        ], [], 'UTF-8');

        return $pdf->stream('questoes.pdf');
    }

    private function getUserTestReferences(int $userTestId): array
    {
        $sql = "SELECT utr.id,
                       utr.question_id
                FROM user_test_references utr
                WHERE utr.user_test_id = $userTestId";

        if ($this->deleteQuestion === FilterDeleteQuestionPDFTest::RESOLVIDAS->value) {
            $sql .= " AND utr.question_id NOT in
                      (SELECT qa.question_id FROM user_test_questions utq, questions_answered qa
                      WHERE utq.question_answered_id = qa.id
					  AND utq.user_test_id = $userTestId )";
        }

        if ($this->deleteQuestion === FilterDeleteQuestionPDFTest::CERTAS->value) {
            $sql .= " AND utr.question_id NOT in (SELECT qa.question_id FROM user_test_questions utq, questions_answered qa
                      WHERE utq.question_answered_id = qa.id
					  AND utq.user_test_id = $userTestId AND qa.is_correct = 1)";
        }

        return DB::select($sql);
    }

    private function getTestQuestions(array $questionsId): array
    {
        $data = [];

        foreach ($questionsId as $key => $question) {
            $this->questionsId[$key]['id'] = $question->question_id;

            $data[$question->question_id] = DB::table('questions')
                ->join('tests', 'questions.test_id', '=', 'tests.id')
                ->join('institutions', 'tests.institution_id', '=', 'institutions.id')
                ->join('years', 'tests.year_id', '=', 'years.id')
                ->where('questions.id', '=', $question->question_id)
                ->select('questions.id', 'questions.ord', 'questions.question', 'questions.explanation', 'questions.discursive_response', 'questions.is_annulled', 'questions.image', 'questions.comment_image', 'institutions.name as name_institution', 'years.name as name_year')
                ->get()[0];

                $data[$question->question_id]->question = $this->normalizeUtf8($data[$question->question_id]->question);

            $this->questionsId[$key]['is_annulled'] = $data[$question->question_id]->is_annulled;

            if ($this->flagTags) {
                $data[$question->question_id]->medicine_area = $this->parseArrayToStringPDF(
                    $this->getMedicineArea($question->question_id)
                ) ?? null;
            }
        }

        return $data;
    }

    private function getAlternativesQuestion(): array
    {
        $data = [];

        if (count($this->questionsId) > 0) {
            foreach ($this->questionsId as $questionNumber => $id) {
                $questionNumber++;
                $this->answerKey[$id['id']]['question'] = $questionNumber;

                $data[$id['id']] = Alternative::query()
                    ->select(['id', 'alternative', 'discursive_response', 'is_correct', 'question_id'])
                    ->where('question_id', $id['id'])
                    ->get();

                foreach ($data[$id['id']] as $key => $alternative) {

                    $data[$id['id']][$key]['alternative'] =  $this->normalizeUtf8(strip_tags($this->removeSpecificString($alternative['alternative'])));
                    $data[$id['id']][$key]['option'] =  $this->parseKeyToABC($key);

                    if ($id['is_annulled'] != 1) {
                        if ($alternative['is_correct']) {
                            $this->answerKey[$id['id']]['correct_alternative'] = $this->parseKeyToABC($key);
                        }

                        if ($alternative['discursive_response']) {
                            $this->answerKey[$id['id']]['correct_alternative'] = 'discursiva';
                        }
                    } else {
                        $this->answerKey[$id['id']]['correct_alternative'] = 'anulada';
                    }


                }
            }
        }

        return $data;
    }

    private function getMedicineArea(int $questionId): array
    {
        return MedicineAreaReference::query()
            ->select('medicine_areas.name')
            ->join('medicine_areas', 'medicine_area_references.medicine_area_id', '=', 'medicine_areas.id')
            ->where('medicine_area_references.question_id', $questionId)
            ->get()->toArray();
    }

    private function parseKeyToABC(int $key): string
    {
        return match ($key) {
            0 => 'A',
            1 => 'B',
            2 => 'C',
            3 => 'D',
            4 => 'E',
            5 => 'F',
            6 => 'G',
            7 => 'H',
            8 => 'I'
        };
    }

    private function parseArrayToStringPDF(array $data): string
    {
        $string = '';

        foreach ($data as $name) {
            $string .= "<span class='span-specialty'> {$name['name']} </span> ";
        }

        return $string;
    }

    private function getFontSizePDF(string $size): array
    {
        $fontSize = [
            'p' => [
                'title_question'       => '20px',
                'medicine_area'        => '10px',
                'subtitle_question'    => '12px',
                'annulled_question'    => '10px',
                'question_description' => '12px',
                'alternative'          => '12px'
            ],
            'm' => [
                'title_question'       => '24px',
                'medicine_area'        => '12px',
                'subtitle_question'    => '16px',
                'annulled_question'    => '12px',
                'question_description' => '16px',
                'alternative'          => '16px'
            ],
            'g' => [
                'title_question'       => '28px',
                'medicine_area'        => '14px',
                'subtitle_question'    => '20px',
                'annulled_question'    => '14px',
                'question_description' => '20px',
                'alternative'          => '20px'
            ]
        ];

        return $fontSize[$size];
    }

    private function getLogoBackgroundPDFBase64(): string
    {
        $filepathLogo = public_path('images/logo_background_medtask.jpg');
        $filetype = pathinfo($filepathLogo, PATHINFO_EXTENSION);
        $getLogo = file_get_contents($filepathLogo);
        return 'data:image/' . $filetype . ';base64,' . base64_encode($getLogo);
    }

    private function removeSpecificString(string $string): string
    {
        return str_ireplace("Microsoft Word - Revalida_P1_2024_Prova Objetiva.docx", '', $string);
    }

    public function sanitize($texto){
        $search = [
            "’", "‘", "“", "”",    // Aspas e apóstrofos “curvos”
            "–", "—",               // Hífens curtos e longos
            "…",                    // Reticências especiais
            " ",                    // Espaço especial (non-breaking space 0xC2A0) 
        ];
    
        $replace = [
            "'", "'", "\"", "\"",   // substitui por aspas/linha reta
            "-", "-",               // substitui hifens por hifen simples
            "...",                  // substitui reticências
            " ",                    // substitui espaço "especial" por espaço normal
        ];

       return str_replace($search, $replace, $texto);
    }

    public function normalizeUtf8($text)
    {
        // Verifica se a classe Normalizer existe e se o texto NÃO está normalizado
        if (class_exists('Normalizer') && !Normalizer::isNormalized($text, Normalizer::FORM_C)) {
            // Normaliza no formato de composição (NFC)
            return Normalizer::normalize($text, Normalizer::FORM_C);
        }

        // Se já estiver normalizado ou a extensão não existir, apenas retorna como está
        return $text;
    }
}
