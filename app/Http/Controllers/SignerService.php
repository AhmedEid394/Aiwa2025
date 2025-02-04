<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;

class SignerService
{
    /**
     * Sign data using PKCS #1 v1.5 padding mode
     *
     * @param string $data Data to be signed
     * @param string $privateKeyPath Path to the private key file
     * @param int $hashAlgorithm Hashing algorithm (e.g., OPENSSL_ALGO_SHA256)
     * @return string Base64 encoded signature
     * @throws \Exception If signing fails
     */
    protected function signData($data, $privateKeyPath, $hashAlgorithm = OPENSSL_ALGO_SHA256)
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

            // Create signature
            if (openssl_sign($data, $signature, $privateKey, $hashAlgorithm)) {
                return base64_encode($signature);
            }

            // Get OpenSSL error if signing fails
            $error = openssl_error_string();
            throw new \Exception('Failed to create signature: ' . $error);

        } catch (\Exception $e) {
            Log::error('Signature generation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Encode data to the specified encoding
     *
     * @param string $data Data to encode
     * @param string $encoding Encoding type ('UTF-8', 'UTF-16', or 'Unicode')
     * @return string Encoded data
     * @throws \Exception If encoding is unsupported
     */
    protected function encodeData($data, $encoding)
    {
        try {
            switch ($encoding) {
                case 'UTF-8':
                    return mb_convert_encoding($data, 'UTF-8');
                case 'UTF-16':
                    return mb_convert_encoding($data, 'UTF-16');
                case 'Unicode':
                    return mb_convert_encoding($data, 'UTF-16BE');
                default:
                    throw new \Exception('Unsupported encoding');
            }
        } catch (\Exception $e) {
            Log::error('Data encoding failed: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data,
                'encoding' => $encoding
            ]);
            throw $e;
        }
    }

    /**
     * Generate signature for send transaction
     *
     * @param array $transactionData
     * @param string $privateKeyPath
     * @param string $encoding Encoding type ('UTF-8', 'UTF-16', or 'Unicode')
     * @return string|null
     * @throws \Exception
     */
    public function generateSendTransactionSignature(array $transactionData, string $privateKeyPath, string $encoding = 'UTF-8')
    {
        try {
            if(isset($transactionData['status']) && $transactionData['status'] == true) {
                // Create signature string
                $signString = implode('', [
                    $transactionData['MessageId'],
                    $transactionData['TransactionId'],
                    $transactionData['CorporateCode'],
                ]);
            }else{
                // Create signature string
                $signString = implode('', [
                    $transactionData['MessageId'],
                    $transactionData['TransactionId'],
                    $transactionData['CorporateCode'],
                    $transactionData['DebtorAccount'],
                    $transactionData['CreditorAccountNumber'],
                    $transactionData['CreditorBank'],
                    $transactionData['TransactionAmount'],
                    $transactionData['Currency']
                ]);
            }


            // Encode the data
            $encodedSignString = $this->encodeData($signString, $encoding);

            // Generate and return the signature
            return $this->signData($encodedSignString, $privateKeyPath);

        } catch (\Exception $e) {
            Log::error('Signature generation failed: ' . $e->getMessage(), [
                'exception' => $e,
                'transaction_data' => $transactionData
            ]);
            throw $e;
        }
    }

}
