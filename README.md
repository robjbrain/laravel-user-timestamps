# Laravel User Timestamps

This is a drop in replacement for `HasTimestamps` trait in Laravel. It will add the additional fields. `created_by_id` and `updated_by_id` of the user that performed the operation.

It also supports polymorphic relationship which will add `created_by_type` and `updated_by_type` fields.

It also has support for `SoftDeletes` replacing Laravel's built in `SoftDeletes` trait to include `deleted_by_id` for the user who performed the soft delete. 

## Installation

You can install the package via composer:

```bash
composer require robjbrain/laravel-user-timestamps
```

## Usage

Add the `CacheMutationsTrait` trait to a model you like to cache the mutations of.

```php
use Robjbrain\LaravelUserTimestamps\HasUserTimestamps;
use Robjbrain\LaravelUserTimestamps\UserSoftDeletes;

class YourEloquentModel extends Model
{
    use HasUserTimestamps;
    use UserSoftDeletes;
}
```

## Example

When a mutator is accessed the response is cached so that the mutation does not occur again.

```php
use Robjbrain\LaravelCacheMutations\CacheMutationsTrait;

class User extends Model
{
    use CacheMutationsTrait;

	/**
	 * Get the user's full name.
	 *
	 * @return string
	 */
	public function getFullNameAttribute()
	{
	    return "{$this->first_name} {$this->last_name}";
	}
}
```

```php
$user = User::find(1);

// The getFullNameAttribute() method WILL be called
echo $user->fullName;

// The getFullNameAttribute() method WILL NOT be called
echo $user->fullName;

// The getFullNameAttribute() method WILL NOT be called
echo $user->fullName;
```

Ths is useful is the mutator is doing some extra work such as creating a class or accessing the database or an API

```php
use Robjbrain\LaravelCacheMutations\CacheMutationsTrait;

class User extends Model
{
    use CacheMutationsTrait;

	/**
	 * Get an md5 hash of the users avatar
	 *
	 * @return string
	 */
	public function getAvatarHashAttribute()
	{
	    return md5_file($this->avatar_path);
	}
}


$user = User::find(1);

// The potentially costly md5_file() function will only be called once
doSomethingWithHash($user->avatar_hash);

doSomethingElse(user->avatar_hash);
```

```php
use Robjbrain\LaravelCacheMutations\CacheMutationsTrait;

class User extends Model
{
    use CacheMutationsTrait;

	/**
	 * Use a complicate regular expression to generate a slug
	 *
	 * @return string
	 */
	public function getSlugAttribute()
	{
	    return preg_replace($complicated_and_slow_regex, $replacements, $this->title);
	}
}


$user = User::find(1);

// The complicated and slow regular expression will only occur once
doSomethingWithSlug($user->slug);

doSomethingElse($user->slug);
```

```php
use Robjbrain\LaravelCacheMutations\CacheMutationsTrait;
use Path\To\Some\Class\Called\PaymentsApi;

class User extends Model
{
    use CacheMutationsTrait;

	/**
	 * Access the users payment.
	 *
	 * @return string
	 */
	public function getPaymentsApiAttribute()
	{
	    return new PaymentsApi();
	}
}


$user = User::find(1);

// Only one instance of the PaymentsApi will be made 
$transactions = $user->paymentsApi->getTransactions();

$transactions = $user->paymentsApi->makePayment($details);
```

## Updating the model

When a model is updated, either just one attribute or a range of attributes, the mutations cache will be cleared, on the assumption that the mutations are in some way dependent on the existing attributes. This may mean that a mutator is called more than once if you update the model multiple times within one request. However this is unlikely.