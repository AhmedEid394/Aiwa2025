<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;


class SignerService
{
    /**
     * Generate signature for send transaction
     *
     * @param array $transactionData
     * @param string $privateKeyPath
     * @return string|null
     * @throws \Exception
     */
    
    public function generateSendTransactionSignature(array $transactionData, string $privateKeyPath)
    {
        try {

            $absolutePath = base_path($privateKeyPath);

            // Verify private key exists
            if (!file_exists($absolutePath)) {
                throw new \Exception('Private key file not found');
            }

            // Read private key
            $privateKey = file_get_contents($absolutePath);
            if (!$privateKey) {
                throw new \Exception('Failed to read private key');
            }

            // Create signature string
            $signString = implode('', [
                $transactionData['MessageId'],
                $transactionData['TransactionId'],
                $transactionData['DebtorAccount'],
                $transactionData['Currency'],
                number_format((float) $transactionData['TransactionAmount'], 2, '.', ''),
                $transactionData['CreditorAccountNumber'],
                $transactionData['CreditorBank'],
                $transactionData['CorporateCode']
            ]);

            // Create signature
            $signature = '';
            $success = openssl_sign($signString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            
            if (!$success) {
                throw new \Exception('Failed to generate signature');
            }

            return base64_encode($signature);

        } catch (\Exception $e) {
            Log::error('Signature generation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'transaction_data' => $transactionData
            ]);
            throw $e;
        }
    }
}