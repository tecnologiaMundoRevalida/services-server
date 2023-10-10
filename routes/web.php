<?php
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFController;
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
// Route::get('/myPDF/{checklist_id}/{user_id}', [PDFController::class, 'generatePDF'], function () {
//     return view('resume');
// });
Route::get('/', function () {
    return view('welcome');
});

Route::get('email-test', function(){
    $user = User::find(322);
    $password = 12345678;
    $type = 'user-banco-questoes';
    dispatch(new App\Jobs\SendEmailJob($user, $password,$type));
    dd('done');
});
