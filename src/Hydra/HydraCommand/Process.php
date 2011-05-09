<?php 
namespace Hydra\HydraCommand;

//use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 *
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Process extends SymfonyCommand
{
	protected $dic = array();

	public function __construct($dic)
	{
		parent::__construct();
		$this->dic = $dic;
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

		$files = $this->dic['finder']['txtFiles'];

		foreach ($files as $file) {

			$verbose = false;
			if($input->getOption('v')) {
				$verbose = true;
			}

			// The name of the final html file is, by default, the name of the txt file
			$wwwFile = reset(explode('.', end(explode('/',$file))));

			if($verbose) $output->writeln('<info>[info]</info> Processing file '.$wwwFile);

			// Get rid of CR and spaces
			$fileArray = file($file);
			array_walk($fileArray, function(&$item, $key) {
				$item = trim($item);
			});                                     

			// Get the metadatas
			$metaDatasStr =  implode("\n", array_slice($fileArray, 0, array_search("---", $fileArray)));                 

			// Parse the metadatas in a array, using defaults when necessary
			$metaDatas = $this->dic['yaml']['parser']->parse($metaDatasStr);
			array_walk($metaDatas, function(&$item, $key, $hydraConf) {
				if($item == "") {
					$item = $hydraConf['metaDatasDefaults'][$key];
				}
			}, $this->dic['conf']);


			// Get the content
			$content = implode("\n", array_slice($fileArray, array_search("---", $fileArray) + 1, sizeof($fileArray)));  

			// Generate the html
			$html = $this->dic['twig']['parser']->render(
				$metaDatas['template'].'.twig',
				array_merge($metaDatas, array("content" => $content))
			); 

			if(isset($metaDatas['fileName']) && $metaDatas['fileName'] != "") {
				$wwwFile = $metaDatas['fileName'];
			}
			if(isset($metaDatas['fileExtension']) && $metaDatas['fileExtension'] != "") {
				$wwwFile .= '.'.$metaDatas['fileExtension'];
			} else {
				$wwwFile .= '.'.$this->dic['conf']['wwwFileExtension'];
			}

			// Write the html
			file_put_contents(
				$this->dic['conf']['wwwDir'].$wwwFile,
				$html
			);                                                     

			if($verbose) $output->writeln('<info>[info]</info> Successfully writed '.str_replace(__DIR__.'/', '', $wwwFile));
		}

		if($verbose) $output->writeln('<info>[info]</info> Done.');

	}
}
