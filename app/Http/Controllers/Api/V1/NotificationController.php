<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $service) {}

    /**
     * Paginated notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $request->user()
            ->notifications()
            ->latest('created_at')
            ->paginate(max(1, min(100, $request->integer('per_page', 20))));

        $items = NotificationResource::collection($paginator->items())->resolve();

        return ApiResponse::paginate($items, $paginator, 'Notifikasi berhasil diambil');
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $id, Request $request): JsonResponse
    {
        $this->service->markAsRead($id, $request->user());

        return ApiResponse::success(null, 'Notifikasi berhasil ditandai sebagai sudah dibaca.');
    }
}
