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
			->setName('hydrastic:version')
			->setDefinition(array())
			->setDescription('What version of Hydrastic are you using right now ?')
			->setHelp(<<<EOF
The <info>hydrastic:version</info> command let you know what version of Hydrastic you are using
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln('');
		$output->writeln('<info>Hydrastic version '.$this->dic['conf']['version'].' </info>');
		$output->writeln(<<<EOF
-------------------------------------------------------------------------------

____    ____                ___                                                      
`MM'    `MM'                `MM                                   68b                
 MM      MM                  MM                             /     Y89                
 MM      MM ____    ___  ____MM ___  __    ___      ____   /M     ___   ____         
 MM      MM `MM(    )M' 6MMMMMM `MM 6MM  6MMMMb    6MMMMb\/MMMMM  `MM  6MMMMb.       
 MMMMMMMMMM  `Mb    d' 6M'  `MM  MM69 " 8M'  `Mb  MM'    ` MM      MM 6M'   Mb       
 MM      MM   YM.  ,P  MM    MM  MM'        ,oMM  YM.      MM      MM MM    `'       
 MM      MM    MM  M   MM    MM  MM     ,6MM9'MM   YMMMMb  MM      MM MM             
 MM      MM    `Mbd'   MM    MM  MM     MM'   MM       `Mb MM      MM MM             
 MM      MM     YMP    YM.  ,MM  MM     MM.  ,MM  L    ,MM YM.  ,  MM YM.   d9       
_MM_    _MM_     M      YMMMMMM__MM_    `YMMM9'Yb.MYMMMM9   YMMM9 _MM_ YMMMM9        
                d'                                                                   
            (8),P                                                                    
             YMM
                          My website is Hydrastic!

-------------------------------------------------------------------------------
     Static website generator - Baptiste Pizzighini <baptiste@bpizzi.fr>
-------------------------------------------------------------------------------
EOF
	);

	}

}
