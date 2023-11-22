<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\product;
use Illuminate\Support\Facades\File;;

class ProductController extends Controller
{
    public function index(Request $request){
        $products = product::with('category')->latest();
        if(!empty($request->get('keyword'))){
            $products = $products->where('name','like','%'.$request->get('keyword').'%');
        }  

        $products = $products->paginate(10);
        
        return view('admin.product.list',compact('products'));
    
    }

    public function store(Request $request){
        $request->validate([
            'category'=>'required',
            'name'=>'required',
            'slug'=>'required|unique:product',
            'status'=>'required'
        ]);

        $product = new product();
        $product->category_id = $request->category;
        $product->name = $request->name;
        $product->slug = $request->slug;
        $product->status = $request->status;
        
        if($request->image){
            $newFileName = $request->image->getClientOriginalName();
            $request->image->move('images',$newFileName);
            $product->image = $newFileName;
        }
        $result = $product->save();

        if($result){
            return redirect()->route('product.create')->with('success','Product added Successfully');

        }else{

            return redirect()->route('product.create')->with("Fail','Product can't added.");
        }

    }

    public function destroy($id){
        $product = product::find($id);

        if (!is_null($product)) {
            $imageFilename = $product->image;
    
            if (!is_null($imageFilename)) {
                $imagePath = public_path('images/' . $imageFilename);
                
                
                if (File::exists($imagePath)) {
                    File::delete($imagePath);
                }
            }


            $product->delete();
            return redirect()->route('product.index')->with('success','Product successfully deleted.');
        }
        return redirect()->route('product.index')->with('fail',"Product con't deleted.");
  }
}




?>