<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Property;
use App\Policies\MessagePolicy;
use App\Services\ChannexService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function index(Request $request, ChannexService $channexService)
    {

        $this->authorize('view', Message::class);
        // ...
        // Todo: validate property id của tất cả các trang, kiểm tra user hiện tại có quyền xem không?
        $property_id = $request->property_id;

        $property = Property::find($property_id);

        if (!$property) {
            return Inertia::render('Message/Index', [
                'property_id' => $property_id,
                'url' => ''
            ]);
        }

        if (!$property->external_id) {
            return Inertia::render('Message/Index', [
                'property_id' => $property_id,
                'error' => 'Chỗ nghỉ này chưa liên kết với OTA',
                'url' => ''
            ]);
        }

        $external_id = $property->external_id;

        $server = config('services.channex.iframe_base_url');
        try {
            //code...
            $token = $channexService->generateOneTimeToken($property);
        } catch (\Throwable $th) {
            return back()->with('error', 'Không thể truy cập tin nhắn');
        }

        $url = "$server/auth/exchange?oauth_session_key=$token&app_mo
de=headless&redirect_to=/messages&property_id=$external_id";

        return Inertia::render('Message/Index', [
            'property_id' => $property_id,
            'url' => $url
        ]);
    }
}
