<?php
// Show the raw REQUEST_URI exactly as the browser sent it — no redirects.
$rawUri   = $_SERVER['REQUEST_URI'];
$path     = parse_url( $rawUri, PHP_URL_PATH );
$rawTitle = null;

if ( preg_match( '#^/wiki/(.+)$#', $path, $m ) ) {
	$rawTitle    = $m[1];   // percent-encoding intact, as browser sent it
	$decodedTitle = rawurldecode( $rawTitle );

	$endsWithDot     = str_ends_with( $rawTitle, '.' );
	$endsWithEncoded = str_ends_with( strtoupper( $rawTitle ), '%2E' );

	if ( $endsWithEncoded ) {
		$verdict      = '%2E reached the server intact';
		$verdictColor = '#2d6a2d';
		$conclusion   = 'The browser preserved the percent-encoding. '
			. 'Markdown linkifiers seeing this URL in plain text will not strip the period.';
	} elseif ( $endsWithDot ) {
		$verdict      = 'Browser decoded %2E → "." before sending';
		$verdictColor = '#8b0000';
		$conclusion   = 'The browser normalised the URL before the request, '
			. 'confirming that a server-side redirect from "." → "%2E" would loop forever. '
			. 'However, %2E in generated &lt;a href&gt; attributes still helps '
			. 'copy-as-link and server-rendered markdown.';
	} else {
		$verdict      = 'No trailing period';
		$verdictColor = '#444';
		$conclusion   = 'Unaffected by this bug.';
	}

	echo <<<HTML
	<!doctype html>
	<html lang="en">
	<head>
	  <meta charset="utf-8">
	  <title>Article: {$decodedTitle}</title>
	  <style>
	    body  { font-family: sans-serif; max-width: 720px; margin: 3rem auto; line-height: 1.5; }
	    .box  { padding: .75rem 1rem; border-radius: 4px; color: #fff;
	            background: {$verdictColor}; margin-bottom: 1.5rem; font-weight: bold; }
	    .note { background: #f5f5f5; padding: .75rem 1rem; border-left: 4px solid #999;
	            margin-bottom: 1.5rem; }
	    code  { background: #eee; padding: .1em .4em; border-radius: 3px; font-size: .95em; }
	    table { border-collapse: collapse; width: 100%; margin-bottom: 1.5rem; }
	    td,th { border: 1px solid #ccc; padding: .4rem .7rem; }
	    th    { background: #f0f0f0; }
	    a     { color: #0645ad; }
	  </style>
	</head>
	<body>
	  <h1>{$decodedTitle}</h1>
	  <div class="box">{$verdict}</div>
	  <div class="note">{$conclusion}</div>

	  <table>
	    <tr><th>What</th><th>Value</th></tr>
	    <tr><td>Raw <code>REQUEST_URI</code> (server perspective)</td>
	        <td><code>{$rawUri}</code></td></tr>
	    <tr><td>Path segment as browser sent it</td>
	        <td><code>{$rawTitle}</code></td></tr>
	    <tr><td>Decoded for display</td>
	        <td><code>{$decodedTitle}</code></td></tr>
	  </table>

	  <p><a href="/">&larr; Back</a></p>
	</body>
	</html>
	HTML;
	exit;
}

// Home page
echo <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>%2E browser normalisation demo</title>
  <style>
    body { font-family: sans-serif; max-width: 720px; margin: 3rem auto; line-height: 1.5; }
    code { background: #eee; padding: .1em .4em; border-radius: 3px; }
    li   { margin-bottom: .5rem; }
  </style>
</head>
<body>
  <h1>Does the browser preserve <code>%2E</code>?</h1>
  <p>
    Each link below loads an article page that reports exactly what
    <code>REQUEST_URI</code> the server received — no redirects involved.
    This answers whether Firefox normalises <code>%2E</code> back to
    <code>.</code> before sending the HTTP request.
  </p>
  <ol>
    <li>
      <a href="/wiki/Mata_v._Avianca,_Inc.">Bare period in href</a>
      — <code>href="/wiki/Mata_v._Avianca,_Inc."</code>
    </li>
    <li>
      <a href="/wiki/Mata_v._Avianca,_Inc%2E">Percent-encoded in href</a>
      — <code>href="/wiki/Mata_v._Avianca,_Inc%2E"</code>
    </li>
    <li>
      <a href="/wiki/Regular_Article">No trailing period</a>
      — <code>href="/wiki/Regular_Article"</code>
    </li>
  </ol>
</body>
</html>
HTML;
