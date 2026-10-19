<?php
class RegistroDb
{
    private ?int $id = null;

    public function setId(int $miId): void
    {
        $this->id = $miId;
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function tieneId(): bool
    {
        return $this->id !== null;
    }
}
?>
