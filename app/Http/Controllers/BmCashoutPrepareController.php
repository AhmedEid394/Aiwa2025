<?php

namespace App\Http\Controllers;

use App\Models\BmCashoutPrepare;

use App\Http\Controllers\SignerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BmCashoutPrepareController extends Controller
{

    protected $signerService;

    public function __construct(SignerService $signerService)
    {
        $this->signerService = $signerService;
    }


    public function generateSignAndSendTransaction(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $this->validateTransactionRequest($request);


            // Create initial transaction record and get the generated IDs
            $transaction = $this->createInitialTransaction();

            if (!$transaction) {
                return response()->json(['error' => 'Failed to create transaction record'], 500);
            }

            // Refresh to get the trigger-generated IDs
            $transaction->refresh();

            // Prepare transaction data with the generated IDs
            $transactionData = $this->prepareTransactionData($validatedData, $transaction);

            // Generate signature
            $signature = $this->signerService->generateSendTransactionSignature(
                $transactionData,
                config('services.bank_misr.private_key_path')
            );

            if (!$signature) {
                throw new \Exception('Failed to generate signature');
            }

            // Prepare final data for API
            $postData = $this->prepareFinalPostData($transactionData, $validatedData, $signature);

            // Save transaction details
            $this->saveTransactionDetails($transaction, $postData);

            // For testing, return prepared data without actual API call
            if (config('services.bank_misr.testing_mode', true)) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Transaction prepared successfully',
                    'data' => $postData
                ]);
            }

            // Send to Bank Misr API
            $response = $this->sendToBank($postData);

            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Transaction processing failed: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Transaction processing failed'], 500);
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
            'CreditorName' => 'required|string',
            'CategoryCode' => 'required|string',
            'CreditorId' => 'required|string'
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
            'CorporateCode' => $validatedData['CorporateCode'] ?? 'AIWACORP',
            'DebtorAccount' => $validatedData['DebtorAccount'],
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
    }

    /**
     * Send data to Bank Misr API
     */
    private function sendToBank($postData)
    {
        $response = Http::withoutVerifying()
            ->timeout(120)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post(config('services.bank_misr.api_url'), $postData);

        if (!$response->successful()) {
            Log::error('Bank API request failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'request_data' => $postData
            ]);
            throw new \Exception('Failed to communicate with bank API');
        }

        // Handle potentially double-encoded JSON response
        $decodedResponse = $response->json();

        // If the response is a string and appears to be JSON, decode it again
        if (is_string($decodedResponse) && str_starts_with(trim($decodedResponse), '{')) {
            try {
                $decodedResponse = json_decode($decodedResponse, true);
            } catch (\Exception $e) {
                Log::error('Failed to decode double-encoded JSON response', [
                    'original_response' => $decodedResponse,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Invalid response format from bank API');
            }
        }

        // Verify we have the expected response structure
        if (!is_array($decodedResponse) || !isset($decodedResponse['ResponseCode'])) {
            Log::error('Unexpected response structure from bank API', [
                'response' => $decodedResponse
            ]);
            throw new \Exception('Invalid response structure from bank API');
        }

        // Update transaction with response
        $this->updateTransactionWithResponse(
            $postData['TransactionId'],
            $decodedResponse['ResponseCode'],
            $decodedResponse['ResponseDescription'] ?? null
        );

        return [
            'status' => 'success',
            'data' => [
                'postData' => $postData,
                'response' => $decodedResponse
            ]
        ];
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
