eloquent-paginator
==================

A very little project to replace Eloquent's paginator while using Eloquent ORM outside of Laravel-4

Basic usage:

$query = Article::where('likes','>','20');
$pagination = EloquentPaginator::paginate($query);
