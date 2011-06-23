<?php 
/**
 * This file is part of the Hydrastic package.
 *
 * (c) Baptiste Pizzighini <baptiste@bpizzi.fr> 
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Hydrastic\command;

use Hydrastic\ArrayMerger;
use Hydrastic\Post;
use Hydrastic\Taxonomy;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Symfony\Component\Finder\Finder as SymfonyFinder;

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
			->setName('hydrastic:process')
			->setDefinition(array(
				new InputOption('f', '', InputOption::VALUE_NONE, 'Force recursive cleaning of the www dir - USE WITH CAUTION'),
			))
			->setDescription('Generate your website')
			->setHelp(<<<EOF
The <info>hydrastic:process</info> command generate your website !
--f option force the recursive cleaning of your www directory: use it with caution (always backup template and content file as a good habit)
EOF
		);

	}

	public function log($msg, $level = 'info') 
	{
		$msg = ' '.strip_tags($msg);
		switch ($level) {
		case "warning":
			$this->dic['logger']['hydration']->addWarning($msg);
			break;
		case "error":
			$this->dic['logger']['hydration']->addError($msg);
			break;
		case "alert":
			$this->dic['logger']['hydration']->addAlert($msg);
			break;
		case "critical":
			$this->dic['logger']['hydration']->addCritical($msg);
			break;
		case "debug":
			$this->dic['logger']['hydration']->addDebug($msg);
			break;
		case "info":
		default:
			$this->dic['logger']['hydration']->addInfo($msg);
			break;
		}

		$this->dic['output']->writeln($this->dic['conf']['command_prefix'].$msg);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{

		$this->dic['output'] = $output;

		$this->log('Started hydration of your text files');

		$files = $this->dic['finder']['txt_files'];

		$this->log('Found <comment>'.count($files).'</comment> text files');

		$taxonomy = $this->dic['taxonomy'];
		$taxonomy->initiateTaxonStorage();

		foreach ($files as $file) {
			$post = new Post($this->dic);

			$post->read($file)
				->clean()
				->parseMetas()
				->parseContent()
				->hydrate()
				->attachToTaxonomy();
		} //-- parsing content files

		if ($input->getOptions('f')) {
			$taxonomy->cleanWwwDir(true);
		} else {
			$taxonomy->cleanWwwDir();
		}
		$taxonomy->createDirectoryStruct();

		$taxonomy->hydrateIndexFile()->writeIndexFile($this->dic['working_directory'].'/'.$this->dic['conf']['www_dir']);

		$this->log('Hydration finished.');

	}
}
