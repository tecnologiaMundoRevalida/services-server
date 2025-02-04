<?php

namespace App\Http\Controllers;

use App\Services\PDFTestService;
use Exception;

class PDFTestController extends Controller
{
    protected object $pdfTestService;

    public function __construct(PDFTestService $pdfTestService)
    {
        $this->pdfTestService = $pdfTestService;
    }

    public function processPdfTest(int $userTestId)
    {
        try {
            return $this->pdfTestService->processPdfTest($userTestId);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
