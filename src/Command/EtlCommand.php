<?php

namespace App\Command;

use App\Entity\Parking;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:command:extract',
    description: 'ETL file xml',
)]
class EtlCommand extends Command
{
    private $em;

    public function __construct(
        private EntityManagerInterface $entityManager,
        protected ParameterBagInterface $parameterBag
    ) {
        parent::__construct();
    } 

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $file = $this->parameterBag->get('kernel.project_dir');
            $directory = $file . '/public/XML/';
            $xmlFiles = glob($directory . 'PKG*.xml');

            foreach($xmlFiles as $xmlFile) {
                $xml = simplexml_load_file($xmlFile);

                // Extraire les données du fichiers XML
                $name = (string)$xml->Nom;
                $placesLibres = (int)$xml->Places_libres;
                $exploitation = (int)$xml->Exploitation;
                $horodatage = new DateTime((string)$xml->Horodatage);
                
                // Créer une nouvelle entité Parking
                $parking = new Parking();
                $parking->setName($name);
                $parking->setFree($placesLibres);
                $parking->setTaken($exploitation);
                $parking->setCheckedAt($horodatage);
                $parking->setSource('orbility');

                // Sauveguarder l'entité dans la base de données
                $this->entityManager->persist($parking);
            }
            $this->entityManager->flush();
            return Command::SUCCESS;

        } catch (\Throwable $th) {
            $output->writeln('Une erreur est survenu :' . $th->getMessage());
            return Command::FAILURE;
        }
    }
}
