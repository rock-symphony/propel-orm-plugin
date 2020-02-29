<?php
// Book:
$book_1 = Book::create(['name' => 'The definitive guide to symfony']);

// Category:
$category_1 = Category::create(['name' => 'Category 1']);
$category_2 = Category::create(['name' => 'Category 2']);

// Article:
$article_1 = Article::create([
    'title'       => 'foo title',
    'body'        => 'bar body',
    'excerpt'     => 'foo excerpt',
    'online'      => true,
    'category_id' => $category_1->getId(),
    'created_at'  => time(),
]);
$article_2 = Article::create([
    'title'       => 'foo foo title',
    'body'        => 'bar bar body',
    'excerpt'     => 'foo excerpt',
    'online'      => false,
    'category_id' => $category_2->getId(),
    'created_at'  => time(),
]);

// Author:
$fabien = Author::create([
    'name' => 'Fabien',
    'author_articles' => [$article_1, $article_2],
    'hobbies' => ['foo', 'bar'],
]);
$thomas = Author::create([
    'name' => 'Thomas',
    'author_articles' => [$article_1],
]);
$helene = Author::create([
    'name' => 'Hélène',
]);

// Movie:
$la_vita_e_bella = Movie::create([
    'director' => 'Roberto Benigni',
]);

// MovieI18n:
$la_vita_e_bella_it = MovieI18n::create([
    'id' => $la_vita_e_bella->getId(),
    'culture' => 'it',
    'title' => 'La Vita è bella',
]);
$la_vita_e_bella_fr = MovieI18n::create([
    'id' => $la_vita_e_bella->getId(),
    'culture' => 'fr',
    'title' => 'La Vie est belle',
]);

// MoviePropel:
$la_vita_e_bella = MoviePropel::create([
    'director' => 'Roberto Benigni',
]);

// MoviePropelI18n:
$la_vita_e_bella_it = MoviePropelI18n::create([
    'id' => $la_vita_e_bella->getId(),
    'culture' => 'it',
    'title' => 'La Vita è bella',
]);
$la_vita_e_bella_fr = MoviePropelI18n::create([
    'id' => $la_vita_e_bella->getId(),
    'culture' => 'fr',
    'title' => 'La Vie est belle',
]);

// Product:
$product1 = Product::create([
    'price' => 5.00,
    'a_primary_string' => 'PRIMARY STRING',
]);

// ProductI18n:
$product1_en = ProductI18n::create([
    'id' => $product1->getId(),
    'culture' => 'en',
    'name' => 'PRIMARY STRING I18N',
]);
