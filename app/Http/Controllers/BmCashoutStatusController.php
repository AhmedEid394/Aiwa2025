<?php

namespace App\Http\Controllers;

use App\Events\BmCashoutStatusUpdate;
use App\Models\BmCashoutStatus;
use App\Models\BmCashoutPrepare;
use App\Http\Controllers\SignerService;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BmCashoutStatusController extends Controller
{
    protected $signerService;

    const STATUS_INITIAL_SUCCESS = '8000';
    const STATUS_PROCESSING = '8222';
    const STATUS_ACCEPTED_NOT_SETTLED = '8111';
    const STATUS_SETTLED = '8222';
    const STATUS_SETTLED_FINAL = '8333';
    const STOP_CHECK_STATUSES = ['8001', '8002', '8003', '8004', '8005', '8006', '8007', '8008', '8011', '8888'];
    const ERROR_STATUSES = ['0100', '0101', '0102', '0103', '0104','0105','0106','0107','0108', '0001', '0002', '0003'];

    public function __construct(SignerService $signerService)
    {
        $this->signerService = $signerService;
    }

    public function checkTransactionStatuses()
    {
        try {
            $this->logMessage('Starting transaction status check');

            $transactions = $this->getTransactionsForStatusCheck();
            $results = [];

            foreach ($transactions as $transaction) {
                $result = $this->processTransactionStatus($transaction);
                if ($result) {
                    $results[] = $result;
                }
            }

            $this->logMessage('Transaction status check completed');

            return response()->json([
                'status' => 'success',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            $this->logMessage('Transaction status check failed: ' . $e->getMessage(), 'error');
            return response()->json(['error' => 'Status check failed'], 500);
        }
    }

    private function getTransactionsForStatusCheck()
    {
        $normalCheckTime = Carbon::now()->subMinutes(config('services.bank_misr.status_check_interval', 30));
        $dailyCheckTime = Carbon::now()->subDay();

        $transactions = BmCashoutPrepare::with('status')
            ->where(function ($query) use ($normalCheckTime, $dailyCheckTime) {
                $query->where(function ($q) use ($normalCheckTime) {
                    $q->where('response_code', self::STATUS_INITIAL_SUCCESS)
                        ->where('prepared_flag', true)
                        ->where('updated_at', '<=', $normalCheckTime);
                })
                    ->orWhereHas('status', function ($q) use ($dailyCheckTime) {
                        $q->where('transaction_status_code', self::STATUS_PROCESSING)
                            ->where('updated_at', '<=', $dailyCheckTime);
                    });
            })
            ->whereDoesntHave('status', function ($query) {
                $query->whereIn('transaction_status_code', array_merge(self::STOP_CHECK_STATUSES, self::ERROR_STATUSES));
            })
            ->get();

        $this->logMessage('Found ' . count($transactions) . ' transactions to process');

        return $transactions;
    }

    private function processTransactionStatus($transaction)
    {
        try {
            $this->logMessage("Processing transaction: {$transaction->transaction_id}");

            $status = $this->getOrCreateStatus($transaction);
            $requestData = $this->prepareStatusRequest($transaction);
            $response = $this->sendStatusRequest($requestData);

            if (!$response) {
                return null;
            }

            return $this->processStatusResponse($transaction, $status, $response, $requestData['Signature']);
        } catch (\Exception $e) {
            $this->logMessage('Failed to process transaction status for ' . $transaction->transaction_id . ': ' . $e->getMessage(), 'error');
            return null;
        }
    }

    private function getOrCreateStatus($transaction)
    {
        return BmCashoutStatus::updateOrCreate(
            ['transaction_id' => $transaction->transaction_id],  // Search condition
            [
                'bm_cashout_id' => $transaction->bm_cashout_id,
                'message_id' => $this->generateUniqueMessageId($transaction)
            ] // Fields to update or insert
        );
    }

    private function generateUniqueMessageId($transaction)
    {
        return substr(uniqid($transaction->transaction_id . '-', true), 0, 50);
    }

    private function prepareStatusRequest($transaction)
    {
        $transaction->load('status');
        $transaction->status->update([
            'message_id' => $this->generateUniqueMessageId($transaction)
        ]);

        $requestData = [
            'MessageId' => $transaction->status?->message_id,
            'TransactionId' => $transaction->transaction_id,
            'CorporateCode' => $transaction->corporate_code,
            'status' => true
        ];

        $this->logMessage('Data before signature generation: ' . json_encode($requestData, JSON_PRETTY_PRINT));

        $signature = $this->signerService->generateSendTransactionSignature(
            $requestData,
            config('services.bank_misr.private_key_path')
        );

        $requestData['Signature'] = $signature;

        $this->logMessage("Prepared request data for transaction: " . json_encode($requestData, JSON_PRETTY_PRINT));

        return $requestData;
    }

    private function sendStatusRequest($requestData)
    {
        try {
            $baseUrl = config('services.bank_misr.status_api_url');

            // Log the request being sent
            $this->logMessage('Sending status request to bank API: ' . json_encode($requestData, JSON_PRETTY_PRINT));

            // Send GET request with the formatted request data
            $response = Http::withoutVerifying()
                ->timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->get($baseUrl . '/api/Transactions', ['request' => json_encode($requestData, JSON_UNESCAPED_SLASHES)]);

            // Decode JSON response
            $decodedResponse = json_decode($response->body(), true);

            // Handle double-encoded JSON response
            if (is_string($decodedResponse) && str_starts_with(trim($decodedResponse), '{')) {
                $decodedResponse = json_decode($decodedResponse, true);
            }

            // Validate response structure
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedResponse['TransactionStatusCode'])) {
                $this->logMessage('Invalid response from bank API: ' . $response->body(), 'error');
                throw new \Exception('Invalid response structure from bank API');
            }

            // Log successful response
            $this->logMessage('Received status response from bank API: ' . json_encode($decodedResponse, JSON_PRETTY_PRINT));

            return $decodedResponse;

        } catch (\Exception $e) {
            $this->logMessage('Failed to send status request to bank API: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function processStatusResponse($transaction, $status, $response, $signature)
    {
        $statusCode = $response['TransactionStatusCode'];

        $status->update([
            'transaction_status_code' => $statusCode,
            'transaction_status_description' => $response['TransactionStatusDescription'],
            'request_status' => $response['RequestStatus'],
            'request_status_description' => $response['RequestStatusDescription'],
            'signature' => $signature
        ]);

        if ($statusCode === self::STATUS_SETTLED || $statusCode === self::STATUS_ACCEPTED_NOT_SETTLED || $statusCode === self::STATUS_SETTLED_FINAL || in_array($statusCode, self::ERROR_STATUSES)) {
            $transaction->update([
                'prepared_flag' => false,
                'response_code' => $statusCode,
                'response_description' => $response['TransactionStatusDescription']
            ]);
            $transactionCashout=Transaction::where('transaction_reference', $transaction->transaction_id)->first();
            if ($statusCode === self::STATUS_SETTLED || $statusCode === self::STATUS_ACCEPTED_NOT_SETTLED || $statusCode === self::STATUS_SETTLED_FINAL) {
                $wallet=Wallet::where('provider_id',$transaction->creditor_id)->first();
                $wallet->update([
                    'available_amount' => $wallet->available_amount - $transaction->transaction_amount,
                    'total_amount' => $wallet->total_amount - $transaction->transaction_amount
                ]);
                $transactionCashout->update([
                    'status' => 'completed'
                ]);
            }
            if (in_array($statusCode, self::ERROR_STATUSES)) {
                $transactionCashout->update([
                    'status' => 'failed'
                ]);
            }
            $transactionCashout->transaction_details=$transaction;
            event(new BmCashoutStatusUpdate($transactionCashout, $transaction->creditor_id));

        }

        return [
            'transaction_id' => $transaction->transaction_id,
            'status_code' => $statusCode,
            'description' => $response['TransactionStatusDescription']
        ];
    }

    private function logMessage($message, $level = 'info')
    {
        Log::$level($message);
        file_put_contents(
            storage_path('logs/scheduler.log'),
            "[" . now() . "] " . strtoupper($level) . ": " . $message . PHP_EOL,
            FILE_APPEND
        );
    }
}
