<?php

use App\Models\SosAlert;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Semua channel berikut harus melalui auth:sanctum sehingga token API maupun
| sesi stateful sama-sama dapat berlangganan. Data sensitif (SOS, lokasi
| responder) hanya disiarkan ke private channel yang sudah diotorisasi.
|
*/

/**
 * Private channel global dashboard.
 * Dikenakan kepada semua user yang sudah login. Dipakai untuk event data
 * master (sekolah, fasilitas kesehatan, dll.) dan sinkronisasi data publik.
 */
Broadcast::channel('dashboard', function ($user) {
    return true;
});

/**
 * Private channel command center.
 * Hanya operator/admin (yang berhak mengelola SOS) dan responder petugas
 * yang boleh menerima event SOS/lokasi responder/command alert.
 */
Broadcast::channel('command-center', function ($user) {
    return $user->canManageData() || $user->isResponder();
});

/**
 * Private channel SOS per alert.
 * Pemilik SOS, operator/admin, dan responder dapat memantau alert tertentu.
 */
Broadcast::channel('sos.{sosId}', function ($user, $sosId) {
    $sos = SosAlert::find($sosId);

    if (! $sos) {
        return false;
    }

    return $user->can('view', $sos) || $user->responderForCategory($sos->category);
});

/**
 * Private channel notifikasi per user.
 * Memastikan request hanya menerima notifikasinya sendiri.
 */
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
