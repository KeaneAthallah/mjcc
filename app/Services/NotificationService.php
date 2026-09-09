<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SosAlert;
use App\Models\User;

class NotificationService
{
    /**
     * Find users with matching responder_type and create notifications for them.
     */
    public function notifyRelevantResponders(SosAlert $sos): void
    {
        $responderType = $sos->category;

        User::query()
            ->where('responder_type', $responderType)
            ->where('id', '!=', $sos->user_id)
            ->each(function (User $responder) use ($sos) {
                $this->notifyUser(
                    $responder,
                    'SOS Baru',
                    "Ada SOS kategori {$sos->categoryLabel} yang membutuhkan bantuan Anda.",
                    'sos',
                    ['sos_alert_id' => $sos->id, 'category' => $sos->category],
                );
            });
    }

    /**
     * Create a notification for a specific user.
     */
    public function notifyUser(User $user, string $title, string $body, string $type, array $data = []): Notification
    {
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        return $notification;
    }

    /**
     * Mark a notification as read, ensuring it belongs to the user.
     */
    public function markAsRead(int $notificationId, User $user): void
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $user->id)
            ->first();

        if ($notification !== null && $notification->read_at === null) {
            $notification->markAsRead();
        }
    }
}
