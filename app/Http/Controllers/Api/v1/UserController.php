<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\ApiController;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    /**
     * @OA\Delete(
     *     security={{"bearerAuth":{}}},
     *     path="/api/v1/user/{id}",
     *     tags={"User"},
     *     description="Delete a user",
     *     summary="Delete a user",
     *     @OA\Parameter(
     *          in="path",
     *          name="id",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *     @OA\Response(
     *          response=200,
     *          description="Success"
     *      )
     * )
     *
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                throw new \Exception('The user does not exist.', 404);
            }
            $user->delete();
            return $this->result();
        } catch (\Exception $e) {
            return $this->result('', $e->getMessage(), $e->getCode());
        }
    }
}
