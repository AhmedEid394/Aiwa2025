<?php

namespace App\Http\Controllers;

use App\Models\BmCashoutPrepare;

use App\Http\Controllers\SignerService;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class BmCashoutPrepareController extends Controller
{

    protected $signerService;

    public function __construct(SignerService $signerService)
    {
        $this->signerService = $signerService;
    }




    public function generateSignAndSendTransaction(Request $request)
    {
        DB::beginTransaction();

        try {
            // Validate the incoming request
            $validatedData = $this->validateTransactionRequest($request);
            $validatedData['CreditorName'] = $request->user()->f_name . ' ' . $request->user()->l_name;
            $validatedData['CreditorId'] = $request->user()->provider_id;

            // Create initial transaction record and get the generated IDs
            $transaction = $this->createInitialTransaction();

            if (!$transaction) {
                DB::rollBack(); // Rollback transaction if transaction creation fails
                return response()->json([
                    'status' => 'error',
                    'error' => 'Failed to create transaction record'
                ], 500);
            }

            // Refresh to get the trigger-generated IDs
            $transaction->refresh();

            // Prepare transaction data
            $transactionData = $this->prepareTransactionData($validatedData, $transaction);

            // Generate signature
            $signature = $this->signerService->generateSendTransactionSignature(
                $transactionData,
                config('services.bank_misr.private_key_path')
            );

            if (!$signature) {
                DB::rollBack(); // Rollback transaction if signature generation fails
                throw new \Exception('Failed to generate signature');
            }

            // Prepare final data for API
            $postData = $this->prepareFinalPostData($transactionData, $validatedData, $signature);

            // Save transaction details
            $this->saveTransactionDetails($transaction, $postData);

            // For testing, return prepared data without actual API call
            if (config('services.bank_misr.testing_mode', true)) {
                DB::commit(); // Commit transaction if all steps are successful
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'postData' => $postData,
                        'response' => [
                            'ResponseCode' => '8000',
                            'ResponseDescription' => 'Received and Validated Successfully',
                            'MessageId' => $postData['MessageId'],
                            'TransactionId' => $postData['TransactionId'],
                            'Signature' => $signature
                        ]
                    ]
                ]);
            }

            // Send data to bank API
            $response = $this->sendToBank($postData);

            // Process response and return formatted result
            if ($response['ResponseCode'] === '8000') {
                DB::commit(); // Commit transaction if bank response is successful
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'postData' => $postData,
                        'response' => $response
                    ]
                ]);
            } else {
                DB::rollBack(); // Rollback transaction if bank response is not successful
                return response()->json([
                    'status' => 'error',
                    'error' => $response['ResponseDescription']
                ], 400);
            }
        } catch (ValidationException $e) {
            DB::rollBack(); // Rollback transaction if validation fails
            return response()->json([
                'status' => 'error',
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback transaction for any other exception
            Log::error('Transaction processing failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'error' => 'Transaction processing failed'
            ], 500);
        }
    }

    /**
     * Validate the incoming transaction request
     */
    private function validateTransactionRequest(Request $request)
    {
        return $request->validate([
            'CorporateCode' => 'required|string',
            'DebtorAccount' => 'required|string',
            'CreditorAccountNumber' => 'required|string',
            'CreditorBank' => 'required|string',
            'TransactionAmount' => 'required|numeric|min:0',
            'Currency' => 'required|string|size:3',
//            'CreditorName' => 'required|string',
            'CategoryCode' => 'required|string',
//            'CreditorId' => 'required|string'
        ]);
    }

    /**
     * Create initial transaction record
     */
    private function createInitialTransaction()
    {
        return BmCashoutPrepare::create([]);
    }

    /**
     * Prepare transaction data for signature generation
     */
    private function prepareTransactionData($validatedData, $transaction)
    {
        return [
            'MessageId' => $transaction->message_id,
            'TransactionId' => $transaction->transaction_id,
            'CorporateCode' => 'AIWACORP',
            'DebtorAccount' => '7990001000002795',
            'CreditorAccountNumber' => $validatedData['CreditorAccountNumber'],
            'CreditorBank' => $validatedData['CreditorBank'],
            'TransactionAmount' => number_format((float) $validatedData['TransactionAmount'], 4, '.', ''),
            'Currency' => $validatedData['Currency'] ?? 'EGP'
        ];
    }

    /**
     * Prepare final data for API submission
     */
    private function prepareFinalPostData($transactionData, $validatedData, $signature)
    {
        return [
            'MessageId' => $transactionData['MessageId'],
            'TransactionId' => $transactionData['TransactionId'],
            'DebtorAccount' => $transactionData['DebtorAccount'],
            'Currency' => $transactionData['Currency'],
            'TransactionAmount' => sprintf('%09.2f', $transactionData['TransactionAmount']),
            'CreditorName' => $validatedData['CreditorName'],
            'CreditorAccountNumber' => $transactionData['CreditorAccountNumber'],
            'CreditorBank' => $transactionData['CreditorBank'],
            'CorporateCode' => $transactionData['CorporateCode'],
            'CategoryCode' => $validatedData['CategoryCode'] ?? 'CASH',
            'TransactionDateTime' => Carbon::now()->format('d/m/Y H:i:s'),
            'CreditorId' => $validatedData['CreditorId'],
            'Signature' => $signature
        ];
    }

    /**
     * Save transaction details to database
     */
    private function saveTransactionDetails($transaction, $postData)
    {
        $transactionAmount = (float) $postData['TransactionAmount'];
        $aiwaFees = $transactionAmount * 0.125;
        $finalAmount = $transactionAmount - $aiwaFees;
        $postData['TransactionAmount'] = $finalAmount;

        $transaction->update([
            'message_id' => $postData['MessageId'],
            'transaction_id' => $postData['TransactionId'],
            'debtor_account' => $postData['DebtorAccount'],
            'creditor_account_number' => $postData['CreditorAccountNumber'],
            'creditor_bank' => $postData['CreditorBank'],
            'transaction_amount' => $transactionAmount,
            'transaction_amount_aiwa_fees' => $aiwaFees,
            'final_transaction_amount' => $finalAmount,
            'currency' => $postData['Currency'],
            'creditor_name' => $postData['CreditorName'],
            'corporate_code' => $postData['CorporateCode'],
            'category_code' => $postData['CategoryCode'],
            'transaction_date_time' => Carbon::createFromFormat('d/m/Y H:i:s', $postData['TransactionDateTime']),
            'creditor_id' => $postData['CreditorId'],
            'signature' => $postData['Signature']
        ]);
        Transaction::create([
            'transaction_type' => 'cash_out',
            'amount' => $finalAmount,
            'status' => 'pending',
            'user_id' => $postData['CreditorId'],
            'user_type' => 'Provider',
            'transaction_reference'=>$postData['TransactionId']
        ]);
    }

    /**
     * Send data to Bank Misr API
     */
    private function sendToBank($postData)
    {
        try {
            $baseUrl = config('services.bank_misr.api_url');

            Log::info('Sending request to bank API', ['request_data' => $postData]);

            $response = Http::withoutVerifying()
                ->timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($baseUrl, $postData);

            $decodedResponse = json_decode($response->body(), true);

            if (is_string($decodedResponse) && str_starts_with(trim($decodedResponse), '{')) {
                $decodedResponse = json_decode($decodedResponse, true);
            }

            if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedResponse['ResponseCode'])) {
                Log::error('Invalid response from bank API', ['response' => $response->body()]);
                throw new \Exception('Invalid response structure from bank API');
            }

            $this->updateTransactionWithResponse($postData['TransactionId'], $decodedResponse['ResponseCode'], $decodedResponse['ResponseDescription']);

            return $decodedResponse;
        } catch (\Exception $e) {
            Log::error('Bank API request failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
    /**
     * Decode the API response and handle double-encoded JSON
     */
    private function decodeResponse($response)
    {
        try {
            $decodedResponse = $response->json();

            // Handle double-encoded JSON
            if (is_string($decodedResponse) && str_starts_with(trim($decodedResponse), '{')) {
                $decodedResponse = json_decode($decodedResponse, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON decode error: ' . json_last_error_msg());
                }
            }

            return $decodedResponse;

        } catch (\Exception $e) {
            Log::error('Failed to decode API response', [
                'original_response' => $response->body(),
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Invalid response format from bank API: ' . $e->getMessage());
        }
    }

    /**
     * Validate the response structure
     */
    private function validateResponseStructure($decodedResponse)
    {
        if (!is_array($decodedResponse)) {
            throw new \Exception('Invalid response type: expected array, got ' . gettype($decodedResponse));
        }

        if (!isset($decodedResponse['ResponseCode'])) {
            throw new \Exception('Missing required field: ResponseCode');
        }

        // Add any additional validation rules here
        return true;
    }

    /**
     * Get formatted error message from response
     */
    private function getErrorMessage($response)
    {
        try {
            $body = $response->json();

            return $body['ResponseDescription']
                ?? $body['message']
                ?? $body['error']
                ?? "HTTP {$response->status()}";

        } catch (\Exception $e) {
            return "HTTP {$response->status()}: Unable to parse error message";
        }
    }
    /**
     * Update transaction with API response
     */
    private function updateTransactionWithResponse($transactionId, $responseCode, $responseDescription)
    {
        BmCashoutPrepare::where('transaction_id', $transactionId)
            ->update([
                'response_code' => $responseCode,
                'response_description' => $responseDescription,
                'prepared_flag' => ($responseCode == 8000) ? (true) : (false)
            ]);
    }

    public function index()
    {
        $prepares = BmCashoutPrepare::with('status')->get();
        return response()->json($prepares);
    }

    public function show($id)
    {
        $prepare = BmCashoutPrepare::with('status')->find($id);
        if (!$prepare) {
            return response()->json(['error' => 'Cashout prepare not found'], 404);
        }
        return response()->json($prepare);
    }
}
