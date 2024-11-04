<?php

namespace App\Http\Controllers;

use App\Models\BmCashoutStatus;
use App\Models\BmCashoutPrepare;
use App\Http\Controllers\SignerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BmCashoutStatusController extends Controller
{
    protected $signerService;

    // Status code constants
    const STATUS_INITIAL_SUCCESS = '8000';
    const FIRST_STATUS_PROCESSING = '8111';
    const STATUS_PROCESSING = '8222';
    const STATUS_SETTLED = '8333';
    const STOP_CHECK_STATUSES = [
        '8001',
        '8002',
        '8003',
        '8004',
        '8005',
        '8006',
        '8007',
        '8008',
        '8011',
        '8888'
    ];

    const ERROR_STATUSES = [
        '0100',
        '0101',
        '0102',
        '0103',
        '0104',
        '0105',
        '0106',
        '0107',
        '0108',
        '0109',
        '0110',
        '0111',
        '0112',
        '0001',
        '0002',
        '0003',
        '0004',
        '0005',
        '0006',
        '0007',
        '0008',
        '0009',
        '0010',
        '0011',
        '0012',
        '0013',
        '0014'
    ];

    public function __construct(SignerService $signerService)
    {
        $this->signerService = $signerService;
    }


    /**
     * Check status of transactions based on configured rules
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkTransactionStatuses()
    {
        try {
            $results = [];

            // Get transactions that need status check based on rules
            $transactions = $this->getTransactionsForStatusCheck();

            foreach ($transactions as $transaction) {
                $result = $this->processTransactionStatus($transaction);
                if ($result) {
                    $results[] = $result;
                }
            }

            return response()->json([
                'status' => 'success',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            Log::error('Status check failed: ' . $e->getMessage());
            return response()->json(['error' => 'Status check failed'], 500);
        }
    }

    /**
     * Get transactions that need status check based on rules
     */
    private function getTransactionsForStatusCheck()
    {
        $normalCheckTime = Carbon::now()->subMinutes(
            config('services.bank_misr.status_check_interval', 30)
        );
        $dailyCheckTime = Carbon::now()->subDay();

        return BmCashoutPrepare::with('status')
            ->where(function ($query) use ($normalCheckTime, $dailyCheckTime) {
                // Regular 30-40 minute check for transactions with 8000 status
                $query->where(function ($q) use ($normalCheckTime) {
                    $q->where('response_code', self::STATUS_INITIAL_SUCCESS)
                        ->where('prepared_flag', true)
                        ->where('updated_at', '<=', $normalCheckTime);
                })
                // Daily check for transactions with 8222 status
                ->orWhereHas('status', function ($q) use ($dailyCheckTime) {
                    $q->where('transaction_status_code', self::STATUS_PROCESSING)
                        ->where('updated_at', '<=', $dailyCheckTime);
                });
            })
            // Exclude transactions with stop-check statuses
            ->whereDoesntHave('status', function ($query) {
                $query->whereIn('transaction_status_code', array_merge(
                    self::STOP_CHECK_STATUSES,
                    self::ERROR_STATUSES
                ));
            })
            ->get();
    }

    /**
     * Process individual transaction status
     */
    private function processTransactionStatus($transaction)
    {
        try {
            // Create or get status record
            $status = $this->getOrCreateStatus($transaction);

            // Generate signature and prepare request data
            $requestData = $this->prepareStatusRequest($transaction);

            // Send request to bank
            $response = $this->sendStatusRequest($requestData);

            if (!$response) {
                return null;
            }

            // Process and save response
            return $this->processStatusResponse($transaction, $status, $response, $requestData['Signature']);
        } catch (\Exception $e) {
            Log::error('Failed to process transaction status', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get or create status record for transaction
     */
    private function getOrCreateStatus($transaction)
    {
        return BmCashoutStatus::firstOrCreate(
            ['transaction_id' => $transaction->transaction_id],
            [
                'bm_cashout_id' => $transaction->id,
                'message_id' => null // Will be set by database trigger
            ]
        );
    }

    /**
     * Prepare status request data
     */
    private function prepareStatusRequest($transaction)
    {
        $requestData = [
            'MessageId' => $transaction->status->message_id,
            'TransactionId' => $transaction->transaction_id,
            'CorporateCode' => $transaction->corporate_code
        ];

        $signature = $this->signerService->generateSendTransactionSignature(
            $requestData,
            config('services.bank_misr.private_key_path')
        );

        $requestData['Signature'] = rawurlencode($signature);

        return $requestData;
    }

    /**
     * Send status request to bank
     */
    private function sendStatusRequest($requestData)
    {
        $response = Http::withoutVerifying() // Remove in production
            ->get(config('services.bank_misr.status_api_url'), [
                'request' => json_encode($requestData)
            ]);

        if (!$response->successful()) {
            Log::error('Bank status API request failed', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_data' => $requestData
            ]);
            return null;
        }

        return json_decode(json_decode($response->body(), true), true);
    }

    /**
     * Process and save status response
     */
    private function processStatusResponse($transaction, $status, $response, $signature)
    {
        $statusCode = $response['TransactionStatusCode'];

        // Update status record
        $status->update([
            'transaction_status_code' => $statusCode,
            'transaction_status_description' => $response['TransactionStatusDescription'],
            'request_status' => $response['RequestStatus'],
            'request_status_description' => $response['RequestStatusDescription'],
            'signature' => $signature
        ]);

        // Handle specific status codes
        if ($statusCode === self::STATUS_SETTLED || in_array($statusCode, self::ERROR_STATUSES)) {
            $transaction->update(['prepared_flag' => false]);
        }

        return [
            'transaction_id' => $transaction->transaction_id,
            'status_code' => $statusCode,
            'description' => $response['TransactionStatusDescription']
        ];
    }

    public function index()
    {
        $statuses = BmCashoutStatus::with('prepare')->get();
        return response()->json($statuses);
    }

    public function show($id)
    {
        $status = BmCashoutStatus::with('prepare')->find($id);
        if (!$status) {
            return response()->json(['error' => 'Cashout status not found'], 404);
        }
        return response()->json($status);
    }

}
