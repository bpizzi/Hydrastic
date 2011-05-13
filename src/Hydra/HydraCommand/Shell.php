<?php 
namespace Hydra\HydraCommand;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Shell as SymfonyShell;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Shell extends SymfonyCommand
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
			->setName('hydra:shell')
			->setDefinition(array(
				//new InputOption('v', '', InputOption::VALUE_NONE, 'Be verbose or not'),
				))
				->setDescription('Run hydra shell')
				->setHelp(<<<EOF
The <info>hydra:shell</info> command allow you to interact with hydra commands in its own shell
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$hydraShell = new SymfonyShell($this->dic['hydra_app']);
		$hydraShell->run();
	}

}


