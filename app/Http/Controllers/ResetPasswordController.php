<?php

namespace App\Http\Controllers;

use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    public function checkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();
            $provider = ServiceProvider::where('email', $request->email)->first();

            if ($user || $provider) {
                // Generate OTP and send it to the email
                $otp = $this->generateOtp();
                $email = $request->email;
                // Uncomment the line below to send the email in a production environment
// Send the OTP email using Laravel's Mail facade
                Mail::raw('Your OTP is: ' . $otp, function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Reset Password OTP');
                });
                // Store OTP in cache for 10 minutes
                Cache::put('otp.' . $email, $otp, now()->addMinutes(10));

                return response()->json([
                    'message' => 'Reset password OTP sent to your email',
                    'otp' => $otp,
                    'status' => 'success',
                ]);
            }

            return response()->json([
                'error' => 'Email does not exist',
            ], 404);
        } catch (Exception $e) {
            Log::error('Error in checkEmail: ' . $e->getMessage());
            return response()->json([
                'error' => 'An error occurred while processing your request.',
            ], 500);
        }
    }

    public function confirmOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        try {
            $cachedOtp = Cache::get('otp.' . $request->email);

            if ($cachedOtp && $cachedOtp == $request->otp) {
                // OTP is correct; clear it from cache
                Cache::forget('otp.' . $request->email);

                return response()->json([
                    'message' => 'OTP is valid, proceed to reset password.',
                    'status' => 'success',
                ]);
            }

            return response()->json([
                'message' => 'Invalid OTP or OTP expired',
            ], 400);
        } catch (Exception $e) {
            Log::error('Error in confirmOtp: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while verifying the OTP.',
            ], 500);
        }
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();
            $provider = ServiceProvider::where('email', $request->email)->first();

            if ($user) {
                $user->update([
                    'password' => Hash::make($request->password),
                ]);
            } elseif ($provider) {
                $provider->update([
                    'password' => Hash::make($request->password),
                ]);
            } else {
                return response()->json([
                    'message' => 'Email does not exist',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'message' => 'Password reset successfully',
                'status' => 'success',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in reset: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while resetting the password.',
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6',
        ]);

        try {
            $user = $request->user();

            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'message' => 'Password changed successfully',
                'status' => 'success',
            ]);
        } catch (Exception $e) {
            Log::error('Error in changePassword: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while changing the password.',
            ], 500);
        }
    }

    private function generateOtp()
    {
        return mt_rand(100000, 999999); // Generate a 6-digit OTP
    }
}
