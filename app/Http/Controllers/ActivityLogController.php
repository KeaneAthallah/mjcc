<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(private readonly ActivityLogService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        return view('audit.index', [
            'logs' => $this->service->paginate($request),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'actions' => [
                ActivityLog::ACTION_LOGIN,
                ActivityLog::ACTION_LOGOUT,
                ActivityLog::ACTION_CREATE,
                ActivityLog::ACTION_UPDATE,
                ActivityLog::ACTION_DELETE,
                ActivityLog::ACTION_RESTORE,
                ActivityLog::ACTION_FORCE_DELETE,
                ActivityLog::ACTION_ROLE_CHANGE,
                ActivityLog::ACTION_SOS_CREATED,
                ActivityLog::ACTION_SOS_ACKNOWLEDGED,
                ActivityLog::ACTION_SOS_RESPONDING,
                ActivityLog::ACTION_SOS_RESOLVED,
                ActivityLog::ACTION_SOS_CANCELLED,
                ActivityLog::ACTION_ALERT_STATUS,
            ],
            'resources' => collect([
                'School', 'HealthFacility', 'Kecamatan', 'Kelurahan', 'Polsek',
                'Market', 'Poskamling', 'Tipkamtikmas', 'Subject', 'User', 'SosAlert',
            ]),
        ]);
    }
}
