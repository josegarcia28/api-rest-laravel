<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Category;

class CategoryController extends Controller
{
    public function __construct() {
        $this->middleware('api.auth',['except' => ['index','show']]);
    }
    public function pruebas(Request $request) {
        return "Accion de pruebas de CATEGORY-CONTROLLER";
    }

    public function index() {
        $categories = Category::all();

        return response()->json([
            'code'      => 200,
            'status'    => 'success',
            'categories'=> $categories
        ]);
    }

    public function show($id){
        $category = Category::find($id);

        if(is_object($category)) {
            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'category'=> $category
            );
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'categories'=> 'La categoria no existe'
            );
        }

        return response()->json($data, $data['code']);
    }

    public function store(Request $request) {
        // Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){

            // Validar los datos
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            // Guardar las categorias
            if($validate->fails()){
                $data = array (
                    'code'      => 404,
                    'status'    => 'error',
                    'categories'=> 'No se ha guardado la categoria'
                );
            } else {
            $category = new Category();
            $category->name = $params_array['name'];
            $category->save();

            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'category'=> $category
                );
            }
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'categories'=> 'No se ha enviado ninguna categoria'
            );
        }
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request) {
        // recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
        // Validar los datos
             $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

        // Quitar los datos que no voy a utilizar
            unset($params_array['id']);
            unset($params_array['created_at']);

            //Actualizar el registro (categoria)
            $category = Category::where('id', $id)->update($params_array);
            
            $data = array (
                'code'      => 200,
                'status'    => 'success',
                'category'=> $params_array
                );
        } else {
            $data = array (
                'code'      => 404,
                'status'    => 'error',
                'categories'=> 'No se ha enviado ninguna categoria'
            );
        }
        return response()->json($data, $data['code']);
    }
}
