<?php declare(strict_types=1);

namespace Symplify\Monorepo\Console\Command;

use GitWrapper\GitWrapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\Monorepo\Configuration\ConfigurationGuard;
use Symplify\Monorepo\Filesystem\Filesystem;
use Symplify\Monorepo\PackageToRepositorySplitter;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class SplitCommand extends Command
{
    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    /**
     * @var ConfigurationGuard
     */
    private $configurationGuard;

    /**
     * @var GitWrapper
     */
    private $gitWrapper;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var PackageToRepositorySplitter
     */
    private $packageToRepositorySplitter;

    public function __construct(
        ParameterProvider $parameterProvider,
        ConfigurationGuard $configurationGuard,
        GitWrapper $gitWrapper,
        SymfonyStyle $symfonyStyle,
        Filesystem $filesystem,
        PackageToRepositorySplitter $packageToRepositorySplitter
    ) {
        $this->parameterProvider = $parameterProvider;
        $this->configurationGuard = $configurationGuard;
        $this->gitWrapper = $gitWrapper;
        $this->symfonyStyle = $symfonyStyle;
        $this->filesystem = $filesystem;
        $this->packageToRepositorySplitter = $packageToRepositorySplitter;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription('Split monolithic repository from provided config to many repositories.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $splitConfig = $this->parameterProvider->provideParameter('split');
        $this->configurationGuard->ensureConfigSectionIsFilled($splitConfig, 'split');

        // git subsplit init .git
        $gitWorkingCopy = $this->gitWrapper->workingCopy(getcwd());

        // @todo check exception if subsplit alias not installed
        if (! file_exists(getcwd() . '/.subsplit')) {
            $gitWorkingCopy->run('subsplit', ['init', '.git']);
            $this->symfonyStyle->success(sprintf(
                'Directory "%s" with local clone created',
                $this->getSubsplitDirectory()
            ));
        }

        $this->packageToRepositorySplitter->splitDirectoriesToRepositories($gitWorkingCopy, $splitConfig);

        $this->filesystem->deleteDirectory($this->getSubsplitDirectory());
        $this->symfonyStyle->success(sprintf('Directory "%s" cleaned', $this->getSubsplitDirectory()));
    }

    private function getSubsplitDirectory(): string
    {
        return getcwd() . '/.subsplit';
    }
}