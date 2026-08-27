<?php

test('guests are redirected to login when accessing the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('the login page can be displayed', function () {
    $this->get(route('login'))->assertOk()->assertSee('MOROWALI JUARA');
});
