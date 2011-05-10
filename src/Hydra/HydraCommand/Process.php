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
    protected $verbose = false;
	protected $veryverbose = false;

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
The <info>hydra:process</info> command generate your website !
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		if($input->getOption('v')) {
			$this->verbose = true;
			$this->veryverbose = false;
		} 
		if($input->getOption('vv')) {
			$this->verbose = true;
			$this->veryverbose = true;
		}

		if($this->veryverbose) $output->writeln('<info>[info]</info> Started hydration of your text files');

		$files = $this->dic['finder']['txtFiles'];

		if($this->veryverbose) $output->writeln('<info>[info]</info> Found <comment>'.count($files).' '.$this->dic['conf']['txtFileExtension'].'</comment> files');

		foreach ($files as $file) {

			// The name of the final html file is, by default, the name of the txt file
			$wwwFile = reset(explode('.', end(explode('/',$file->getRealPath()))));

			if($this->verbose) $output->writeln('<info>[info]</info> Processing file <comment>'.$wwwFile.' ('.$file.')</comment>');

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
			if($this->veryverbose) {
				foreach ($metaDatas as $k => $v) {
					$output->writeln('  <info>[info]</info> ... with metadata <comment>'.$k.'</comment> => <comment>'.$v.'</comment>');
				}
				$output->writeln('  <info>[info]</info> ... and a content of <comment>'.strlen($content).'</comment> char(s) (<comment>'.str_word_count($content).'</comment> word(s))');

			}
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
				$this->dic['workingDirectory'].'/'.$this->dic['conf']['wwwDir'].'/'.$wwwFile,
				$html
			);                                                     

			if($this->verbose) $output->writeln('<info>[info]</info> Successfully hydrated <comment>'.str_replace(__DIR__.'/', '', $wwwFile).'</comment>');
		}

		if($this->verbose) $output->writeln('<info>[info]</info> Done.');

	}
}
