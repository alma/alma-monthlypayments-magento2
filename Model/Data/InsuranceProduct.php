<?php

namespace Alma\MonthlyPayments\Model\Data;

class InsuranceProduct
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var float
     */
    private $price;
    /**
     * @var mixed
     */
    private $linkToken;
    /**
     * @var string
     */
    private $parentName;

    public function __construct(
        int $id,
        string $name,
        float $price,
        string $parentName,
        int $linkToken = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->linkToken = $linkToken;
        $this->parentName = $parentName;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return array
     */
    public function toArray():array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'link' => $this->linkToken,
            'parent_name' => $this->parentName
        ];
    }

    /**
     * @return string|null
     */
    public function getLinkToken(): ?string
    {
        return $this->linkToken;
    }

    /**
     * @param string $linkToken
     * @return void
     */
    public function setLinkToken(string $linkToken): void
    {
        $this->linkToken = $linkToken;
    }

    /**
     * @return string
     */
    public function getParentName(): string
    {
        return $this->parentName;
    }
}
