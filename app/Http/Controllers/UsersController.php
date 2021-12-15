<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\User;

class UsersController extends Controller
{
    /**
     * Registrar un nuevo usuario
     * Únicamente pueden registrar nuevos usuarios los directivos y RRHH, gestionado por Middleware "checkUserRole"
     */
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

        // COMPROBAR ENUM
        if($user->role == 'users' || $user->role == 'hr' || $user->role == 'directive'){
            $checkRole = true;
        } else {
            $checkRole = false;
        }

        // COMPROBAR SI SE HA INTRODUCIDO UN EMAIL VALIDO
        $checkEmail = $this->CheckEmail($user->email);

        // COMPROBAR SI LA CONTRASEÑA ES SEGURA
        $checkPassword = $this->CheckPassword($user->password);

        // COMPROBAR SI EL EMAIL YA ESTÁ REGISTRADO
        $exists = User::where('email', '=', $data->email)->first();
        
        try {
            if($checkRole){
                if($checkEmail){
                    if($checkPassword){
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
                } else {
                    $msg['msg'] = "Por favor, introduce una dirección de correo valida";
                    $msg['status'] = 4;
                }
            } else {
                $msg['msg'] = "No se ha especificado un rol valido";
                $msg['status'] = 5;
            }
        } catch(\Exception $e){
            $msg['msg'] = $e->getMessage();
            $msg['status'] = 0;
        }

        return response()->json($msg);
    }

    /**
     * Iniciar sesión con un usuario
     * Genera un TOKEN
     */
    public function login(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('email')){
            $email = $req->input('email');
        } else {
            $email = "";
        }
        if($req->has('pswd')){
            $pswd = $req->input('pswd');
        } else {
            $pswd = "";
        }

        try {
            $checkEmail = DB::table('users')
                            ->where('email', '=', $email)
                            ->first();
            $checkPswd = DB::table('users')
                            ->where('password', '=', $pswd)
                            ->first();
            $checkUser = DB::table('users')
                            ->where('email', '=', $email)
                            ->where('password', '=', $pswd)
                            ->first();
            if(!$checkEmail){
                $response['status'] = 2;
                $response['msg'] = "Introduce un email valido";
            } else if(!$checkPswd || !$checkUser){
                $response['status'] = 3;
                $response['msg'] = "Contraseña incorrecta";  
            } else if ($checkUser){
                $user = User::find($checkUser->id);
                $user->api_token = Str::random(60);
                $user->save();
                
                $response['msg'] = "Usuario logeado";
                $response['query'] = DB::table('users')
                            ->where('id', '=', $checkUser->id)
                            ->select('email', 'api_token')
                            ->get();
            }

        }catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($response);
    }

    /**
     * Restablecer contraseña del usuario
     * Requiere introducir un email, genera una contraseña aleatoria y la envía por correo electrónico
     * TODO: Enviar correo electrónico
     */
    public function resetPassword(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('email')){
            $email = $req->input('email');
        } else {
            $email = "";
        }

        $user = User::where('email', $req->email)->first();

        if($user){
            $user->password = Str::random(12);
            $user -> save();

            // TODO: Enviar por email la contraseña

            $response["msg"] = "Se ha enviado una nueva contraseña temporal por email."; 
            return response()->json($response);
        } else {
            $response["status"] = 2;
            $response["msg"] = "No se ha encontrado el correo electronico introducido."; 
            return response()->json($response);
        }
    }

    /**
     * Listar todos los usuarios de la empresa
     * Únicamente puden listar usuarios los directivos y RRHH, gestionado por Middlware "checkUserRole"
     * Directivos ven a todos los usuarios excepto a otros directivos
     * RRHH ven a todos los usuarios excepto los de RRHH y directivos
     */
    public function list(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        
        try {
            $user = User::where('api_token', $req->token)->first();

            if ($user->role == "directive"){
                $query = DB::table('users')
                            ->select('name', 'role', 'salary')
                            ->whereIn('role', array('users', 'hr'))
                            ->get();
                $response["status"] = 2;
            } else {
                $query = DB::table('users')
                            ->select('name', 'role', 'salary')
                            ->where('role', '=', 'users')
                            ->get();
                $response["status"] = 3;                            
            }
            $response['msg'] = $query;
        } catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
        return response()->json($response); 
    }

    /**
     * Ver un perfil de un usuario de la empresa
     * Lista nombre, email, puesto, biografía y salario.
     * Únicamente pueden ver un perfil ajeno los usuarios directivos y de RRHH
     * Los directivos ven todos los usuarios excepto a otros usuarios directivos.
     * RRHH ve a todos los usuarios excepto a otros usuarios de RRHH y directivos.
     * El usuario directivo o de RRHH puede ver su propio perfil
     */
    public function view(Request $req){
        $response = ["status" => 1, "msg" => ""];

        if($req->has('token')){
            $token = $req->input('token');
        } else {
            $token = "";
        }
        if($req->has('profileId')){
            $profileId = $req->input('profileId');
        } else {
            $profileId = "";
        }
        
        try {
            $user = User::where('api_token', $req->token)->first();
            $profile = User::where('id', $profileId)->first();

            if ($user->role == "directive"){
                if($profile->role != "directive" || $profile->id == $user->id){
                    $query = User::where('id', $profileId)
                                    ->select('name', 'email', 'role', 'biography', 'salary')
                                    ->first();
                    $response['msg'] = $query;
                } else {
                    $response['status'] = 3;
                    $response['msg'] = "Acceso denegado.";
                }
            } else {
                if($profile->role != "hr" && $profile->role != "directive" || $profile->id == $user->id){
                    $query = User::where('id', $profileId)
                                    ->select('name', 'email', 'role', 'biography', 'salary')
                                    ->first();
                    $response['msg'] = $query;
                } else {
                    $response['status'] = 3;
                    $response['msg'] = "Acceso denegado.";
                }                        
            }
            
        } catch(\Exception $e){
            $response['msg'] = $e->getMessage();
            $response['status'] = 0;
            $response['msg'] = "Se ha producido un error: ".$e->getMessage();
        }
        return response()->json($response); 
    }

    /**
     * Comprobar si la contraseña es segura
     * Param: $password -> string
     */
    function CheckPassword($password){
        $pattern = '/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{6,}/';

        if(preg_match($pattern, $password)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Comprobar si se ha introducido un email válido
     * Param: $email -> string
     */
    function CheckEmail($email){
        if(filter_var($email, FILTER_VALIDATE_EMAIL)){
            return true;
        } else {
            return false;
        }
    }
}
