<?php
require '../vendor/autoload.php';
require_once 'link.php';
require_once 'word.php';

// Prepare app
$app = new \Slim\Slim();
class_alias('RedBeanPHP\R', 'R');

// Create monolog logger and store logger in container as singleton
// (Singleton resources retrieve the same log resource definition each time)
$app->container->singleton('log', function () {
    $log = new \Monolog\Logger('slim-skeleton');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('../logs/app.log', \Monolog\Logger::DEBUG));
    return $log;
});

$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());

R::setup('mysql:host=localhost;dbname=lsfixed;charset=UTF8','imilka','123456');
R::freeze(true);

$app->get('/words', function() use ($app) {
    $query = $app->request()->get('query');
    $type = $app->request()->get('type');
    $properties = explode(",", $app->request()->get('properties'));

    switch($type) {
        case "leftWord":
            $col = "word1";
            $sh = "sh1";
            break;
        case "rightWord":
            $col = "word2";
            $sh = "sh2";
            break;
        case "preposition":
            $col = "preposition";
            break;
    }

    $qr = "SELECT DISTINCT ".$col." FROM linksview ";

    $conditions = array();
    if($col != "preposition") {
        $conditions[] = $sh." IN ('".implode("','", $properties)."')";
    }

    $conditions[] = $col." LIKE '".$query."%'";

    if(count($conditions) != 0) {
        $qr .= "WHERE ".implode(" AND ", $conditions)." ";
    }

    $data = R::getAll($qr);

    $words = array();
    foreach($data as $word) {
        $words[] = $word[$col];
    }

    $app->render(200, array(
       'query' => $query,
       'type' => $type,
       'words' => $words
    ));
});

$app->get('/links', function () use ($app) {
    $leftWord = $app->request()->get('leftWord');
    $rightWord = $app->request()->get('rightWord');
    $preposition = $app->request()->get('preposition');

    $leftWordExactMatch = $app->request()->get('leftWordExactMatch') === "true";
    $rightWordExactMatch = $app->request()->get('rightWordExactMatch') === "true";
    $prepositionExactMatch = $app->request()->get('prepositionExactMatch') === "true";

    $leftProperties = explode(",", $app->request()->get('leftProperties'));
    $rightProperties = explode(",", $app->request()->get('rightProperties'));

    $query = "SELECT * FROM linksview ";
    $conditions = array();

    if($leftWord != "null") {
        $lwq = $leftWord;
        if(!$leftWordExactMatch) $lwq .= "%";
        $conditions[] = "word1 LIKE '".$lwq."'";
    }
    if($rightWord != "null") {
        $rwq = $rightWord;
        if(!$rightWordExactMatch) $rwq .= "%";
        $conditions[] = "word2 LIKE '".$rwq."'";
    }
    if($preposition != "null") {
        $pq = $preposition;
        if(!$prepositionExactMatch) $pq .= "%";
        $conditions[] = "preposition LIKE '".$pq."'";
    }

    $conditions[] = "sh1 IN ('".implode("','", $leftProperties)."')";
    $conditions[] = "sh2 IN ('".implode("','", $rightProperties)."')";

    if(count($conditions) != 0) {
        $query .= "WHERE ".implode(" AND ", $conditions)." ";
    }

    $query .= " ORDER BY count DESC LIMIT 1000";

    $data = R::getAll($query);

    $links = array();
    foreach($data as $rawLink) {
        $links[] = new Link(
            new Word($rawLink['word1'], $rawLink['pos1'], $rawLink['sh1']),
            new Word($rawLink['word2'], $rawLink['pos2'], $rawLink['sh2']),
            $rawLink['count'],
            $rawLink['preposition']
        );
    }

    $app->render(200,array(
        'leftWord' => $leftWord,
        'rightWord' => $rightWord,
        'preposition' => $preposition,
	    'data' => $links
    ));
});

// Run app
$app->run();
