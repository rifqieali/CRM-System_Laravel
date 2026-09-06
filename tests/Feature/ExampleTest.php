<?php

test('homepage returns 200 in phase 1 (auth enforced in phase 4)', function () {
    $this->get('/')->assertOk();
});
