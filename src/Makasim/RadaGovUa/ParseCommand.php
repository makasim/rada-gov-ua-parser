<?php
namespace Makasim\RadaGovUa;

use ForceUTF8\Encoding;
use Goutte\Client;
use Guzzle\Http\Client as GuzzleClient;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Filesystem\Filesystem;

class ParseCommand extends Command
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('parse')
            ->addArgument('date', InputArgument::OPTIONAL, 'The date of laws to be fetched. The default date is when Constitution was ratified', '1996-06-28')
            ->addOption('dir', null, InputOption::VALUE_OPTIONAL, 'The directory where to store parsed files', getcwd())
        ;

        $this->client = new Client();
        $this->client->setClient(new GuzzleClient('')); // setting explicitly to enable redirects
        $this->client->followRedirects(true);

        $this->filesystem = new Filesystem;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        $date = new \DateTime($input->getArgument('date'));

//        $crawler = $this->requestHttpPage('http://zakon2.rada.gov.ua/laws/main/a'.$date->format('Ymd'));
//
//        $crawler->filter('a[href^="/laws/show"]')->each(function($node) {
//            $articleUrl = $node->link()->getUri();
        $articleUrl = 'http://zakon4.rada.gov.ua/laws/show/2755-17';

            $article = $this->fetchArticle($articleUrl);

            $this->filesystem->dumpFile($this->urlToPath($articleUrl).'.html', $article);

            foreach ($this->fetchArticleEditions($articleUrl) as $articleEditionUrl => $articleEdition) {
                $this->filesystem->dumpFile($this->urlToPath($articleEditionUrl).'.html', $articleEdition);
            }
//        });
    }

    /**
     * @param string $articleUrl
     *
     * @return string
     */
    protected function fetchArticle($articleUrl)
    {
        $this->output->writeln(sprintf('Fetching <info>%s</info>', urldecode($articleUrl)));

        $crawler = $this->client->request('GET', $articleUrl);

        $article = $crawler->filter('#article')->html();

//        $nextPage = $crawler->filter('.pages a[title="наступна сторінка"]');
//        if (count($nextPage) > 0) {
//            $article .= $this->fetchArticle($nextPage->link()->getUri());
//        }

        return Encoding::toUTF8($article);
    }

    /**
     * @param string $articleUrl
     *
     * @return string[]
     */
    protected function fetchArticleEditions($articleUrl)
    {
        $crawler = $this->requestHttpPage($articleUrl);

        $editions = array();


        $crawler->filter('#panel select.form option')->each(function(Crawler $optionNode) use (&$editions){
            $articleEditionUrl = 'http://zakon2.rada.gov.ua/'.ltrim($optionNode->attr('value'), '/');
            $editions[$articleEditionUrl] = $this->fetchArticle($articleEditionUrl);
        });

        return $editions;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    protected function urlToPath($url)
    {
        list(, $path) = explode('rada.gov.ua/', $url);

        $path = Encoding::toUTF8($path);
        $path = urldecode($path);
        $path = str_replace('/show', '', $path);

        return rtrim($this->input->getOption('dir'),'/').'/'.ltrim($path, '/');
    }

    /**
     * @param $url
     *
     * @throws \LogicException
     *
     * @return Crawler
     */
    protected function requestHttpPage($url)
    {
        $crawler = $this->client->request('GET', $url);

        /** @var Response $response */
        $response = $this->client->getResponse();

        if (200 != $response->getStatus()) {
            throw new \LogicException(sprintf(
                "Response on request <info>%s</info> is not succcess. \n\nStatus: %s\nContent: %s\n",
                $url,
                $response->getStatus(),
                $response->getContent()
            ));
        }

        return $crawler;
    }
}
