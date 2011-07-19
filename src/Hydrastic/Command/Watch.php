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

namespace Hydrastic\Command;

use Hydrastic\ArrayMerger;
use Hydrastic\Post;
use Hydrastic\Taxonomy;
use Hydrastic\Theme;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

use Symfony\Component\Finder\Finder as SymfonyFinder;

class Watch extends SymfonyCommand
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
			->setName('hydrastic:watch')
			->setDefinition(array(
				//new InputOption('optionName', '', InputOption::VALUE_NONE, ''),
				))
				->setDescription('Launch hydrastic:process each time your change a file')
				->setHelp(<<<EOF
The <info>hydrastic:watch</info> automaticaly launch hydrastic:process each time you change a text, asset or a template file.
This command use inotify and it's PHP extension: run 'pecl install -f inotify' and activate it in php.ini before.
NOTE: inotify was merged into the 2.6.13 Linux kernel (28 Aug 2005).
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
		if (false === extension_loaded('inotify')) {
			$this->log('<error>ERROR</error> You must install/activate inotify extension for PHP CLI before calling this command.');
		}
		$this->dic['output'] = $output;

		//Setting folders to watch
		$topLevelFolders = array(
			$this->dic['working_directory'].'/'.$this->dic['conf']['txt_dir'],
			$this->dic['working_directory'].'/'.$this->dic['conf']['tpl_dir'],
	   	);
		foreach ($topLevelFolders as $f) {
			$this->log("Monitoring: $f");
		}

		//Finding top folders child's
		$finder = $this->dic['finder']['find']->directories();
		foreach ($topLevelFolders as $f) {
			$finder->in($f);
		}
		$folders = array_merge($topLevelFolders, iterator_to_array($finder));


		//Adding a inotify watch on each top folder and their children
		$i = inotify_init();
		foreach ($folders as $f) {
			$w = inotify_add_watch($i, $f, IN_MODIFY);
		}

		$this->log('Will launch hydrastic:process each time a file change in those folders... Hit CTRL+C to end.');

		//Looping forever: reads events from inotify and launch Process when a file change
		while(true) {
			$r = inotify_read($i);
			foreach ($r as $event => $details) {
				if ($details["name"] !== "" && substr($details["name"], 0, 1) !== ".") {
					$this->log('One file changed : '.$details["name"]."\n");
					$this->dic['hydrastic_app']->run(new StringInput('hydrastic:process'), new ConsoleOutput());
				}
			}
		}


	}
}

