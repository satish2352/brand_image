<?php

return [
     // 'PAGINATION' => env('EMPLOYEES_PAGINATION', 1),
     'PAGINATION' => 10,
     'SEARCH-PAGINATION' => 50,

     'IMAGE_ADD'    => 'upload/images/media',          // storage path
     'IMAGE_DELETE' => 'upload/images/media',          // relative storage path
     'IMAGE_VIEW'   => env('FILE_VIEW') . '/upload/images/media/',
     // 🖥️ Server disk path
     'IMAGE_PATH' => public_path('upload/images/media/'),

];
