<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class UserController extends Controller
{
    public function pruebas(Request $request) {
        return "Accion de pruebas de USER-CONTROLLER";
    }
    
    public function register(Request $request) {

        // recoger los datos enviados por post desde el frontend
        $json = $request->input('json', null);
        $params = json_decode($json); //objeto
        $params_array = json_decode($json, TRUE); //arreglo

        if(!empty($params) && !empty($params_array)) {

            // limpiar datos
            $params_array = array_map('trim', $params_array);
    
            // validar los datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', ///-> unique comprueba si el usuario existe
                'password'  => 'required'
            ]);
    
            if($validate->fails()) {
                $data = array(
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'el usuario no se ha creado directamente',
                    'error' => $validate->errors()
                );
            } else {
                // Validacion aceptada
                $pwd = hash('sha256', $params->password);

                // Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                // Guardar el usuario
                $user->save();

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'el usuario se ha creado correctamente',
                    'user' => $user
                );
            }
    
            
        } else {
            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'los datos enviados no son correctos',
                'post' => 'algo'
            );
        }

        return response() -> json($data, $data['code']);

    }

    public function login(Request $request) {
        $jwtAuth = new \JwtAuth();

        // Recibir los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        // Validar esos datos
        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required'
        ]);

        if($validate->fails()) {
            $signup = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'el usuario no se ha podido identificar',
                'error' => $validate->errors()
            );
        }else {
            // Cifrar la password
            $pwd = hash('sha256', $params->password);

            // Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);
            if(!empty($params->gettoken)){
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }
        }

        return response()->json($signup, 200);
    }

    public function update(Request $request) {
        // Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);


        if($checkToken && !empty($params_array)) {
            // Sacar usuarios identificados
            $user = $jwtAuth->checkToken($token, true);

            // Validar datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users'.$user->sub
            ]);

            // Quitar los campos que no quiero actualizar
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['password']);
            unset($params_array['created_a']);
            unset($params_array['remember_token']);

            // Actualizar el usuario en BD
            $user_update = User::where('id', $user->sub)->update($params_array);

            // Devolver array con resultados
            $data = array(
                'code'      => 200,
                'status'    => 'success',
                'user'      => $user,
                'changes'   => $params_array 
            );

        } else {
            $data = array(
                'code'    => 400,
                'status'  => 'error',
                'message' => 'el usuario no esta identificado' 
            );
        }

        return response()->json($data, $data['code']);
    }

    public function upload(Request $request) {
        //recoger datos de la peticion
        $imagen = $request->file('file0');

        //validar imagen
        $validar = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        //guardar imagen
        if(!$imagen || $validar->fails()){
            $data = array(
                'code'    => 400,
                'status'  => 'error',
                'message' => 'Error al subir archivo' 
            );
        } else {
            $imagen_name = time().$imagen->getClientOriginalName();
            \Storage::disk('users')->put($imagen_name, \File::get($imagen));
            
            $data = array(
                'code'    => 200,
                'status'  => 'success',
                'image' => $imagen_name 
            );
              
        }

        
        return response()->json($data, $data['code']);
    }

    public function getImagen($filename) {
        $isset = \Storage::disk('users')->exists($filename);

        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        } else {
            $data = array(
                'code'    => 400,
                'status'  => 'error',
                'message' => 'No existe la imagen' 
            );
            return response()->json($data, $data['code']);
        }

    }

    public function detail($id) {
        $user = User::find($id);

        if(is_object($user)){
            $data = array(
                'code'    => 200,
                'status'  => 'success',
                'user' => $user 
            );
        } else {
            $data = array(
                'code'    => 400,
                'status'  => 'error',
                'message' => 'El usuario no existe' 
            );
        }
        return response()->json($data, $data['code']);
    }
}
