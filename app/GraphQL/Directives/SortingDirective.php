<?php

namespace App\GraphQL\Directives;

use Nuwave\Lighthouse\Schema\Directives\BaseDirective;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;


class SortingDirective extends BaseDirective implements FieldMiddleware
{
  public static function definition(): string
  {
    return /** @lang GraphQL */ <<<'GRAPHQL'
directive @sorting on FIELD_DEFINITION
GRAPHQL;
  }

  public function handleField(FieldValue $fieldValue, \Closure $next): FieldValue
  {
    $resolver = $fieldValue->getResolver();

    $fieldValue->setResolver(function ($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo) use ($resolver) {
      // Do something before the resolver, e.g. validate $args, check authentication

      // Call the actual resolver
      $result = $resolver($root, $args, $context, $resolveInfo);

      // Do something with the result, e.g. transform some fields

      return $result;
    });

    // Keep the chain of adding field middleware going by calling the next handler.
    // Calling this before or after ->setResolver() allows you to control the
    // order in which middleware is wrapped around the field.
    return $next($fieldValue);
  }

}
