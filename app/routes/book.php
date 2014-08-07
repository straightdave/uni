<?php
require "../app/models/Book.php";

$app->get('/book', function () use ($app) {
    
    $app->response->headers->set('Content-Type', 'application/json');
    $books = \Book::all();
    echo $books->toJson();
    
});
