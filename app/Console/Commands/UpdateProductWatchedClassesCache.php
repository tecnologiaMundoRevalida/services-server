<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class UpdateProductWatchedClassesCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-product-watched-classes-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o cache com o percentual global de aulas assistidas para cada produto ativo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando atualização do cache de aulas assistidas por produto...');
        
        $this->updateProductWatchedClassesCache();
        
        $this->info('Cache atualizado com sucesso!');
        
        return Command::SUCCESS;
    }

    /**
     * Atualiza o cache com percentual global de aulas assistidas para cada produto ativo
     * considerando apenas as visualizações dos últimos 6 meses
     */
    private function updateProductWatchedClassesCache()
    {
        // Obtém todos os produtos ativos iniciados nos últimos 6 meses
        $activeProducts = DB::select("
            SELECT *
            FROM products
            WHERE active = 1
              AND start_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        ");

        $totalProducts = count($activeProducts);
        $this->info("Processando {$totalProducts} produtos ativos");

        // Primeiro, limpa os registros antigos da tabela para dados de aulas assistidas (is_global_question = 0)
        DB::table('temp_global_score')
            ->where('is_global_question', 0)
            ->delete();
        
        $this->info("Registros antigos de aulas assistidas removidos do banco de dados");

        // Para cada produto, calcula o percentual global de aulas assistidas
        foreach ($activeProducts as $product) {
            $productId = $product->id;
            
            // Busca todos os usuários que possuem este produto
            $usersWithProduct = DB::select("
                SELECT DISTINCT pu.user_id
                FROM product_user pu
                INNER JOIN product_profile pp ON pp.product_id = pu.product_id
                WHERE pu.product_id = ?
                    AND pp.profile_id = 1
                    AND pu.expiration_date > NOW()
            ", [$productId]);

            // Se não tiver usuários com o produto, define como 0%
            if (empty($usersWithProduct)) {
                $globalPercentage = 0;
            } else {
                // Busca todos os vídeos das aulas dos cursos do produto com seus respectivos IDs            
                $videosList = DB::select("
                    SELECT clv.id as video_id
                    FROM course_lesson_videos clv
                    JOIN course_lessons cl ON cl.id = clv.course_lesson_id
                    JOIN courses c ON c.id = cl.course_id
                    JOIN course_product cp ON cp.course_id = c.id
                    WHERE cp.product_id = ?
                    AND cl.active = 1
                ", [$productId]);

                $totalVideos = count($videosList);

                // Se não tem vídeos, considera 0%
                if ($totalVideos == 0) {
                    $globalPercentage = 0;
                } else {
                    // Calcula a porcentagem de progresso para cada usuário
                    $totalUsersProgress = 0;
                    $userCount = count($usersWithProduct);

                    // Para cada usuário, verifica o progresso dos vídeos
                    foreach ($usersWithProduct as $user) {
                        // Busca o progresso de todos os vídeos assistidos pelo aluno (finalizados ou não)
                        // Mas apenas as visualizações dos últimos 6 meses
                        $videoProgress = DB::select("
                            SELECT
                                clv.id as video_id,
                                CASE
                                    WHEN clvw.finished = 1 THEN 100
                                    WHEN clvw.finished = 0 THEN clvw.current_percentege
                                    ELSE 0
                                END as progress_percentage
                            FROM course_lesson_videos clv
                            JOIN course_lessons cl ON cl.id = clv.course_lesson_id
                            JOIN courses c ON c.id = cl.course_id
                            JOIN course_product cp ON cp.course_id = c.id
                            LEFT JOIN course_lesson_video_watched clvw ON clvw.course_lesson_video_id = clv.id AND clvw.user_id = ?
                                AND (clvw.updated_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) OR clvw.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH))
                            WHERE cp.product_id = ?
                        ", [$user->user_id, $productId]);

                        // Calcula a soma total de progresso para todos os vídeos do usuário atual
                        $totalProgress = 0;
                        $progressByVideoId = [];

                        // Organiza os dados de progresso por video_id
                        foreach ($videoProgress as $progress) {
                            $progressByVideoId[$progress->video_id] = $progress->progress_percentage;
                        }

                        // Soma o progresso para cada vídeo do curso
                        foreach ($videosList as $video) {
                            $videoProgress = isset($progressByVideoId[$video->video_id]) ? $progressByVideoId[$video->video_id] : 0;
                            $totalProgress += $videoProgress;
                        }

                        // Calcula a média do progresso (total de progresso / número de vídeos) para este usuário
                        $userAverageProgress = $totalProgress / $totalVideos;

                        // Soma o progresso médio de cada usuário para calcular o total
                        $totalUsersProgress += $userAverageProgress;
                    }

                    // Calcula a média global de progresso para todos os usuários deste produto
                    $globalPercentage = $userCount > 0 ? round($totalUsersProgress / $userCount, 2) : 0;
                }
            }

            // Em vez disso, armazena na tabela temp_global_score
            DB::table('temp_global_score')->insert([
                'product_id' => $productId,
                'is_global_question' => 0, // indica que é dado de aulas assistidas
                'score' => $globalPercentage,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("Produto {$product->name} (ID: {$productId}): {$globalPercentage}% - Salvo no banco de dados");
        }        
        
        $this->info('Dados salvos no banco de dados com sucesso!');
    }
} 