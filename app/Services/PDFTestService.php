<?php

namespace App\Services;

use App\Models\Alternative;
use App\Models\MedicineAreaReference;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Enums\FilterDeleteQuestionPDFTest;
use Illuminate\View\View;

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

                    $data[$id['id']][$key]['alternative'] =  self::sanitize(strip_tags($this->removeSpecificString($alternative['alternative'])));
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
        $search = [                 // www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
                    "\xC2\xAB",     // « (U+00AB) in UTF-8
                    "\xC2\xBB",     // » (U+00BB) in UTF-8
                    "\xE2\x80\x98", // ‘ (U+2018) in UTF-8
                    "\xE2\x80\x99", // ’ (U+2019) in UTF-8
                    "\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
                    "\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
                    "\xE2\x80\x9C", // “ (U+201C) in UTF-8
                    "\xE2\x80\x9D", // ” (U+201D) in UTF-8
                    "\xE2\x80\x9E", // „ (U+201E) in UTF-8
                    "\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
                    "\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
                    "\xE2\x80\xBA", // › (U+203A) in UTF-8
                    "\xE2\x80\x93", // – (U+2013) in UTF-8
                    "\xE2\x80\x94", // — (U+2014) in UTF-8
                    "\xE2\x80\xA6"  // … (U+2026) in UTF-8
        ];

        $replacements = [
                    "<<", 
                    ">>",
                    "'",
                    "'",
                    "'",
                    "'",
                    '"',
                    '"',
                    '"',
                    '"',
                    "<",
                    ">",
                    "-",
                    "-",
                    "..."
        ];

       return str_replace($search, $replacements, $texto);
    }
}
