<?php
namespace App\Model;

class BatchManagement {
    private int $id;
    private ?string $batch_name = null;
    private ?string $batch_file = null;
    private ?string $batch_split_date = null;
    private ?string $department = null;
    private ?string $database_name = null;
    private ?string $table_name = null;
    private ?int $phone_prefix = null;
    private int $phone_nombre = 0;
    private bool $archiver = false;
    private ?string $date_insertion = null;
    private ?string $date_modification = null;
    private int $number_uses = 0;
    private ?string $date_last_use = null;

    // GETTERS
    public function getId(): int { return $this->id; }
    public function getBatchName(): ?string { return $this->batch_name; }
    public function getBatchFile(): ?string { return $this->batch_file; }
    public function getBatchSplitDate(): ?string { return $this->batch_split_date; }
    public function getDepartment(): ?string { return $this->department; }
    public function getDatabaseName(): ?string { return $this->database_name; }
    public function getTableName(): ?string { return $this->table_name; }
    public function getPhonePrefix(): ?int { return $this->phone_prefix; }
    public function getPhoneNombre(): int { return $this->phone_nombre; }
    public function getArchiver(): bool { return $this->archiver; }
    public function getDateInsertion(): ?string { return $this->date_insertion; }
    public function getDateModification(): ?string { return $this->date_modification; }
     public function getNumberUses(): int { return $this->number_uses; } 
    public function getDateLastUse(): ?string { return $this->date_last_use; }

    // SETTERS
    public function setId(int $id): void { $this->id = $id; }
    public function setBatchName(?string $name): void { $this->batch_name = $name; }
    public function setBatchFile(?string $file): void { $this->batch_file = $file; }
    public function setBatchSplitDate(?string $date): void { $this->batch_split_date = $date; }
    public function setDepartment(?string $dept): void { $this->department = $dept; }
    public function setDatabaseName(?string $db): void { $this->database_name = $db; }
    public function setTableName(?string $table): void { $this->table_name = $table; }
    public function setPhonePrefix(?int $prefix): void { $this->phone_prefix = $prefix; }
    public function setPhoneNombre(int $nombre): void { $this->phone_nombre = $nombre; }
    public function setArchiver(bool $archiver): void { $this->archiver = $archiver; }
    public function setDateInsertion(?string $date): void { $this->date_insertion = $date; }
    public function setDateModification(?string $date): void { $this->date_modification = $date; }
    public function setNumberUses(int $nombre): void { $this->number_uses = $nombre; }
    public function setDateLastUse(?string $date): void { $this->date_last_use = $date; }
}
?>
