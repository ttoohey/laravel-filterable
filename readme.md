# Gency\Filterable

A trait to provide composable queries to Laravel models.

The intended usage is to provide an easy way to declare how a model's fields are filterable and allow the filter query to be expressed in a form that can be easily represented (as a JSON object for example).

The Gency\Filterable\FilterableTrait adds a filter() method to a Model which works with the Laravel query builder. The filter() method accepts a single argument which is an array of [field => value] pairs that define the search query being made. 

# Install

```
composer require ttoohey/laravel-filterable
```

# Usage

Basic usage is to add the trait to a Model class and configure filterable fields.

```
use Gency\Filterable;

class User extends Model
{
  public $filterable = [
    'name' => Filterable::String,
    'email' => Filterable::String
  ];
}

$filter = [
  'name' => 'John'
];
User::filter($filter)->toSql();
// select * from users where name = ?

$filter = [
  'name_LIKE' => 'Jo%n',
  'email' => 'john@example.com'
];
User::filter($filter)->toSql();
// select * from users where name like ? and email = ?
```

The `$filterable` property defines the fields that may be used in filter queries. The value is an array of *filter rules* that lists the possible variations the filter may use.

The built-in rules are:

* EQ - equalality
* LIKE - SQL like
* ILIKE - a case-insensitive version of LIKE
* MATCH - wildcard pattern matching
* MIN - greater than or equal to
* MAX - less than or equal to
* GT - greater than
* LT - less than
* RE - regular expression
* FT - full text search (not yet implemented)
* IN - contained in list

A standard set of rules are provided

* String = [EQ, LIKE, ILIKE, MATCH]
* Numeric = [EQ, MIN, MAX, LT, GT]
* Enum = [EQ, IN]
* Date = [EQ, MIN, MAX, LT, GT]
* Boolean = [EQ]

A model's filterable definition sets the rules available for each field. The first rule in the list is the default rule for the field. Other rules must add the rule name as a suffix in the filter query field name.

In the following definition, the 'name' field can use any of the String rules: EQ, LIKE, ILIKE, MATCH.

```
User::$filterable = [
  'name' => Filterable::String
]
```

A query can thus use:

```
$filter = [
  'name_MATCH' => 'John'
];
User::filter($filter);
```

The SQL that is run will match any user whose name is a case-insensitive match containing 'John', such as 'Little john', 'Johnathon', 'JOHN'.

# Custom rules

A class may provide custom rules to apply to fields.

```
class User extends Model
{
  public $filterable = [
    'keyword' => 'Keyword'
  ];
  public function scopeFilterKeyword($query, $field, $arg) {
    $query->where(function ($q) use ($arg) {
      $q->where('name', $arg);
      $q->orWhere('email', $arg);
      $q->orWhere('phone', $arg);
    });
    return $query;
  }
}
```

Custom rules can be listed in the $filterable definition along with the built-in rules and work in the same way. The first rule in $filterable is the default rule for the field. If it's not the first rule it must have the rule name added as a suffix to the field name in the query.

Rule names are converted to 'ucfirst' and appended to 'scopeFilter'.

# Logical combinations with AND, OR, and NOT

A filter can create more complex queries by structuring the filter into nested queries.

```
$filter = [
  'OR' => [
    [
      'name' => 'John'
    ],
    [
      'name' => 'Alice'
    ]
  ]
];
User::filter($filter)->toSql();
// select * from users where ((name = ?) or (name = ?))
```

The 'AND', 'OR', 'NOT', and 'NOR' nesting operators each take a list of nested filters to apply. Nested filter queries can in turn use the nesting operators to create more complex queries.

# Relationships

Relationships can be used to filter related models.

```

class Post extends Model
{
  use Gency\Filterable\FilterableTrait;
  
  public function filterable () {
    return [
      'comment' => $this->comments()
    ];
  }
  
  public function comments() {
    return $this->hasMany(Comment::class);
  }
}

class Comment extends Model
{
  use Gency\Filterable\FilterableTrait;
  
  public function filterable () {
    return [
      'created_at' => Filterable::Date
    ];
  }
  
  public function post() {
    return $this->belongsTo(Post::class);
  }
}

# Get all posts that have a comment in June 2017
$filter = [
  'comment' => [
    'created_at_MIN' => '2017-06-01',
    'cerated_at_MAX' => '2017-07-01'
  ]
];
Post::filter($filter)->toSql()
// select * from posts left join (select distinct comments.post_id from comments where created_at >= ? and created_at <= ?) as comments_1 on posts.id = comments_1.post_id where comments_1.post_id is not null;
```
