<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class MockTestPDFService extends AbstractTestMockPdfService
{
    private array $questionsId = [];
    private array $answerKey = [];

    public function processMockTestPdf(int $mockTestId)
    {
        $mockTestReferences = $this->getMockTestReferences($mockTestId);
        $questions = $this->getQuestions($mockTestReferences);
        $alternatives = $this->getAlternativesQuestion();

        foreach ($this->questionsId as $id) {
            $questions[$id['id']]->alternatives = $alternatives[$id['id']];
        }

        $fontSize = $this->getFontSizePDF(
            request()->get('font_size') ?? 'm'
        );

        $html = \Illuminate\Support\Facades\View::make('pdfs.mock-test-pdf', [
            'data' => $questions,
            'fontSize' => $fontSize,
            'logoBackground' => $this->getLogoBackgroundPDFBase64(),
            'answerKey' => $this->answerKey
        ])->render();

        $html = mb_convert_encoding($html, 'UTF-8');

        $pdf = Pdf::loadHTML($html);

        return $pdf->stream('simulado-questoes.pdf');
    }

    private function getMockTestReferences(int $mockTestId): array
    {
        return DB::table('mock_test_references')->select('id', 'question_id')
            ->where('mock_test_id', '=', $mockTestId)
            ->get()->toArray();
    }

    private function getQuestions(array $questionsId): array
    {
        $data = [];

        foreach ($questionsId as $key => $question) {
            $results = DB::table('questions')
                ->join('tests', 'questions.test_id', '=', 'tests.id')
                ->join('institutions', 'tests.institution_id', '=', 'institutions.id')
                ->join('years', 'tests.year_id', '=', 'years.id')
                ->where('questions.id', '=', $question->question_id)
                ->select('questions.id', 'questions.ord', 'questions.question', 'questions.explanation', 'questions.discursive_response', 'questions.is_annulled', 'questions.image', 'questions.comment_image', 'institutions.name as name_institution', 'years.name as name_year')
                ->get();

            if ($results->isNotEmpty()) {
                $this->questionsId[$key]['id'] = $question->question_id;

                $data[$question->question_id] = $results[0];
                $data[$question->question_id]->question = $this->normalizeUtf8($data[$question->question_id]->question);
                $this->questionsId[$key]['is_annulled'] = $data[$question->question_id]->is_annulled ?? 0;
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

                $data[$id['id']] = DB::table('alternatives')
                    ->select(['id', 'alternative', 'discursive_response', 'is_correct', 'question_id'])
                    ->where('question_id', $id['id'])
                    ->get()->toArray();

                foreach ($data[$id['id']] as $key => $alternative) {

                    $data[$id['id']][$key]->alternative =  strip_tags($this->removeSpecificString($alternative->alternative));
                    $data[$id['id']][$key]->option =  $this->parseKeyToABC($key);

                    if ($id['is_annulled'] != 1) {
                        if ($alternative->is_correct) {
                            $this->answerKey[$id['id']]['correct_alternative'] = $this->parseKeyToABC($key);
                        }

                        if ($alternative->discursive_response) {
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
}
