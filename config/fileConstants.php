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

     /**
      * Bulk upload fetches the "Image URLs" / "Panorama Image URL" columns over
      * the network, so by default it refuses hosts that resolve to private or
      * reserved ranges (localhost, 10.x, 192.168.x, link local ...).
      *
      * Set MEDIA_IMPORT_ALLOW_PRIVATE_HOSTS=true to import from an intranet or
      * a local XAMPP URL while developing.
      */
     'IMAGE_IMPORT_ALLOW_PRIVATE_HOSTS' => env('MEDIA_IMPORT_ALLOW_PRIVATE_HOSTS', false),

     /**
      * Bulk upload can also read images straight off the server's own disk when
      * the "Image URLs" / "Panorama Image URL" cell holds a local file path
      * (e.g. C:\Users\me\Downloads\site.jpg or \\server\share\site.jpg).
      *
      * This only makes sense on a self-hosted / localhost install where the
      * person filling the sheet and the server are the same machine. DISABLE it
      * on any shared or production server (MEDIA_IMPORT_ALLOW_LOCAL_PATHS=false)
      * — a local path there would read the server's files, not the user's.
      */
     'IMAGE_IMPORT_ALLOW_LOCAL_PATHS' => env('MEDIA_IMPORT_ALLOW_LOCAL_PATHS', true),

];
