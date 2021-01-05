<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth',['except' => [
            'index',
            'show',
            'getImagen',
            'getPostsByCategory',
            'getPostsByUser'
        ]]);
    }

    public function index(){
        $posts = Post::all()->load('category');

        return response()->json([
            'code'      => 200,
            'status'    => 'success',
            'posts'     => $posts
        ]);

    }

    public function show($id){
        $post = Post::find($id)->load('category')
                               ->load('user');

        if(is_object($post)){
            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'posts'=> $post
            );
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'message'=> 'La entrada no existe'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        $json = request()->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
            /// Conseguir usuarios identificados
            $user = $this->getIdentity($request);

            //Validar los datos
            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required',
                'image'   => 'required'
            ]);
    

            if($validate->fails()){
                $data = array (
                    'code'      => 404,
                    'status'    => 'error',
                    'message'=> 'No se pudo guardar el post, faltan datos'
                );
            } else {
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();
    
                $data = array (
                    'code'      => 200,
                    'status'    => 'success',
                    'post'=> $post
                );

            }
            // var_dump($data);
            // die();


            // Guardar el Post     
            
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'message'=> 'Envia los datos correctamente'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {
        // Recoger datos del usuario
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        $data = array (
            'code'      => 404,
            'status'    => 'error',
            'message'=> 'Envia los datos correctamente'
        );

        if(!empty($params_array)){
            // Validar los datos
            $validate = \Validator::make($params_array, [
                'title'         => 'required',
                'content'       => 'required',
                'category_id'   => 'required'
            ]);

            if($validate->fails()){
                $data['error'] = $validate->errors();
                return response()->json($data, $data['code']);
            } else {
    
                // Eliminar datos que no seran actualizados
                unset($params_array['id']);
                unset($params_array['user_id']);
                unset($params_array['created_at']);
                unset($params_array['user']);

                 /// Conseguir usuarios identificados
                $user = $this->getIdentity($request);

                // conseguir el registro
                $post = Post::where('id', $id)
                                    ->where('user_id', $user->sub)
                                    ->first();

                if(!empty($post) && is_object($post)){
                    // Actualizar registro
                    $post->update($params_array);

                    $data = array (
                        'code'      => 200,
                        'status'    => 'success',
                        'post'      => $post,
                        'changes'=> $params_array
                    );
                }

                /*
                // condicion
                $where = [
                    'id' => $id,
                    'user_id' => $user->sub
                ];
                
                // Actualizar registro
                $post = Post::updateOrCreate($where, $params_array); // Actualiza los registros que no esten definido en la eliminacion
        
                */

                
            }

        } 
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request) {
         /// Conseguir usuarios identificados
        $user = $this->getIdentity($request);

        // conseguir el registro
        $post = Post::where('id', $id)
                            ->where('user_id', $user->sub)
                            ->first();

        if(!empty($post)){
            // Borrarlo
            $post->delete();
    
            // devolver algo
            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'post'      => $post
            );

        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'message'=> 'No se encontro el registro a eliminar'
            );
        }


        return response()->json($data, $data['code']);
    
    }

    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request) {
        //Recoger la imagen de la peticion
        $image = $request->file('file0');

        //Validar imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);

        if(!$image || $validate->fails()) {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'message'=> 'Error al subir la imagen'
            );
        } else {
            $image_name = time().$image->getClientOriginalName();
            
            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'image'     => $image_name
            );
        }
        
        return response()->json($data, $data['code']);
    }

    public function getImagen($filename){
        // comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            //conseguir la imagen
            $file = \Storage::disk('images')->get($filename);

            return new Response($file, 200);
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'message'=> 'Error la imagen no existe'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $post = Post::where('category_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts'  => $post
        ], 200);
    }

    public function getPostsByUser($id){
        $post = Post::where('user_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts'  => $post
        ], 200);
    }

    
}
