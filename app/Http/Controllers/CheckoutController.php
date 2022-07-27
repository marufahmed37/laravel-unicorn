<?php

namespace App\Http\Controllers;

use App\Mail\SendInVoiceMail;
use App\Models\BillingDetails;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Mail;

class CheckoutController extends Controller
{
    function checkout(){
       $countries = Country::all();
       return view('frontend.checkout',[
        'countries'=>$countries,
       ]);

    }

function get_city(Request $request){
   $cities =  City::where('country_id', $request->country_id)->select('name', 'id')->get();
   $str = '<option value=""> Select a City </option>';
   foreach($cities as $city){
     $str .= '<option value="'.$city->id.'">'.$city->name.'</option>';
   }
   echo $str;
}

function checkout_insert(Request $request){
    if($request->payment_method == 1){
        $order_id = Order::insertGetId([
            'user_id'=>Auth::guard('customerlogin')->id(),
            'sub_total'=>$request->sub_total,
            'discount'=>$request->discount,
            'delivery_charge'=>$request->del_charge,
            'total'=>$request->sub_total+$request->del_charge - ($request->discount),
            'created_at'=>Carbon::now(),
        ]);


        BillingDetails::insert([
            'user_id' => Auth::guard('customerlogin')->id(),
            'order_id'=> $order_id,
            'name'=> $request->name,
            'email'=> $request->email,
            'phone'=> $request->phone,
            'country_id'=> $request->country_id,
            'city_id'=> $request->city_id,
            'address'=> $request->address,
            'company'=> $request->company,
            'notes'=> $request->notes,
            'created_at'=>Carbon::now(),

        ]);

        $carts = Cart::where('customer_id', Auth::guard('customerlogin')->id())->get();

        foreach($carts as $cart){
            Orderproduct::insert([
                'user_id' => Auth::guard('customerlogin')->id(),
                'order_id'=>$order_id,
                'product_id'=>$cart->product_id,
                'price'=>$cart->rel_to_product->after_discount,
                'quantity'=>$cart->quantity,
                'created_at'=>Carbon::now(),
            ]);
        }
// SEND E-mail......................................
        Mail::to($request->email)->send(new SendInVoiceMail($order_id));


$total_amount = $request->sub_total+$request->del_charge - ($request->discount);
//POST Method SMS SEND.............................
        $url = "http://66.45.237.70/api.php";
        $number= $request->phone;
        $text="Thank you for order our product!!, your total amount is :".$total_amount;
        $data= array(
        'username'=>"maruf123",
        'password'=>"5D9GVBKH",
        'number'=>"$number",
        'message'=>"$text"
        );

        $ch = curl_init(); // Initialize cURL
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsresult = curl_exec($ch);
        $p = explode("|",$smsresult);
        $sendstatus = $p[0];


        foreach($carts as $cart){
            Inventory::where('product_id', $cart->product_id)->where('color_id', $cart->color_id)->where('size_id', $cart->size_id)->decrement('quantity', $cart->quantity);
        }

        foreach($carts as $cart){
            Cart::find($cart->id)->delete();
        }

        return redirect()->route('order.success')->with('order_success','YOUR ORDER HAS BEEN PLACED!!');

    }
    elseif($request->payment_method == 2){
        $data = $request->all();
        return view('exampleHosted',[
            'data'=>$data,
        ]);
    }
    else{
        $data = $request->all();
        return view('stripe',[
            'data'=>$data,
        ]);
    }
}

function order_success(){
    if(session('order_success')){
        return view('frontend.order_success');
    }
    else{
        abort('404');
    }

}



}
