<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\GeneralNotification;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        // ၁။ Input Validation
        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|min:8|confirmed',
            'gender' => 'nullable|in:male,female,other',
            'birthdate' => 'nullable|date',
            'nrc' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        try {
            // ၂။ Username Auto-generate (@fullname + random digits)
            $username = $this->generateUsername($request->fullname);

            // ၃။ Cloudflare R2 သို့ Image Upload တင်ခြင်း
            $imageUrl = null;
            if ($request->hasFile('profile_image')) {
                // ၁။ ပုံကို R2 ပေါ်တင်ပြီး Path ကိုယူမယ် (e.g., profiles/MrdzD0o...jpg)
                $path = $request->file('profile_image')->store('profiles', 'r2');

                if ($path) {
                    // ၂။ .env ထဲက URL ကိုယူပြီး Path နဲ့ ကိုယ်တိုင်ဆက်မယ်
                    $baseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
                    $imageUrl = $baseUrl . '/' . $path; // အခုဆိုရင် URL အပြည့်အစုံ ရပါပြီ
                }
            }

            // ၄။ OTP နှင့် သက်တမ်းသတ်မှတ်ခြင်း
            $otp = (string) rand(111111, 999999);
            $otpExpiredAt = Carbon::now()->addMinutes(1);

            // ၅။ User Database ထဲတွင် သိမ်းဆည်းခြင်း
            $user = User::create([
                'fullname'      => $request->fullname,
                'username'      => $username,
                'email'         => $request->email,
                'phone'         => $request->phone,
                'password'      => Hash::make($request->password),
                'gender'        => $request->gender,
                'birthdate'     => $request->birthdate,
                'nrc'           => $request->nrc,
                'profile_image' => $imageUrl,
                'otp'           => $otp,
                'otp_expired_at'=> $otpExpiredAt,
            ]);

            // ၆။ Gmail မှတစ်ဆင့် OTP ပို့ပေးခြင်း
            try {
                Mail::to($user->email)->send(new OtpMail($otp, $user->fullname));
            } catch (\Exception $mailError) {
                // Mail ပို့မရသော်လည်း Register ဖြစ်သွားပြီဖြစ်၍ Log မှတ်ထားပါမည်
                Log::error("OTP Mail Error: " . $mailError->getMessage());
            }

            // ၇။ Sanctum Token ထုတ်ပေးခြင်း
            $token = $user->createToken('auth_token')->plainTextToken;

            $details = [
                'subject' => 'Welcome to Our App',
                'body' => 'Account Registration is successful',
                'actionText' => 'View Profile',
                'actionURL' => url('/profile')
            ];

            $user->notify(new GeneralNotification($details));

            return response()->json([
                'status'  => 'success',
                'message' => 'Registration အောင်မြင်ပါသည်။ သင်၏ Gmail ထဲရှိ OTP ကိုစစ်ဆေးပါ။',
                'data'    => [
                    'user'  => $user,
                    'access_token' => $token,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Register ပြုလုပ်ရာတွင် အမှားအယွင်းရှိပါသည်။: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['message' => 'OTP မှားယွင်းနေပါသည်။'], 400);
        }

        if (Carbon::now()->gt($user->otp_expired_at)) {
            return response()->json(['message' => 'OTP သက်တမ်းကုန်ဆုံးသွားပါပြီ။'], 400);
        }

        // OTP မှန်ရင် (Verified ဖြစ်သွားရင်) OTP ကို ပြန်ဖျက်ထားပါ
        $user->update([
            'otp' => null,
            'otp_expired_at' => null,
        ]);

        return response()->json([
            'message' => 'OTP အတည်ပြုခြင်း အောင်မြင်ပါသည်။',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'အီးမေးလ် သို့မဟုတ် စကားဝှက် မှားယွင်းနေပါသည်။'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // Token အသစ်ထုတ်ပေးခြင်း
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login အောင်မြင်ပါသည်',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'google_token' => 'required',
        ]);

        $token = $request->input('google_token');

        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver('google');
            $socialUser = $driver->stateless()->userFromToken($token);

            // UpdateOrCreate မှာ Table schema အသစ်နဲ့ ကိုက်ညီအောင် ပြင်ပါ
            $user = User::updateOrCreate([
                'email' => $socialUser->getEmail(),
            ], [
                // 'name' အစား 'fullname' ကို သုံးရပါမယ် (Schema မှာ fullname လို့ ပေးခဲ့လို့ပါ)
                'fullname' => $socialUser->getName(), 
                
                // Username ကိုလည်း မရှိမဖြစ်လိုအပ်လို့ တစ်ခါတည်း Generate လုပ်ပေးရပါမယ်
                'username' => $this->generateUsername($socialUser->getName()),
                
                'provider_id' => $socialUser->getId(),
                'provider_name' => 'google',
                'profile_image' => $socialUser->getAvatar(),
                'password' => Hash::make(str()->random(24)), // Social login အတွက် random password
            ]);

            /** @var \App\Models\User $user */
            $apiToken = $user->createToken('google_auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Google Login အောင်မြင်ပါသည်',
                'access_token' => $apiToken,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Social login တက်နေပါသည် - ' . $e->getMessage()
            ], 401);
        }
    }

    public function forgetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        // OTP အသစ်ထုတ်ခြင်း
        $otp = (string) rand(111111, 999999);
        $user->otp = $otp;
        $user->otp_expired_at = Carbon::now()->addMinutes(10);
        $user->save();

        try {
            // Mail ပို့ခြင်း (Mailable ကို ပြန်သုံးထားသည်)
            Mail::to($user->email)->send(new OtpMail($otp, $user->fullname));
            
            return response()->json(['message' => 'စကားဝှက်အသစ်လဲရန် OTP ကို အီးမေးလ်သို့ ပို့ထားပြီးပါပြီ။']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'အီးမေးလ်ပို့ရာတွင် အမှားရှိနေပါသည်'], 500);
        }
    }

    public function verifyForgetPasswordOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|string|min:6|max:6',
        ]);

        $user = User::where('email', $request->email)
                    ->where('otp', $request->otp)
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'OTP ကုဒ် မှားယွင်းနေပါသည်။'], 400);
        }

        if (Carbon::now()->gt($user->otp_expired_at)) {
            return response()->json(['message' => 'OTP သက်တမ်း ကုန်ဆုံးသွားပါပြီ။'], 400);
        }

        // OTP မှန်ကန်ပါက Password ပြောင်းခွင့်ပေးရန် Temporary Token ထုတ်ပေးခြင်း
        $token = $user->createToken('password_reset_token', ['password-reset'])->plainTextToken;

        return response()->json([
            'message' => 'OTP မှန်ကန်ပါသည်။ စကားဝှက်အသစ် ပြောင်းနိုင်ပါပြီ။',
            'reset_token' => $token
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
            'otp' => null,
            'otp_expired_at' => null,
        ]);

        $token = $user->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->otp_expired_at && Carbon::now()->lt(Carbon::parse($user->otp_expired_at)->subMinutes(9))) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP တစ်ခါပို့ပြီးလျှင် ၁ မိနစ် စောင့်ပေးပါ။'
            ], 429);
        }

        // OTP အသစ်ထုတ်ခြင်း
        $otp = (string) rand(111111, 999999);
        $user->otp = $otp;
        $user->otp_expired_at = Carbon::now()->addMinutes(10);
        $user->save();

        try {
            Mail::to($user->email)->send(new OtpMail($otp, $user->fullname));

            return response()->json([
                'status' => 'success',
                'message' => 'OTP အသစ်ကို အီးမေးလ်သို့ ထပ်မံ ပို့ပေးထားပြီးပါပြီ။',
                'otp_test_only' => $otp 
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'အီးမေးလ်ပို့ရာတွင် အမှားအယွင်းရှိပါသည်။'
            ], 500);
        }
    }

    private function generateUsername($name)
    {
        $base = '@' . str()->slug($name, '');
        $username = $base . rand(100, 999);
        
        while (User::where('username', $username)->exists()) {
            $username = $base . rand(100, 999);
        }
        
        return $username;
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Validation
        $validated = $request->validate([
            'fullname'      => 'sometimes|string|max:255',
            'gender'        => 'nullable|in:male,female,other',
            'birthdate'     => 'nullable|date',
            'nrc'           => 'nullable|string',
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // 2MB max
        ]);

        try {
            if ($request->hasFile('profile_photo')) {
                $storage = Storage::disk('r2');

                // Delete old photo if exists
                if (!empty($user->profile_photo)) {
                    $oldPath = ltrim(parse_url($user->profile_photo, PHP_URL_PATH), '/');
                    if ($storage->exists($oldPath)) {
                        $storage->delete($oldPath);
                    }
                }

                // Upload new photo
                $path = $request->file('profile_photo')->store('profile_photos', 'r2');
                $validated['profile_photo'] = rtrim(env('R2_URL'), '/') . '/' . $path;

            }

            $user->update($validated);

            $details = [
                'subject' => 'Welcome to Our App',
                'body' => 'Profile is updated successfully',
                'actionText' => 'View Profile',
                'actionURL' => url('/profile')
            ];

            $user->notify(new GeneralNotification($details));

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile updated successfully.',
                'data'    => $user
            ]);

        } catch (\Exception $e) {
            Log::error("Profile Update Error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred while updating profile - ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    public function getNotifications(Request $request)
    {
        $user = $request->user();

        // စာရင်းအသစ်ကို အပေါ်ကနေ ပြချင်လို့ latest() သုံးထားပါတယ်
        $notifications = $user->notifications()->paginate(20);

        return response()->json([
            'status' => 'success',
            'unread_count' => $user->unreadNotifications->count(), // မဖတ်ရသေးတဲ့ အရေအတွက်
            'notifications' => $notifications
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // ပေးလိုက်တဲ့ ID နဲ့ Notification ကို ရှာမယ်
        $notification = $user->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'စာဖတ်ပြီးကြောင်း မှတ်သားပြီးပါပြီ။']);
        }

        return response()->json(['message' => 'Notification ရှာမတွေ့ပါ။'], 404);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        
        return response()->json(['message' => 'အားလုံးကို ဖတ်ပြီးကြောင်း မှတ်သားပြီးပါပြီ။']);
    }
}