<?php

namespace Migrator;

use Medoo\Medoo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;

class MonocleMigrator extends Command
{
    /**
     * The database driver we're going to migrate from.
     *
     * @var Meedo
     */
    private $from;

    /**
     * The database driver we're going to migrate to.
     *
     * @var Meedo
     */
    private $to;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SymfonyStyle
     */
    private $io;

    protected function configure(): void
    {
        $this
            ->setName('monocle-migrator:migrate')
            ->setDescription('Migrate Monocle data between MySQL and Postgres')
            ->setHelp('This command allows you to migrate your existing data from one RDBMS to another.')
            ->addOption(
                'credentials',
                'c',
                InputOption::VALUE_REQUIRED,
                'Credentials file',
                'credentials.yml'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->output = $output;

        $credentialsFile = $input->getOption('credentials');

        if (!is_file($credentialsFile)) {
            $this->error("File {$credentialsFile} not found!");
            exit;
        }

        $credentials = Yaml::parseFile($credentialsFile);

        $this->from = $this->connect($credentials['from']);
        $this->to = $this->connect($credentials['to']);

        $this->migrateGyms();
        $this->migratePokestops();

        $this->success('Done.');
    }

    private function connect(array $credentials)
    {
        $this->info("Connecting to {$credentials['type']}...");
        $conn = new Medoo([
            'database_type' => $credentials['type'],
            'database_name' => $credentials['database'],
            'server' => $credentials['host'],
            'username' => $credentials['user'],
            'password' => $credentials['pass'],
            'charset' => 'utf8',
        ]);

        return $conn;
    }

    private function migrateGyms(): void
    {
        $this->migrate('forts', 'external_id', [
            'external_id' => ':external_id',
            'lat' => ':lat',
            'lon' => ':lon',
            'name' => ':name',
            'url' => ':url',
        ]);
    }

    private function migratePokestops(): void
    {
        $this->migrate('pokestops', 'external_id', [
            'external_id' => ':external_id',
            'lat' => ':lat',
            'lon' => ':lon',
            'name' => ':name',
            'url' => ':url',
            'updated' => ':updated',
            'lure_start' => 0,
        ]);
    }

    private function migrate(string $table, string $pk, array $columns): void
    {
        $this->info("Querying {$table}...");
        $query = "SELECT * FROM {$table} ORDER BY id ASC";
        $records = $this->from->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        $i = 0;
        $this->info("Migrating {$table}...");

        if (!$this->output->isDebug()) {
            $progressBar = new ProgressBar($this->output, count($records));
            $progressBar->start();
        }

        foreach ($records as $record) {
            ++$i;
            $this->debug("Migrating {$table} #{$i} {$record[$pk]}...");

            if (!$this->output->isDebug()) {
                $progressBar->setFormat($this->getProgressbarFormat());
                $progressBar->advance();
            }

            foreach ($columns as $column => $value) {
                if (':' != \substr($value, 0, 1)) {
                    $values[$column] = $value;
                } else {
                    $values[$column] = $record[\substr($value, 1)];
                }
            }

            $this->to->insert($table, $values);
        }

        if (!$this->output->isDebug()) {
            $progressBar->finish();
            $this->output->writeln('');
        }
    }

    private function info(string $msg): void
    {
        $dt = new \DateTime();
        $this->output->writeln("<info>[{$dt->format('Y-m-d H:i:s')}] INFO {$msg}</info>");
    }

    private function error(string $msg): void
    {
        $dt = new \DateTime();
        $this->io->error("[{$dt->format('Y-m-d H:i:s')}] {$msg}");
    }

    private function debug(string $msg): void
    {
        if ($this->output->isDebug()) {
            $dt = new \DateTime();
            $this->output->writeln("<comment>[{$dt->format('Y-m-d H:i:s')}] DEBUG {$msg}</comment>");
        }
    }

    private function success(string $msg): void
    {
        $dt = new \DateTime();
        $this->io->success("[{$dt->format('Y-m-d H:i:s')}] {$msg}");
    }

    private function getProgressbarFormat(): string
    {
        $dt = new \DateTime();

        return "<info>[{$dt->format('Y-m-d H:i:s')}] %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%</info>";
    }
}
