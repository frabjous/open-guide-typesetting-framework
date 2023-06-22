<?php

if (!(isset($err_page_msg))) {
    $err_page_msg = "There was an error accessing this web page.";
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="robots" content="noindex,nofollow" />  
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="apple-mobile-web-app-capable" content="yes" />
        <meta name="mobile-web-app-capable" content="yes" />
        <title>Error</title>
    </head>
    <body>
        <p><?php echo $err_page_msg; ?></p>
    </body>
</html>