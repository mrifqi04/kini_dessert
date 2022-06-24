<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\CartRepository;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\BankAccount;
use Kavist\RajaOngkir\Facades\RajaOngkir;

class CheckoutController extends Controller
{
    private $api_key = '81e6e26ae728c50249f5447ed6e449a8';

    public function form(Request $request, CartRepository $cart)
    {
        $user = auth()->user();

        $daftarProvinsi = RajaOngkir::provinsi()->all();        

        return view('front.pages.checkout.form', [
            'items' => $cart->all(),
            'addresses' => $user->member_addresses,
            'daftarProvinsi' => $daftarProvinsi
        ]);
    }

    public function submit(Request $request, CartRepository $cart)
    {
        $this->validate($request, [
            'member_address_id' => 'required|exists:member_addresses,member_address_id'
        ]);

        $user = auth()->user();
        $address = $user->member_addresses()->where('member_address_id', $request->get('member_address_id'))->first();
        if (!$address) {
            return back()->with('danger', "Alamat tidak terdaftar.");
        }

        $items = $cart->all();
        if (!count($items)) {
            return back()->with('danger', 'Keranjang belanja anda kosong.');
        }        

        $order = new Order;
        $order->code = Order::generateCode();
        $order->member_user_id = $user->user_id;
        $order->member_address_id = $address->member_address_id;
        $order->shipping_cost = $request->opKirim;
        $order->phone = $address->phone;
        $order->province_id = $address->province_id;
        $order->regency_id = $address->regency_id;
        $order->district_id = $address->district_id;
        $order->subdistrict_id = $address->subdistrict_id;
        $order->address = $address->address;
        $order->status = Order::STATUS_PENDING;
        $order->save();

        foreach ($items as $item) {
            $order_detail = new OrderDetail;
            $order_detail->order_id = $order->order_id;
            $order_detail->product_id = $item['product']->product_id;
            $order_detail->price = $item['product']->price;
            $order_detail->qty = $item['qty'];
            $order_detail->save();

            $getProductId = $item['product']->product_id;
            $product = Product::find($getProductId);
            $product->stock = 0;
            $product->save();
        }

        $cart->clear();

        return redirect()->route('front::checkout.success', ['order_code' => $order->code]);
    }

    public function success(Request $request, $order_code)
    {
        $user = auth()->user();
        $order = $user->orders()->where('code', $order_code)->first();
        if (!$order) {
            return abort(404, "Pesanan tidak ditemukan");
        }

        $bank_accounts = BankAccount::all();

        return view('front.pages.checkout.success', [
            'order' => $order,
            'bank_accounts' => $bank_accounts,
        ]);
    }

   public function getRegencies(Request $request)
   {    
    return response()->json([
        'data' => RajaOngkir::kota()->dariProvinsi($request->province)->get(),
    ]);
   }

    public function getOngkos(Request $request)
    {
		$output = '<option value="">-- Opsi Layanan --</option>';

        $get_ongkir = RajaOngkir::ongkosKirim([
            'origin'        => 154,     // ID kota/kabupaten asal
            'destination'   => $request->city,      // ID kota/kabupaten tujuan
            'weight'        => 1300,    // berat barang dalam gram
            'courier'       => $request->opKirim    // kode kurir pengiriman: ['jne', 'tiki', 'pos'] untuk starter
        ])->get();
        
        // foreach ($get_ongkir as $cty) {            
        //     foreach ($cty['costs'] as $tarif) {
        //         // dd($tarif);         
		// 		$output .= '<option value="' . $tarif . '">Estimasi hari Harga ' . $tarif['cost'] . ' </option>';
		// 		// $output .= '<option value="' . $tarif['value'] . '">' . $cty['service'] . ' Estimasi ' . $tarif['etd'] . ' hari Harga ' . $tarif['value'] . '</option>';
		// 	}
		// }

        // foreach ($get_ongkir as $cty) {            
        //     foreach ($cty['costs'] as $tarif) {
        //         foreach ($tarif['cost'] as $cost) {
		// 		    $output .= '<option value="' . print_r($cost['value']) . '">Estimasi hari Harga ' . $tarif['cost'] . ' </option>';                    
        //         }
		// 	}
		// }

		return response()->json($get_ongkir);
    }
}
