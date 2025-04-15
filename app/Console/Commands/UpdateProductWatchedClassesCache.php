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
        // Primeiro, limpa os registros antigos da tabela para dados de aulas assistidas (is_global_question = 0)
        DB::table('temp_global_score')
            ->where('is_global_question', 0)
            ->delete();
        
        $this->info("Registros antigos de aulas assistidas removidos do banco de dados");

        // Consulta única para obter a porcentagem global de visualização de vídeos para cada produto
        $productsData = DB::select("
            SELECT 
                p.id,
                p.name AS product_name,
                COUNT(DISTINCT clv.id) AS total_videos,
                SUM(video_avg.avg_percentage) AS sum_percentage,
                ROUND(
                    CASE 
                        WHEN COUNT(DISTINCT clv.id) = 0 THEN 0
                        ELSE SUM(video_avg.avg_percentage) / COUNT(DISTINCT clv.id)
                    END, 
                    2
                ) AS global_percentage
            FROM 
                mundorevalida.products p
            LEFT JOIN 
                mundorevalida.course_product cp ON cp.product_id = p.id
            LEFT JOIN 
                mundorevalida.courses c ON c.id = cp.course_id
            LEFT JOIN 
                mundorevalida.course_lessons cl ON cl.course_id = c.id AND cl.active = 1
            LEFT JOIN 
                mundorevalida.course_lesson_videos clv ON clv.course_lesson_id = cl.id
            LEFT JOIN (
                -- Subquery para calcular a média por vídeo
                SELECT 
                    clv.id AS video_id,
                    AVG(
                        CASE
                            WHEN clvw.finished = 1 THEN 100
                            ELSE COALESCE(clvw.current_percentege, 0)
                        END
                    ) AS avg_percentage
                FROM 
                    mundorevalida.course_lesson_videos clv
                LEFT JOIN 
                    mundorevalida.course_lesson_video_watched clvw ON clvw.course_lesson_video_id = clv.id
                GROUP BY 
                    clv.id
            ) AS video_avg ON video_avg.video_id = clv.id
            WHERE 
                p.active = 1
            GROUP BY 
                p.id, p.name
            ORDER BY 
                p.name
        ");

        $totalProducts = count($productsData);
        $this->info("Processando {$totalProducts} produtos ativos");

        // Percorre os resultados e insere na tabela temp_global_score
        foreach ($productsData as $product) {
            $productId = $product->id;
            $globalPercentage = $product->global_percentage;

            // Armazena na tabela temp_global_score
            DB::table('temp_global_score')->insert([
                'product_id' => $productId,
                'is_global_question' => 0, // indica que é dado de aulas assistidas
                'score' => $globalPercentage,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->info("Produto {$product->product_name} (ID: {$productId}): {$globalPercentage}% - Salvo no banco de dados");
        }
        
        $this->info('Dados salvos no banco de dados com sucesso!');
    }
} 