<?php declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\FinalInternalClassFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__ . '/config.php');

    $services = $containerConfigurator->services();

    $services->defaults()
        ->public()
        ->autowire()
        ->autoconfigure();

    $services->set(FinalInternalClassFixer::class);

    $services->load('Symplify\CodingStandard\Fixer\\', __DIR__ . '/../src/Fixer');

    $services->load('Symplify\CodingStandard\Sniffs\\', __DIR__ . '/../src/Sniffs');

    $services->set(PhpdocLineSpanFixer::class);
};
