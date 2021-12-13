<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Models\User;

class UsersController extends Controller
{
    public function register(Request $req){

        $msg = ["status" => 1, "msg" => ""];
                
        // JSON
        $data = $req->getContent();
        $data = json_decode($data);
       
        // NUEVO USUARIO
        $user = new User();

        $user->name = $data->name;
        $user->email = $data->email;
        $user->password = $data->password;    
        $user->role = $data->role;      
        $user->salary = $data->salary;
        if(isset($data->biography)){
            $user->biography = $data->biography;
        }
        $user->api_token = Str::random(60);

        // COMPROBAR SI LA CONTRASEÑA ES SEGURA
        $safe = $this->CheckPassword($user->password);

        // COMPROBAR SI EL EMAIL YA ESTÁ REGISTRADO
        $exists = User::where('email', '=', $data->email)->first();
        
        try {
            if($safe){
                if($exists){
                    $msg['msg'] = "No se pudo crear el usuario especificado, ya existe un usuario con el email: ".$user->email;
                    $msg['status'] = 2;
                } else {
                    $user->save();
                    $msg['msg'] = "Nuevo usuario registrado con email ".$user->email;
                    $msg['status'] = 1;
                }
            } else {
                $msg['msg'] = "La contraseña no es segura";
                $msg['status'] = 3;
            }
        } catch(\Exception $e){
            $msg['msg'] = $e->getmsg();
            $msg['status'] = 0;
        }

        return response()->json($msg);
    }

    /**
     * Comprobar si la contraseña es segura
     * Param: $password -> string
     */
    public function CheckPassword($password){
        $pattern = '/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/';

        if(preg_match($pattern, $password)){
            return true;
        } else {
            return false;
        }
    }
}
