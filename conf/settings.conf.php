<?php
# Edit this config file to control the uploader behaviour

# Where to store uploaded files? This should be outside the webserver root!
$conf['uploaddir'] = '/tmp/';

# A title to display, maybe your company name?
$conf['title'] = 'Crocofile';

# The logo to display. maybe your company logo?
$conf['icon'] = 'img/crocodile-icon.png';

# Store passwords hashed (1) or in plain text (0)
# you need to delete your users.conf.php whenever you change this
$conf['passhash'] = 1;
