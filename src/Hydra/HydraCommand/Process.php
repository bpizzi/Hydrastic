<?php 
namespace Hydra\HydraCommand;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Finder\Finder as SymfonyFinder;

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
			//->setDefinition(array())
			->setDescription('Generate your website')
			->setHelp(<<<EOF
The <info>hydra:process</info> command generate your website !
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$output->writeln($this->dic['conf']['command_prefix'].' Started hydration of your text files');

		$files = $this->dic['finder']['txt_files'];

		$output->writeln($this->dic['conf']['command_prefix'].' Found <comment>'.count($files).' '.$this->dic['conf']['txt_file_extension'].'</comment> files');

		foreach ($files as $file) {

			// The name of the final html file is, by default, the name of the txt file
			$wwwFile = reset(explode('.', end(explode('/',$file->getRealPath()))));

			$output->writeln($this->dic['conf']['command_prefix'].' Processing file <comment>'.$wwwFile.' ('.$file.')</comment>');

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
					$item = $hydraConf['metadata_defaults'][$key];
				}
			}, $this->dic['conf']);


			// Get the content
			$content = implode("\n", array_slice($fileArray, array_search("---", $fileArray) + 1, sizeof($fileArray)));  

			// Generate the html
			if($output->getVerbosity()==2) {
				foreach ($metaDatas as $k => $v) {
					$output->writeln($this->dic['conf']['command_prefix'].'   ... with metadata <comment>'.$k.'</comment> => <comment>'.$v.'</comment>');
				}
				$output->writeln($this->dic['conf']['command_prefix'].'   ... and a content of <comment>'.strlen($content).'</comment> char(s) (<comment>'.str_word_count($content).'</comment> word(s))');
			}

			$html = $this->dic['twig']['parser']->render(
				$metaDatas['template'].'.twig',
				array_merge($metaDatas, array("content" => $content))
			); 

			if(isset($metaDatas['filename']) && $metaDatas['filename'] != "") {
				$wwwFile = $metaDatas['filename'];
			}
			if(isset($metaDatas['file_extension']) && $metaDatas['file_extension'] != "") {
				$wwwFile .= '.'.$metaDatas['file_extension'];
			} else {
				$wwwFile .= '.'.$this->dic['conf']['www_file_extension'];
			}

			// Write the html
			file_put_contents(
				$this->dic['working_directory'].'/'.$this->dic['conf']['www_dir'].'/'.$wwwFile,
				$html
			);                                                     

			$output->writeln($this->dic['conf']['command_prefix'].' Successfully hydrated <comment>'.str_replace(__DIR__.'/', '', $wwwFile).'</comment>');
		}

		$output->writeln($this->dic['conf']['command_prefix'].' Done.');

	}
}
