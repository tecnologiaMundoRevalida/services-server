<?php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/send-email-create-user', function(Request $request){
    $body = $request->all();
    $user = User::find($body["id"]);
    $password = $body["password"];
    $type = $body["type"];
    dispatch(new App\Jobs\SendEmailCreateUserJob($user, $password,$type));
    return response()->json(["message" => "adicionado na fila de envio de e-mails"]);
});

Route::post('/send-email-simulated', function(Request $request){
    $body = $request->all();
    $user = User::find($body["id"]);
    dispatch(new App\Jobs\SendEmailSimulated($user));
    return response()->json(["message" => "adicionado na fila de envio de e-mails"]);
});

Route::post('/send-email-updated-user', function(Request $request){
    $body = $request->all();
    $user = User::find($body["id"]);
    dispatch(new App\Jobs\SendEmailUpdatedUser($user));
    return response()->json(["message" => "adicionado na fila de envio de e-mails"]);
});

Route::post('/processPdfFile', function(Request $request){
    // $body = $request->all();
    $filename = "uerj.pdf";
    dispatch(new App\Jobs\ProcessPdfTestFileJob($filename));
});


Route::post('/openai-response', [OpenAIController::class, 'getResponse']);
Route::post('/upload-file', [OpenAIController::class, 'uploadFile']);
Route::post('/send-message', [OpenAIController::class, 'sendMessage']);
Route::post('/processPdf', [OpenAIController::class, 'processPdf']);
Route::post('/retrieveMessage', [OpenAIController::class, 'retrieveMessage']);
