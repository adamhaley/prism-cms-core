<?php

use PrismCms\Models\Page;

return [

    /*
    | The site's Page model: PrismCms\Models\Page itself, or a site model that extends it to add
    | its own columns and relations.
    */
    'models' => [
        'page' => Page::class,
    ],

    /*
    | The site's template enum. It implements PrismCms\Contracts\PageTemplate, so each template
    | says which view renders it, which form sections it uses and what extra data it needs.
    */
    'page_template' => 'App\\Enums\\PageTemplate',

    /*
    | Register the home route and the catch-all route that resolves every other URL to a page by
    | its path. Turn off to register PrismCms\Http\Controllers\PageController routes yourself.
    */
    'routes' => true,

];
