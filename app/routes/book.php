<?php
require "../app/models/Book.php";

$app->get('/book', function () {
    
    $books = \Book::all();
    echo $books->toJson();
    
});
