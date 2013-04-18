<?php
require_once __DIR__ . "/silex/vendor/autoload.php";
require_once __DIR__ . "/code_runner.php";
require_once __DIR__ . "/snippets.php";

/**
 * 
 * @var Silex\Application
 */
$app = new Silex\Application();
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));
/**
 
 */
$app->get('/snippets/read', function () use ($app) {
  $dao = new SnippetsDAO();
  $response = $dao->get_all();
  $response = count($response) > 0 ? $response : null;
  return $app->json($response);
});
/**
 
 */
$app->get('/snippets/{snippet_id}/read', function ($snippet_id) use ($app) {
  $dao = new SnippetsDAO();
  $response = $dao->get($snippet_id);
  $response['content'] = stripslashes($response['content']);
  return $app->json($response);
});
/**
 
 */
$app->get('/snippets/{snippet_id}/delete', function ($snippet_id) use ($app) {
  $dao = new SnippetsDAO();
  $response = $dao->delete($snippet_id);
  return $app->json($response);
});
/**
 
 */
$app->post('/snippets/create', function (Request $request) use ($app) {
  $dao = new SnippetsDAO();
  $id = $dao->add($request->get('content'));
  return $app->json(array('id' => $id));
});
/**
 
 */
$app->post('/snippets/{snippet_id}/update', function (Request $request, $snippet_id) use ($app) {
  $dao = new SnippetsDAO();
  $result = $dao->update($snippet_id, $request->get('content'));
  return $app->json(array());
});
/**
 
 */
$app->post('/run_code', function (Request $request) use ($app) {
  $code = stripslashes($request->get('code'));

  $code_runner = new CodeRunner();
  $response = $code_runner->run_code($code);
  return $app->json($response);
});
/**
 
 */
$app->get('/', function() use ($app) {
  return $app['twig']->render('template.twig', array());
});
/**
 
 */
$app->get('/{snippet_id}', function(Request $request, $snippet_id) use ($app) {
  $snippet_id = (int) $request->get('snippet_id');
  $dao = new SnippetsDAO();
  if(!$dao->get($snippet_id)) {
    $app->abort(404);
  }
  return $app['twig']->render('template.twig', array('snippet_id' => $snippet_id));
});
/**
 
 */
/**
 * Run
 */
return $app->run();
