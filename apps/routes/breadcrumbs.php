<?php

// routes/breadcrumbs.php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;
// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Home
Breadcrumbs::for('home', function (BreadcrumbTrail $trail) {
    $trail->push('Home', route('home'));
});

// Home > About MAMIAS
Breadcrumbs::for('about', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('About', route('about'));
});

// My profile
Breadcrumbs::for('profile', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('Profile', route('profile'));
});

// My Bibliographic References
Breadcrumbs::for('references', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Bibliographic References', route('references'));
});

// My Species Reports
Breadcrumbs::for('my-species-reports', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Species Reports', route('my-species-reports'));
});

// My Species Suggestions
Breadcrumbs::for('suggestions', function (BreadcrumbTrail $trail) {
    $trail->parent('home');
    $trail->push('My Suggestions', route('suggestions'));
});

// CMS pages
Breadcrumbs::for('layup.page.show', function (BreadcrumbTrail $trail, $page = null) {
    $trail->parent('home');
    if ($page) {
        $trail->push($page->title, url('pages/'.$page->path));
    }
});
