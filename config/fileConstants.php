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
      * Largest images ZIP accepted alongside a bulk upload sheet, in kilobytes.
      *
      * Keep it at or below the smaller of PHP's upload_max_filesize and
      * post_max_size — on cPanel both are set from MultiPHP INI Editor, and a
      * file above either limit never reaches Laravel at all, so the admin would
      * just see an empty page instead of a validation message.
      */
     'IMAGE_IMPORT_ZIP_MAX_KB' => env('MEDIA_IMPORT_ZIP_MAX_KB', 51200), // 50MB

     /**
      * Bulk upload fetches "Image URLs" / "Panorama Image URL" cells that hold a
      * https:// link over the network, so by default it refuses hosts that
      * resolve to private or reserved ranges (localhost, 10.x, 192.168.x,
      * link local ...).
      *
      * Set MEDIA_IMPORT_ALLOW_PRIVATE_HOSTS=true to import from an intranet or
      * a local XAMPP URL while developing.
      */
     'IMAGE_IMPORT_ALLOW_PRIVATE_HOSTS' => env('MEDIA_IMPORT_ALLOW_PRIVATE_HOSTS', false),

     /**
      * Legacy escape hatch: read images straight off the server's own disk when
      * an Image cell holds an absolute path (C:\Users\me\Downloads\site.jpg,
      * \\server\share\site.jpg, /home/me/site.jpg) and no images ZIP was
      * uploaded.
      *
      * Only ever correct on a localhost install, where the person filling the
      * sheet and the server are the same machine. On a real server that path
      * belongs to the admin's laptop, which the server cannot see — hence the
      * "local file was not found on the server disk" error. Off by default:
      * the images ZIP is the supported way to bring pictures along.
      */
     'IMAGE_IMPORT_ALLOW_LOCAL_PATHS' => env('MEDIA_IMPORT_ALLOW_LOCAL_PATHS', false),

];
