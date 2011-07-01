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

use Hydrastic\Command\HydraCommandBase;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Helper\DialogHelper;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Init extends SymfonyCommand
{

	protected $dic;

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
			->setName('hydrastic:init')
			->setDefinition(array(
				new InputOption('f', '', InputOption::VALUE_NONE, 'Force deletion of current config and installation of default theme'),
			))
			->setDescription('Initiate folder structure and config file')
			->setHelp(<<<EOF
The <info>hydrastic:init</info> command checks if the current directory could be used for holding your txt/twig/html files.
If there no folder in the current place, then you can name some.
If there is already some folders, you can tell Hydrastic which one should be used for txt/twig/html files.
If an hydrastic-conf.yml exists, <info>hydrastic:init</info> will ask you to confirm it.
If there is no hydrastic-conf.yml yet, you will have the opportunity to create it here
EOF
		);

	}

	public function log($msg, $level = 'info') 
	{
		$msg = ' '.strip_tags($msg);
		switch ($level) {
		case "warning":
			$this->dic['logger']['init']->addWarning($msg);
			break;
		case "error":
			$this->dic['logger']['init']->addError($msg);
			break;
		case "alert":
			$this->dic['logger']['init']->addAlert($msg);
			break;
		case "critical":
			$this->dic['logger']['init']->addCritical($msg);
			break;
		case "debug":
			$this->dic['logger']['init']->addDebug($msg);
			break;
		case "info":
		default:
			$this->dic['logger']['init']->addInfo($msg);
			break;
		}

		$this->dic['output']->writeln($this->dic['conf']['command_prefix'].$msg);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->dic['output'] = $output;
		$dialog = new DialogHelper();


		//If --f, backup existing config file (overwriting existing backup), and unset the current config file.
		if (true === $this->dic['user_conf_defined'] && false === $input->getOption('f')) {
			//If no --f and existing config : do nothing.
			$this->log('I can see you already have a configuration file. Please make a backup and delete it before running me again.');
			die();
		} elseif (true === $this->dic['user_conf_defined']) {
			//Make a backup of the config file
			$this->log('I can see you already have a configuration file. I\'m going to make a backup before deleting it.');
			if (file_exists($this->dic['working_directory'].'/hydrastic-conf.yml.backup')) {
				unlink($this->dic['working_directory'].'/hydrastic-conf.yml.backup');
			} 
			file_put_contents(
				$this->dic['working_directory'].'/hydrastic-conf.yml.backup',
				file_get_contents($this->dic['working_directory'].'/hydrastic-conf.yml')
			);
			unlink($this->dic['working_directory'].'/hydrastic-conf.yml');
		}

		//Proceed to config file creation
		$this->log('hydrastic-conf.yml not found, let\'s see if we can create one together.');

		//Definition of the type of folders
		$folderConf = array(
			'txt_dir' => null,
			'tpl_dir' => null,
			'www_dir' => null,
			'asset_dir' => null,
			'log_dir' => null,
		);
		$folderConfDefinition = array(
			'txt_dir' => array(
				'type' => 'text files',
				'default' => 'txt',
				'question' => "Holding text files",
				'answer' => "txt",
			),
			'tpl_dir' => array(
				'type' => 'template files',
				'default' => 'tpl',
				'question' => "Holding template files",
				'answer' => "tpl",
			),
			'www_dir' => array(
				'type' => 'generated html files',
				'default' => 'www',
				'question' => "Holding generated html files ",
				'answer' => "www",
			),
			'asset_dir' => array(
				'type' => 'assets (images/css/js)',
				'default' => 'assets',
				'question' => "Holding assets (images, css, js)",
				'answer' => "asset",
			),
			'log_dir' => array(
				'type' => 'Logs',
				'default' => 'log',
				'question' => "Holding hydration-time logs",
				'answer' => "log",
			),
		);

		// Look for folders in current directory 
		$f = $this->dic['finder']['find'];
		$f->directories()->depth('< 1')->in($this->dic['working_directory']);

		//Foreach folder we found, ask which type of file it should be used for
		$questionPattern = "        [-->] %s (answer <info>%s</info>)\n";
		foreach ($f as $d) {
			$validAnswers = array();
			$question = $this->dic['conf']['command_prefix']." `<comment>".$d->getRelativePathname()."</comment>` folder found, shall I use it for :\n";
			foreach ($folderConf as $k => $v) {
				//Only ask for a particular folder when it has yet to be defined.
				if (null === $folderConf[$k]) {
					$question .= sprintf($questionPattern, $folderConfDefinition[$k]['question'], $folderConfDefinition[$k]['answer']);
					$validAnswers[] = $folderConfDefinition[$k]['answer'];
				}
			}
			$question .= sprintf($questionPattern, "None", "none");

			//Only accept knowns answers
			$validate = function ($v) use ($validAnswers) {
				return in_array($v, $validAnswers) ? $v : false;
			};

			try {
				$response = $dialog->askAndValidate($output, $question, $validate, 3);
			} catch (\Exception $e) {
				$this->log('<error>[error]</error> Error : Maximum tries reached (3)');
				die();
			}

			if ($response === "none") {
				$this->log($d->getRelativePathname().' will not be used by Hydrastic');
			} else {
				$this->log($d->getRelativePathname().' will be used for holding <info>'.$response.'</info> files');
				$folderConf[$response.'_dir'] = $d->getRelativePathname();
			}
			$this->log('---');
			if (false === in_array(null,$folderConf)) {
				//If every folder conf key has been defined, quit the loop
				break;
			}
		} //-- ask the user what should be existing folders used for

		if (iterator_count($f) == 0 ) {
			$this->log('No folders found, let\'s create some.');
		} 

		//If a type of folder isn't defined, ask the user to create it
		foreach ($folderConf as $key => $folder) {
			if (is_null($folder)) {
				$this->log('You need to create a folder for holding <comment>'.$folderConfDefinition[$key]["type"].'</comment>');
				$folder = $dialog->ask($output, "Tell me the name of that folder and I shall create it for you (may I suggest <comment>".$folderConfDefinition[$key]["default"]."</comment> ?):");
				$folderConf[$key] = $folder;
			}
		} //-- creation of folders yet to be configured

		//Configuration keys for the static site (those are default values 
		//that could be overidden by each text file)
		$siteConf = array(
			'title'        => null,
			'description'  => null,
			'author'       => null,
		);

		foreach ($siteConf as $k => $v) {
			$siteConf[$k] = $dialog->ask($output, $this->dic['conf']['command_prefix']." Please enter your site configuration for the key: <comment>".$k."</comment>");
			$this->log('---');
		}

		//Reviewing the configuration
		$confValid = false;
		while (false === $confValid) {
			$this->log('Almost done! please review your configuration before I write it to disc:');
			foreach ($folderConf as $k => $v) {
				$this->log('         For holding <comment>'.$folderConfDefinition[$k]["type"].'</comment>, the following folder will be used: <comment>'.$v.'</comment> (key <info>'.$k.'</info>)');
			}
			foreach ($siteConf as $k => $v) {
				$this->log('         The configuration key <comment>'.$k.'</comment> is <comment>'.$v.'</comment> (key <info>'.$k.'</info>)');
			}
			$response = $dialog->ask($output, $this->dic['conf']['command_prefix']." If it looks ok to you, answer <info>done</info>.\n".$this->dic['conf']['command_prefix']." if you want to modify a configuration key now: please indicate me that key (you will have the opportunity to tweak it later by modifying <comment>hydrastic-conf.yml</comment>)");
			if ($response == "done") {
				$confValid = true;
			} else {
				//Last chance update of a configuration key
				//TODO: Fix warning/bug when trying to modify a key frim $folderConf
				if (array_key_exists($response, $folderConfDefinition)) {
					$folderConf[$response] = $dialog->ask($output, "          New folder for holding <comment>".$folderConfDefinition[$response]["type"]."</comment>: ");
				} elseif (array_key_exists($response, $siteConf)) {
					$siteConf[$response] = $dialog->ask($output, $this->dic['conf']['command_prefix']." New value for site configuration key <comment>".$response."</comment>: ");
				} else {
					$this->log("<error>$response</error> isn't a valid answer.");
				}
			}
		}


		//Dumping configuration to yml and creating folders
		$this->log('---');
		foreach ($folderConf as $k => $folder) {
			if (false === is_dir($this->dic['working_directory'].'/'.$folder)) {
				if (false === @mkdir($folder)) {
					$this->log('<error>[error]</error> Creation of the folder "'.$folder.'" failed.');
				} else {
					$this->log('Folder created : <info>'.$folder.'/</info>');
				}
			} else {
				$this->log('Folder already exists : <info>'.$folder.'/</info>');
			}
		}
		$masterConfig = array_merge_recursive($folderConf, array('metadata_defaults' => array('General' => $siteConf)));
		$dumper = $this->dic['yaml']['dumper'];
		file_put_contents('hydrastic-conf.yml',$dumper->dump($masterConfig, 3));

		//Copy the default template from the archive into tpl_dir
		$installDefaultTpl = false;
		if ($input->getOptions('f')) {
			$installDefaultTpl = true;
		} else {
			$response = $dialog->ask($output, $this->dic['conf']['command_prefix']." Do you want me to install a default theme in your tpl_dir ? (y/n, default=no)");
			if ('y' === $response) {
				$installDefaultTpl = true;
			}
		}
		if ($installDefaultTpl) {
			$defaultTplDir = $this->dic['working_directory'].'/'.$folderConf['tpl_dir'].'/default';
			if (false === file_exists($defaultTplDir)) {
				mkdir($defaultTplDir);
			}

			$defaultThemeUrl = $this->dic['hydrastic_dir'].'/themes/default';
			//echo "\n".$defaultThemeUrl."\n";
			$tplFiles = $this->dic['finder']['find']->files()
				->ignoreVCS(true)
				->name('*.twig')
				->in($defaultThemeUrl);

			foreach ($tplFiles as $file) {
				$filename = $this->dic['working_directory'].'/'.$folderConf['tpl_dir'].'/default/'.end(explode("/", $file));
				//echo "\n".$file." : $filename\n";
				file_put_contents(
					$filename,
					file_get_contents($file)
				);
			}
			$this->log("Default theme writed to disc (".iterator_count($tplFiles)." files).");
		}

		//Done ! :)
		$this->log('Configuration file writed to disc.');
		$this->log('You can begin to write your templates and text files and try <info>hydrastic:process</info> command to generate you static content!');

		//Create an executable shortcut to hydrastic.phar under linux
		if (PHP_OS === 'Linux') {
			file_put_contents('hydrastic', "#!/bin/sh\nphp hydrastic.phar $@");
			system('chmod +x hydrastic');
			$this->log('I created a shorcut for you : you can now run me with <info>./hydrastic</info>, assuming PHP binary is accessible from your ENV.');
		}


	}

}
