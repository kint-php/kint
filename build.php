<?php

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Finder\Finder;
use ClassPreloader\Application;

require_once __DIR__.'/vendor/autoload.php';

$finder = new Finder();
$finder->files()->name('*.php')->in(__DIR__.'/src');

$files = iterator_to_array($finder);
sort($files);
$files[] = __DIR__.'/init_footer.php';
$files[] = __DIR__.'/init_helpers.php';

$outfile = __DIR__.'/build/kint.php';

$input = new ArrayInput(array(
    'command' => 'compile',
    '--config' => implode(',', $files),
    '--fix_dir' => '0',
    '--fix_file' => '0',
    '--strip_comments' => '1',
    '-v' => '1',
    '--output' => $outfile,
));

$builder = new Application();
$builder->setAutoExit(false);
$builder->run($input);

// Strip namespaces for sub 5.3
$output = file_get_contents($outfile);
$output = preg_replace('/}\s*namespace\s*{/', '', $output);
$output = preg_replace('/}\s*$/', '', $output);

// Replace the leading namespace with the full init_header, including license comment
$output = preg_replace('/^<\?php\s*namespace\s*{/', file_get_contents(__DIR__.'/init_header.php'), $output);

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
