<?php

it('redirects to login for unauthenticated users', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
