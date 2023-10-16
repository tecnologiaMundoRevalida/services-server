<?php
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
