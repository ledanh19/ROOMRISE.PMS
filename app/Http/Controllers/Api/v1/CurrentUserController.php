<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Intervention\Image\Laravel\Facades\Image;

class CurrentUserController extends ApiController
{
    /**
     * @OA\Get(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/currentUser",
     *     tags={"Current User"},
     *     summary="Get current user",
     *     description="Get current user",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       )
     * )
     */
    public function currentUser(Request $request)
    {
        try {
            $user = resolve('user');
            if ($user->roles->count() > 0) $user->role_name = $user->roles->first()->name;
            return $this->result($user);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }


    /**
     * @OA\Post (
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/currentUser/Update",
     *     tags={"Current User"},
     *     description="Update current user",
     *     summary="Update current user",
     *      @OA\RequestBody(
     *          required=true,
     *          description="Media file to upload",
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  required={"first_name", "last_name", "email", "phone"},
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
     *                  )
     *              )
     *          )
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *      )
     * )
     *
     */
    public function update(Request $request)
    {

        try {
            $user = resolve('user');
            if (empty($user)) throw new \Exception('We apologize, but we are unable to locate the user you are searching for at this time.', 404);
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'min:8', 'max:11', Rule::unique('users')->ignore($user->id)],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)]
            ]);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);

            if (!empty($request->file('avatar'))) {
                $validatorAvatar = Validator::make($request->all(), [
                    'avatar' => 'max:2048|file|mimes:jpeg,png,jpg|max:2048',
                ]);
                if ($validatorAvatar->fails()) return $this->result('', $validatorAvatar->errors(), 400);
                $profilePhoto = $request->file('avatar');
                $imageName = time() . '.' . $profilePhoto->getClientOriginalExtension();
                Image::read($profilePhoto)->save(public_path('storage/' . $imageName));
                $user->profile_photo_path = '/storage/' . $imageName;
            }
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->name = $request->input('first_name') . ' ' . $request->input('last_name');
            $user->phone = $request->input('phone');
            $user->email = $request->input('email');
            $user->save();
            if ($user->roles->count() > 0) $user->role_name = $user->roles->first()->name;
            return $this->result(['user' => $user]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/currentUser/ChangePassword",
     *     tags={"Current User"},
     *     summary="Change Password",
     *     description="Change Password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                      type="object",
     *                      @OA\Property(
     *                          property="password",
     *                          type="string"
     *                      ),
     *                       @OA\Property(
     *                           property="new_password",
     *                           type="string"
     *                       ),
     *                        @OA\Property(
     *                            property="new_password_confirmation",
     *                            type="string"
     *                        )
     *                 ),
     *                 example={
     *                     "password":"2135498782",
     *                     "new_password":"123456789",
     *                     "new_password_confirmation":"123456789",
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
    public function changePassword(Request $request)
    {
        try {
            $user = resolve('user');
            if (empty($user)) throw new \Exception('We apologize, but we are unable to locate the user you are searching for at this time.', 404);
            $validator = Validator::make($request->all(), [
                'password' => ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) $fail('Your password is incorrect.');
                }],
                'new_password' => $this->passwordRules()
            ]);
            if ($validator->fails()) return $this->result('', $validator->errors(), 400);
            $user->password = Hash::make($request->input('new_password'));
            $user->save();
            return $this->result(['user' => $user]);
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }
}
