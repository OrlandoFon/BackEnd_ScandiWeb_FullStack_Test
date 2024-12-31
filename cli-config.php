<?php

use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

$entityManager = require __DIR__ . '/config/bootstrap.php';

// Create the EntityManagerProvider
$entityManagerProvider = new SingleManagerProvider($entityManager);

// Return the EntityManagerProvider instead of HelperSet
return $entityManagerProvider;
