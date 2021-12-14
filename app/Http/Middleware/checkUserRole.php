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
        // COMPRUEBA SI EL ID INTRODUCIDO ES DE UN USUARIO DE RRHH (HR) O DIRECTIVO (DIRECTIVE)
        $response = ["status" => 1, "msg" => ""];

        if($request->has('id')){
            $id = $request->input('id');
        } else {
            $id = "";
        }

        try {
            if($id != ""){
                $allow = DB::table('users')
                                ->where('id', '=', $id)
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
                $response["msg"] = "No se ha proprocionado una id de usuario.";
            }
        }catch(\Exception $e){
            $respuesta["msg"] = $e->getMessage();
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Se ha producido un error: ".$e->getMessage();
        }
            return response()->json($response);
    }
}
