<?php

use App\Models\HealthFacility;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Market;
use App\Models\Polsek;
use App\Models\Poskamling;
use App\Models\School;
use App\Models\Subject;
use App\Models\Tipkamtikmas;
use App\Models\User;

it('renders all main views without errors for an admin', function () {
    $admin = User::factory()->admin()->create();

    $kecamatan = Kecamatan::factory()->create();
    $kelurahan = Kelurahan::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $subject = Subject::factory()->create();
    $school = School::factory()->create(['kecamatan_id' => $kecamatan->id, 'kelurahan_id' => $kelurahan->id]);
    $polsek = Polsek::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $tipkamtikmas = Tipkamtikmas::factory()->create();
    $poskamling = Poskamling::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $market = Market::factory()->create(['kecamatan_id' => $kecamatan->id]);
    $facility = HealthFacility::factory()->create(['kecamatan_id' => $kecamatan->id]);

    $this->actingAs($admin);

    $urls = [
        route('dashboard'),
        route('education.dashboard'),
        route('security.dashboard'),
        route('health.dashboard'),
        route('maps.index'),

        route('education.schools.index'),
        route('education.schools.create'),
        route('education.schools.show', $school),
        route('education.schools.edit', $school),

        route('security.polseks.index'),
        route('security.polseks.create'),
        route('security.polseks.show', $polsek),

        route('security.tipkamtikmas.index'),
        route('security.tipkamtikmas.create'),

        route('security.poskamlings.index'),
        route('security.poskamlings.create'),

        route('security.markets.index'),
        route('security.markets.create'),

        route('health.facilities.index'),
        route('health.facilities.create'),
        route('health.facilities.show', $facility),
        route('health.facilities.edit', $facility),

        route('master.kecamatans.index'),
        route('master.kecamatans.create'),
        route('master.kecamatans.show', $kecamatan),
        route('master.kecamatans.edit', $kecamatan),

        route('master.kelurahans.index'),
        route('master.kelurahans.create'),
        route('master.kelurahans.show', $kelurahan),
        route('master.kelurahans.edit', $kelurahan),

        route('master.subjects.index'),
        route('master.subjects.create'),
        route('master.subjects.show', $subject),

        route('users.index'),
        route('users.create'),
        route('users.show', $admin),
        route('users.edit', $admin),
        route('profile.edit'),
    ];

    foreach ($urls as $url) {
        $this->get($url)->assertOk();
    }
});
