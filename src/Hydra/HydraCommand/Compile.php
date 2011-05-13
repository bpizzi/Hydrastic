<?php 
namespace Hydra\HydraCommand;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Finder\Finder;


/**
 * @author Baptiste Pizzighini <baptiste@bpizzi.fr>
 */
class Compile extends SymfonyCommand
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
			->setName('hydra:compile')
			->setDefinition(array(
				new InputOption('gz', '', InputOption::VALUE_NONE, 'GZip compression of the archive'),
			))
			->addArgument('pharfile', InputArgument::OPTIONAL, '', 'hydra.phar')
			->setDescription('Compile Hydra files into a PHAR archive')
			->setHelp(<<<EOF
The <info>hydra:compile</info> command skrink down Hydra to a single PHAR archive that you can easily embed into your website directory.
EOF
		);

	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$pharFile = $input->getArgument('pharfile');
		if (file_exists($pharFile)) {
			$output->writeln($this->dic['conf']['command_prefix'].' Deleting existing archive');
			unlink($pharFile);
		}


		$output->writeln($this->dic['conf']['command_prefix'].' Creating archive : '.$pharFile);
		$phar = new \Phar($pharFile, 0, 'Hydra');
		$phar->setSignatureAlgorithm(\Phar::SHA1);

		$phar->startBuffering();

		$finder = new Finder();
		$finder->files()
			->ignoreVCS(true)
			->name('*.php')
			->notName('Compiler.php')
			->in(__DIR__.'/../..')
			->in(__DIR__.'/../../../vendor/pimple')
			->in(__DIR__.'/../../../vendor/Symfony/Component/ClassLoader')
			->in(__DIR__.'/../../../vendor/Symfony/Component/Console')
			->in(__DIR__.'/../../../vendor/Symfony/Component/Finder')
			->in(__DIR__.'/../../../vendor/Symfony/Component/Yaml')
			->in(__DIR__.'/../../../vendor/twig/lib')
			;

		foreach ($finder as $file) {
			$filepath = str_replace(realpath(__DIR__.'/../../..').'/', '', $file->getRealPath());
			if($output->getVerbosity()==2) {
				$output->writeln($this->dic['conf']['command_prefix'].' Adding file to archive : '.$filepath);
			}
			$this->addFile($phar, $filepath);
		}

		$otherFiles = array(
			'hydra.php',
			'autoload.php',
			'hydra-default-conf.yml',
			'LICENSE',
			'vendor/twig/lib/Twig/Compiler.php', //TODO: see why this file isn't loaded by $finder...
		);
		foreach ($otherFiles as $file) {
			$filepath = str_replace(realpath(__DIR__.'/../../..').'/', '', $file);
			if($output->getVerbosity()==2) {
				$output->writeln($this->dic['conf']['command_prefix'].' Adding file to archive : '.$filepath);
			}
			$this->addFile($phar, $filepath);
		}

		// Stubs
		$phar->setDefaultStub('hydra.php');

		$phar->stopBuffering();

		if($input->getOption('gz')) {
			$phar->compressFiles(\Phar::GZ);
		}

		unset($phar);
		$output->writeln($this->dic['conf']['command_prefix'].' Done: compiled Hydra to hydra.phar');
	}

	protected function addFile($phar, $file, $strip = true)
	{
		$content = file_get_contents($file);
		if ($strip) {
			$content = $this->stripComments($content);
		}

		$content = str_replace('@package_version@', $this->dic['conf']['version'], $content);

		$phar->addFromString($file, $content);
	}

	/**
	 * From Symfony2 HttpKernel Component
	 */
	protected function stripComments($source)
	{
		if (!function_exists('token_get_all')) {
			return $source;
		}

		$output = '';
		foreach (token_get_all($source) as $token) {
			if (is_string($token)) {
				$output .= $token;
			} elseif (!in_array($token[0], array(T_COMMENT, T_DOC_COMMENT))) {
				$output .= $token[1];
			}
		}

		// replace multiple new lines with a single newline
		$output = preg_replace(array('/\s+$/Sm', '/\n+/S'), "\n", $output);

		return $output;
	}

}

