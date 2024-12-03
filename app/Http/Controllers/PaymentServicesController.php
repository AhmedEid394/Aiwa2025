<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\GeideaPayment;
use App\Models\GeideaOrder;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PaymentServicesController extends Controller
{
    /**
     * Geidea API configuration
     */
    private const MERCHANT_PUBLIC_KEY = 'be52d857-7d8c-428f-ab60-98e43073a744';
    private const API_PASSWORD = 'bafc3a69-0f31-4e4b-99dd-febc572305b8';
    private const BASE_URL = 'https://api.merchant.geidea.net';

    /**
     * Fee percentages as constants for easier maintenance
     */
    private const BM_FEE_PERCENTAGE = 0.125;
    private const GEIDEA_FEE_PERCENTAGE = 0.1;
    private const AIWA_FEE_PERCENTAGE = 0.125;
    private const TAX_PERCENTAGE = 0.14;

    /**
     * Calculate fees based on amount and percentage
     *
     * @param float $amount
     * @param float $feePercentage
     * @return float
     */
    private function calculateFees(float $amount, float $feePercentage): float
    {
        return round($amount * $feePercentage, 2);
    }

    /**
     * Generate signature for Geidea API request
     *
     * @param string $amount
     * @param string $currency
     * @param string $merchantReferenceId
     * @param string $timestamp
     * @return string
     */
    private function generateSignature(string $amount, string $currency, string $merchantReferenceId, string $timestamp): string
    {
        $data = self::MERCHANT_PUBLIC_KEY . $amount . $currency . $merchantReferenceId . $timestamp;
        $hash = hash_hmac('sha256', $data, self::API_PASSWORD, true);
        return base64_encode($hash);
    }

    /**
     * Prepare session data for Geidea API
     *
     * @param float $amount
     * @param float $totalAmount
     * @return array
     */
    private function prepareSessionData(float $amount, float $totalAmount): array
    {
        $timestamp = now()->format('m/d/Y h:i:s A');
        $amountStr = number_format($totalAmount, 2, '.', '');
        $orderCurrency = 'EGP';
        $merchantReferenceId = "10";
        $callbackUrl = config('app.payment_callback_url', "http://127.0.0.1:8000/api/payment/callback");

        return [
            'amount' => $amountStr,
            'currency' => $orderCurrency,
            'timestamp' => $timestamp,
            'merchantReferenceId' => $merchantReferenceId,
            'signature' => $this->generateSignature($amountStr, $orderCurrency, $merchantReferenceId, $timestamp),
            'paymentOperation' => 'Pay',
            'language' => 'en',
            'callbackUrl' => 'https://apistage.aiwagroup.org/Paymentservices/GeideaPay',
            'returnUrl' => "https://apistage.aiwagroup.org/Paymentservices/GeideaPay",
            'customer' => [
                'email' => auth()->user()->email,
                'phoneNumber' => auth()->user()->phone
            ]
        ];
    }

    /**
     * Create payment session
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createSession(Request $request)
    {
        try {
            // Validate input
            $validatedData = $this->validateRequest($request);

            // Retrieve booking
            $reservation = Booking::where('booking_id',$validatedData['Booking_id'])->first();

            // Calculate fees and total amount
            $amount = $validatedData['amount'];
            $fees = $this->calculateAllFees($amount);

            // Prepare session data for API
            $sessionData = $this->prepareSessionData($amount, $fees['totalAmount']);

            // Send request to Geidea API
            $response = $this->sendGeideaRequest($sessionData);

            // Process API response
            return $this->processGeideaResponse($response, $validatedData, $reservation, $amount, $fees);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate incoming request
     *
     * @param Request $request
     * @return array
     */
    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'Booking_id' => 'required',
            'user_send_id' => 'required',
            'user_receive_id' => 'required',
            'amount' => 'required|numeric|min:0'
        ]);
    }

    /**
     * Calculate all applicable fees
     *
     * @param float $amount
     * @return array
     */
    private function calculateAllFees(float $amount): array
    {
        $bmFees = $this->calculateFees($amount, self::BM_FEE_PERCENTAGE);
        $geideaFees = $this->calculateFees($amount, self::GEIDEA_FEE_PERCENTAGE);
        $aiwaFees = $this->calculateFees($amount, self::AIWA_FEE_PERCENTAGE);
        $tax = $this->calculateFees($amount, self::TAX_PERCENTAGE);

        $totalAmount = $amount + $bmFees + $geideaFees + $aiwaFees + $tax;

        return [
            'bmFees' => $bmFees,
            'geideaFees' => $geideaFees,
            'aiwaFees' => $aiwaFees,
            'tax' => $tax,
            'totalAmount' => $totalAmount
        ];
    }

    /**
     * Send request to Geidea API
     *
     * @param array $sessionData
     * @return mixed
     */
    private function sendGeideaRequest(array $sessionData)
    {
        try {
            // Initialize cURL
            $ch = curl_init(self::BASE_URL . '/payment-intent/api/v2/direct/session');
    
            // Prepare headers
            $headers = [
                'Authorization: Basic ' . base64_encode(self::MERCHANT_PUBLIC_KEY . ':' . self::API_PASSWORD),
                'Accept: application/json',
                'Content-Type: application/json'
            ];
    
            // Prepare JSON payload
            $jsonPayload = json_encode($sessionData);
    
            // Set cURL options
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30, // 30 seconds timeout
                CURLOPT_FAILONERROR => false, // Don't fail on HTTP error codes
                CURLOPT_SSL_VERIFYPEER => true, // Verify SSL certificate
                CURLOPT_SSL_VERIFYHOST => 2, // Verify hostname in SSL certificate
            ]);
    
            // Execute request
            $response = curl_exec($ch);
    
            // Check for cURL errors
            if ($response === false) {
                $curlError = curl_error($ch);
                $curlErrno = curl_errno($ch);
    
                // Log the cURL error
                Log::error('Geidea API cURL Error', [
                    'error_message' => $curlError,
                    'error_number' => $curlErrno
                ]);
    
                curl_close($ch);
    
                return [
                    'status' => 'error',
                    'message' => 'API Request Failed',
                    'curl_error' => $curlError,
                    'curl_errno' => $curlErrno
                ];
            }
    
            // Get HTTP status code
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            // Close cURL resource
            curl_close($ch);
    
            // Decode JSON response
            $responseData = json_decode($response, true);
    
            // Additional error checking
            if ($httpCode !== 200) {
                Log::warning('Geidea API Non-200 Response', [
                    'http_code' => $httpCode,
                    'response' => $responseData ?? $response
                ]);
    
                return [
                    'status' => 'error',
                    'message' => 'API Request Failed',
                    'http_code' => $httpCode,
                    'response' => $responseData ?? $response
                ];
            }
    
            // Validate response structure
            if (!isset($responseData['session'])) {
                Log::error('Invalid API Response Structure', [
                    'response' => $responseData
                ]);
    
                return [
                    'status' => 'error',
                    'message' => 'Invalid API Response',
                    'raw_response' => $responseData
                ];
            }
    
            return $responseData;
    
        } catch (\Exception $e) {
            // Catch any unexpected errors
            Log::error('Unexpected Error in Geidea API Request', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
    
            return [
                'status' => 'error',
                'message' => 'Unexpected error occurred',
                'exception_message' => $e->getMessage()
            ];
        }
    }

    /**
     * Process Geidea API response
     *
     * @param array $responseData
     * @param array $validatedData
     * @param Booking $reservation
     * @param float $amount
     * @param array $fees
     * @return \Illuminate\Http\JsonResponse
     */
    private function processGeideaResponse(array $responseData, array $validatedData, Booking $reservation, float $amount, array $fees)
    {    
        if ($responseData['responseMessage'] === 'Success') {

            $paymentRecord = GeideaPayment::create([
                'session_id' => $responseData['session']['id'],
                'service_amount' => $amount,
                'bm_fees' => $fees['bmFees'],
                'geidea_fees' => $fees['geideaFees'],
                'aiwa_fees' => $fees['aiwaFees'],
                'tax_14_percent' => $fees['tax'],
                'total_amount' => $fees['totalAmount'],
                'cash_in' => true,
                'reservation_id' => $reservation->booking_id,
                'user_send_id' => $validatedData['user_send_id'],
                'user_receive_id' => $validatedData['user_receive_id'],
                'payment_intent_id' => $responseData['session']['paymentIntentId'],
                'merchant_reference_id' => $responseData['session']['merchantReferenceId']
            ]);

            return response()->json([
                'status' => 'success',
                'sessionId' => $responseData['session']['id']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $responseData['session']['responseMessage']
        ], 400);
    }

    /**
 * Handle Geidea payment callback
 *
 * @param Request $request
 * @return \Illuminate\Http\Response
 */
public function paymentCallback(Request $request)
{
    try {
        $sessionId = $request->input('sessionId');
        $orderId = $request->input('orderId');
        $responseCode = $request->input('responseCode');
        $responseMessage = $request->input('responseMessage');

        // Fetch order details from Geidea using the existing cURL method
        $response = $this->fetchGeideaOrderDetails($orderId);

        // Check if the response was successful
        if (isset($response['status']) && $response['status'] === 'error') {
            throw new \Exception('Failed to fetch order details');
        }

        // Update or create Geidea order record
        $geideaOrder = GeideaOrder::updateOrCreate(
            ['order_id' => $orderId],
            [
                'session_id' => $sessionId,
                'total_amount' => $response['order']['totalAmount'],
                'currency' => $response['order']['currency'],
                'status' => $response['order']['status'],
                'detailed_status' => $response['order']['detailedStatus'],
                'response_code' => $responseCode,
                'payment_method_type' => $response['order']['paymentMethod']['type'],
                'payment_method_brand' => $response['order']['paymentMethod']['brand']
            ]
        );

        // Find the associated payment and reservation
        $payment = GeideaPayment::where('session_id', $sessionId)->first();
        if ($payment) {
            $reservation = Booking::find($payment->reservation_id);
            
            // Update reservation status based on payment status
            if ($response['order']['status'] === 'Paid') {
                $reservation->status = 'accepted';
                $reservation->save();
            }
        }

    } catch (\Exception $e) {
        // Log error
        Log::error('Payment Callback Error: ' . $e->getMessage());
        
        return response()->json(['status' => 'error'], 500);
    }
}

/**
 * Fetch Geidea order details using existing cURL method
 *
 * @param string $orderId
 * @return array
 */
private function fetchGeideaOrderDetails(string $orderId): array
{
    try {
        // Prepare request data similar to the existing sendGeideaRequest method
        $timestamp = now()->format('m/d/Y h:i:s A');
        $merchantReferenceId = "10";
        $signature = $this->generateSignature('', 'EGP', $merchantReferenceId, $timestamp);

        // Initialize cURL
        $ch = curl_init(self::BASE_URL . "/pgw/api/v1/direct/order/{$orderId}");

        // Prepare headers
        $headers = [
            'Authorization: Basic ' . base64_encode(self::MERCHANT_PUBLIC_KEY . ':' . self::API_PASSWORD),
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        // Execute request
        $response = curl_exec($ch);

        // Check for cURL errors
        if ($response === false) {
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);

            // Log the cURL error
            Log::error('Geidea API Order Fetch cURL Error', [
                'error_message' => $curlError,
                'error_number' => $curlErrno
            ]);

            curl_close($ch);

            return [
                'status' => 'error',
                'message' => 'API Request Failed',
                'curl_error' => $curlError,
                'curl_errno' => $curlErrno
            ];
        }

        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Close cURL resource
        curl_close($ch);

        // Decode JSON response
        $responseData = json_decode($response, true);

        // Additional error checking
        if ($httpCode !== 200) {
            Log::warning('Geidea API Order Fetch Non-200 Response', [
                'http_code' => $httpCode,
                'response' => $responseData ?? $response
            ]);

            return [
                'status' => 'error',
                'message' => 'API Request Failed',
                'http_code' => $httpCode,
                'response' => $responseData ?? $response
            ];
        }

        // Validate response structure
        if (!isset($responseData['order'])) {
            Log::error('Invalid API Order Response Structure', [
                'response' => $responseData
            ]);

            return [
                'status' => 'error',
                'message' => 'Invalid API Response',
                'raw_response' => $responseData
            ];
        }

        return $responseData;

    } catch (\Exception $e) {
        // Catch any unexpected errors
        Log::error('Unexpected Error in Geidea API Order Fetch', [
            'error_message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'status' => 'error',
            'message' => 'Unexpected error occurred',
            'exception_message' => $e->getMessage()
        ];
    }
}


}