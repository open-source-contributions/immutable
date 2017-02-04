<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\RegexException;
use Innmind\Immutable\Exception\SubstringException;

class Str implements PrimitiveInterface, StringableInterface
{
    const PAD_RIGHT = STR_PAD_RIGHT;
    const PAD_LEFT = STR_PAD_LEFT;
    const PAD_BOTH = STR_PAD_BOTH;
    const PREG_NO_FLAGS = 0;
    const PREG_SPLIT_NO_EMPTY = PREG_SPLIT_NO_EMPTY;
    const PREG_SPLIT_DELIM_CAPTURE = PREG_SPLIT_DELIM_CAPTURE;
    const PREG_SPLIT_OFFSET_CAPTURE = PREG_SPLIT_OFFSET_CAPTURE;
    const PREG_OFFSET_CAPTURE = PREG_OFFSET_CAPTURE;

    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Split the string into a collection of ones
     *
     * @param string $delimiter
     *
     * @return StreamInterface<self>
     */
    public function split(string $delimiter = null): StreamInterface
    {
        $parts = empty($delimiter) ?
                str_split($this->value) : explode($delimiter, $this->value);
        $stream = new Stream(self::class);

        foreach ($parts as $part) {
            $stream = $stream->add(new self($part));
        }

        return $stream;
    }

    /**
     * Returns a collection of the string splitted by the given chunk size
     *
     * @param int $size
     *
     * @return StreamInterface<self>
     */
    public function chunk(int $size = 1): StreamInterface
    {
        $pieces = str_split($this->value, $size);
        $stream = new Stream(self::class);

        foreach ($pieces as $piece) {
            $stream = $stream->add(new self($piece));
        }

        return $stream;
    }

    /**
     * Returns the position of the first occurence of the string
     *
     * @param string $needle
     * @param int $offset
     *
     * @throws SubstringException If the string is not found
     *
     * @return int
     */
    public function position(string $needle, int $offset = 0): int
    {
        $position = mb_strpos($this->value, $needle, $offset);

        if ($position === false) {
            throw new SubstringException(sprintf(
                'Substring "%s" not found',
                $needle
            ));
        }

        return (int) $position;
    }

    /**
     * Replace all occurences of the search string with the replacement one
     *
     * @param string $search
     * @param string $replacement
     *
     * @return self
     */
    public function replace(string $search, string $replacement): self
    {
        return new self(str_replace(
            (string) $search,
            (string) $replacement,
            $this->value
        ));
    }

    /**
     * Returns the string following the given delimiter
     *
     * @param string $delimiter
     *
     * @throws SubstringException If the string is not found
     *
     * @return self
     */
    public function str(string $delimiter): self
    {
        $sub = mb_strstr($this->value, $delimiter);

        if ($sub === false) {
            throw new SubstringException(sprintf(
                'Substring "%s" not found',
                $delimiter
            ));
        }

        return new self($sub);
    }

    /**
     * Return the string in upper case
     *
     * @return self
     */
    public function toUpper(): self
    {
        return new self(mb_strtoupper($this->value));
    }

    /**
     * Return the string in lower case
     *
     * @return self
     */
    public function toLower(): self
    {
        return new self(mb_strtolower($this->value));
    }

    /**
     * Return the string length
     *
     * @return int
     */
    public function length(): int
    {
        return strlen($this->value);
    }

    /**
     * Reverse the string
     *
     * @return self
     */
    public function reverse(): self
    {
        return new self(strrev($this->value));
    }

