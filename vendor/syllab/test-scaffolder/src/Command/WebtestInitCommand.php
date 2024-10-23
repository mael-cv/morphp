<?php

namespace Syllab\TestScaffolder\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Config;
use Composer\Autoload\ClassLoader;

#[AsCommand(
    name: 'webtest:init',
    description: 'Create a test project for web functional testing using phpunit and selenium webdriver',
)]
class WebtestInitCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('namespace', InputArgument::OPTIONAL, 'The namespace of the test project')
            ->addArgument('driver', InputArgument::OPTIONAL, 'The webdriver to use', null, ['firefox', 'chrome', 'microsoftEdge'])
            ->addOption('server', 's', InputOption::VALUE_OPTIONAL, 'The WebDriver server url', 'http://localhost:4444')
            ->addOption('empty', null, InputOption::VALUE_OPTIONAL, 'No sample code will be added', false)
        ;
    }

    private static function generate($to_dir, $from_dir, $filename, $edits) {
        $fileContent = file_get_contents("$from_dir/$filename.dist");
        foreach($edits as $search => $replace) {
            $fileContent = str_replace($search, $replace, $fileContent);
        }
        file_put_contents("$to_dir/$filename", $fileContent);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $vendorDir = dirname(dirname($reflection->getFileName()));
        $projectRoot = dirname($vendorDir);

        $io = new SymfonyStyle($input, $output);
        $namespace = $input->getArgument('namespace');
        $driver = $input->getArgument('driver');
        $server = $input->getOption('server');

        if (!$namespace) {
            $composerConfig = json_decode(file_get_contents("$projectRoot/composer.json"), true); 
            $defaultNamespace = isset($composerConfig['autoload']['psr-4']) && count($composerConfig['autoload']['psr-4'])
                ? key($composerConfig['autoload']['psr-4'])
                : 'App\\';
            $namespace = $io->ask('What is the namespace of the test project?', $defaultNamespace.'Tests');
        }
        if (!$driver) {
            $driver = $io->choice('Which webdriver do you want to use?', ['firefox', 'chrome', 'microsoftEdge'], 'firefox');
        }
        
        $testDir = "$projectRoot/tests";
        is_dir($testDir) || mkdir($testDir);
        is_dir("$testDir/functional") || mkdir("$testDir/functional");
        is_dir("$testDir/unit") || mkdir("$testDir/unit") && touch("$testDir/unit/.gitkeep");
                
        $resourceDir = __DIR__.'/../Resources';
        $edits = ['Syllab\\TestScaffolder\\Resources'=>$namespace, 'firefox'=>$driver];
        $this::generate($projectRoot, $resourceDir, 'phpunit.xml', []);
        $this::generate($testDir    , $resourceDir, 'bootstrap.php', []);
        if (!$input->getOption('empty')) {
            $this::generate("$testDir/functional", $resourceDir, 'SampleWebTest.php', $edits);               
        }
        if($input->getOption('server')) {
            $edits['http://localhost:4444'] = $input->getOption('server');
        }
        $this::generate("$testDir/functional", $resourceDir, "WebtestBase.php", $edits);
        
        $io->success(
            'Don\'t forget to add "'.str_replace('\\', '\\\\', $namespace).'\\\\": "tests/" '
            .'in autoload/psr-4 section of your composer.json and run : composer dump-autoload'
        );
        return Command::SUCCESS;
    }
}
