<?php
/**
 * OpenShelf Legacy Edit Profile Redirector
 * Redirects users accessing the old edit profile URL to the new unified Settings page.
 */

header("HTTP/1.1 301 Moved Permanently");
header("Location: /settings/");
exit;