<?php

namespace App\Http\Controllers;

use App\Services\MockTestPDFService;
use Exception;

class MockTestPDFController extends Controller
{
    protected object $mockTestPdfService;

    public function __construct(MockTestPDFService $mockTestPDFService)
    {
        $this->mockTestPdfService = $mockTestPDFService;
    }

    public function processMockTestPdf(int $mockTestId)
    {
        try {
            return $this->mockTestPdfService->processMockTestPdf($mockTestId);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
