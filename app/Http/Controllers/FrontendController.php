<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Size;
use App\Models\Color;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function Ramsey\Uuid\v1;

class FrontendController extends Controller
{
    //
    public function  welcome()
    {
        return view('frontend.welcome');
    }

    function index(){
        $products = Product::latest()->take(6)->get();
        $categories = Category::all();
        $new_arrival = Product::latest()->take(4)->get();
        return view('frontend.index', [
            'products'=>$products,
            'categories'=>$categories,
            'new_arrival'=>$new_arrival,
        ]);
    }

    function product_details($product_id){
        $product_info = Product::find($product_id);
        $related_products = Product::where('id', '!=', $product_id)->where('category_id', $product_info->category_id)->get();
        $available_colors = Inventory::where('product_id', $product_id)->groupBy('color_id')->selectRaw('count(*) as total, color_id')->get();
        $reviews = OrderProduct::where('product_id', $product_id)->whereNotNull('review')->get();
        $total_review = OrderProduct::where('product_id', $product_id)->whereNotNull('review')->count();
        $total_star = OrderProduct::where('product_id', $product_id)->whereNotNull('review')->sum('star');

        return view('frontend.product_details', [
            'product_info'=>$product_info,
            'available_colors'=>$available_colors,
            'related_products'=>$related_products,
            'reviews'=>$reviews,
            'total_review'=>$total_review,
            'total_star'=>$total_star,
        ]);
    }

    function getSize(Request $request){
        $str = '<option value="">Choose A Option</option>';
        $sizes = Inventory::where('product_id', $request->product_id)->where('color_id', $request->color_id)->get();
        foreach($sizes as $size){
            $str .= '<option value="'.$size->size_id.'">'.$size->rel_to_size->size_name.'</option>';
        }
        echo $str;
    }

    function review(Request $request){
        OrderProduct::where('user_id', Auth::guard('customerlogin')->id())->where('product_id', $request->product_id)->update([
            'review'=>$request->review,
            'star'=>$request->star,
            'updated_at'=>Carbon::now(),
        ]);
        return back();
    }


    function shop(Request $request){
        $data = $request->all();

        $all_products = Product::where(function ($q) use ($data){
            if(!empty($data['q']) && $data['q'] != '' && $data['q'] != 'Open this select menu'){
                $q->where(function ($q) use ($data){
                    $q->where('product_name', 'like', '%'.$data['q'].'%');
                    $q->orWhere('short_desp', 'like', '%'.$data['q'].'%');
                });
            }

            if(!empty($data['category_id']) && $data['category_id'] != '' && $data['category_id'] != 'Open this select menu'){
                    $q->where('category_id', 'like', '%'.$data['category_id'].'%');
            }

            if(!empty($data['price_range']) && $data['price_range'] != '' && $data['price_range'] != 'Open this select menu'){
                $price_range =  explode('-', $data['price_range']);
                $q->whereBetween('after_discount', [$price_range[0], $price_range[1]]);
        }

            if(!empty($data['color_id']) && $data['color_id'] != '' && $data['color_id'] != 'undefined' || !empty($data['size_id']) && $data['size_id'] != '' && $data['size_id'] != 'Open this select menu'){
                $q->whereHas('rel_to_inventories', function($q) use ($data){

                   if(!empty($data['color_id']) && $data['color_id'] != '' && $data['color_id'] != 'Open this select menu'){
                      $q->whereHas('rel_to_color', function($q) use ($data){
                        $q->where('colors.id', $data['color_id']);
                      });
                   }

                   if(!empty($data['size_id']) && $data['size_id'] != '' && $data['size_id'] != 'Open this select menu'){
                    $q->whereHas('rel_to_size', function($q) use ($data){
                      $q->where('sizes.id', $data['size_id']);
                    });
                 }

                });

        }

        })->get();



        $categories = Category::all();
        $sizes = Size::all();
        $colors = Color::all();
        return view('frontend.shop',[
            'all_products'=>$all_products,
            'categories'=>$categories,
            'sizes'=>$sizes,
            'colors'=>$colors,
        ]);
    }


}
