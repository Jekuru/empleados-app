<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class checkUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // COMPRUEBA SI EL TOKEN INTRODUCIDO ES DE UN USUARIO DE UN USUARIO DE RRHH (HR) O DIRECTIVO (DIRECTIVE)
        $response = ["status" => 1, "msg" => "Error en middleware"];

        if($request->has('token')){
            $token = $request->input('token');
        } else {
            $token = "";
        }

        try {
            if($token != ""){
                $allow = DB::table('users')
                                ->where('api_token', '=', $token)
                                ->whereIn('role', array('hr', 'directive'))
                                ->first();

                if($allow){
                    return $next($request);
                } else {
                    $response["status"] = 3;
                    $response["msg"] = "Acceso denegado.";
                }
            } else {
                $response["status"] = 2;
                $response["msg"] = "Inicie sesion para acceder a esta funcion.";
            }
        }catch(\Exception $e){
            $respuesta["msg"] = $e->getMessage();
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Se ha producido un error: ".$e->getMessage();
        }
            return response()->json($response);
    }
}
