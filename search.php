<?php

require 'lib/AnsiToRgb.php';

$_styleStack = 0;
$_styles = array(
  '@d' => 0,
  '@D' => 8,
  '@r' => 1,
  '@R' => 9,
  '@g' => 2,
  '@G' => 10,
  '@y' => 3,
  '@Y' => 11,
  '@b' => 4,
  '@B' => 12,
  '@m' => 5,
  '@M' => 13,
  '@c' => 6,
  '@C' => 14,
  '@w' => 7,
  '@W' => 15,
  '@p' => 135,
  '@P' => 207,
  '@o' => 130,
  '@O' => 202,
  '@+' => 'font-weight: bold',
  '@-' => 'text-decoration: blink',
  '@_' => 'text-decoration: underline',
  '@=' => 'background-color: #fff', // not fully implemented yet
);

function styleize ($matches) {
  global $_styles, $_styleStack;

  if ($matches[0] == '@n') { // reset styles
    $ret = '';
    for (; $_styleStack > 0; $_styleStack--) $ret .= '</span>';
    return $ret;
  } else if ($matches[0] == '@*') { // escaped @
    return '@';
  }

  if (array_key_exists($matches[0], $_styles)) {
    // convert xterm code to rgb hex
    if (is_int($_styles[$matches[0]])) {
      $_styles[$matches[0]] = 'color: #' .
          AnsiToRgb::toRgbHex($_styles[$matches[0]]);
    }

    $_styleStack++;
    return '<span style="' . $_styles[$matches[0]] . '">';
  } else {
    return $matches[0];
  }
}

$search = empty($_GET['q']) ? null : trim($_GET['q']);

$title = empty($search) ? 'CircleMUD Help Search' :
    'Search results for "' . htmlentities($search) . '"';

$head = <<<HEAD
  <html>
    <head>
      <title>$title</title>
      <style>
        body {
  	      font-family: monospace;
  	      background-color: #000;
  	      color: #EEE;
        }
      </style>
    </head>
    <body>
HEAD;

$foot = <<<FOOT
    </body>
  </html>
FOOT;

$form = <<<FORM
      <h1>$title</h1><hr/>
      <form method="get" action="">
        <input type="text" name="q" value=""/>
        <input type="submit" value="Search"/>
      </form><hr/>
FORM;

print $head;
print $form;
$search = strtoupper($search);
$fh = $search ? fopen("help.hlp","r") : null;

if ($fh) while($line = fgets($fh)) {
  $article = '';

	$match = strpos($line, $search) !== false;
	while (strpos($line = fgets($fh),'#') !== 0) {
	  if ($match) $article .= $line;
	}

  if ($match && substr($line, 1) > 0) {
    $article .= sprintf("\n@DMinimum level to view entry: %d\n", substr($line, 1));
  }

  if ($match) {
    print '<pre>';
    print preg_replace_callback('/@.?/i', 'styleize', $article);
    // close any outstanding styles to prevent bleeding across articles
    for (; $_styleStack > 0; $_styleStack--) print '</span>';
    print '</pre>';
    break;
  }
}

if ($fh) fclose($fh);
print $foot;

?>

