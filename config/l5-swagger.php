<?php

use OpenApi\Analysers\AttributeAnnotationFactory;
use OpenApi\Analysers\DocBlockAnnotationFactory;
use OpenApi\Analysers\ReflectionAnalyser;

$config = require base_path('vendor/darkaonline/l5-swagger/config/l5-swagger.php');

$config['documentations']['default']['api']['title'] = 'E-Gallery API Documentation';
$config['defaults']['scanOptions']['analyser'] = new ReflectionAnalyser([
    new AttributeAnnotationFactory,
    new DocBlockAnnotationFactory,
]);

return $config;
