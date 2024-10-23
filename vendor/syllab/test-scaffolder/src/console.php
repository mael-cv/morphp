<?php

$vendorDir = file_exists(__DIR__ . '/../vendor') ? __DIR__ . '/../vendor' : __DIR__ . '/../../../../vendor';

require "$vendorDir/autoload.php";

use Symfony\Bundle\MakerBundle\Command\MakerCommand;
use Symfony\Component\Console\Application;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\Util\TemplateLinter;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Maker\MakeCommand;

use Syllab\TestScaffolder\Command\WebtestInitCommand;
use Syllab\TestScaffolder\Command\WebtestClassCommand;

$app  = new Application();
$fileManager = new FileManager(
    new \Symfony\Component\Filesystem\Filesystem(),
    new \Symfony\Bundle\MakerBundle\Util\AutoloaderUtil(
        new \Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder('Syllab\\TestScaffolder\\Command')
    ),
    new \Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter(),
    __DIR__
);
$cmd = new MakerCommand(
    $maker = new MakeCommand(),
    $fileManager, 
    new Generator($fileManager, 'Syllab\\TestScaffolder'), 
    new TemplateLinter()
);
$cmd->setName($maker::getCommandName());
$cmd->setDescription($maker::getCommandDescription());

$app->add($cmd);
$app->add(new Syllab\TestScaffolder\Command\WebtestInitCommand());
$app->run();