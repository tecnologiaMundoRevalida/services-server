<?php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;
use App\Http\Controllers\MedtaskActionsController;
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
    dispatch((new App\Jobs\SendEmailCreateUserJob($user, $password,$type))->onQueue('high'));
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
    dispatch((new App\Jobs\SendEmailUpdatedUser($user))->onQueue('high'));
    return response()->json(["message" => "adicionado na fila de envio de e-mails"]);
});

Route::post('/processPdf', [OpenAIController::class, 'processPdf'])->middleware(['auth:sanctum']);
Route::post('/generateTags', [OpenAIController::class, 'generateTags'])->middleware(['auth:sanctum']);
Route::post('/generateComments', [OpenAIController::class, 'generateComments'])->middleware(['auth:sanctum']);

Route::group(
    [
        'middleware' => 'auth:sanctum',
        'prefix' => 'medtask-actions'
    ],
    function () {
        Route::post('/storeUserTest', [MedtaskActionsController::class, 'storeUserTest']);
    }
);

