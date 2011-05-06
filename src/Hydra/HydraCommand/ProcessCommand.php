<?php 
namespace Hydra\HydraCommand;

//use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Parser as YamlParser;

use Hydra\Container\TwigContainer;

/**
 *
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class ProcessCommand extends SymfonyCommand
{
	protected $hConf = array();

	protected $hContainer = array();

	public function setHcontainer($hContainer)
	{
		
		printf("\nloaded ProcessCommand->setHcontainer\n");

		$this->hContainer = $hContainer;
	}

	/**
	 * @see Command
	 */
	protected function configure()
	{
		$this
			->setName('hydra:process')
			->setDefinition(array(
				new InputOption('v', '', InputOption::VALUE_NONE, 'Be verbose or not'),
				new InputOption('vv', '', InputOption::VALUE_NONE, 'Be very verbose or shut the fuck up'),
			))
			->setDescription('Generate your website')
			->setHelp(<<<EOF
The <info>hydra:process</info> generate your website !
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$finder = new Finder();
		$finder->files()
			->ignoreVCS(true)
			->name('*.txt')
			->in($this->hContainer['conf']['txtDir']);

		foreach ($finder as $file) {
			printf("Found $file...\n");

			// The name of the final html file is, by default, the name of the txt file
			$wwwFile = reset(explode('.', end(explode('/',$file))));

			printf("Processing $wwwFile...");

			// Get rid of CR and spaces
			$fileArray = file($file);
			array_walk($fileArray, function(&$item, $key) {
				$item = trim($item);
			});                                     

			// Get the metadatas
			$metaDatasStr =  implode("\n", array_slice($fileArray, 0, array_search("---", $fileArray)));                 

			// Parse the metadatas in a array, using defaults when necessary
			$yamlParser = new YamlParser();
			$metaDatas = $yamlParser->parse($metaDatasStr);
			array_walk($metaDatas, function(&$item, $key, $hydraConf) {
				if($item == "") {
					$item = $hydraConf['metaDatasDefaults'][$key];
				}
			}, $this->hContainer['conf']);


			// Get the content
			$content = implode("\n", array_slice($fileArray, array_search("---", $fileArray) + 1, sizeof($fileArray)));  

			// Generate the html
			$this->hContainer['twig'] = $this->hContainer->share(function ($c) { return new TwigContainer($c); }); 
			$html = $this->hContainer['twig']['parser']->render(
				$metaDatas['template'].'.twig',
				array_merge($metaDatas, array("content" => $content))
			); 

			if(isset($metaDatas['fileName']) && $metaDatas['fileName'] != "") {
				$wwwFile = $metaDatas['fileName'];
			}
			if(isset($metaDatas['fileExtension']) && $metaDatas['fileExtension'] != "") {
				$wwwFile .= '.'.$metaDatas['fileExtension'];
			} else {
				$wwwFile .= '.'.$this->hContainer['conf']['wwwFileExtension'];
			}

			// Write the html
			file_put_contents(
				$this->hContainer['conf']['wwwDir'].$wwwFile,
				$html
			);                                                     

			printf(" successfully writed ".str_replace(__DIR__.'/', '', $wwwFile)." !\n");
		}

		printf("Done !\n");

	}
}
