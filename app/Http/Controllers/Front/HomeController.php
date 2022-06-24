<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Prediction;
use App\Models\Product;
use App\Repositories\CartRepository;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {   
        if (Auth::user()) {
            $user = Auth::user()->user_id;
            $predictions = Prediction::where('user_id', $user)->with(['product', 'user'])->get();                                      
        } else {
            $predictions = [];
        }

        $popular_products = Product::inRandomOrder()->take(8)->get();
        $newest_products = Product::orderBy('product_id', 'desc')
        ->take(8)
        ->where('stock', '!=', 0)
        ->get();
        return view('front.pages.home', [
            'popular_products' => $popular_products,
            'newest_products' => $newest_products,
            'predictions' => $predictions         
        ]);
    }
}
