<?php

return [
     'PAGINATION' => env('EMPLOYEES_PAGINATION', 10),

     // 'IMAGE_ADD' => 'app/public/upload/images/media/',

     // 'IMAGE_DELETE'              => '/upload/images/media/',
     // 'IMAGE_VIEW'              => env("FILE_VIEW") . '/upload/images/media/',

     /*
    |--------------------------------------------------------------------------
    | Storage path (used with Storage::disk('public'))
    |--------------------------------------------------------------------------
    | Actual path:
    | storage/app/public/upload/images/media/
    */
     'IMAGE_ADD' => 'upload/images/media',

     /*
    |--------------------------------------------------------------------------
    | Public delete path (after storage:link)
    |--------------------------------------------------------------------------
    | public/storage/upload/images/media/
    */
     'IMAGE_DELETE' => 'storage/upload/images/media/',

     /*
    |--------------------------------------------------------------------------
    | Image view URL
    |--------------------------------------------------------------------------
    */
     'IMAGE_VIEW' => env('APP_URL') . '/storage/upload/images/media/',

];
