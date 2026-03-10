<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    |
    | This value sets the root class namespace for Livewire component classes in
    | your application. This value will change where component auto-discovery
    | finds components. It's also referenced by the file creation commands.
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This value is the path where Livewire's view creation commands will
    | generate new view files. Additionally, this path is checked for
    | Livewire component views during the component rendering phase.
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The view to be used as a layout when rendering a single component
    | via route. By default, Livewire expects a 'components.layouts.app'
    | view. You can customize this layout to perfectly match your app.
    |
    */

    'layout' => 'components.layouts.app',

];
