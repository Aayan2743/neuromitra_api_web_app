<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Dipesh79\LaravelPhonePe\LaravelPhonePe;
use Validator;

class PhonePaycontroller extends Controller
{
    //

    public function phonePePayment(Request $request)
{

    $validtor=validator::make($request->all(),[
        'amount'=>'required|numeric',
        'phone'=>'required|digits:10',
        'transaction_id'=>'required'
    ]);

    if($validtor->fails()){
        return response()->json([
            'status'=>false,
            'message'=>$validtor->errors()->first()
        ]);
    };


    $transactionId = $request->transaction_id ?: uniqid('txn_');



// Initiate payment
$phonepe = new LaravelPhonePe();
$url = $phonepe->makePayment($request->amount, $request->phone, 'http://192.168.80.237:8000/api/redirct-url', $transactionId);


dd($url);
return redirect()->away($url);


}


public function phone(Request $request){
    $request->validate([
        'amount' => 'required|numeric|min:1',
        'mobile' => 'required|digits:10',
    ]);

    $merchantId = env('PHONEPE_MERCHANT_ID');
    $saltKey = env('PHONEPE_SALT_KEY');
    $saltIndex = env('PHONEPE_SALT_INDEX');
    $apiUrl = env('PHONEPE_API_URL');
    $amount = $request->amount * 100; // Convert to paise
    $merchantTransactionId = 'TXN' . time();
    $callbackUrl = env('PHONEPE_CALLBACK_URL');
    $redirectUrl = env('PHONEPE_REDIRECT_URL');

    $payload = [
        'merchantId' => $merchantId,
        'merchantTransactionId' => $merchantTransactionId,
        'merchantUserId' => 'MUID' . time(),
        'amount' => $amount,
        'redirectUrl' => $redirectUrl,
        'callbackUrl' => $callbackUrl,
        'mobileNumber' => $request->mobile,
        'paymentInstrument' => [
            'type' => 'PAY_PAGE'
        ]
    ];

    $jsonPayload = base64_encode(json_encode($payload));
    $checksum = hash('sha256', $jsonPayload . '/pg/v1/pay' . $saltKey) . '###' . $saltIndex;

    try {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-VERIFY' => $checksum,
        ])->post("{$apiUrl}/pg/v1/pay", [
            'request' => $jsonPayload
        ]);

        $result = $response->json();
        dd($result);
        if ($response->successful() && isset($result['success']) && $result['success'] && isset($result['data']['instrumentResponse']['redirectInfo']['url'])) {
           
            
            return redirect($result['data']['instrumentResponse']['redirectInfo']['url']);
        }

        Log::error('PhonePe Payment Initiation Failed', ['response' => $result]);
        return back()->withErrors(['error' => 'Payment initiation failed']);
    } catch (\Exception $e) {
        Log::error('PhonePe Payment Error', ['error' => $e->getMessage()]);
        return back()->withErrors(['error' => 'Payment initiation error']);
    }
}

public function callBackAction(Request $request)
{
  
    $transactionId = $request->input('transactionId'); // Or 'merchantTransactionId', depending on what PhonePe sends

    // Optional: You can also log or inspect $request->all() to see full payload
    \Log::info('PhonePe Callback:', $request->all());

    // Verify payment
    $phonepe = new LaravelPhonePe();
    $response = $phonepe->checkStatus($transactionId);

    if ($response['success'] && $response['data']['paymentStatus'] === 'COMPLETED') {
        // Payment successful
        // Update DB accordingly
        // $order = Order::where('transaction_id', $transactionId)->first();
        // $order->status = 'paid';
        // $order->save();

        return response()->json(['message' => 'Payment successful']);
    } else {
        // Payment failed or pending
        return response()->json(['message' => 'Payment failed or pending'], 400);
    }


}


}
