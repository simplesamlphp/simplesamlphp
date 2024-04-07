<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Entity;

class ErrorReport {
    protected string $emailAddress;
    protected string $text;
    protected string $reportId;

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }


    public function setEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }


    public function getText(): string
    {
        return $this->text;
    }


    public function setText(string $text): void
    {
        $this->text = $text;
    }


    public function getReportId(): string
    {
        return $this->reportId;
    }


    public function setReportId(string $reportId): void
    {
        $this->reportId = $reportId;
    }
}
