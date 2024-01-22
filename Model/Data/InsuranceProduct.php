<?php

namespace Alma\MonthlyPayments\Model\Data;

use Alma\API\Entities\Insurance\Contract;
use Alma\API\Entities\Insurance\File;

class InsuranceProduct
{
    /**
     * @var mixed
     */
    private $linkToken;
    /**
     * @var string
     */
    private $parentName;
    /**
     * @var Contract
     */
    private $contract;

    /**
     * @param Contract $contract
     * @param string $parentName
     * @param int|null $linkToken
     */
    public function __construct(
        Contract $contract,
        string $parentName,
        int    $linkToken = null
    ) {
        $this->linkToken = $linkToken;
        $this->parentName = $parentName;
        $this->contract = $contract;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->contract->getId();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->contract->getName();
    }

    /**
     * @return int
     */
    public function getPrice():int
    {
        return $this->contract->getPrice();
    }
    public function getFloatPrice(): float
    {
        return (float)($this->getPrice()/100);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'price' => $this->getPrice(),
            'duration_year' => $this->getDurationYear(),
            'link' => $this->linkToken,
            'parent_name' => $this->parentName,
            'files' => $this->getFiles()
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

    public function getDurationYear(): int
    {
        return $this->contract->getProtectionDurationInYear();
    }

    public function getContract(): Contract
    {
        return $this->contract;
    }

    public function getFiles(): array
    {
        $filesArray= [];
        /** @var File $file */
        foreach ($this->contract->getFiles() as $file) {
            $fileArray['name'] = $file->getName();
            $fileArray['type'] = $file->getType();
            $fileArray['url'] = $file->getPublicUrl();
            $filesArray[] = $fileArray;
        }
        return $filesArray;
    }
}
