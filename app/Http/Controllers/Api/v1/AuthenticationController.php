<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Mail\VerifyEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;
use Twilio\Rest\Client;

class AuthenticationController extends ApiController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/Login",
     *     tags={"Authentication"},
     *     summary="Login to get verify code",
     *     description="Login by either phone or email + password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="phone",
     *                          type="string"
     *                      )
     *                 ),
     *                 example={
     *                     "phone":"0645978456"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function login(Request $request)
    {
        try {
            if ((empty($request->phone) && empty($request->email)) || (!empty($request->phone)) && !empty($request->email)) {
                throw new \Exception('You should enter either your phone number or your email.', 400);
            }
            if (!empty($request->phone)) {
                $validator = Validator::make($request->all(), ['phone' => ['required', 'string', 'min:8', 'max:11', 'exists:users']]);
                $user = User::where('phone', $request->input('phone'))->first();
            }

            if (!empty($request->email)) {
                $user = User::where('email', $request->input('email'))->first();
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
                    'password' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                        if ((!empty($user)) && !Hash::check($value, $user->password)) $fail('Your password is incorrect.');
                    }]
                ]);
            }

            if ((!empty($validator)) && $validator->fails()) return $this->result('', $validator->errors(), 400);
            if (!$user) throw new \Exception('Invalid parameters.', 400);

            // Generate a new verification code
            $verificationCode = $this->generateVerificationCode(4);

            // Send the new verification code to the user's phone number
