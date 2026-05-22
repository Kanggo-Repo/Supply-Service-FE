<?php

test('root redirects to the materials workspace', function () {
    $response = $this->get('/');

    $response->assertRedirect('/materials');
});
