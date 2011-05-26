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

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Shell as SymfonyShell;


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
			->setName('hydrastic:shell')
			->setDefinition(array())
			->setDescription('Run hydrastic shell')
			->setHelp(<<<EOF
The <info>hydrastic:shell</info> command allow you to interact with hydrastic commands in its own shell
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$shell = new SymfonyShell($this->dic['hydrastic_app']);
		$shell->run();
	}

}
