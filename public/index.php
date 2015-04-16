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
    
    $app->render(200, array(
       'query' => $query,
       'words' => ['word1', 'word2', 'word3']
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

    if(count($conditions) != 0) {
        $query .= "WHERE ".implode(" AND ", $conditions)." ";
    }

    $query .= "LIMIT 1000";

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