//            if(!empty($request->phone)) $this->sendVerificationCode($verificationCode, $user->phone);

            // Send the new verification code to the user's email
            if (!empty($request->email)) Mail::to($user->email)->send(new VerifyEmail($user, $verificationCode));

            // Store the new verification code in session or database
            $user->verify_code = $verificationCode;
            $user->save();
            return $this->result(['code' => $verificationCode]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/ResendCode",
     *     tags={"Authentication"},
     *     summary="Resend verify code",
     *     description="Resend verify code",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="phone",
     *                          type="string"
     *                      )
     *                 ),
     *                 example={
     *                     "phone":"0645978456"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function resendCode(Request $request)
    {
        try {
            return $this->login($request);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/Verify",
     *     tags={"Authentication"},
     *     summary="Verify",
     *     description="Verify",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="phone",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="verify_code",
     *                          type="string"
     *                      )
     *                 ),
     *                 example={
     *                     "phone":"0645978456",
     *                     "verify_code":"9634"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function verify(Request $request)
    {
        try {
            if ((empty($request->phone) && empty($request->email)) || (!empty($request->phone)) && !empty($request->email)) {
                throw new \Exception('You should enter either your phone number or your email.', 400);
            }
            if (!empty($request->phone)) {
                $validator = Validator::make($request->all(), [
                    'phone' => ['required', 'string', 'min:8', 'max:11', 'exists:users'],
                    'verify_code' => ['required', 'string', 'size: 4'],
                ]);
                $user = User::where('phone', $request->input('phone'))->where('verify_code', $request->input('verify_code'))->first();
            }
            if (!empty($request->email)) {
                $validator = Validator::make($request->all(), [
                    'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
                    'verify_code' => ['required', 'string', 'size: 4'],
                ]);
                $user = User::where('email', $request->input('email'))->where('verify_code', $request->input('verify_code'))->first();
            }
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            if (!$user) throw new \Exception('Invalid parameters.', 400);

            $user->verify_code = '';
            $user->tokens()->delete();
            $accessToken = $user->createToken('access_token', ['access'], Carbon::now()->addSeconds(config('sanctum.expiration')))->plainTextToken;
            $refreshToken = $user->createToken('refresh_token', ['refresh'], Carbon::now()->addSeconds(config('sanctum.rt_expiration')))->plainTextToken;
            $user->save();
            if ($user->roles->count() > 0) $user->role_name = $user->roles->first()->name;
            return $this->result([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/auth/RefreshToken",
     *     tags={"Authentication"},
     *     summary="Get new token",
     *     description="Get new token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="refresh_token",
     *                          type="string"
     *                      )
     *                 ),
     *                 example={
     *                     "refresh_token":"10d2505c780d59bd9e09197265badef215d36cab03f81c93e8d3f0ee9bd2501b"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function refresh(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'refresh_token' => 'required'
            ]);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            $bearer = $request->bearerToken();
            if (!empty($bearer)) {
                if (DB::table('personal_access_tokens')
                    ->where('name', 'access_token')
                    ->where('token', hash('sha256', substr($bearer, strpos($bearer, '|') + 1)))
                    ->first()) {
                    $refreshToken = $request->input('refresh_token');
                    $token = PersonalAccessToken::findToken($refreshToken);
                    if (!$token || Carbon::parse($token->expires_at) < Carbon::now()) {
                        throw new \Exception('Invalid refresh token.', 401);
                    }
                    $user = User::findOrFail($token->tokenable_id);
                    $user->tokens()->delete();
                    $accessToken = $user->createToken('access_token', ['access'], Carbon::now()->addSeconds(config('sanctum.expiration')))->plainTextToken;
                    $refreshToken = $user->createToken('refresh_token', ['refresh'], Carbon::now()->addSeconds(config('sanctum.rt_expiration')))->plainTextToken;
                    return $this->result([
                        'access_token' => $accessToken,
                        'refresh_token' => $refreshToken
                    ]);
                }
            }
            throw new \Exception('Access denied.', 401);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/auth/Logout",
     *     tags={"Authentication"},
     *     summary="Logout",
     *     description="Logout",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            if (!$token) throw new \Exception('Access denied.', 401);

            $personalAccessToken = PersonalAccessToken::findToken($token);
            if (!$personalAccessToken) throw new \Exception('Access denied.', 401);
            // Revoke all tokens...
            optional(User::find($personalAccessToken->tokenable_id))->tokens()->delete();

            return $this->result();
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/Register",
     *     tags={"Authentication"},
     *     summary="Register",
     *     description="Register",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                  required={"first_name", "last_name", "email", "phone", "password", "password_confirmation"},
     *                  @OA\Property(
     *                      property="avatar",
     *                      type="string",
     *                      format="binary"
     *                  ),
     *                  @OA\Property(
     *                      property="first_name",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="last_name",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="phone",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="password_confirmation",
     *                      type="string"
     *                  )
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'avatar' => ['nullable', 'file', 'mimes:jpeg,png,jpg', 'max:2048'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'min:8', 'max:11', 'unique:users'],
                'email' => ['required', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules()
            ]);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            $avatar = '/images/avt-' . rand(1, 5) . '.jpg';
            if (!empty($request->file('avatar'))) {
                $validatorAvatar = Validator::make($request->all(), ['avatar' => ['file', 'mimes:jpeg,png,jpg', 'max:2048'],]);
                if ($validatorAvatar->fails()) return $this->result('', $validatorAvatar->errors(), 400);
                $profilePhoto = $request->file('avatar');
                $imageName = time() . '.' . $profilePhoto->getClientOriginalExtension();
                Image::read($profilePhoto)->save(public_path('storage/' . $imageName));
                $avatar = '/storage/' . $imageName;
            }

            $newUser = User::create([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'name' => $request->input('first_name') . ' ' . $request->input('last_name'),
                'phone' => $request->input('phone'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'profile_photo_path' => $avatar,
                'verified' => false
            ]);
            // Generate a new verification code
            $verificationCode = $this->generateVerificationCode(4);
            // Send the new verification code to the user's email
            if (!empty($request->email)) Mail::to($newUser->email)->send(new VerifyEmail($newUser, $verificationCode));
            $newUser->verify_register_code = $verificationCode;
            $newUser->save();
            return $this->result(
                [
                    'user' => $newUser,
                    'code' => $verificationCode
                ]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/VerifyRegister",
     *     tags={"Authentication"},
     *     summary="Verify Register",
     *     description="Verify Register",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="email",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="verify_register_code",
     *                          type="string"
     *                      )
     *                 ),
     *                 example={
     *                     "email":"example@gmail.com",
     *                     "verify_register_code":"9634"
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function verifyRegister(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email', 'max:255', 'exists:users'],
                'verify_register_code' => ['required', 'string', 'size: 4'],
            ]);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            $user = User::where('email', $request->input('email'))->where('verify_register_code', $request->input('verify_register_code'))->first();

            if (!$user) throw new \Exception('Invalid parameters.', 400);

            $user->verify_register_code = '';
            $user->verified = true;
            $user->save();
            if ($user->roles->count() > 0) $user->role_name = $user->roles->first()->name;
            return $this->result([
                'user' => $user,
                'message' => 'Your account has been verified.'
            ]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/ForgotPassword",
     *     tags={"Authentication"},
     *     summary="Forgot password",
     *     description="Forgot password: enter either phone or email to get OTP || Link to reset password.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                       type="object",
     *                       @OA\Property(
     *                           property="phone",
     *                           type="string"
     *                       )
     *                  ),
     *                  example={
     *                      "phone":"0645978456"
     *                 }
     *              )
     *          )
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function forgotPassword(Request $request)
    {
        try {
            if ((empty($request->phone) && empty($request->email)) || (!empty($request->phone)) && !empty($request->email)) {
                throw new \Exception('You should enter either your phone number or your email.', 400);
            }
            if (!empty($request->phone)) {
                $validator = Validator::make($request->all(), ['phone' => ['required', 'string', 'min:8', 'max:11', 'exists:users']]);
                $user = User::where('phone', $request->input('phone'))->first();
            }
            if (!empty($request->email)) {
                $validator = Validator::make($request->all(), ['email' => ['required', 'string', 'email', 'max:255', 'exists:users']]);
                $user = User::where('email', $request->input('email'))->first();
            }
            if ((!empty($validator)) && $validator->fails()) return $this->result('', $validator->errors(), 400);

            // Generate a new verification code
            $verificationCode = $this->generateVerificationCode(4);

            $user->verify_forgot_code = $verificationCode;
            $user->save();

            // Send the new verification code to the user's phone number
//            if(!empty($request->phone)) $this->sendVerificationCode($verificationCode, $user->phone);

            // Send the new verification code to the user's email
            if (!empty($request->email)) Mail::to($user->email)->send(new VerifyEmail($user, $verificationCode));

            return $this->result(['code' => $verificationCode]);

        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/ResetPassword",
     *     tags={"Authentication"},
     *     summary="Reset Password: enter either phone or email",
     *     description="Reset Password: enter either phone or email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="phone",
     *                          type="string"
     *                      ),
     *                      @OA\Property(
     *                          property="verify_forgot_code",
     *                          type="string"
     *                      ),
     *                       @OA\Property(
     *                           property="password",
     *                           type="string"
     *                       ),
     *                        @OA\Property(
     *                            property="password_confirmation",
     *                            type="string"
     *                        )
     *                 ),
     *                 example={
     *                     "phone":"0645978456",
     *                     "verify_forgot_code":"9634",
     *                     "password":"123456789",
     *                     "password_confirmation":"123456789",
     *                }
     *             )
     *         )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    function resetPassword(Request $request)
    {
        try {
            if ((empty($request->phone) && empty($request->email)) || (!empty($request->phone)) && !empty($request->email)) {
                throw new \Exception('You should enter either your phone number or your email.', 400);
            }
            $validate = ['verify_forgot_code' => ['required', 'string', 'size: 4'], 'password' => $this->passwordRules(),];
            $validator = Validator::make($request->all(), $validate);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            if (!empty($request->phone)) {
                $validate['phone'] = ['required', 'string', 'min:8', 'max:11', 'exists:users'];
                $user = User::where('phone', $request->input('phone'))->where('verify_forgot_code', $request->input('verify_forgot_code'))->first();
            }
            if (!empty($request->email)) {
                $validate['email'] = ['required', 'string', 'email', 'max:255', 'exists:users'];
                $user = User::where('email', $request->input('email'))->where('verify_forgot_code', $request->input('verify_forgot_code'))->first();
            }
            $validator = Validator::make($request->all(), $validate);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            if (!$user) throw new \Exception('Invalid parameters.', 400);

            $user->password = Hash::make($request->input('password'));
            $user->verify_forgot_code = '';
            $user->save();

            if ($user->roles->count() > 0) $user->role_name = $user->roles->first()->name;

            return $this->result([
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }


    private function generateVerificationCode($length = 6)
    {
        if ($length == 4) {
            return str_pad(rand(0, 9999), $length, '0', STR_PAD_LEFT);
        }
        // Generate a random 6-digit code
        return str_pad(rand(0, 999999), $length, '0', STR_PAD_LEFT);
    }

    private function sendVerificationCode($code, $phoneNumber)
    {
        // Send verification code using your chosen service (e.g., Twilio)
        $client = new Client(env('TWILIO_SID'), env('TWILIO_TOKEN'));
        $client->messages->create(
            $phoneNumber,
            [
                'from' => env('TWILIO_NUMBER'),
                'body' => "Your verification code is: $code",
            ]
        );
    }

    private function generateRandomPassword($length = 10)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
}
