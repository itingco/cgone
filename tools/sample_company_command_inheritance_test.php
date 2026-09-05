<?php
$commandFile = $argv[1] ?? dirname(__DIR__).'/app/Console/Commands/SeedSampleCompany.php';
$php = PHP_BINARY;
$probe = <<<'CODE'
namespace Illuminate\Console {
    class Command {
        public const SUCCESS = 0;
        public const FAILURE = 1;
        public function line($string, $style = null, $verbosity = null) {}
    }
}
namespace {
    require $argv[1];
    echo "COMMAND_CLASS_LOAD_OK\n";
}
CODE;
$tmp = tempnam(sys_get_temp_dir(), 'cgone-command-probe-').'.php';
file_put_contents($tmp, "<?php\n".$probe);
$cmd = escapeshellarg($php).' '.escapeshellarg($tmp).' '.escapeshellarg($commandFile).' 2>&1';
exec($cmd, $output, $status);
@unlink($tmp);
if ($status !== 0) {
    fwrite(STDERR, implode(PHP_EOL, $output).PHP_EOL);
    exit(1);
}
echo implode(PHP_EOL, $output).PHP_EOL;
