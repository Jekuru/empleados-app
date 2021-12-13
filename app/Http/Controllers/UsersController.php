<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        // COMPROBAR SI EL EMAIL YA ESTÁ REGISTRADO
        $exists = User::where('email', '=', $data->email)->first();

        
        try {
            if($exists){
                $msg['msg'] = "No se pudo crear el usuario especificado, ya existe un usuario con el email: ".$user->email;
                $msg['status'] = 2;
            } else {
                $user->save();
                $msg['msg'] = "Nuevo usuario registrado con email ".$user->email;
                $msg['status'] = 1;
            }
            
        } catch(\Exception $e){
            $msg['msg'] = $e->getmsg();
            $msg['status'] = 0;
        }

        return response()->json($msg);
    }
}
