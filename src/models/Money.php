<?php

declare(strict_types=1);

namespace App\Model;

/*
Value object: kwota w groszach jako int (bez float). Niemutowalny; operacje zwracają nowy obiekt.
Górny limit odpowiada CHECK na bids.amount (100 000 000 000 groszy), więc obejmuje też ceny aukcji.
*/
final class Money
{
    public const MAX_GROSZE = 100_000_000_000;

    private readonly int $grosze;

    public function __construct(int $grosze)
    {
        if ($grosze < 0 || $grosze > self::MAX_GROSZE) {
            throw new \InvalidArgumentException('Amount must be between 0 and ' . self::MAX_GROSZE . ' grosze.');
        }
        $this->grosze = $grosze;
    }

    public static function fromZloty(int $zloty): self
    {
        if ($zloty < 0 || $zloty > intdiv(self::MAX_GROSZE, 100)) {
            throw new \InvalidArgumentException('Amount in zloty is out of range.');
        }
        return new self($zloty * 100);
    }

    public function grosze(): int
    {
        return $this->grosze;
    }

    public function add(self $other): self
    {
        return new self($this->grosze + $other->grosze);
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->grosze > $other->grosze;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->grosze >= $other->grosze;
    }

    public function equals(self $other): bool
    {
        return $this->grosze === $other->grosze;
    }

    /*
    "45 500 zł" (spacja niełamliwa jako separator tysięcy); grosze tylko, gdy niezerowe: "45 500,50 zł".
    */
    public function format(): string
    {
        $nbsp = "\u{00A0}";
        $zloty = number_format(intdiv($this->grosze, 100), 0, '', $nbsp);
        $rest = $this->grosze % 100;
        $fraction = $rest === 0 ? '' : sprintf(',%02d', $rest);
        return $zloty . $fraction . $nbsp . 'zł';
    }
}
