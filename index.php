<?php
// Show the raw REQUEST_URI exactly as the browser sent it — no redirects.
$rawUri = $_SERVER['REQUEST_URI'];
$path   = parse_url( $rawUri, PHP_URL_PATH );

// Route: /wiki/(dot|encoded)/<title>  — canonical controlled by path prefix
// Route: /wiki/<title>                — no trailing period baseline
if ( preg_match( '#^/wiki/(dot|encoded)/(.+)$#', $path, $m ) ) {
	$canonParam   = $m[1];
	$rawTitle     = $m[2];
} elseif ( preg_match( '#^/wiki/([^/]+)$#', $path, $m ) ) {
	$canonParam   = null;
	$rawTitle     = $m[1];
} else {
	$canonParam = null;
	$rawTitle   = null;
}

if ( $rawTitle !== null ) {
	$decodedTitle = rawurldecode( $rawTitle );

	$endsWithDot     = str_ends_with( $rawTitle, '.' );
	$endsWithEncoded = str_ends_with( strtoupper( $rawTitle ), '%2E' );

	$scheme = isset( $_SERVER['HTTPS'] ) ? 'https' : 'http';
	$host   = $_SERVER['HTTP_HOST'];

	// Strip the trailing period/encoding to get the bare base title
	$baseTitle = $endsWithEncoded
		? substr( $rawTitle, 0, -3 )
		: ( $endsWithDot ? substr( $rawTitle, 0, -1 ) : $rawTitle );

	if ( $canonParam === 'dot' ) {
		$canonicalTitle = $baseTitle . '.';
	} elseif ( $canonParam === 'encoded' ) {
		$canonicalTitle = $baseTitle . '%2E';
	} else {
		$canonicalTitle = $rawTitle;
	}

	$canonPrefix  = $canonParam ? $canonParam . '/' : '';
	$canonicalUrl = $scheme . '://' . $host . '/wiki/' . $canonPrefix . $canonicalTitle;

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

	$canonDisplay = htmlspecialchars( $canonicalUrl );

	echo <<<HTML
	<!doctype html>
	<html lang="en">
	<head>
	  <meta charset="utf-8">
	  <title>Article: {$decodedTitle}</title>
	  <link rel="canonical" href="{$canonicalUrl}">
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
	    <tr><td><code>link rel=canonical</code></td>
	        <td><code>{$canonDisplay}</code></td></tr>
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
    td,th { border: 1px solid #ccc; padding: .4rem .7rem; }
    th    { background: #f0f0f0; }
    table { border-collapse: collapse; margin-top: 1rem; }
    a   { color: #0645ad; }
  </style>
</head>
<body>
  <h1>Does the browser preserve <code>%2E</code>?</h1>
  <p>
    Each link tests a combination of the URL form in the <code>href</code>
    and what the page declares as its <code>link rel=canonical</code>.
    The trailing period (or <code>%2E</code>) is always the last character
    of the path.
  </p>
  <table>
    <tr>
      <th>#</th>
      <th>href form</th>
      <th>canonical form</th>
      <th>Link</th>
    </tr>
    <tr>
      <td>1</td>
      <td>bare <code>.</code></td>
      <td>bare <code>.</code></td>
      <td><a href="/wiki/dot/Mata_v._Avianca,_Inc.">/wiki/dot/Mata_v._Avianca,_Inc.</a></td>
    </tr>
    <tr>
      <td>2</td>
      <td>bare <code>.</code></td>
      <td><code>%2E</code></td>
      <td><a href="/wiki/encoded/Mata_v._Avianca,_Inc.">/wiki/encoded/Mata_v._Avianca,_Inc.</a></td>
    </tr>
    <tr>
      <td>3</td>
      <td><code>%2E</code></td>
      <td>bare <code>.</code></td>
      <td><a href="/wiki/dot/Mata_v._Avianca,_Inc%2E">/wiki/dot/Mata_v._Avianca,_Inc%2E</a></td>
    </tr>
    <tr>
      <td>4</td>
      <td><code>%2E</code></td>
      <td><code>%2E</code></td>
      <td><a href="/wiki/encoded/Mata_v._Avianca,_Inc%2E">/wiki/encoded/Mata_v._Avianca,_Inc%2E</a></td>
    </tr>
    <tr>
      <td>5</td>
      <td colspan="2">no trailing period (baseline)</td>
      <td><a href="/wiki/Regular_Article">/wiki/Regular_Article</a></td>
    </tr>
  </table>
</body>
</html>
HTML;
