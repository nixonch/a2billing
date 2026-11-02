<?php
// Keep the reports' explicit colours and built-in GD fonts when loading JpGraph.
// Define settings before the Composer package reads jpg-config.inc.php.
require_once __DIR__ . '/vendor/autoload.php';
if (!defined('DEFAULT_THEME_CLASS')) define('DEFAULT_THEME_CLASS', '');
// FF_FONT1 is defined by JpGraph itself; its value is 1.
if (!defined('FF_DEFAULT')) define('FF_DEFAULT', 1);
\mitoteam\jpgraph\MtJpGraph::load();

function a2b_jpgraph_no_data($width, $height)
{
    \mitoteam\jpgraph\MtJpGraph::load('canvas');
    $graph = new CanvasGraph($width, $height);
    $text = new Text(gettext('No data'), $width / 2, $height / 2);
    $text->SetAlign('center', 'center');
    $graph->AddText($text);
    $graph->Stroke();
}