    /**
     * Pad to the right
     *
     * @param int $length
     * @param string $character
     *
     * @return self
     */
    public function rightPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_RIGHT);
    }

    /**
     * Pad to the left
     *
     * @param int $length
     * @param string $character
     *
     * @return self
     */
    public function leftPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_LEFT);
    }

    /**
     * Pad both sides
     *
     * @param int $length
     * @param string $character
     *
     * @return self
     */
    public function uniPad(int $length, string $character = ' '): self
    {
        return $this->pad($length, $character, self::PAD_BOTH);
    }

    /**
     * Find length of initial segment not matching mask
     *
     * @param string $mask
     * @param int $start
     * @param int $length
     *
     * @return int
     */
    public function cspn(string $mask, int $start = 0, int $length = null): int
    {
        if ($length === null) {
            $value = strcspn($this->value, $mask, $start);
        } else {
            $value = strcspn(
                $this->value,
                $mask,
                $start,
                $length
            );
        }

        return (int) $value;
    }

    /**
     * Repeat the string n times
     *
     * @param int $repeat
     *
     * @return self
     */
    public function repeat(int $repeat): self
    {
        return new self(str_repeat($this->value, $repeat));
    }

    /**
     * Shuffle the string
     *
     * @return self
     */
    public function shuffle(): self
    {
        return new self(str_shuffle($this->value));
    }

    /**
     * Strip slashes
     *
     * @return self
     */
    public function stripSlashes(): self
    {
        return new self(stripslashes($this->value));
    }

    /**
     * Strip C-like slashes
     *
     * @return self
     */
    public function stripCSlashes(): self
    {
        return new self(stripcslashes($this->value));
    }

    /**
     * Return the word count
     *
     * @param string $charlist
     *
     * @return int
     */
    public function wordCount(string $charlist = ''): int
    {
        return (int) str_word_count(
            $this->value,
            0,
            $charlist
        );
    }

    /**
     * Return the collection of words
     *
     * @param string $charlist
     *
     * @return MapInterface<int, self>
     */
    public function words(string $charlist = ''): MapInterface
    {
        $words = str_word_count($this->value, 2, $charlist);
        $map = new Map('int', self::class);

        foreach ($words as $position => $word) {
            $map = $map->put($position, new self($word));
        }

        return $map;
    }

    /**
     * Split the string using a regular expression
     *
     * @param string $regex
     * @param int $limit
     *
     * @return StreamInterface<self>
     */
    public function pregSplit(string $regex, int $limit = -1): StreamInterface
    {
        $strings = preg_split($regex, $this->value, $limit);
        $stream = new Stream(self::class);

        foreach ($strings as $string) {
            $stream = $stream->add(new self($string));
        }

        return $stream;
    }

    /**
     * Check if the string match the given regular expression
     *
     * @param string $regex
     * @param int $offset
     *
     * @throws Exception If the regex failed
     *
     * @return bool
     */
    public function matches(string $regex, int $offset = 0): bool
    {
        $matches = [];
        $value = preg_match($regex, $this->value, $matches, 0, $offset);

        if ($value === false) {
            throw new RegexException('', preg_last_error());
        }

        return (bool) $value;
    }

    /**
     * Return a collection of the elements matching the regex
     *
     * @param string $regex
     * @param int $offset
     * @param int $flags
     *
     * @throws Exception If the regex failed
     *
     * @return MapInterface<scalar, self>
     */
    public function getMatches(
        string $regex,
        int $offset = 0,
        int $flags = self::PREG_NO_FLAGS
    ): MapInterface {
        $matches = [];
        $value = preg_match(
            $regex,
            $this->value,
            $matches,
            $flags,
            $offset
        );
        $map = new Map('scalar', self::class);

        foreach ($matches as $key => $match) {
            $map = $map->put($key, new self($match));
        }

        if ($value === false) {
            throw new RegexException('', preg_last_error());
        }

        return $map;
    }

    /**
     * Replace part of the string by using a regular expression
     *
     * @param string $regex
     * @param string $replacement
     * @param int $limit
     *
     * @throws Exception If the regex failed
     *
     * @return self
     */
    public function pregReplace(
        string $regex,
        string $replacement,
        int $limit = -1
    ): self {
        $value = preg_replace(
            $regex,
            $replacement,
            $this->value,
            $limit
        );

        if ($value === null) {
            throw new RegexException('', preg_last_error());
        }

        return new self($value);
    }

    /**
     * Return part of the string
     *
     * @param int $start
     * @param int $length
     *
     * @return self
     */
    public function substring(int $start, int $length = null): self
    {
        if ($length === null) {
            $sub = substr($this->value, $start);
        } else {
            $sub = substr($this->value, $start, $length);
        }

        return new self($sub);
    }

    /**
     * Return a formatted string
     *
     * @return self
     */
    public function sprintf(...$values): self
    {
        array_unshift($values, $this->value);
        $formatted = call_user_func_array('sprintf', $values);

        return new self($formatted);
    }

    /**
     * Return the string with the first letter as uppercase
     *
     * @return self
     */
    public function ucfirst(): self
    {
        return new self(ucfirst($this->value));
    }

    /**
     * Return the string with the first letter as lowercase
     *
     * @return self
     */
    public function lcfirst(): self
    {
        return new self(lcfirst($this->value));
    }

    /**
     * Return a CamelCase representation of the string
     *
     * @return self
     */
    public function camelize(): self
    {
        return new self(
            (string) $this
                ->pregSplit('/_| /')
                ->map(function(self $part) {
                    return $part->ucfirst();
                })
                ->join('')
        );
    }

    /**
     * Append a string at the end of the current one
     *
     * @param string $string
     *
     * @return self
     */
    public function append(string $string): self
    {
        return new self((string) $this.$string);
    }

    /**
     * Prepend a string at the beginning of the current one
     *
     * @param string $string
     *
     * @return self
     */
    public function prepend(string $string): self
    {
        return new self($string.(string) $this);
    }

    /**
     * Check if the 2 strings are equal
     *
     * @param self $string
     *
     * @return bool
     */
    public function equals(self $string): bool
    {
        return (string) $this === (string) $string;
    }

    /**
     * Trim the string
     *
     * @param string $mask
     *
     * @return self
     */
    public function trim(string $mask = null): self
    {
        return new self(
            $mask === null ? trim((string) $this) : trim((string) $this, $mask)
        );
    }

    /**
     * Trim the right side of the string
     *
     * @param string $mask
     *
     * @return self
     */
    public function rightTrim(string $mask = null): self
    {
        return new self(
            $mask === null ? rtrim((string) $this) : rtrim((string) $this, $mask)
        );
    }

    /**
     * Trim the left side of the string
     *
     * @param string $mask
     *
     * @return self
     */
    public function leftTrim(string $mask = null): self
    {
        return new self(
            $mask === null ? ltrim((string) $this) : ltrim((string) $this, $mask)
        );
    }

    /**
     * Pad the string
     *
     * @param int $length
     * @param string $character
     * @param int $direction
     *
     * @return self
     */
    private function pad(
        int $length,
        string $character = ' ',
        int $direction = self::PAD_RIGHT
    ): self {
        return new self(str_pad(
            $this->value,
            $length,
            $character,
            $direction
        ));
    }
}
