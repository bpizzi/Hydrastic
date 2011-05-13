<?php 
namespace Hydra\HydraCommand;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Version extends SymfonyCommand
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
			->setName('hydra:version')
			->setDefinition(array())
			->setDescription('What version of Hydra are you using right now ?')
			->setHelp(<<<EOF
The <info>hydra:version</info> command let you know what version of Hydra you are using
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('');
		$output->writeln('<info>Hydra version '.$this->dic['conf']['version'].' </info>');
		$output->writeln(<<<EOF
----------------------------------------------------------------------
ooooo   ooooo oooooo   oooo oooooooooo.   ooooooooo.         .o.       
`888'   `888'  `888.   .8'  `888'   `Y8b  `888   `Y88.      .888.      
 888     888    `888. .8'    888      888  888   .d88'     .8"888.     
 888ooooo888     `888.8'     888      888  888ooo88P'     .8' `888.    
 888     888      `888'      888      888  888`88b.      .88ooo8888.   
 888     888       888       888     d88'  888  `88b.   .8'     `888.  
o888o   o888o     o888o     o888bood8P'   o888o  o888o o88o     o8888o  
----------------------------------------------------------------------
 Static content generator - Baptiste Pizzighini <baptiste@bpizzi.fr>
----------------------------------------------------------------------
EOF
	);

	}

}



