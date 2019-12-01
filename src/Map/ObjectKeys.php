<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Map;

use Innmind\Immutable\{
    Map,
    Type,
    Str,
    Stream,
    Set,
    Pair,
    ValidateArgument,
    ValidateArgument\ClassType,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptyMapException
};

/**
 * {@inheritdoc}
 */
final class ObjectKeys implements Implementation
{
    private string $keyType;
    private string $valueType;
    private ValidateArgument $validateKey;
    private ValidateArgument $validateValue;
    private \SplObjectStorage $values;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $keyType, string $valueType)
    {
        $this->validateKey = Type::of($keyType);

        if (!$this->validateKey instanceof ClassType && $keyType !== 'object') {
            throw new LogicException;
        }

        $this->validateValue = Type::of($valueType);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->values = new \SplObjectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function keyType(): string
    {
        return $this->keyType;
    }

    /**
     * {@inheritdoc}
     */
    public function valueType(): string
    {
        return $this->valueType;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->values->count();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * {@inheritdoc}
     */
    public function put($key, $value): Implementation
    {
        ($this->validateKey)($key, 1);
        ($this->validateValue)($value, 2);

        $map = clone $this;
        $map->values = clone $this->values;
        $map->values[$key] = $value;
        $map->values->rewind();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->contains($key)) {
            throw new ElementNotFoundException;
        }

        return $this->values->offsetGet($key);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key): bool
    {
        if (!is_object($key)) {
            return false;
        }

        return $this->values->offsetExists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): Implementation
    {
        $map = clone $this;
        $map->values = new \SplObjectStorage;

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(Implementation $map): bool
    {
        if ($map->size() !== $this->size()) {
            return false;
        }

        foreach ($this->values as $k) {
            $v = $this->values[$k];

            if (!$map->contains($k)) {
                return false;
            }

            if ($map->get($k) !== $v) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): Implementation
    {
        $map = $this->clear();

        foreach ($this->values as $k) {
            $v = $this->values[$k];

            if ($predicate($k, $v) === true) {
                $map->values[$k] = $v;
            }
        }

        $map->values->rewind();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): void
    {
        foreach ($this->values as $k) {
            $v = $this->values[$k];

            $function($k, $v);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): Map
    {
        if ($this->size() === 0) {
            throw new GroupEmptyMapException;
        }

        $map = null;

        foreach ($this->values as $k) {
            $v = $this->values[$k];

            $key = $discriminator($k, $v);

            if ($map === null) {
                $map = Map::of(
                    Type::determine($key),
                    Map::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->put($k, $v)
                );
            } else {
                $map = $map->put(
                    $key,
                    $this->clearMap()->put($k, $v)
                );
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): Set
    {
        return $this->reduce(
            Set::of($this->keyType),
            static function(Set $keys, $key): Set {
                return $keys->add($key);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function values(): Stream
    {
        return $this->reduce(
            Stream::of($this->valueType),
            static function(Stream $values, $key, $value): Stream {
                return $values->add($value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): Implementation
    {
        $map = $this->clear();

        foreach ($this->values as $k) {
            $v = $this->values[$k];

            $return = $function($k, $v);

            if ($return instanceof Pair) {
                ($this->validateKey)($return->key(), 1);

                $key = $return->key();
                $value = $return->value();
            } else {
                $key = $k;
                $value = $return;
            }

            ($this->validateValue)($value, 2);

            $map->values[$key] = $value;
        }

        $map->values->rewind();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->values()->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key): Implementation
    {
        if (!$this->contains($key)) {
            return $this;
        }

        $map = clone $this;
        $map->values = clone $this->values;
        $map->values->detach($key);
        $map->values->rewind();

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function merge(Implementation $map): Implementation
    {
        return $map->reduce(
            $this,
            function(self $carry, $key, $value): self {
                return $carry->put($key, $value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): Map
    {
        $truthy = $this->clearMap();
        $falsy = $this->clearMap();

        foreach ($this->values as $k) {
            $v = $this->values[$k];

            $return = $predicate($k, $v);

            if ($return === true) {
                $truthy = $truthy->put($k, $v);
            } else {
                $falsy = $falsy->put($k, $v);
            }
        }

        return Map::of('bool', Map::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->values as $k) {
            $v = $this->values[$k];

            $carry = $reducer($carry, $k, $v);
        }

        return $carry;
    }

    public function empty(): bool
    {
        $this->values->rewind();

        return !$this->values->valid();
    }

    /**
     * @return Map<T, S>
     */
    private function clearMap(): Map
    {
        return Map::of($this->keyType, $this->valueType);
    }
}
