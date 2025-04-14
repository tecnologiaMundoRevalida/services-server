<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UpdateScoreCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-score-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o cache com a pontuação de todas as questões resolvidas por todos os estudantes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando atualização do cache de pontuações...');
        
        $result = $this->updateScoreAllQuestionsSolvedAllStudentsCache();
        
        $this->info('Cache atualizado com sucesso!');
        $this->info('Total de questões: ' . $result['total_questions']);
        $this->info('Questões corretas: ' . $result['correct_questions']);
        $this->info('Porcentagem geral: ' . $result['accuracy'] . '%');
        
        return Command::SUCCESS;
    }

    /**
     * Atualiza o cache com estatísticas de pontuação de todas as questões resolvidas
     */
    private function updateScoreAllQuestionsSolvedAllStudentsCache()
    {
        // Data de 6 meses atrás
        $sixMonthsAgo = now()->subMonths(6)->format('Y-m-d H:i:s');

        $baseQuery = "
            WITH all_answers AS (
                -- BLOCO_RECOMENDADO (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    abq.is_correct,
                    abq.percent_correct,
                    abq.points,
                    'BLOCO_RECOMENDADO' as source
                FROM answer_block_questions abq
                JOIN recommendation_block_questions rbq ON abq.block_id = rbq.block_id
                JOIN recommendation_blocks rb ON abq.block_id = rb.id
                JOIN questions q ON abq.question_id = q.id
                WHERE abq.user_id IS NULL
                    AND abq.block_id IS NOT NULL
                    AND abq.notebook_error = 0
                    AND abq.created_at >= ?

                UNION ALL

                -- BLOCO_LISTAGEM_GERAL (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    abq.is_correct,
                    abq.percent_correct,
                    abq.points,
                    'BLOCO_LISTAGEM_GERAL' as source
                FROM answer_block_questions abq
                JOIN questions q ON abq.question_id = q.id
                WHERE abq.block_id IS NULL
                    AND abq.created_at >= ?

                UNION ALL

                -- BLOCO_CADERNO_ERROS (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    abq.is_correct,
                    abq.percent_correct,
                    abq.points,
                    'BLOCO_CADERNO_ERROS' as source
                FROM answer_block_questions abq
                JOIN recommendation_block_questions rbq ON abq.block_id = rbq.block_id
                JOIN recommendation_blocks rb ON abq.block_id = rb.id
                JOIN questions q ON abq.question_id = q.id
                WHERE abq.notebook_error = 1
                    AND abq.created_at >= ?

                UNION ALL

                -- BANCO_QUESTOES (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    qa.is_correct,
                    qa.percent_correct,
                    qa.points,
                    'BANCO_QUESTOES' as source
                FROM questions_answered qa
                JOIN questions q ON qa.question_id = q.id
                WHERE qa.created_at >= ?

                UNION ALL

                -- SIMULADO (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    mtuq.is_correct,
                    mtuq.percent_correct,
                    mtuq.points,
                    'SIMULADO' as source
                FROM mock_test_user_questions mtuq
                JOIN questions q ON mtuq.question_id = q.id
                WHERE mtuq.created_at >= ?

                UNION ALL

                -- PRE_QUESTOES (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    pqur.is_correct,
                    pqur.percent_correct,
                    pqur.points,
                    'PRE_QUESTOES' as source
                FROM pre_question_user_responses pqur
                JOIN course_lesson_pre_questions clpq ON pqur.pre_question_id = clpq.id
                JOIN course_lessons cl ON clpq.course_lesson_id = cl.id
                JOIN questions q ON clpq.question_id = q.id
                WHERE pqur.created_at >= ?

                UNION ALL

                -- POS_QUESTOES (Objetivas e Discursivas)
                SELECT
                    q.id as question_id,
                    q.is_discursive,
                    pqur.is_correct,
                    pqur.percent_correct,
                    pqur.points,
                    'POS_QUESTOES' as source
                FROM pos_question_user_responses pqur
                JOIN course_lesson_pos_questions clpq ON pqur.pos_question_id = clpq.id
                JOIN course_lessons cl ON clpq.course_lesson_id = cl.id
                JOIN questions q ON clpq.question_id = q.id
                WHERE pqur.created_at >= ?
            ),

            discursive_with_weighted_percent AS (
                SELECT
                    question_id,
                    source,
                    SUM(percent_correct * points) OVER (PARTITION BY question_id, source) /
                        NULLIF(SUM(points) OVER (PARTITION BY question_id, source), 0) as weighted_percent
                FROM all_answers
                WHERE is_discursive = 1
            ),

            unique_discursive_questions AS (
                SELECT DISTINCT
                    question_id,
                    source,
                    CASE WHEN weighted_percent >= 50 THEN 1 ELSE 0 END as is_correct
                FROM discursive_with_weighted_percent
            ),

            objective_stats AS (
                SELECT
                    source,
                    COUNT(DISTINCT question_id) as total_questions,
                    COUNT(DISTINCT CASE WHEN is_correct = 1 THEN question_id END) as correct_questions
                FROM all_answers
                WHERE is_discursive = 0
                GROUP BY source
            ),

            discursive_stats AS (
                SELECT
                    source,
                    COUNT(question_id) as total_questions,
                    SUM(is_correct) as correct_questions
                FROM unique_discursive_questions
                GROUP BY source
            ),

            combined_stats AS (
                SELECT
                    IFNULL(o.source, d.source) as source,
                    IFNULL(o.total_questions, 0) + IFNULL(d.total_questions, 0) as total_questions,
                    IFNULL(o.correct_questions, 0) + IFNULL(d.correct_questions, 0) as correct_questions
                FROM objective_stats o
                LEFT JOIN discursive_stats d ON o.source = d.source

                UNION

                SELECT
                    d.source,
                    IFNULL(o.total_questions, 0) + IFNULL(d.total_questions, 0) as total_questions,
                    IFNULL(o.correct_questions, 0) + IFNULL(d.correct_questions, 0) as correct_questions
                FROM discursive_stats d
                LEFT JOIN objective_stats o ON d.source = o.source
                WHERE o.source IS NULL
            )

            SELECT
                source,
                total_questions,
                correct_questions,
                CASE WHEN total_questions > 0
                    THEN ROUND((correct_questions / total_questions) * 100, 2)
                    ELSE 0
                END as accuracy
            FROM combined_stats
            ORDER BY source
        ";

        // Parâmetros são apenas as datas de 6 meses atrás (repetidos 7 vezes para cada UNION)
        $params = array_fill(0, 7, $sixMonthsAgo);

        $results = DB::select($baseQuery, $params);

        // Organiza os resultados
        $result = [
            'total_questions' => 0,
            'correct_questions' => 0,
            'accuracy' => 0,
            'sources' => [],
            'cached_at' => now()->toDateTimeString()
        ];

        foreach ($results as $sourceData) {
            $totalQuestions = (int)$sourceData->total_questions;
            $correctQuestions = (int)$sourceData->correct_questions;

            // Atualiza totais gerais
            $result['total_questions'] += $totalQuestions;
            $result['correct_questions'] += $correctQuestions;

            // Adiciona dados específicos da fonte
            $result['sources'][$sourceData->source] = [
                'total' => $totalQuestions,
                'correct' => $correctQuestions,
                'accuracy' => (float)$sourceData->accuracy
            ];
        }

        // Calcula acurácia geral
        $result['accuracy'] = $result['total_questions'] > 0 ?
            round(($result['correct_questions'] / $result['total_questions']) * 100, 2) : 0;

        // Armazena no Redis com expiração de 24 horas
        Redis::set('score_all_questions_all_students', json_encode($result), 'EX', 24 * 60 * 60);

        return $result;
    }
}
