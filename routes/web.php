<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\PDFTestController;
use App\Http\Controllers\MockTestPDFController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/getResumePDF/{checklist_id}/{user_id}', [PDFController::class, 'generatePDF']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/process-pdf-test/{userTestId}', [PDFTestController::class, 'processPdfTest']);
Route::get('/process-mock-test-pdf/{mockTestId}', [MockTestPDFController::class, 'processMockTestPdf']);


