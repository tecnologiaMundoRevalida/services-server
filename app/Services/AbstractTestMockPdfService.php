<?php

namespace App\Services;
use Normalizer;

abstract class AbstractTestMockPdfService
{
    protected function parseKeyToABC(int $key): string
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

    protected function getLogoBackgroundPDFBase64(): string
    {
        $filepathLogo = public_path('images/logo_background_medtask.jpg');
        $filetype = pathinfo($filepathLogo, PATHINFO_EXTENSION);
        $getLogo = file_get_contents($filepathLogo);
        return 'data:image/' . $filetype . ';base64,' . base64_encode($getLogo);
    }

    protected function removeSpecificString(string $string): string
    {
        return str_ireplace("Microsoft Word - Revalida_P1_2024_Prova Objetiva.docx", '', $string);
    }

    protected function getFontSizePDF(string $size): array
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
