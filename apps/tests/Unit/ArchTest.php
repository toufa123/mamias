<?php

arch('no debugging calls are left in app code')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('policies stay policies')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');
