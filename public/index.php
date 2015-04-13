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

R::setup('mysql:host=localhost;dbname=linkstats;charset=UTF8','imilka','123456');
R::freeze(true);

$app->get('/links', function () use ($app) {
    $leftWord = $app->request()->get('leftWord');
    $rightWord = $app->request()->get('rightWord');
    $leftProperties = explode(",", $app->request()->get('leftProperties'));
    $rightProperties = explode(",", $app->request()->get('rightProperties'));

    $data = R::getAll('SELECT * FROM linksview WHERE word1 LIKE ? LIMIT 1000',
        [ $leftWord.'%' ]
    );

    $links = array();
    foreach($data as $rawLink) {
        $links[] = new Link(
            new Word($rawLink['word1'], $rawLink['pos1'], $rawLink['sh1']),
            new Word($rawLink['word2'], $rawLink['pos2'], $rawLink['sh2']),
            $rawLink['count']
        );
    }

    $app->render(200,array(
        'leftWord' => $leftWord,
        'rightWord' => $rightWord,
        'data' => $links
    ));
});

// Run app
$app->run();
