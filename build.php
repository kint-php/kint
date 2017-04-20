<?php

use Symfony\Component\Finder\Finder;

require_once __DIR__.'/vendor/autoload.php';

$finder = new Finder();
$finder->files()->name('*.php')->in(__DIR__.'/src');

$files = iterator_to_array($finder);
sort($files);
$files[] = __DIR__.'/init_footer.php';
$files[] = __DIR__.'/init_helpers.php';

$output = '<?php';

foreach ($files as $file) {
    $output .= substr(file_get_contents($file), 5); // strip opening tag
}

// Add CSS and JS
$output .= 'Kint_Renderer_Rich::$pre_render_sources[\'script\'] = ';
$output .= var_export(array(
    file_get_contents(__DIR__.'/resources/compiled/rich.js').file_get_contents(__DIR__.'/resources/compiled/rich_microtime.js'),
), true);
$output .= ";\n";
$output .= 'Kint_Renderer_Plain::$pre_render_sources[\'style\'] = ';
$output .= var_export(array(
    file_get_contents(__DIR__.'/resources/compiled/plain.css'),
), true);
$output .= ";\n";

mkdir(__DIR__.'/build');
file_put_contents(__DIR__.'/build/kint.php', $output);

$output = file_get_contents(__DIR__.'/init_header.php');
$output .= ltrim(substr(php_strip_whitespace(__DIR__.'/build/kint.php'), 5));

// Attach and write the different styles
$styles = array(
    'kint.php' => 'original.css',
    'kint-aante-light.php' => 'aante-light.css',
    'kint-solarized.php' => 'solarized.css',
    'kint-solarized-dark.php' => 'solarized-dark.css',
);

foreach ($styles as $outfile => $stylefile) {
    $out = $output;
    $out .= 'Kint_Renderer_Rich::$pre_render_sources[\'style\'] = ';
    $out .= var_export(array(
        file_get_contents(__DIR__.'/resources/compiled/'.$stylefile),
    ), true);
    $out .= ";\n";
    file_put_contents(__DIR__.'/build/'.$outfile, $out);
}
