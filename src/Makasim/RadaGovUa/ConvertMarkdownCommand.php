<?php
namespace Makasim\RadaGovUa;

use ForceUTF8\Encoding;
use Goutte\Client;
use Guzzle\Http\Client as GuzzleClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class ConvertMarkdownCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Finder
     */
    protected $finder;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('convert-md')
            ->addArgument('source-dir', InputArgument::REQUIRED, 'The root dir of where origin html files are located')
            ->addArgument('target-dir', InputArgument::REQUIRED, 'The root dir of where markdown files has to be generated')
        ;

        $this->filesystem = new Filesystem;
        $this->finder = new Finder;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->finder
            ->followLinks()
            ->ignoreVCS(true)
            ->ignoreDotFiles(true)
            ->name('*.html')
            ->files()
        ;

        foreach ($this->finder->in($input->getArgument('source-dir')) as $file) {
            /** @var SplFileInfo $file  */

            $output->writeln(sprintf('Converting <info>%s</info>', $file->getRelativePathname()));

            $md = new \HTML_To_Markdown($file->getContents(), array(
                'header_style' => 'atx',
                'strip_tags' => true,
                'suppress_errors' => false,
                'bold_style'      => '**',
                'italic_style'    => '*',
            ));

            $md = str_replace('. ', ".\n", $md);

            $mdPath = $file->getRelativePathname();
            $mdPath = str_replace('.'.$file->getExtension(), '.md', $mdPath);
            $mdPath = rtrim($input->getArgument('target-dir'), '/').'/'.$mdPath;

            $this->filesystem->dumpFile($mdPath, $md);
        }
    }
}
